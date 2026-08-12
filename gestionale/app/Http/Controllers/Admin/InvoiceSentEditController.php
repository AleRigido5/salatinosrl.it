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
use App\Models\VatRate;
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
        
        // Carica aliquote IVA
        $vatRates = VatRate::where('is_active', 1)
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
        $totalTaxable = 0;
        $totalVat = 0;
        
        foreach ($invoiceRows as $index => $row) {
            // Calcola l'aliquota IVA in percentuale
            $vatRate = $row->vat_rate / 100;
            
            // Trova l'IVA corretta: prima per vat_rate_id (FK diretto), poi
            // in fallback per rate percentuale (utile per righe "vecchie" create
            // prima del fix che salva vat_rate_id, dove il FK è rimasto nullo
            // ma la percentuale vat_rate è comunque corretta).
            $vatInfo = null;
            if ($row->vat_rate_id) {
                $vatInfo = collect($vatRates)->firstWhere('id', $row->vat_rate_id);
            }
            if (!$vatInfo) {
                $vatInfo = collect($vatRates)->firstWhere('rate', $vatRate);
            }
            
            // Calcola l'importo IVA
            $vatAmount = $row->total * $vatRate;
            
            // Accumula totali
            $totalTaxable += $row->total;
            $totalVat += $vatAmount;
            
            $rows[] = [
                'id' => $row->id,
                'code' => $row->code ?? '',
                'description' => $row->description,
                'quantity' => (float)$row->quantity,
                'unit_price' => (float)$row->unit_price,
                'id_unit_measure' => $row->id_unit_measure ?? 1,
                'discount_percentage' => (float)$row->discount_percentage,
                // FIX: usa l'id risolto da $vatInfo (che include il fallback per
                // rate) invece del solo $row->vat_rate_id, altrimenti la select
                // in modifica resta vuota per le righe con FK nullo ma percentuale
                // corretta, causando la perdita dell'IVA nel totale ricalcolato.
                'vat_rate_id' => $vatInfo['id'] ?? $row->vat_rate_id,
                'vat_rate' => $vatRate,
                'vat_sdi_nature' => $vatInfo['sdi_nature'] ?? '',
                'vat_description' => $vatInfo['description'] ?? '',
                'id_cost_center' => $row->id_cost_center,
                'id_service' => $row->id_service,
                'taxable_amount' => (float)$row->total,
                'vat_amount' => $vatAmount,
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
        
        // Carica pagamenti
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
        
        // Calcola riepilogo IVA
        if ($invoice->is_manual ?? false) {
            // Fattura manuale: i totali mostrati sono quelli ricalcolati dalle
            // righe, cioè lo stesso valore che l'utente vede aggiornarsi dal
            // vivo mentre modifica quantità/prezzi/IVA in pagina.
            $vatSummary = $this->calculateVatSummary($rows);
            $totalDiscount = array_sum(array_map(function($row) {
                return ($row['quantity'] * $row['unit_price']) * ($row['discount_percentage'] / 100);
            }, $rows));
            $importoTotale = $totalTaxable + $totalVat;
        } else {
            // FIX: fattura importata da XML (importi e IVA di sola lettura in
            // questa pagina). Prima anche qui si ricalcolavano SEMPRE
            // totalTaxable/totalVat/importoTotale sommando le righe
            // (row->total, vat_rate), valore che può divergere da quello
            // realmente registrato sulla fattura per arrotondamenti, spese
            // accessorie o sconti applicati diversamente in fase di import.
            // Questo causava un totale diverso tra la pagina di modifica e
            // l'elenco Fatture di Vendita (che legge importo_totale
            // direttamente) per la stessa identica fattura.
            //
            // Ora, quando i valori non sono comunque modificabili, mostriamo
            // gli stessi identici dati "veri" usati dall'elenco: importo_totale
            // della fattura e il riepilogo IVA da invoice_vat_summaries (la
            // stessa tabella che alimenta i totali dell'elenco fatture).
            $invoiceVatSummaries = $invoice->vatSummaries()->get();

            if ($invoiceVatSummaries->isNotEmpty()) {
                $vatSummary = $invoiceVatSummaries->map(function ($vat) {
                    return [
                        'rate' => $vat->tax_rate / 100,
                        'rate_percent' => $vat->tax_rate,
                        'taxable_amount' => (float) $vat->taxable_amount,
                        'vat_amount' => (float) $vat->tax_amount,
                        'description' => 'IVA ' . $vat->tax_rate . '%',
                        'nature_code' => null,
                    ];
                })->values()->toArray();

                $totalTaxable = (float) $invoiceVatSummaries->sum('taxable_amount');
                $totalVat = (float) $invoiceVatSummaries->sum('tax_amount');
            } else {
                // Fallback: se per qualche motivo la fattura non ha righe di
                // riepilogo IVA salvate, si ricade sul calcolo dalle righe
                // (meglio un valore ricostruito che nessun valore).
                $vatSummary = $this->calculateVatSummary($rows);
            }

            $totalDiscount = array_sum(array_map(function($row) {
                return ($row['quantity'] * $row['unit_price']) * ($row['discount_percentage'] / 100);
            }, $rows));

            // Il totale fattura mostrato è SEMPRE quello realmente registrato,
            // non una ricostruzione dalle righe.
            $importoTotale = (float) $invoice->importo_totale;
        }
        
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
                    'importo_totale' => 'numeric|min:0',
                    'rows.*.code' => 'nullable|string',
                    'rows.*.quantity' => 'required|numeric|min:0.001',
                    'rows.*.unit_price' => 'required|numeric|min:0',
                    'rows.*.vat_rate_id' => 'nullable|exists:vat_rates,id',
                    'payments' => 'array|nullable',
                    'payments.*.amount' => 'numeric|min:0',
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
            $totalTaxable = 0;
            $totalVat = 0;
            
            foreach ($rows as $row) {
                // Salta le righe marcate per eliminazione
                if (isset($row['_delete']) && $row['_delete']) {
                    if (isset($row['id']) && $row['id']) {
                        InvoiceRow::where('id', $row['id'])->delete();
                    }
                    continue;
                }

                // Calcola il totale imponibile
                $quantity = floatval(str_replace(',', '.', $row['quantity'] ?? 1));
                $unitPrice = floatval(str_replace(',', '.', $row['unit_price'] ?? 0));
                $discount = floatval(str_replace(',', '.', $row['discount_percentage'] ?? 0));
                $grossAmount = $quantity * $unitPrice;
                $discountAmount = $grossAmount * ($discount / 100);
                $taxable = $grossAmount - $discountAmount;
                
                // Ottieni l'aliquota IVA
                $vatRate = floatval($row['vat_rate'] ?? 0);
                $vatAmount = $taxable * $vatRate;
                
                // Accumula totali
                $totalTaxable += $taxable;
                $totalVat += $vatAmount;

                // FIX: per le fatture importate (is_manual = false) la select
                // dell'IVA in edit.blade.php è renderizzata con l'attributo
                // "disabled" — e un <select disabled> NON viene mai inviato
                // nel submit del form (comportamento standard HTML). Quindi
                // $row['vat_rate_id'] risultava sempre assente dalla request
                // per queste fatture, e la vecchia riga
                //   'vat_rate_id' => $row['vat_rate_id'] ?? null,
                // azzerava silenziosamente vat_rate_id su OGNI riga a ogni
                // salvataggio (anche solo per cambiare un centro di costo),
                // causando la perdita dell'aliquota IVA corretta nelle
                // visualizzazioni successive (edit, PDF, riepiloghi). Ora, se
                // vat_rate_id non è presente nella request, preserviamo il
                // valore già salvato sulla riga invece di sovrascriverlo con
                // null.
                $existingVatRateId = null;
                if (isset($row['id']) && $row['id']) {
                    $existingVatRateId = InvoiceRow::where('id', $row['id'])->value('vat_rate_id');
                }
                $submittedVatRateId = array_key_exists('vat_rate_id', $row) && $row['vat_rate_id'] !== ''
                    ? $row['vat_rate_id']
                    : null;
                $resolvedVatRateId = $submittedVatRateId ?? $existingVatRateId;

                // Prepara i dati della riga
                $rowData = [
                    'code' => $row['code'] ?? '',
                    'description' => $row['description'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'id_unit_measure' => intval($row['id_unit_measure'] ?? 1),
                    'discount_percentage' => $discount,
                    'id_cost_center' => !empty($row['id_cost_center']) ? $row['id_cost_center'] : null,
                    'id_service' => !empty($row['id_service']) ? $row['id_service'] : null,
                    'vat_rate_id' => $resolvedVatRateId,
                    'vat_rate' => $vatRate * 100, // salva come percentuale
                    'total' => $taxable,
                ];
                
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

            // Elimina righe rimosse
            if ($invoice->is_manual ?? false) {
                InvoiceRow::where('document_id', $id)
                    ->where('document_type', 'invoice_sent')
                    ->whereNotIn('id', $existingRowIds)
                    ->delete();
            }

            // Aggiorna pagamenti
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

                // Ricalcola il riepilogo IVA
                $updatedRows = InvoiceRow::where('document_id', $id)
                    ->where('document_type', 'invoice_sent')
                    ->get();
                
                $vatSummary = $this->calculateVatSummaryFromRows($updatedRows);
                
                foreach ($vatSummary as $vat) {
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
                
                // Aggiorna l'importo totale della fattura
                $invoice->importo_totale = $totalTaxable + $totalVat;
                $invoice->save();
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

    private function calculateVatSummaryFromRows($rows)
    {
        $vatSummary = [];
        foreach ($rows as $row) {
            $rate = $row->vat_rate / 100;
            $key = $rate . '|default';
            
            if (!isset($vatSummary[$key])) {
                $vatSummary[$key] = [
                    'rate' => $rate,
                    'rate_percent' => $rate * 100,
                    'taxable_amount' => 0,
                    'vat_amount' => 0,
                    'description' => 'IVA ' . ($rate * 100) . '%',
                    'nature_code' => null,
                ];
            }
            $vatSummary[$key]['taxable_amount'] += floatval($row->total);
            $vatSummary[$key]['vat_amount'] += floatval($row->total * $rate);
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

        try {
            $results = Service::where('Stato', 1)
                ->where(function($query) use ($search) {
                    $query->where('Titolo', 'like', '%' . $search . '%')
                          ->orWhere('Descrizione', 'like', '%' . $search . '%')
                          ->orWhere('Descr_fattura', 'like', '%' . $search . '%');
                })
                ->orderBy('Titolo')
                ->limit(10)
                ->get()
                ->map(function($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->Titolo,
                        'description' => $service->Descrizione ?? '',
                        'descr_fattura' => $service->Descr_fattura ?? '',
                        'prezzo_un' => $service->Prezzo_un ?? 0,
                        'vat_rate_id' => $service->id_vat_rate ?? null,
                        'unita_misura_id' => $service->UnitaMisura_id_unita ?? null,
                        'category_id' => $service->id_categories ?? null,
                    ];
                });

            return response()->json($results);
            
        } catch (\Exception $e) {
            Log::error('Error in searchServices: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
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