<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceSent;
use App\Models\InvoiceRow;
use App\Models\InvoiceSeries;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\UnitaMisura;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceSentEdit extends Component
{
    public $invoiceId;
    public $invoice;
    
    public $id_ownership = '';
    public $type_invoice = '';
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
    public $selectedServiceId = [];
    public $selectedServiceName = [];
    public $showServiceDropdown = [];
    
    public $payments = [];
    public $total_payments_amount = 0;
    public $paymentMethods = [];
    
    public $vatRatesList = [];
    public $vatRatesMap = [];
    
    protected $rules = [
        'id_ownership' => 'required',
        'type_invoice' => 'required',
        'selectedSeriesId' => 'required',
        'data_invoice' => 'required|date',
        'selectedCustomerId' => 'required',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
    ];
    
    public function mount($id = null)
    {
        Log::info('=== MOUNT InvoiceSentEdit - ID: ' . $id . ' ===');
        
        if (!$id && request()->route('id')) {
            $id = request()->route('id');
        }
        
        if (!$id) {
            abort(404, 'ID fattura non specificato');
        }
        
        $this->invoiceId = $id;
        $this->loadVatRates();
        $this->loadUnitMeasures();
        $this->loadPaymentMethods();
        $this->loadInvoiceData();
        $this->loadCompanyBankAccount();
        
        $this->dispatch('refresh');
        Log::info('Mount completato');
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
        
        $this->vatRatesMap = [];
        foreach ($this->vatRatesList as $vat) {
            $key = (float)$vat['rate'] . '_' . ($vat['sdi_nature'] ?? '');
            $this->vatRatesMap[$key] = $vat['id'];
        }
    }
    
    public function findVatRateId($rate, $sdiNature = '')
    {
        $rate = (float)$rate;
        $sdiNature = $sdiNature ?? '';
        $key = $rate . '_' . $sdiNature;
        
        if (isset($this->vatRatesMap[$key])) {
            return $this->vatRatesMap[$key];
        }
        
        $keyEmpty = $rate . '_';
        if (isset($this->vatRatesMap[$keyEmpty])) {
            return $this->vatRatesMap[$keyEmpty];
        }
        
        foreach ($this->vatRatesList as $vat) {
            if ((float)$vat['rate'] === $rate) {
                return $vat['id'];
            }
        }
        
        return null;
    }
    
    public function getReturnUrl()
    {
        return route('admin.invoices-sent.index');
    }

    public function loadInvoiceData()
    {
        $this->invoice = InvoiceSent::findOrFail($this->invoiceId);
        
        $this->id_ownership = $this->invoice->id_ownership;
        $this->type_invoice = $this->invoice->type_invoice;
        $this->n_invoice = $this->invoice->n_invoice;
        $this->n_invoice_ext = $this->invoice->n_invoice_ext ?? '';
        $this->data_invoice = date('Y-m-d', strtotime($this->invoice->data_invoice));
        $this->importo_totale = $this->invoice->importo_totale;
        $this->causale = $this->invoice->causale ?? '';
        
        $this->selectedCustomerId = $this->invoice->id_entities;
        $customer = Entity::find($this->selectedCustomerId);
        if ($customer) {
            if ($customer->ragione_sociale) {
                $this->selectedCustomerName = $customer->ragione_sociale;
            } else {
                $this->selectedCustomerName = trim($customer->nome . ' ' . $customer->cognome);
            }
            $this->customerSearch = $this->selectedCustomerName;
        }
        
        $this->selectedSeriesId = $this->invoice->id_invoice_series;
        $this->loadAvailableSeries();
        
        $this->loadRows();
        $this->loadPayments();
        
        $this->calculateTotals();
        $this->calculatePaymentsTotal();
    }
    
    public function loadRows()
    {
        $invoiceRows = InvoiceRow::where('document_id', $this->invoiceId)
            ->where('document_type', 'invoice_sent')
            ->get();
        
        $this->rows = [];
        
        foreach ($invoiceRows as $index => $row) {
            $vatRate = $row->vat_rate / 100;
            $sdiNature = $row->sdi_nature ?? '';
            
            // Trova l'ID dell'aliquota IVA corrispondente
            $vatId = $this->findVatRateId($vatRate, $sdiNature);
            
            // CERCA L'INFORMAZIONE IVA COMPLETA
            $vatInfo = null;
            if ($vatId) {
                $vatInfo = collect($this->vatRatesList)->firstWhere('id', $vatId);
            }
            
            // Se non trova con l'ID, cerca per rate + sdi_nature
            if (!$vatInfo) {
                $vatInfo = collect($this->vatRatesList)->first(function($v) use ($vatRate, $sdiNature) {
                    return (float)$v['rate'] === $vatRate && ($v['sdi_nature'] ?? '') === $sdiNature;
                });
            }
            
            // Se ancora non trova, cerca solo per rate
            if (!$vatInfo) {
                $vatInfo = collect($this->vatRatesList)->firstWhere('rate', $vatRate);
            }
            
            $this->rows[] = [
                'id' => $row->id,
                'code' => $row->code ?? '',
                'description' => $row->description,
                'quantity' => round((float)$row->quantity, 2),
                'unit_price' => round((float)$row->unit_price, 3),
                'id_unit_measure' => $row->id_unit_measure ?? 1,
                'discount_percentage' => $row->discount_percentage,
                'vat_rate_id' => $vatId,
                'vat_rate' => $vatRate,
                'vat_sdi_nature' => $sdiNature,
                'vat_description' => $vatInfo['description'] ?? 'IVA ' . ($vatRate * 100) . '%',
                'id_cost_center' => $row->id_cost_center,
                'id_service' => $row->id_service ?? null,
                'taxable_amount' => $row->total,
                'vat_amount' => $row->total * $vatRate,
            ];
            
            // Carica centro di costo
            if ($row->id_cost_center) {
                $costCenter = CostCenter::find($row->id_cost_center);
                if ($costCenter) {
                    $this->costCenterSearch[$index] = $costCenter->Nome;
                    $this->selectedCostCenterId[$index] = $row->id_cost_center;
                    $this->selectedCostCenterName[$index] = $costCenter->Nome;
                } else {
                    $this->costCenterSearch[$index] = '';
                    $this->selectedCostCenterId[$index] = '';
                    $this->selectedCostCenterName[$index] = '';
                }
            } else {
                $this->costCenterSearch[$index] = '';
                $this->selectedCostCenterId[$index] = '';
                $this->selectedCostCenterName[$index] = '';
            }
            
            // Carica servizio
            if ($row->id_service) {
                $service = Service::find($row->id_service);
                if ($service) {
                    $this->serviceSearch[$index] = $service->Titolo;
                    $this->selectedServiceId[$index] = $row->id_service;
                    $this->selectedServiceName[$index] = $service->Titolo;
                } else {
                    $this->serviceSearch[$index] = '';
                    $this->selectedServiceId[$index] = null;
                    $this->selectedServiceName[$index] = '';
                }
            } else {
                $this->serviceSearch[$index] = '';
                $this->selectedServiceId[$index] = null;
                $this->selectedServiceName[$index] = '';
            }
            
            $this->costCenterResults[$index] = [];
            $this->showCostCenterDropdown[$index] = false;
            $this->serviceResults[$index] = [];
            $this->showServiceDropdown[$index] = false;
        }
    }
    
    public function loadPayments()
    {
        $this->payments = DB::table('invoice_payments')
            ->where('payable_id', $this->invoiceId)
            ->where('payable_type', InvoiceSent::class)
            ->get()
            ->map(function($payment) {
                return [
                    'id' => $payment->id,
                    'due_date' => $payment->due_date,
                    'amount' => $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'iban' => $payment->iban,
                    'bank_name' => $payment->bank_name ?? '',
                ];
            })
            ->toArray();
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
        // Carica i metodi di pagamento dal config
        $this->paymentMethods = config('gestionale.modalita_pagamento', []);
        Log::info('Modalità di pagamento caricate: ' . count($this->paymentMethods));
    }
    
    public function loadCompanyBankAccount()
    {
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
        } else {
            $this->companyIban = '';
            $this->companyBankName = '';
        }
    }
    
    public function updatedIdOwnership()
    {
        $this->loadAvailableSeries();
        $this->loadCompanyBankAccount();
        $this->updatePaymentsIban();
    }
    
    public function updatePaymentsIban()
    {
        foreach ($this->payments as &$payment) {
            if (empty($payment['iban']) || $payment['iban'] === $this->companyIban) {
                $payment['iban'] = $this->companyIban;
                $payment['bank_name'] = $this->companyBankName;
            }
        }
    }
    
    public function loadAvailableSeries()
    {
        if (!$this->id_ownership) {
            $this->availableSeries = [];
            return;
        }
        
        $this->availableSeries = InvoiceSeries::where('id_ownership', $this->id_ownership)
            ->orderBy('year', 'desc')
            ->orderBy('code')
            ->get()
            ->map(function($series) {
                $series->is_active = (bool)$series->active;
                return $series;
            })
            ->toArray();
    }
    
    public function addRow()
    {
        $index = count($this->rows);
        
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
            'id_unit_measure' => 1,
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
        if (isset($this->rows[$index]['id']) && $this->rows[$index]['id']) {
            $this->rows[$index]['_delete'] = true;
        }
        
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
        if (isset($this->payments[$index]['id']) && $this->payments[$index]['id']) {
            unset($this->payments[$index]);
        } else {
            unset($this->payments[$index]);
        }
        $this->payments = array_values($this->payments);
        $this->calculatePaymentsTotal();
    }
    
    /**
     * Gestisce i cambiamenti delle proprietà delle righe
     */
    public function updatedRows($value, $key)
    {
        // Log per debug - commenta in produzione
        // Log::info('updatedRows - key: ' . $key . ', value: ' . $value);
        
        // Verifica che la chiave sia una stringa
        if (!is_string($key)) {
            return;
        }
        
        // Divide la chiave in parti (es. "rows.0.quantity" -> ["rows", "0", "quantity"])
        $parts = explode('.', $key);
        
        // Verifica che ci siano almeno 2 parti (indice di riga e campo)
        if (count($parts) < 2) {
            return;
        }
        
        // Estrae l'indice della riga e il campo
        $index = (int)$parts[1];
        $field = $parts[2] ?? '';
        
        // Verifica che l'indice esista nell'array rows
        if (!isset($this->rows[$index])) {
            return;
        }
        
        // Gestione specifica per il campo vat_rate_id
        if ($field === 'vat_rate_id') {
            $vatInfo = collect($this->vatRatesList)->firstWhere('id', (int)$value);
            if ($vatInfo) {
                $this->rows[$index]['vat_rate'] = (float)$vatInfo['rate'];
                $this->rows[$index]['vat_sdi_nature'] = $vatInfo['sdi_nature'] ?? '';
                $this->rows[$index]['vat_description'] = $vatInfo['description'] ?? '';
            }
            $this->calculateTotals();
            return;
        }
        
        // Gestione dei campi numerici
        if (in_array($field, ['quantity', 'unit_price', 'discount_percentage'])) {
            if (is_string($value)) {
                $value = str_replace(',', '.', $value);
            }
            $this->rows[$index][$field] = floatval($value);
            $this->calculateTotals();
            return;
        }
        
        // Gestione degli altri campi (description, code, ecc.)
        $this->rows[$index][$field] = $value;
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
                // Cerca le informazioni dell'IVA
                $vatInfo = collect($this->vatRatesList)->first(function($v) use ($vatRate, $sdiNature) {
                    return (float)$v['rate'] === $vatRate && ($v['sdi_nature'] ?? '') === ($sdiNature ?? '');
                });
                
                if (!$vatInfo) {
                    $vatInfo = collect($this->vatRatesList)->firstWhere('rate', $vatRate);
                }

                // Costruisci la descrizione corretta
                $description = $vatInfo['description'] ?? '';
                $ratePercent = $vatRate * 100;
                
                // Se non c'è descrizione, creane una
                if (empty($description)) {
                    if ($vatRate == 0) {
                        $description = !empty($sdiNature) ? 'Esente/Non imponibile' : 'Esente IVA';
                    } else {
                        $description = 'IVA ' . number_format($ratePercent, 0) . '%';
                    }
                }

                $vatGroup[$key] = [
                    'rate' => $vatRate,
                    'rate_percent' => $ratePercent,
                    'taxable_amount' => 0,
                    'vat_amount' => 0,
                    'description' => $description,
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
        
        if (count($this->payments) === 1) {
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
    
    public function updatedCostCenterSearch($value, $index)
    {
        if (strlen($value) < 2) {
            $this->costCenterResults[$index] = [];
            $this->showCostCenterDropdown[$index] = false;
            
            if ($value === '') {
                $this->rows[$index]['id_cost_center'] = null;
                $this->selectedCostCenterId[$index] = '';
                $this->selectedCostCenterName[$index] = '';
            }
            return;
        }
        
        if ($value !== ($this->selectedCostCenterName[$index] ?? '')) {
            $this->rows[$index]['id_cost_center'] = null;
            $this->selectedCostCenterId[$index] = '';
            $this->selectedCostCenterName[$index] = '';
        }
        
        $this->costCenterResults[$index] = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $value . '%')
            ->limit(10)
            ->get(['id', 'Nome as name'])
            ->toArray();
        
        $this->showCostCenterDropdown[$index] = count($this->costCenterResults[$index]) > 0;
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
    
    public function updatedServiceSearch($value, $index): void
    {
        $idx = (int)$index;

        if (strlen($value) < 2) {
            $this->serviceResults[$idx] = [];
            $this->showServiceDropdown[$idx] = false;
            
            if ($value === '') {
                $this->rows[$idx]['id_service'] = null;
                $this->selectedServiceId[$idx] = null;
                $this->selectedServiceName[$idx] = '';
            }
            return;
        }
        
        if ($value !== ($this->selectedServiceName[$idx] ?? '')) {
            $this->rows[$idx]['id_service'] = null;
            $this->selectedServiceId[$idx] = null;
            $this->selectedServiceName[$idx] = '';
        }
        
        if (isset($this->selectedServiceId[$idx]) && 
            $this->serviceSearch[$idx] === ($this->selectedServiceName[$idx] ?? '')) {
            $this->showServiceDropdown[$idx] = false;
            return;
        }

        $results = Service::where('Stato', 1)
            ->where('Titolo', 'like', '%' . $value . '%')
            ->limit(10)
            ->get(['id', 'Titolo', 'Descr_fattura', 'Prezzo_un']);

        $this->serviceResults[$idx] = $results->map(fn($s) => [
            'id' => $s->id,
            'name' => $s->Titolo,
            'descr_fattura' => $s->Descr_fattura ?? '',
            'prezzo_un' => $s->Prezzo_un,
        ])->toArray();

        $this->showServiceDropdown[$idx] = count($this->serviceResults[$idx]) > 0;
    }

    public function selectService(int $index, int $serviceId, string $serviceName, string $descrFattura, $prezzoUn): void
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

        if ($prezzoUn && (!isset($this->rows[$index]['unit_price']) || $this->rows[$index]['unit_price'] == 0)) {
            $this->rows[$index]['unit_price'] = $prezzoUn;
        }
        
        $service = Service::with('vatRate')->find($serviceId);
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

    public function closeDropdowns()
    {
        foreach ($this->showCostCenterDropdown as $index => $value) {
            $this->showCostCenterDropdown[$index] = false;
        }
        
        foreach ($this->showServiceDropdown as $index => $value) {
            $this->showServiceDropdown[$index] = false;
        }
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
    
    public function update()
    {
        foreach ($this->rows as $index => $row) {
            $this->rows[$index]['unit_price'] = floatval($row['unit_price'] ?? 0);
            $this->rows[$index]['quantity'] = floatval($row['quantity'] ?? 1);
            $this->rows[$index]['discount_percentage'] = floatval($row['discount_percentage'] ?? 0);
        }

        Log::info('=== INIZIO AGGIORNAMENTO FATTURA VENDITA ID: ' . $this->invoiceId . ' ===');
        
        try {
            $this->validate();
        } catch (\Exception $e) {
            Log::error('❌ Errore validazione: ' . $e->getMessage());
            session()->flash('error', 'Errore validazione: ' . $e->getMessage());
            return null;
        }
        
        try {
            DB::beginTransaction();
            
            $invoice = InvoiceSent::findOrFail($this->invoiceId);
            $invoice->update([
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->selectedCustomerId,
                'id_invoice_series' => $this->selectedSeriesId,
                'type_invoice' => $this->type_invoice,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'n_invoice_ext' => $this->n_invoice_ext,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            $existingRowIds = [];
            
            foreach ($this->rows as $row) {
                // PRENDI LE INFO IVA CORRETTE
                $vatRate = floatval($row['vat_rate'] ?? 0);
                $sdiNature = $row['vat_sdi_nature'] ?? null;
                $vatDescription = $row['vat_description'] ?? '';
                
                // Se c'è un vat_rate_id, usalo per ottenere le info corrette
                if (!empty($row['vat_rate_id'])) {
                    $vatInfo = collect($this->vatRatesList)->firstWhere('id', (int)$row['vat_rate_id']);
                    if ($vatInfo) {
                        $vatRate = (float)$vatInfo['rate'];
                        $sdiNature = $vatInfo['sdi_nature'] ?? null;
                        $vatDescription = $vatInfo['description'] ?? '';
                    }
                }
                
                Log::info('Salvataggio riga - vat_rate: ' . ($vatRate * 100) . ', sdi_nature: "' . ($sdiNature ?? '') . '", description: "' . $vatDescription . '"');
                
                if (isset($row['id']) && $row['id'] && !isset($row['_delete'])) {
                    InvoiceRow::where('id', $row['id'])->update([
                        'code' => $row['code'] ?? '',
                        'description' => $row['description'],
                        'quantity' => floatval($row['quantity'] ?? 1),
                        'unit_price' => floatval($row['unit_price'] ?? 0),
                        'id_unit_measure' => intval($row['id_unit_measure'] ?? 1),
                        'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                        'vat_rate' => $vatRate * 100,
                        'sdi_nature' => $sdiNature,
                        'total' => floatval($row['taxable_amount'] ?? 0),
                        'id_cost_center' => $row['id_cost_center'] ?? null,
                        'id_service' => $row['id_service'] ?? null,
                    ]);
                    $existingRowIds[] = $row['id'];
                } 
                elseif (!isset($row['_delete'])) {
                    $newRow = InvoiceRow::create([
                        'document_id' => $this->invoiceId,
                        'document_type' => 'invoice_sent',
                        'code' => $row['code'] ?? '',
                        'description' => $row['description'],
                        'quantity' => floatval($row['quantity'] ?? 1),
                        'unit_price' => floatval($row['unit_price'] ?? 0),
                        'id_unit_measure' => intval($row['id_unit_measure'] ?? 1),
                        'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                        'vat_rate' => $vatRate * 100,
                        'sdi_nature' => $sdiNature,
                        'total' => floatval($row['taxable_amount'] ?? 0),
                        'id_cost_center' => $row['id_cost_center'] ?? null,
                        'id_service' => $row['id_service'] ?? null,
                    ]);
                    $existingRowIds[] = $newRow->id;
                }
            }
            
            InvoiceRow::where('document_id', $this->invoiceId)
                ->where('document_type', 'invoice_sent')
                ->whereNotIn('id', $existingRowIds)
                ->delete();
            
            DB::table('invoice_vat_summaries')
                ->where('vatable_id', $this->invoiceId)
                ->where('vatable_type', InvoiceSent::class)
                ->delete();
            
            foreach ($this->vatSummary as $vat) {
                DB::table('invoice_vat_summaries')->insert([
                    'vatable_id' => $this->invoiceId,
                    'vatable_type' => InvoiceSent::class,
                    'tax_rate' => $vat['rate'] * 100,
                    'taxable_amount' => $vat['taxable_amount'],
                    'tax_amount' => $vat['vat_amount'],
                    'esigibilita_iva' => 'I',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            $existingPaymentIds = [];
            
            foreach ($this->payments as $payment) {
                if (isset($payment['id']) && $payment['id']) {
                    DB::table('invoice_payments')
                        ->where('id', $payment['id'])
                        ->update([
                            'due_date' => $payment['due_date'],
                            'amount' => $payment['amount'],
                            'payment_method' => $payment['payment_method'] ?? 'MP05',
                            'iban' => $payment['iban'] ?? $this->companyIban,
                            'updated_at' => now(),
                        ]);
                    $existingPaymentIds[] = $payment['id'];
                } else {
                    $newPaymentId = DB::table('invoice_payments')->insertGetId([
                        'payable_id' => $this->invoiceId,
                        'payable_type' => InvoiceSent::class,
                        'due_date' => $payment['due_date'],
                        'amount' => $payment['amount'],
                        'paid_amount' => 0,
                        'residual_amount' => $payment['amount'],
                        'payment_method' => $payment['payment_method'] ?? 'MP05',
                        'iban' => $payment['iban'] ?? $this->companyIban,
                        'status' => 'issued',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $existingPaymentIds[] = $newPaymentId;
                }
            }
            
            DB::table('invoice_payments')
                ->where('payable_id', $this->invoiceId)
                ->where('payable_type', InvoiceSent::class)
                ->whereNotIn('id', $existingPaymentIds)
                ->delete();
            
            DB::commit();
            
            session()->flash('success', 'Fattura ' . $this->n_invoice . ' aggiornata con successo!');
            return redirect()->to(route('admin.invoices-sent.index'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ ERRORE AGGIORNAMENTO: ' . $e->getMessage());
            session()->flash('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
            return null;
        }
    }
    
    public function cancel()
    {
        return redirect()->to(route('admin.invoices-sent.index'));
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
        return view('livewire.admin.invoice-sent-edit', [
            'ownerships' => $this->ownerships,
            'typeDocuments' => $this->typeDocuments,
            'vatRatesList' => $this->vatRatesList,
            'paymentMethods' => $this->paymentMethods,
            'unitMeasureList' => $this->unitMeasureList,
        ]);
    }
}