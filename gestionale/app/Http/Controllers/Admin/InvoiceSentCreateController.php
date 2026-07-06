<?php
// app/Http/Controllers/Admin/InvoiceSentCreateController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\InvoiceSent;
use App\Models\InvoiceRow;
use App\Models\InvoiceSeries;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\Service;
use App\Models\UnitaMisura;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InvoiceSentCreateController extends Controller
{
    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per creare fatture di vendita.');
        }

        $ownerships = Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();
        $typeDocuments = config('gestionale.tipo_documento', []);
        $paymentMethods = config('gestionale.modalita_pagamento', []);
        $unitMeasures = UnitaMisura::where('valid', 1)->orderBy('ordinamento')->get();
        
        $vatRates = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('rate', 'desc')
            ->orderBy('sdi_nature')
            ->get();
        
        return view('admin.invoice-sent.create', compact(
            'ownerships',
            'typeDocuments',
            'paymentMethods',
            'unitMeasures',
            'vatRates'
        ));
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // =============================================
            // PRE-PROCESSING DEI DATI
            // =============================================
            
            // 1. Pre-processa le righe
            $rows = $request->input('rows', []);
            $processedRows = [];
            
            foreach ($rows as $index => $row) {
                // Estrai e pulisci i valori
                $quantity = $this->parseItalianNumber($row['quantity'] ?? 0);
                $unitPrice = $this->parseItalianNumber($row['unit_price'] ?? 0);
                $discount = $this->parseItalianNumber($row['discount_percentage'] ?? 0);
                $taxable = $this->parseItalianNumber($row['taxable_amount'] ?? 0);
                
                // Assicurati che i valori siano >= 0
                $quantity = max(0, $quantity);
                $unitPrice = max(0, $unitPrice);
                $discount = max(0, min(100, $discount)); // Sconto tra 0 e 100
                
                // Se unit_price è 0 e quantity è > 0, imposta un valore minimo per evitare errori
                if ($unitPrice == 0 && $quantity > 0) {
                    // Mantieni 0, ma assicurati che sia un float
                    $unitPrice = 0.0;
                }
                
                $processedRows[] = [
                    'code' => trim($row['code'] ?? ''),
                    'description' => trim($row['description'] ?? ''),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_measure' => $row['unit_measure'] ?? 'pz',
                    'discount_percentage' => $discount,
                    'vat_rate_id' => $row['vat_rate_id'] ?? null,
                    'taxable_amount' => $taxable,
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'id_service' => $row['id_service'] ?? null,
                ];
            }

            // 2. Pre-processa i pagamenti
            $payments = $request->input('payments', []);
            $processedPayments = [];
            
            foreach ($payments as $payment) {
                $amount = $this->parseItalianNumber($payment['amount'] ?? 0);
                $amount = max(0, $amount);
                
                $processedPayments[] = [
                    'due_date' => $payment['due_date'] ?? $request->input('data_invoice'),
                    'amount' => $amount,
                    'payment_method' => $payment['payment_method'] ?? 'MP05',
                    'iban' => $payment['iban'] ?? null,
                ];
            }

            // 3. Pre-processa l'importo totale
            $importoTotale = $this->parseItalianNumber($request->input('importo_totale', 0));
            $importoTotale = max(0, $importoTotale);

            // =============================================
            // VALIDAZIONE CON DATI PROCESSATI
            // =============================================
            
            $validator = validator([
                'id_ownership' => $request->input('id_ownership'),
                'selected_series_id' => $request->input('selected_series_id'),
                'type_invoice' => $request->input('type_invoice'),
                'n_invoice' => $request->input('n_invoice'),
                'n_invoice_ext' => $request->input('n_invoice_ext'),
                'data_invoice' => $request->input('data_invoice'),
                'selected_customer_id' => $request->input('selected_customer_id'),
                'causale' => $request->input('causale'),
                'importo_totale' => $importoTotale,
                'rows' => $processedRows,
                'payments' => $processedPayments,
            ], [
                'id_ownership' => 'required|exists:ownership,id_proprieta',
                'selected_series_id' => 'required|exists:invoice_series,id',
                'type_invoice' => 'required|string',
                'n_invoice' => 'required|string',
                'n_invoice_ext' => 'nullable|string|max:100',
                'data_invoice' => 'required|date',
                'selected_customer_id' => 'required|exists:entities,id_cliente',
                'causale' => 'nullable|string',
                'importo_totale' => 'numeric|min:0',
                'rows' => 'required|array|min:1',
                'rows.*.description' => 'required|string',
                'rows.*.quantity' => 'required|numeric|min:0',
                'rows.*.unit_price' => 'required|numeric|min:0',
                'payments' => 'array|nullable',
                'payments.*.amount' => 'numeric|min:0',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            // =============================================
            // CREAZIONE FATTURA
            // =============================================

            $series = InvoiceSeries::find($validated['selected_series_id']);
            if (!$series) {
                throw new \Exception('Sezionale non trovato');
            }

            $series->last_number += 1;
            $series->save();

            // Crea la fattura
            $invoice = InvoiceSent::create([
                'id_ownership' => $validated['id_ownership'],
                'id_entities' => $validated['selected_customer_id'],
                'id_invoice_series' => $validated['selected_series_id'],
                'type_invoice' => $validated['type_invoice'],
                'n_invoice' => $validated['n_invoice'],
                'n_invoice_ext' => $validated['n_invoice_ext'] ?? null,
                'data_invoice' => $validated['data_invoice'],
                'importo_totale' => $validated['importo_totale'],
                'causale' => $validated['causale'] ?? '',
                'divisa' => 'EUR',
                'status' => 'issued',
                'is_manual' => true,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
            ]);

            // Crea le righe
            foreach ($validated['rows'] as $row) {
                $vatInfo = DB::table('vat_rates')->find($row['vat_rate_id'] ?? null);
                $vatRate = $vatInfo ? (float)$vatInfo->rate : 0;
                $sdiNature = $vatInfo->sdi_nature ?? null;
                
                $quantity = floatval($row['quantity'] ?? 1);
                $unitPrice = floatval($row['unit_price'] ?? 0);
                $discount = floatval($row['discount_percentage'] ?? 0);
                $totalRow = ($quantity * $unitPrice) * (1 - $discount / 100);

                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_sent',
                    'code' => $row['code'] ?? null,
                    'description' => $row['description'],
                    'quantity' => round($quantity, 2),
                    'unit_price' => round($unitPrice, 3),
                    'unit_measure' => $row['unit_measure'] ?? 'pz',
                    'discount_percentage' => $discount,
                    'vat_rate' => $vatRate * 100,
                    'sdi_nature' => $sdiNature,
                    'total' => round($totalRow, 2),
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'id_service' => $row['id_service'] ?? null,
                ]);
            }

            // Riepilogo IVA
            if ($request->has('vat_summary') && $request->vat_summary) {
                $vatSummary = json_decode($request->vat_summary, true);
                if (is_array($vatSummary)) {
                    foreach ($vatSummary as $vat) {
                        if (isset($vat['rate']) && isset($vat['taxable_amount']) && isset($vat['vat_amount'])) {
                            DB::table('invoice_vat_summaries')->insert([
                                'vatable_id' => $invoice->id,
                                'vatable_type' => InvoiceSent::class,
                                'tax_rate' => floatval($vat['rate']) * 100,
                                'taxable_amount' => floatval($vat['taxable_amount']),
                                'tax_amount' => floatval($vat['vat_amount']),
                                'esigibilita_iva' => 'I',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            // Scadenze pagamento
            foreach ($validated['payments'] as $payment) {
                if (($payment['amount'] ?? 0) > 0) {
                    DB::table('invoice_payments')->insert([
                        'payable_id' => $invoice->id,
                        'payable_type' => InvoiceSent::class,
                        'due_date' => $payment['due_date'] ?? $validated['data_invoice'],
                        'amount' => $payment['amount'],
                        'paid_amount' => 0,
                        'residual_amount' => $payment['amount'],
                        'payment_method' => $payment['payment_method'] ?? 'MP05',
                        'iban' => $payment['iban'] ?? null,
                        'status' => 'issued',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('admin.invoices-sent.index')
                ->with('success', 'Fattura ' . $invoice->n_invoice . ' creata con successo!');

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Errore validazione: ' . json_encode($e->errors()));
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione fattura: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Errore: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Converte una stringa numerica italiana (con virgola) in float
     * Gestisce anche stringhe vuote e valori null
     */
    private function parseItalianNumber($value)
    {
        // Se è null o vuoto, ritorna 0
        if ($value === null || $value === '') {
            return 0.0;
        }
        
        // Se è già numerico, ritorna come float
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        // Se non è una stringa, ritorna 0
        if (!is_string($value)) {
            return 0.0;
        }
        
        // Rimuovi spazi
        $value = trim($value);
        
        // Se dopo il trim è vuoto, ritorna 0
        if ($value === '') {
            return 0.0;
        }
        
        // Sostituisci la virgola con il punto per il separatore decimale
        $value = str_replace(',', '.', $value);
        
        // Rimuovi eventuali punti come separatori delle migliaia (solo se seguiti da 3 cifre)
        $value = preg_replace('/\.(?=\d{3}(?!\d))/', '', $value);
        
        // Se il risultato non è numerico, ritorna 0
        if (!is_numeric($value)) {
            return 0.0;
        }
        
        return (float)$value;
    }

    // =============================================
    // API ROUTES
    // =============================================

    public function searchCustomers(Request $request)
    {
        $search = $request->get('q', '');
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $results = Entity::where('valid', 1)
            ->whereIn('entity_type', ['cliente', 'entrambi'])
            ->where(function($q) use ($search) {
                $q->where('ragione_sociale', 'like', '%' . $search . '%')
                  ->orWhere('nome', 'like', '%' . $search . '%')
                  ->orWhere('cognome', 'like', '%' . $search . '%')
                  ->orWhere('partita_iva', 'like', '%' . $search . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);

        return response()->json($results);
    }

    public function searchCostCenters(Request $request)
    {
        $search = $request->get('q', '');
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $results = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $search . '%')
            ->limit(10)
            ->get(['id', 'Nome as name']);

        return response()->json($results);
    }

    public function searchServices(Request $request)
    {
        $search = $request->get('q', '');
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $results = Service::where('Stato', 1)
            ->where('Titolo', 'like', '%' . $search . '%')
            ->orderBy('Titolo')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->Titolo,
                'descr_fattura' => $s->Descr_fattura ?? '',
                'prezzo_un' => $s->Prezzo_un ?? 0,
                'vat_rate_id' => $s->vat_rate_id ?? null,
            ]);

        return response()->json($results);
    }

    public function getSeries(Request $request)
    {
        $idOwnership = $request->get('id_ownership');
        if (!$idOwnership) {
            return response()->json([]);
        }

        $series = InvoiceSeries::where('id_ownership', $idOwnership)
            ->orderBy('year', 'desc')
            ->orderBy('code')
            ->get()
            ->map(function($s) {
                return [
                    'id' => $s->id,
                    'code' => $s->code,
                    'name' => $s->name,
                    'year' => $s->year,
                    'active' => (bool)$s->active,
                    'last_number' => $s->last_number,
                    'next_number' => $s->last_number + 1,
                ];
            });

        return response()->json($series);
    }

    public function getVatRates(Request $request)
    {
        $vatRates = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('rate', 'desc')
            ->orderBy('sdi_nature')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'rate' => (float)$item->rate,
                    'rate_percent' => (float)$item->rate * 100,
                    'sdi_nature' => $item->sdi_nature ?? '',
                    'code' => $item->code ?? '',
                ];
            });

        return response()->json($vatRates);
    }

    public function getCustomerInfo($id)
    {
        $customer = Entity::find($id);
        if (!$customer) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $customer->id_cliente,
            'name' => $customer->ragione_sociale ?? $customer->nome . ' ' . $customer->cognome,
            'piva' => $customer->partita_iva,
            'cf' => $customer->codice_fiscale,
            'email' => $customer->email,
            'telefono' => $customer->telefono,
            'indirizzo' => $customer->indirizzo,
            'cap' => $customer->cap,
            'comune' => $customer->comune,
            'provincia' => $customer->provincia,
        ]);
    }

    public function getCompanyBankAccount(Request $request)
    {
        $idOwnership = $request->get('id_ownership');
        if (!$idOwnership) {
            return response()->json(null);
        }

        $bankAccount = DB::table('bank_accounts')
            ->where('id_ownership', $idOwnership)
            ->where('default_invoice', 1)
            ->where('valid', 1)
            ->first();

        if ($bankAccount) {
            return response()->json([
                'iban' => $bankAccount->iban ?? '',
                'bank_name' => $bankAccount->name ?? '',
            ]);
        }

        return response()->json(null);
    }

    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'piva' => 'nullable|string|max:20',
            'cf' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'indirizzo' => 'nullable|string|max:255',
            'cap' => 'nullable|string|max:10',
            'comune' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:10',
        ]);

        try {
            $customer = Entity::create([
                'entity_type' => 'cliente',
                'ragione_sociale' => $validated['name'],
                'partita_iva' => $validated['piva'] ?? null,
                'codice_fiscale' => $validated['cf'] ?? null,
                'email' => $validated['email'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'indirizzo' => $validated['indirizzo'] ?? null,
                'cap' => $validated['cap'] ?? null,
                'comune' => $validated['comune'] ?? null,
                'provincia' => $validated['provincia'] ?? null,
                'valid' => 1,
            ]);

            return response()->json([
                'success' => true,
                'id' => $customer->id_cliente,
                'name' => $customer->ragione_sociale ?? $customer->nome . ' ' . $customer->cognome,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}