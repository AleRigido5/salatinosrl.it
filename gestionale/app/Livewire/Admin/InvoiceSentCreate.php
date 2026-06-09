<?php
// app/Livewire/Admin/InvoiceSentCreate.php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceSent;
use App\Models\InvoiceRow;
use App\Models\InvoiceVatSummary;
use App\Models\InvoiceSeries;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\Service;
use App\Models\UnitaMisura;
use App\Models\Vehicles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceSentCreate extends Component
{
    public $id_ownership = '';
    public $type_invoice = 'TD01';
    public $defaultServiceVatRate = null;
    public $n_invoice = '';
    public $n_invoice_ext = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    public $companyIban = '';
    public $companyBankName = '';
    
    public $availableSeries = [];
    public $selectedSeriesId = '';
    
    public $total_taxable = 0;
    public $total_vat = 0;
    public $total_discount = 0;
    public $vatSummary = [];
    
    public $customerSearch = '';
    public $customerResults = [];
    public $selectedCustomerId = '';
    public $selectedCustomerName = '';
    public $showCustomerDropdown = false;
    
    public $showCustomerModal = false;
    public $newCustomerName = '';
    public $newCustomerPiva = '';
    public $newCustomerCf = '';
    public $newCustomerEmail = '';
    public $newCustomerPhone = '';
    public $newCustomerAddress = '';
    public $newCustomerCap = '';
    public $newCustomerCity = '';
    public $newCustomerProvince = '';
    
    public $rows = [];
    public $unitMeasureList = [];
    
    public $costCenterSearch = [];
    public $costCenterResults = [];
    public $showCostCenterDropdown = [];
    public $selectedCostCenterId = [];
    public $selectedCostCenterName = [];

    public $serviceSearch = [];
    public $serviceResults = [];
    public $showServiceDropdown = [];
    public $selectedServiceId = [];
    public $selectedServiceName = [];
    
    public $payments = [];
    public $total_payments_amount = 0;
    public $paymentMethods = [];
    
    public $vatRatesList = [];
    
    protected $rules = [
        'id_ownership' => 'required',
        'type_invoice' => 'required',
        'selectedSeriesId' => 'required',
        'n_invoice' => 'required|string',
        'n_invoice_ext' => 'nullable|string|max:100', 
        'data_invoice' => 'required|date',
        'selectedCustomerId' => 'required',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
    ];
    
    public function mount()
    {
        Log::info('=== MOUNT InvoiceSentCreate ===');
        
        $this->data_invoice = date('Y-m-d');
        Log::info('Data fattura impostata: ' . $this->data_invoice);
        
        $this->loadVatRates();
        $this->loadUnitMeasures();
        $this->loadPaymentMethods();
        $this->addRow();
        $this->calculateTotals();
        $this->addPayment();
        $this->calculatePaymentsTotal();
        
        Log::info('Mount completato');
    }
    
    public function loadVatRates()
    {
        $this->vatRatesList = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('rate', 'desc')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'description' => $item->description,
                    'rate' => (float)$item->rate,
                    'rate_percent' => (float)$item->rate * 100,
                    'sdi_nature' => $item->sdi_nature,
                ];
            })
            ->toArray();
            
        Log::info('Aliquote IVA caricate: ' . count($this->vatRatesList));
    }
    
    public function loadUnitMeasures()
    {
        $this->unitMeasureList = UnitaMisura::where('valid',1)
        ->orderBy('ordinamento')
        ->get(['id_um', 'nome', 'codice'])
        ->map(fn($um) => [
            'codice' => $um->codice,
            'nome' => $um->nome,
        ])
        ->toArray();
    }
    
    public function loadPaymentMethods()
    {
        // Carica le modalità di pagamento dalla configurazione
        $this->paymentMethods = config('gestionale.modalita_pagamento', []);
        
        Log::info('Modalità di pagamento caricate: ' . count($this->paymentMethods));
    }
    
    public function loadCompanyBankAccount()
    {
        Log::info('loadCompanyBankAccount - ID proprietà: ' . $this->id_ownership);
        
        if (!$this->id_ownership) {
            $this->companyIban = '';
            $this->companyBankName = '';
            Log::info('Nessuna proprietà selezionata');
            return;
        }
        
        $bankAccount = DB::table('bank_accounts')
            ->where('id_ownership', $this->id_ownership)
            ->where('default_invoice', 1)
            ->where('valid', 1)
            ->first();
        
        if ($bankAccount) {
            $this->companyIban = $bankAccount->iban ?? '';
            $this->companyBankName = $bankAccount->name ?? '';
            Log::info('IBAN trovato: ' . $this->companyIban . ' - Banca: ' . $this->companyBankName);
            
            if (count($this->payments) > 0) {
                $this->payments[0]['iban'] = $this->companyIban;
                $this->payments[0]['bank_name'] = $this->companyBankName;
                Log::info('IBAN aggiornato nel pagamento');
            }
        } else {
            $this->companyIban = '';
            $this->companyBankName = '';
            Log::warning('Nessun conto con default_invoice=1 per proprietà ID: ' . $this->id_ownership);
        }
    }
    
    public function updatedIdOwnership()
    {
        Log::info('updatedIdOwnership - Nuova proprietà: ' . $this->id_ownership);
        $this->loadAvailableSeries();
        $this->selectedSeriesId = '';
        $this->n_invoice = '';
        $this->loadCompanyBankAccount();
    }
    
    public function loadAvailableSeries()
    {
        if (!$this->id_ownership) {
            $this->availableSeries = [];
            Log::info('Nessuna proprietà selezionata per i sezionali');
            return;
        }
        
        $this->availableSeries = InvoiceSeries::where('id_ownership', $this->id_ownership)
            ->where('year', date('Y'))
            ->get()
            ->toArray();
            
        Log::info('Sezionali caricati per proprietà ' . $this->id_ownership . ': ' . count($this->availableSeries));
    }
    
    public function updatedSelectedSeriesId()
    {
        Log::info('updatedSelectedSeriesId - Sezionale selezionato: ' . $this->selectedSeriesId);
        
        $series = collect($this->availableSeries)->firstWhere('id', $this->selectedSeriesId);
        if ($series) {
            // Calcola il nuovo progressivo (ultimo numero + 1)
            $nextNumber = $series['last_number'] + 1;
            
            // Formato: progressivo/codice sezionale-anno
            // Esempio: 1/A-2026
            $this->n_invoice = $nextNumber . '/' . $series['code'] . '-' . $series['year'];
            
            Log::info('Numero fattura generato: ' . $this->n_invoice);
        } else {
            Log::warning('Sezionale non trovato per ID: ' . $this->selectedSeriesId);
        }
    }

    public function getPreviewInvoiceNumberAttribute()
    {
        if (!$this->selectedSeriesId) {
            return '';
        }
        
        $series = collect($this->availableSeries)->firstWhere('id', $this->selectedSeriesId);
        if ($series) {
            $nextNumber = $series['last_number'] + 1;
            return $nextNumber . '/' . $series['code'] . '-' . $series['year'];
        }
        
        return '';
    }
    
    public function addRow()
    {
        $index = count($this->rows);
        $this->rows[] = [
            'code' => '',
            'description' => '',
            'quantity' => 1.00,
            'unit_price' => 0.000,
            'unit_measure' => 'pz',
            'discount_percentage' => 0,
            'vat_rate' => 0.22,
            'id_cost_center' => null,
            'id_service' => null,
            'taxable_amount' => 0,
            'vat_amount' => 0,
        ];
        
        $this->costCenterSearch[$index] = '';
        $this->costCenterResults[$index] = [];
        $this->showCostCenterDropdown[$index] = false;
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';
        
        $this->serviceSearch[$index]       = '';
        $this->serviceResults[$index]      = [];
        $this->selectedServiceId[$index]   = null;
        $this->selectedServiceName[$index] = '';
        $this->showServiceDropdown[$index] = false;
        
        $this->calculateTotals();
        
        Log::info('Riga aggiunta, totale righe: ' . count($this->rows));
    }
    
    public function removeRow($index)
    {
        // Rimuovi la riga
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

        // Rimuovi e reindicizza costCenter
        if (isset($this->costCenterSearch[$index])) unset($this->costCenterSearch[$index]);
        if (isset($this->costCenterResults[$index])) unset($this->costCenterResults[$index]);
        if (isset($this->selectedCostCenterId[$index])) unset($this->selectedCostCenterId[$index]);
        if (isset($this->selectedCostCenterName[$index])) unset($this->selectedCostCenterName[$index]);
        if (isset($this->showCostCenterDropdown[$index])) unset($this->showCostCenterDropdown[$index]);
        $this->costCenterSearch = array_values($this->costCenterSearch);
        $this->costCenterResults = array_values($this->costCenterResults);
        $this->selectedCostCenterId = array_values($this->selectedCostCenterId);
        $this->selectedCostCenterName = array_values($this->selectedCostCenterName);
        $this->showCostCenterDropdown = array_values($this->showCostCenterDropdown);

        // Rimuovi e reindicizza service
        if (isset($this->serviceSearch[$index])) unset($this->serviceSearch[$index]);
        if (isset($this->serviceResults[$index])) unset($this->serviceResults[$index]);
        if (isset($this->selectedServiceId[$index])) unset($this->selectedServiceId[$index]);
        if (isset($this->selectedServiceName[$index])) unset($this->selectedServiceName[$index]);
        if (isset($this->showServiceDropdown[$index])) unset($this->showServiceDropdown[$index]);
        $this->serviceSearch = array_values($this->serviceSearch);
        $this->serviceResults = array_values($this->serviceResults);
        $this->selectedServiceId = array_values($this->selectedServiceId);
        $this->selectedServiceName = array_values($this->selectedServiceName);
        $this->showServiceDropdown = array_values($this->showServiceDropdown);

        $this->calculateTotals();
        Log::info('Riga rimossa, totale righe: ' . count($this->rows));
    }
    
    public function addPayment()
    {
        $this->payments[] = [
            'due_date' => $this->data_invoice,
            'amount' => $this->importo_totale,
            'payment_method' => 'MP05',
            'iban' => $this->companyIban,
            'bank_name' => $this->companyBankName,
        ];
        $this->calculatePaymentsTotal();
        Log::info('Pagamento aggiunto, totale pagamenti: ' . count($this->payments) . ', importo: ' . $this->importo_totale);
    }
    
    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->calculatePaymentsTotal();
        Log::info('Pagamento rimosso, totale pagamenti: ' . count($this->payments));
    }
    
    public function updatedRows()
    {
        $this->calculateTotals();
    }
    
    public function calculatePaymentsTotal()
    {
        $total = 0;
        foreach ($this->payments as $payment) {
            $total += floatval($payment['amount'] ?? 0);
        }
        $this->total_payments_amount = $total;
    }
    
    public function calculateTotals()
    {
        $totalTaxable = 0;
        $totalVat = 0;
        $totalDiscount = 0;
        $vatGroup = [];
        
        foreach ($this->rows as $index => &$row) {
            $quantity = floatval($row['quantity'] ?? 1);
            $unitPrice = floatval($row['unit_price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);
            $vatRate = floatval($row['vat_rate'] ?? 0);
            
            $grossAmount = $quantity * $unitPrice;
            $discountAmount = $grossAmount * ($discountPercentage / 100);
            $totalDiscount += $discountAmount;
            $taxable = $grossAmount - $discountAmount;
            $totalTaxable += $taxable;
            $vatAmount = $taxable * $vatRate;
            $totalVat += $vatAmount;
            
            $key = (string)$vatRate;
            if (!isset($vatGroup[$key])) {
                $vatGroup[$key] = [
                    'rate' => $vatRate,
                    'rate_percent' => $vatRate * 100,
                    'taxable_amount' => 0,
                    'vat_amount' => 0,
                ];
            }
            $vatGroup[$key]['taxable_amount'] += $taxable;
            $vatGroup[$key]['vat_amount'] += $vatAmount;
            
            $row['taxable_amount'] = $taxable;
            $row['vat_amount'] = $vatAmount;
        }
        
        $this->vatSummary = array_values($vatGroup);
        $this->total_taxable = $totalTaxable;
        $this->total_vat = $totalVat;
        $this->total_discount = $totalDiscount;
        $this->importo_totale = $totalTaxable + $totalVat;
        
        if (count($this->payments) > 0) {
            $this->payments[0]['amount'] = $this->importo_totale;
            $this->calculatePaymentsTotal();
        }
        
        Log::info('Totali calcolati - Imponibile: ' . $this->total_taxable . ', IVA: ' . $this->total_vat . ', Totale: ' . $this->importo_totale);
    }
    
    public function updatedCustomerSearch()
    {
        if (strlen($this->customerSearch) < 2) {
            $this->customerResults = [];
            $this->showCustomerDropdown = false;
            return;
        }
        
        $this->customerResults = Entity::where('valid', 1)
            ->whereIn('entity_type', ['cliente', 'entrambi'])
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->customerSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva'])
            ->toArray();
        
        $this->showCustomerDropdown = count($this->customerResults) > 0;
    }
    
    public function selectCustomer($id, $name)
    {
        $this->selectedCustomerId = $id;
        $this->selectedCustomerName = $name;
        $this->customerSearch = $name;
        $this->showCustomerDropdown = false;
        Log::info('Cliente selezionato: ID=' . $id . ', Nome=' . $name);
    }
    
    public function clearCustomer()
    {
        $this->selectedCustomerId = '';
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
        Log::info('Cliente deselezionato');
    }

    // ==================== AUTOCOMPLETE CENTRI DI COSTO ====================
    public function updatedCostCenterSearch($value, $key)
    {
        $index = (int) $key;
        
        if (strlen($value) < 2) {
            $this->costCenterResults[$index] = [];
            return;
        }

        $this->costCenterResults[$index] = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $value . '%')
            ->limit(10)
            ->get(['id', 'Nome as name'])
            ->values()->toArray();
    }
    
    public function selectCostCenter($id, $name, $index)
    {
        $this->rows[$index]['id_cost_center'] = $id;
        $this->selectedCostCenterId[$index] = $id;
        $this->selectedCostCenterName[$index] = $name;
        $this->costCenterSearch[$index] = $name;
        $this->showCostCenterDropdown[$index] = false;
        $this->calculateTotals();
        Log::info('Centro di costo selezionato riga ' . $index . ': ' . $name);
    }
    
    // ==================== AUTOCOMPLETE SERVIZI ====================
    public function updatedServiceSearch($value, $key)
    {
        $index = (int) $key; // cast a intero!
        
        if (strlen($value) < 2) {
            $this->serviceResults[$index] = [];
            return;
        }

        $this->serviceResults[$index] = Service::where('Stato', 1)
            ->where('Titolo', 'like', '%' . $value . '%')
            ->orderBy('Titolo')
            ->limit(10)
            ->get()
            ->map(fn($s) => [
                'id'            => $s->id,
                'name'          => $s->Titolo,
                'descr_fattura' => $s->Descr_fattura ?? '',
                'prezzo_un'     => $s->Prezzo_un ?? 0,
            ])->values()->toArray(); // ->values() forza array indicizzato
    }
    
    /**
     * Seleziona un servizio e popola i campi della riga
     */
    public function selectService($index, $serviceId, $serviceName, $descrFattura, $prezzoUn)
    {
        Log::info("Selezionato servizio - Index: {$index}, ID: {$serviceId}, Nome: {$serviceName}");
        
        $this->rows[$index]['id_service'] = $serviceId;
        $this->selectedServiceId[$index] = $serviceId;
        $this->selectedServiceName[$index] = $serviceName;
        $this->serviceSearch[$index] = $serviceName;
        $this->showServiceDropdown[$index] = false;
        
        // Popola la descrizione
        if (!empty($descrFattura)) {
            $this->rows[$index]['description'] = $descrFattura;
        } else {
            $this->rows[$index]['description'] = $serviceName;
        }
        
        // Popola il prezzo
        if ($prezzoUn > 0 && (empty($this->rows[$index]['unit_price']) || $this->rows[$index]['unit_price'] == 0)) {
            $this->rows[$index]['unit_price'] = round((float)$prezzoUn, 3);
        }
        
        // *** AGGIUNGI QUESTA PARTE: Imposta l'IVA predefinita dal servizio ***
        $service = \App\Models\Service::with('vatRate')->find($serviceId);
        if ($service && $service->vatRate) {
            $this->rows[$index]['vat_rate'] = (float)$service->vatRate->rate;
            $this->defaultServiceVatRate = (float)$service->vatRate->rate;
            Log::info("IVA impostata dal servizio: " . $service->vatRate->rate * 100 . "% - " . $service->vatRate->description);
        }
        
        $this->calculateTotals();
    }
    
    public function openCustomerModal()
    {
        $this->showCustomerModal = true;
        Log::info('Apertura modale nuovo cliente');
    }
    
    public function closeCustomerModal()
    {
        $this->showCustomerModal = false;
        $this->resetNewCustomerFields();
        Log::info('Chiusura modale nuovo cliente');
    }
    
    public function resetNewCustomerFields()
    {
        $this->newCustomerName = '';
        $this->newCustomerPiva = '';
        $this->newCustomerCf = '';
        $this->newCustomerEmail = '';
        $this->newCustomerPhone = '';
        $this->newCustomerAddress = '';
        $this->newCustomerCap = '';
        $this->newCustomerCity = '';
        $this->newCustomerProvince = '';
    }
    
    public function createCustomer()
    {
        Log::info('Tentativo creazione nuovo cliente: ' . $this->newCustomerName);
        
        $this->validate([
            'newCustomerName' => 'required|string|max:255',
        ]);
        
        try {
            $customer = Entity::create([
                'entity_type' => 'cliente',
                'ragione_sociale' => $this->newCustomerName,
                'partita_iva' => $this->newCustomerPiva,
                'codice_fiscale' => $this->newCustomerCf,
                'email' => $this->newCustomerEmail,
                'telefono' => $this->newCustomerPhone,
                'indirizzo' => $this->newCustomerAddress,
                'cap' => $this->newCustomerCap,
                'comune' => $this->newCustomerCity,
                'provincia' => $this->newCustomerProvince,
                'valid' => 1,
            ]);
            
            Log::info('Cliente creato con successo, ID: ' . $customer->id_cliente);
            
            $this->selectCustomer($customer->id_cliente, $this->newCustomerName);
            $this->showCustomerModal = false;
            $this->resetNewCustomerFields();
            session()->flash('success', 'Cliente creato con successo!');
        } catch (\Exception $e) {
            Log::error('Errore creazione cliente: ' . $e->getMessage());
            session()->flash('error', 'Errore: ' . $e->getMessage());
        }
    }
    
    public function save()
    {
        Log::info('=== INIZIO SALVATAGGIO FATTURA VENDITA ===');
        Log::info('Dati ricevuti:', [
            'id_ownership' => $this->id_ownership,
            'type_invoice' => $this->type_invoice,
            'selectedSeriesId' => $this->selectedSeriesId,
            'data_invoice' => $this->data_invoice,
            'selectedCustomerId' => $this->selectedCustomerId,
            'total_rows' => count($this->rows),
            'total_payments' => count($this->payments),
            'importo_totale' => $this->importo_totale
        ]);
        
        // Validazione
        Log::info('Step 1: Validazione dati...');
        try {
            $this->validate();
            Log::info('✅ Validazione superata');
        } catch (\Exception $e) {
            Log::error('❌ Errore validazione: ' . $e->getMessage());
            Log::error('Validation errors: ' . json_encode($this->getErrorBag()));
            session()->flash('error', 'Errore validazione: ' . $e->getMessage());
            return null;
        }
        
        try {
            DB::beginTransaction();
            Log::info('Step 2: Transazione iniziata');
            
            // Aggiorna sezionale
            Log::info('Step 3: Verifica sezionale ID: ' . $this->selectedSeriesId);
            $series = InvoiceSeries::find($this->selectedSeriesId);
            if (!$series) {
                throw new \Exception('Sezionale non trovato per ID: ' . $this->selectedSeriesId);
            }
            Log::info('✅ Sezionale trovato: ' . $series->code . ' - Ultimo numero: ' . $series->last_number);
            
            $oldNumber = $series->last_number;
            $series->last_number += 1;
            $series->save();
            Log::info('✅ Sezionale aggiornato: ' . $oldNumber . ' → ' . $series->last_number);
            
            // Crea fattura
            Log::info('Step 4: Creazione fattura...');
            $invoiceData = [
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->selectedCustomerId,
                'id_invoice_series' => $this->selectedSeriesId,
                'type_invoice' => $this->type_invoice,
                'n_invoice' => $this->n_invoice,
                'n_invoice_ext' => $this->n_invoice_ext,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'divisa' => 'EUR',
                'status' => 'issued',
                'is_manual' => true,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
            ];
            Log::info('Dati fattura:', $invoiceData);
            
            $invoice = InvoiceSent::create($invoiceData);
            Log::info('✅ Fattura creata con ID: ' . $invoice->id);

            // Salva righe
            Log::info('Step 5: Salvataggio righe fattura (totale: ' . count($this->rows) . ')');
            
            foreach ($this->rows as $index => $row) {
                Log::info('--- Riga ' . $index . ' ---');
                Log::info('Dati riga:', [
                    'description' => $row['description'] ?? 'null',
                    'quantity' => $row['quantity'] ?? 0,
                    'unit_price' => $row['unit_price'] ?? 0,
                    'vat_rate' => $row['vat_rate'] ?? 0,
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'taxable_amount' => $row['taxable_amount'] ?? 0,
                ]);
                
                $rowData = [
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_sent',
                    'code' => $row['code'] ?? null,
                    'description' => $row['description'],
                    'quantity' => round(floatval($row['quantity'] ?? 1), 2),
                    'unit_price' => round(floatval($row['unit_price'] ?? 0), 3),
                    'unit_measure' => $row['unit_measure'] ?? 'pz',
                    'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                    'vat_rate' => floatval($row['vat_rate'] ?? 0) * 100,
                    'total' => floatval($row['taxable_amount'] ?? 0),
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'id_service' => $row['id_service'] ?? null,
                    'id_vehicle' => $row['id_vehicle'] ?? null,
                ];
                Log::info('Dati per InvoiceRow:', $rowData);
                
                InvoiceRow::create($rowData);
                Log::info('✅ Riga ' . $index . ' salvata con successo');
            }
            
            // Salva riepiloghi IVA
            Log::info('Step 6: Salvataggio riepiloghi IVA (totale: ' . count($this->vatSummary) . ')');
            
            foreach ($this->vatSummary as $index => $vat) {
                Log::info('Riepilogo IVA ' . $index . ':', [
                    'rate' => $vat['rate'] * 100,
                    'taxable_amount' => $vat['taxable_amount'],
                    'vat_amount' => $vat['vat_amount']
                ]);
                
                DB::table('invoice_vat_summaries')->insert([
                    'vatable_id' => $invoice->id,
                    'vatable_type' => InvoiceSent::class,
                    'tax_rate' => $vat['rate'] * 100,
                    'taxable_amount' => $vat['taxable_amount'],
                    'tax_amount' => $vat['vat_amount'],
                    'esigibilita_iva' => 'I',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info('✅ Riepilogo IVA ' . $index . ' salvato');
            }
            
            // Salva pagamenti
            Log::info('Step 7: Salvataggio pagamenti (totale: ' . count($this->payments) . ')');
            
            foreach ($this->payments as $index => $payment) {
                if ($payment['amount'] > 0) {
                    $iban = $payment['iban'] ?? $this->companyIban;
                    if (empty($iban)) {
                        $bankAccount = DB::table('bank_accounts')
                            ->where('id_ownership', $this->id_ownership)
                            ->where('default_invoice', 1)
                            ->where('valid', 1)
                            ->first();
                        $iban = $bankAccount->iban ?? null;
                    }
                    
                    $paymentData = [
                        'payable_id' => $invoice->id,
                        'payable_type' => InvoiceSent::class,
                        'due_date' => $payment['due_date'],
                        'amount' => $payment['amount'],
                        'paid_amount' => 0,
                        'residual_amount' => $payment['amount'],
                        'payment_method' => $payment['payment_method'] ?? 'MP05',
                        'iban' => $iban,
                        'status' => 'issued',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    Log::info('Dati pagamento ' . $index . ':', $paymentData);
                    
                    DB::table('invoice_payments')->insert($paymentData);
                    Log::info('✅ Pagamento ' . $index . ' salvato');
                }
            }
            
            DB::commit();
            Log::info('✅✅✅ TRANSIZIONE COMPLETATA CON SUCCESSO! Fattura ID: ' . $invoice->id);
            
            session()->flash('success', 'Fattura ' . $this->n_invoice . ' creata con successo!');
            return redirect()->route('admin.invoices-sent.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌❌❌ ERRORE GENERALE: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            session()->flash('error', 'Errore: ' . $e->getMessage());
            return null;
        }
    }
    
    public function getOwnershipsProperty()
    {
        return Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();
    }
    
    public function getTypeDocumentsProperty()
    {
        return config('gestionale.tipo_documento', []);
    }
    
    public function render()
    {
        return view('livewire.admin.invoice-sent-create', [
            'ownerships' => $this->ownerships,
            'typeDocuments' => $this->typeDocuments,
            'vatRatesList' => $this->vatRatesList,
            'paymentMethods' => $this->paymentMethods,
            'unitMeasureList' => $this->unitMeasureList,
        ]);
    }
}