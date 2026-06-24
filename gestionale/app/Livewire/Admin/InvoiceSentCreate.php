<?php

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
    public $vatRatesMap = []; // Mappa per lookup veloce
    
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
        $this->loadVatRates();
        $this->loadUnitMeasures();
        $this->loadPaymentMethods();
        $this->addRow();
        $this->calculateTotals();
        $this->addPayment();
        $this->calculatePaymentsTotal();
        
        Log::info('Mount completato, vatRatesList caricati: ' . count($this->vatRatesList));
    }
    
    public function loadVatRates()
    {
        $this->vatRatesList = DB::table('vat_rates')
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
            })
            ->toArray();
        
        // Crea mappa per lookup veloce: [rate_sdi_nature] => id
        $this->vatRatesMap = [];
        foreach ($this->vatRatesList as $vat) {
            $key = (float)$vat['rate'] . '_' . ($vat['sdi_nature'] ?? '');
            $this->vatRatesMap[$key] = $vat['id'];
        }
            
        Log::info('Aliquote IVA caricate: ' . count($this->vatRatesList));
    }
    
    public function findVatRateId($rate, $sdiNature = '')
    {
        $key = (float)$rate . '_' . ($sdiNature ?? '');
        
        // Cerca esatta corrispondenza rate + sdi_nature
        if (isset($this->vatRatesMap[$key])) {
            return $this->vatRatesMap[$key];
        }
        
        // Se non trova, cerca solo per rate (senza sdi_nature)
        foreach ($this->vatRatesList as $vat) {
            if ((float)$vat['rate'] === (float)$rate && empty($vat['sdi_nature'])) {
                return $vat['id'];
            }
        }
        
        // Ultimo tentativo: cerca qualsiasi con quella rate
        foreach ($this->vatRatesList as $vat) {
            if ((float)$vat['rate'] === (float)$rate) {
                return $vat['id'];
            }
        }
        
        return null;
    }
    
    public function loadUnitMeasures()
    {
        $this->unitMeasureList = UnitaMisura::where('valid',1)
        ->orderBy('ordinamento')
        ->get(['id_um', 'nome', 'codice'])
        ->map(fn($um) => [
            'id' => $um->id_um,
            'codice' => $um->codice,
            'nome' => $um->nome,
        ])
        ->toArray();
    }
    
    public function loadPaymentMethods()
    {
        $this->paymentMethods = config('gestionale.modalita_pagamento', []);
        Log::info('Modalità di pagamento caricate: ' . count($this->paymentMethods));
    }
    
    public function loadCompanyBankAccount()
    {
        Log::info('loadCompanyBankAccount - ID proprietà: ' . $this->id_ownership);
        
        if (!$this->id_ownership) {
            $this->companyIban = '';
            $this->companyBankName = '';
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
            Log::info('IBAN trovato: ' . $this->companyIban);
            
            if (count($this->payments) > 0) {
                $this->payments[0]['iban'] = $this->companyIban;
                $this->payments[0]['bank_name'] = $this->companyBankName;
            }
        } else {
            $this->companyIban = '';
            $this->companyBankName = '';
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
            return;
        }
        
        // Carica TUTTI i sezionali (anche quelli disattivati) per la proprietà selezionata
        // Ordinati per anno decrescente (più recenti prima)
        $this->availableSeries = InvoiceSeries::where('id_ownership', $this->id_ownership)
            ->orderBy('year', 'desc')
            ->orderBy('code')
            ->get()
            ->map(function($series) {
                // Aggiungi un flag per sapere se è attivo
                $series->is_active = (bool)$series->active;
                return $series;
            })
            ->toArray();
            
        Log::info('Sezionali caricati per proprietà ' . $this->id_ownership . ': ' . count($this->availableSeries));
    }
    
    public function updatedSelectedSeriesId()
    {
        Log::info('updatedSelectedSeriesId - Sezionale selezionato: ' . $this->selectedSeriesId);
        
        $series = collect($this->availableSeries)->firstWhere('id', $this->selectedSeriesId);
        if ($series) {
            // Controlla se il sezionale è attivo
            if (!$series['active']) {
                session()->flash('warning', 'ATTENZIONE: Questo sezionale è disattivato. La fattura verrà comunque creata ma il sezionale non sarà più utilizzabile per nuove fatture.');
            }
            
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
        
        // Trova l'IVA al 22% di default (senza sdi_nature)
        $defaultVatId = null;
        foreach ($this->vatRatesList as $vat) {
            if ((float)$vat['rate'] === 0.22 && empty($vat['sdi_nature'])) {
                $defaultVatId = $vat['id'];
                break;
            }
        }
        
        $this->rows[] = [
            'code' => '',
            'description' => '',
            'quantity' => 1.00,
            'unit_price' => 0.000,
            'unit_measure' => 'pz',
            'discount_percentage' => 0,
            'vat_rate_id' => $defaultVatId,
            'vat_rate' => 0.22,
            'vat_sdi_nature' => '',
            'vat_description' => 'IVA 22%',
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
        
        $this->serviceSearch[$index] = '';
        $this->serviceResults[$index] = [];
        $this->selectedServiceId[$index] = null;
        $this->selectedServiceName[$index] = '';
        $this->showServiceDropdown[$index] = false;
        
        $this->calculateTotals();
    }
    
    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);

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
    }
    
    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->calculatePaymentsTotal();
    }
    
    public function updatedRows($value, $key)
    {
        if (str_ends_with((string)$key, '.vat_rate_id')) {
            $parts = explode('.', $key);
            $index = (int)$parts[1];
            $vatInfo = collect($this->vatRatesList)->firstWhere('id', (int)$value);
            if ($vatInfo) {
                $this->rows[$index]['vat_rate'] = (float)$vatInfo['rate'];
                $this->rows[$index]['vat_sdi_nature'] = $vatInfo['sdi_nature'] ?? '';
                $this->rows[$index]['vat_description'] = $vatInfo['description'] ?? '';
            }
        }
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
            $sdiNature = $row['vat_sdi_nature'] ?? '';

            $grossAmount = $quantity * $unitPrice;
            $discountAmount = $grossAmount * ($discountPercentage / 100);
            $totalDiscount += $discountAmount;
            $taxable = $grossAmount - $discountAmount;
            $totalTaxable += $taxable;
            $vatAmount = $taxable * $vatRate;
            $totalVat += $vatAmount;

            $key = $vatRate . '_' . $sdiNature;

            if (!isset($vatGroup[$key])) {
                $vatInfo = collect($this->vatRatesList)->first(function($v) use ($vatRate, $sdiNature) {
                    return (float)$v['rate'] === $vatRate && ($v['sdi_nature'] ?? '') === ($sdiNature ?? '');
                });
                
                if (!$vatInfo) {
                    $vatInfo = collect($this->vatRatesList)->firstWhere('rate', $vatRate);
                }

                $vatGroup[$key] = [
                    'rate' => $vatRate,
                    'rate_percent' => $vatRate * 100,
                    'taxable_amount' => 0,
                    'vat_amount' => 0,
                    'description' => $vatInfo['description'] ?? ('IVA ' . ($vatRate * 100) . '%'),
                    'nature_code' => $sdiNature ?: ($vatInfo['sdi_nature'] ?? null),
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
    }
    
    public function clearCustomer()
    {
        $this->selectedCustomerId = '';
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
    }

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
    }
    
    public function updatedServiceSearch($value, $key)
    {
        $index = (int) $key;
        
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
                'id' => $s->id,
                'name' => $s->Titolo,
                'descr_fattura' => $s->Descr_fattura ?? '',
                'prezzo_un' => $s->Prezzo_un ?? 0,
            ])->values()->toArray();
    }
    
    public function selectService($index, $serviceId, $serviceName, $descrFattura, $prezzoUn)
    {
        $this->rows[$index]['id_service'] = $serviceId;
        $this->selectedServiceId[$index] = $serviceId;
        $this->selectedServiceName[$index] = $serviceName;
        $this->serviceSearch[$index] = $serviceName;
        $this->showServiceDropdown[$index] = false;
        
        if (!empty($descrFattura)) {
            $this->rows[$index]['description'] = $descrFattura;
        } else {
            $this->rows[$index]['description'] = $serviceName;
        }
        
        if ($prezzoUn > 0 && (empty($this->rows[$index]['unit_price']) || $this->rows[$index]['unit_price'] == 0)) {
            $this->rows[$index]['unit_price'] = round((float)$prezzoUn, 3);
        }
        
        $service = \App\Models\Service::with('vatRate')->find($serviceId);
        if ($service && $service->vatRate) {
            $vatInfo = $service->vatRate;
            $vatId = $this->findVatRateId($vatInfo->rate, $vatInfo->sdi_nature ?? '');
            
            if ($vatId) {
                $this->rows[$index]['vat_rate_id'] = $vatId;
                $this->rows[$index]['vat_rate'] = (float)$vatInfo->rate;
                $this->rows[$index]['vat_sdi_nature'] = $vatInfo->sdi_nature ?? '';
                $this->rows[$index]['vat_description'] = $vatInfo->description ?? '';
            }
        }
        
        $this->calculateTotals();
    }
    
    public function openCustomerModal()
    {
        $this->showCustomerModal = true;
    }
    
    public function closeCustomerModal()
    {
        $this->showCustomerModal = false;
        $this->resetNewCustomerFields();
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
        foreach ($this->rows as $index => &$row) {
            $row['quantity'] = str_replace(',', '.', $row['quantity'] ?? 0);
            $row['unit_price'] = str_replace(',', '.', $row['unit_price'] ?? 0);
            $row['discount_percentage'] = str_replace(',', '.', $row['discount_percentage'] ?? 0);
        }
        unset($row);

        Log::info('=== INIZIO SALVATAGGIO FATTURA VENDITA ===');
        
        try {
            $this->validate();
        } catch (\Exception $e) {
            Log::error('❌ Errore validazione: ' . $e->getMessage());
            session()->flash('error', 'Errore validazione: ' . $e->getMessage());
            return null;
        }
        
        try {
            DB::beginTransaction();
            
            // Trova il sezionale
            $series = InvoiceSeries::find($this->selectedSeriesId);
            if (!$series) {
                throw new \Exception('Sezionale non trovato per ID: ' . $this->selectedSeriesId);
            }
            
            // Aggiorna il last_number anche se il sezionale è disattivato
            // Questo permette di continuare a usare lo stesso sezionale anche se disattivato
            // (utile per anni passati)
            $series->last_number += 1;
            $series->save();
            
            // Se il sezionale è disattivato, log di warning
            if (!$series->active) {
                Log::warning('Sezionale disattivato utilizzato: ' . $series->code . ' - ' . $series->year);
            }
            
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
            
            $invoice = InvoiceSent::create($invoiceData);
            
            foreach ($this->rows as $row) {
                $vatRate = floatval($row['vat_rate'] ?? 0);
                $sdiNature = $row['vat_sdi_nature'] ?? null;
                
                if (!empty($row['vat_rate_id'])) {
                    $vatInfo = collect($this->vatRatesList)->firstWhere('id', (int)$row['vat_rate_id']);
                    if ($vatInfo) {
                        $vatRate = (float)$vatInfo['rate'];
                        $sdiNature = $vatInfo['sdi_nature'] ?? null;
                    }
                }
                
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_sent',
                    'code' => $row['code'] ?? null,
                    'description' => $row['description'],
                    'quantity' => round(floatval($row['quantity'] ?? 1), 2),
                    'unit_price' => round(floatval($row['unit_price'] ?? 0), 3),
                    'unit_measure' => $row['unit_measure'] ?? 'pz',
                    'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                    'vat_rate' => $vatRate * 100,
                    'sdi_nature' => $sdiNature,
                    'total' => floatval($row['taxable_amount'] ?? 0),
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'id_service' => $row['id_service'] ?? null,
                ]);
            }
            
            foreach ($this->vatSummary as $vat) {
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
            }
            
            foreach ($this->payments as $payment) {
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
                    
                    DB::table('invoice_payments')->insert([
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
                    ]);
                }
            }
            
            DB::commit();
            session()->flash('success', 'Fattura ' . $this->n_invoice . ' creata con successo!');
            return redirect()->route('admin.invoices-sent.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ ERRORE: ' . $e->getMessage());
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