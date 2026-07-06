<?php
// app/Http/Controllers/Admin/InvoiceSentEditController.php

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

class InvoiceSentEditController extends Controller
{
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per modificare fatture di vendita.');
        }

        $invoice = InvoiceSent::findOrFail($id);
        $isReadonly = !($invoice->is_manual ?? false);
        
        $ownerships = Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();
        $typeDocuments = config('gestionale.tipo_documento', []);
        $paymentMethods = config('gestionale.modalita_pagamento', []);
        $unitMeasures = UnitaMisura::where('valid', 1)->orderBy('ordinamento')->get();
        
        $vatRates = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('rate', 'desc')
            ->orderBy('sdi_nature')
            ->get()
            ->map(function($item) {
                return [
                    'id' => (int)$item->id,
                    'description' => $item->description ?? '',
                    'rate' => (float)$item->rate,
                    'rate_percent' => (float)$item->rate * 100,
                    'sdi_nature' => $item->sdi_nature ?? '',
                    'code' => $item->code ?? '',
                ];
            })
            ->toArray();
        
        // Carica sezionali disponibili
        $availableSeries = InvoiceSeries::where('id_ownership', $invoice->id_ownership)
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
                ];
            })->toArray();
        
        // Carica righe con TUTTI i campi
        $invoiceRows = InvoiceRow::where('document_id', $id)
            ->where('document_type', 'invoice_sent')
            ->get();
        
        $rows = [];
        $costCenterIds = [];
        $serviceIds = [];
        
        foreach ($invoiceRows as $index => $row) {
            $vatRate = $row->vat_rate / 100;
            
            // Trova l'IVA corretta
            $vatInfo = null;
            if ($row->vat_rate_id) {
                $vatInfo = collect($vatRates)->firstWhere('id', $row->vat_rate_id);
            }
            if (!$vatInfo) {
                $vatInfo = collect($vatRates)->firstWhere('rate', $vatRate);
            }
            
            $rows[] = [
                'id' => $row->id,
                'code' => $row->code ?? '',
                'description' => $row->description,
                'quantity' => (float)$row->quantity,
                'unit_price' => (float)$row->unit_price,
                'id_unit_measure' => $row->id_unit_measure ?? 1,
                'discount_percentage' => (float)$row->discount_percentage,
                'vat_rate_id' => $row->vat_rate_id,
                'vat_rate' => $vatRate,
                'vat_sdi_nature' => $vatInfo['sdi_nature'] ?? '',
                'vat_description' => $vatInfo['description'] ?? '',
                'id_cost_center' => $row->id_cost_center,
                'id_service' => $row->id_service,
                'taxable_amount' => (float)$row->total,
                'vat_amount' => (float)$row->total * $vatRate,
            ];
            
            if ($row->id_cost_center) {
                $costCenterIds[] = $row->id_cost_center;
            }
            if ($row->id_service) {
                $serviceIds[] = $row->id_service;
            }
        }
        
        // Carica nomi dei centri costo e servizi
        $costCenters = [];
        if (!empty($costCenterIds)) {
            $costCenters = CostCenter::whereIn('id', $costCenterIds)
                ->pluck('Nome', 'id')
                ->toArray();
        }
        
        $services = [];
        if (!empty($serviceIds)) {
            $services = Service::whereIn('id', $serviceIds)
                ->pluck('Titolo', 'id')
                ->toArray();
        }
        
        // Carica pagamenti (senza stato)
        $payments = DB::table('invoice_payments')
            ->where('payable_id', $id)
            ->where('payable_type', InvoiceSent::class)
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'due_date' => $payment->due_date,
                    'amount' => (float)$payment->amount,
                    'payment_method' => $payment->payment_method ?? '',
                    'iban' => $payment->iban ?? '',
                ];
            })->toArray();
        
        // Calcola totali e riepilogo IVA
        $vatSummary = $this->calculateVatSummary($rows);
        $totalTaxable = array_sum(array_column($rows, 'taxable_amount'));
        $totalVat = array_sum(array_column($rows, 'vat_amount'));
        $totalDiscount = array_sum(array_map(function($row) {
            return ($row['quantity'] * $row['unit_price']) * ($row['discount_percentage'] / 100);
        }, $rows));
        $importoTotale = $totalTaxable + $totalVat;
        
        $data = [
            'invoice' => $invoice,
            'isReadonly' => $isReadonly,
            'is_manual' => $invoice->is_manual ?? false,
            'ownerships' => $ownerships,
            'typeDocuments' => $typeDocuments,
            'paymentMethods' => $paymentMethods,
            'unitMeasures' => $unitMeasures,
            'vatRates' => $vatRates,
            'availableSeries' => $availableSeries,
            'rows' => $rows,
            'payments' => $payments,
            'vatSummary' => $vatSummary,
            'totalTaxable' => $totalTaxable,
            'totalVat' => $totalVat,
            'totalDiscount' => $totalDiscount,
            'importoTotale' => $importoTotale,
            'costCenters' => $costCenters,
            'services' => $services,
        ];

        return view('admin.invoice-sent.edit', $data);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $invoice = InvoiceSent::findOrFail($id);
            
            // Validazione base
            $rules = [
                'causale' => 'nullable|string',
                'rows' => 'required|array|min:1',
                'rows.*.description' => 'required|string',
            ];
            
            if ($invoice->is_manual ?? false) {
                $rules = array_merge($rules, [
                    'id_ownership' => 'required|exists:ownership,id_proprieta',
                    'selected_series_id' => 'required|exists:invoice_series,id',
                    'type_invoice' => 'required|string',
                    'data_invoice' => 'required|date',
                    'selected_customer_id' => 'required|exists:entities,id_cliente',
                    'n_invoice_ext' => 'nullable|string|max:100',
                    'importo_totale' => 'numeric',
                    'rows.*.code' => 'nullable|string',
                    'rows.*.quantity' => 'required|numeric',
                    'rows.*.unit_price' => 'required|numeric',
                    'payments' => 'array|nullable',
                    'payments.*.amount' => 'numeric',
                ]);
            }
            
            $validated = $request->validate($rules);

            // Aggiorna la fattura
            $updateData = [
                'causale' => $request->causale,
                'updated_by' => Auth::guard('admin')->id(),
            ];
            
            if ($invoice->is_manual ?? false) {
                $updateData['id_ownership'] = $request->id_ownership;
                $updateData['id_entities'] = $request->selected_customer_id;
                $updateData['id_invoice_series'] = $request->selected_series_id;
                $updateData['type_invoice'] = $request->type_invoice;
                $updateData['data_invoice'] = $request->data_invoice;
                $updateData['importo_totale'] = $request->importo_totale;
                $updateData['n_invoice_ext'] = $request->n_invoice_ext;
            }
            
            $invoice->update($updateData);

            // Aggiorna le righe
            $existingRowIds = [];
            $rows = $request->input('rows', []);
            
            foreach ($rows as $row) {
                // Salta le righe marcate per eliminazione
                if (isset($row['_delete']) && $row['_delete']) {
                    if (isset($row['id']) && $row['id']) {
                        InvoiceRow::where('id', $row['id'])->delete();
                    }
                    continue;
                }

                // Prepara i dati della riga
                $rowData = [
                    'code' => $row['code'] ?? '',
                    'description' => $row['description'],
                    'quantity' => floatval(str_replace(',', '.', $row['quantity'] ?? 1)),
                    'unit_price' => floatval(str_replace(',', '.', $row['unit_price'] ?? 0)),
                    'id_unit_measure' => intval($row['id_unit_measure'] ?? 1),
                    'discount_percentage' => floatval(str_replace(',', '.', $row['discount_percentage'] ?? 0)),
                    'id_cost_center' => !empty($row['id_cost_center']) ? $row['id_cost_center'] : null,
                    'id_service' => !empty($row['id_service']) ? $row['id_service'] : null,
                ];
                
                // Se la fattura è manuale, aggiorna anche i dati IVA
                if ($invoice->is_manual ?? false) {
                    $vatRate = floatval($row['vat_rate'] ?? 0);
                    $rowData['vat_rate'] = $vatRate * 100;
                    $rowData['vat_rate_id'] = $row['vat_rate_id'] ?? null;
                    $rowData['total'] = floatval(str_replace(',', '.', $row['taxable_amount'] ?? 0));
                }

                if (isset($row['id']) && $row['id']) {
                    InvoiceRow::where('id', $row['id'])->update($rowData);
                    $existingRowIds[] = $row['id'];
                } else {
                    $newRow = InvoiceRow::create(array_merge([
                        'document_id' => $id,
                        'document_type' => 'invoice_sent',
                    ], $rowData));
                    $existingRowIds[] = $newRow->id;
                }
            }

            // Elimina righe rimosse (solo per fatture manuali)
            if ($invoice->is_manual ?? false) {
                InvoiceRow::where('document_id', $id)
                    ->where('document_type', 'invoice_sent')
                    ->whereNotIn('id', $existingRowIds)
                    ->delete();
            }

            // Aggiorna pagamenti (solo per fatture manuali)
            if ($invoice->is_manual ?? false) {
                $existingPaymentIds = [];
                $payments = $request->input('payments', []);
                
                foreach ($payments as $payment) {
                    $paymentData = [
                        'due_date' => $payment['due_date'],
                        'amount' => floatval(str_replace(',', '.', $payment['amount'] ?? 0)),
                        'payment_method' => $payment['payment_method'] ?? 'MP05',
                        'iban' => $payment['iban'] ?? null,
                    ];

                    if (isset($payment['id']) && $payment['id']) {
                        DB::table('invoice_payments')
                            ->where('id', $payment['id'])
                            ->update($paymentData);
                        $existingPaymentIds[] = $payment['id'];
                    } else {
                        $newId = DB::table('invoice_payments')->insertGetId(array_merge([
                            'payable_id' => $id,
                            'payable_type' => InvoiceSent::class,
                            'paid_amount' => 0,
                            'residual_amount' => $paymentData['amount'],
                            'status' => 'issued',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ], $paymentData));
                        $existingPaymentIds[] = $newId;
                    }
                }

                DB::table('invoice_payments')
                    ->where('payable_id', $id)
                    ->where('payable_type', InvoiceSent::class)
                    ->whereNotIn('id', $existingPaymentIds)
                    ->delete();

                // Aggiorna riepilogo IVA
                DB::table('invoice_vat_summaries')
                    ->where('vatable_id', $id)
                    ->where('vatable_type', InvoiceSent::class)
                    ->delete();

                if ($request->has('vat_summary') && $request->vat_summary) {
                    $vatSummary = json_decode($request->vat_summary, true);
                    if (is_array($vatSummary)) {
                        foreach ($vatSummary as $vat) {
                            if (isset($vat['rate']) && isset($vat['taxable_amount']) && isset($vat['vat_amount'])) {
                                DB::table('invoice_vat_summaries')->insert([
                                    'vatable_id' => $id,
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
            }

            DB::commit();
            return redirect()->route('admin.invoices-sent.index')
                ->with('success', 'Fattura ' . $invoice->n_invoice . ' aggiornata con successo!');

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Errore validazione: ' . json_encode($e->errors()));
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore aggiornamento fattura: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Errore: ' . $e->getMessage())->withInput();
        }
    }

    private function calculateVatSummary($rows)
    {
        $vatSummary = [];
        foreach ($rows as $row) {
            $rate = floatval($row['vat_rate'] ?? 0);
            $sdiNature = $row['vat_sdi_nature'] ?? '';
            $key = $rate . '|' . $sdiNature;
            
            if (!isset($vatSummary[$key])) {
                $vatSummary[$key] = [
                    'rate' => $rate,
                    'rate_percent' => $rate * 100,
                    'taxable_amount' => 0,
                    'vat_amount' => 0,
                    'description' => $row['vat_description'] ?? 'IVA ' . ($rate * 100) . '%',
                    'nature_code' => $sdiNature ?: null,
                ];
            }
            $vatSummary[$key]['taxable_amount'] += floatval($row['taxable_amount'] ?? 0);
            $vatSummary[$key]['vat_amount'] += floatval($row['vat_amount'] ?? 0);
        }
        return array_values($vatSummary);
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