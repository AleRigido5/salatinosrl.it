<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\Vehicles;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceReceivedEdit extends Component
{
    public $invoiceId;
    public $invoiceData = [];
    public $id_ownership = '';
    public $type_invoice = '';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    public $is_manual = false;
    public $isReadonly = false;
    
    // Totali aggregati
    public $total_taxable = 0;
    public $total_vat = 0;
    public $total_discount = 0;
    public $vatSummary = [];
    
    // Fornitore
    public $supplierSearch = '';
    public $supplierResults = [];
    public $selectedSupplierId = '';
    public $selectedSupplierName = '';
    public $showSupplierDropdown = false;
    
    // Nuovo fornitore (modal)
    public $showSupplierModal = false;
    public $newSupplierName = '';
    public $newSupplierPiva = '';
    public $newSupplierCf = '';
    public $newSupplierEmail = '';
    public $newSupplierPhone = '';
    public $newSupplierAddress = '';
    public $newSupplierCap = '';
    public $newSupplierCity = '';
    public $newSupplierProvince = '';
    
    // Righe fattura
    public $rows = [];

    // Unità di Misura
    public $unitMeasureList = [];
    
    // Aggiungi queste proprietà dopo $all_vehicles = [];
    public $cost_center_all_search = '';
    public $cost_center_all_results = [];
    public $show_cost_center_all_dropdown = false;

    public $vehicle_all_search = '';
    public $vehicle_all_results = [];
    public $show_vehicle_all_dropdown = false;

    public array $all_costCenters = [];
    public array $all_vehicles_list = [];

    // Centri di costo per righe
    public $costCenterSearch = [];
    public $costCenterResults = [];
    public $showCostCenterDropdown = [];
    public $selectedCostCenterId = [];
    public $selectedCostCenterName = [];
    
    // Mezzi per righe
    public $vehicleSearch = [];
    public $vehicleResults = [];
    public $showVehicleDropdown = [];
    public $selectedVehicleId = [];
    public $selectedVehicleName = [];
    
    // Scadenze pagamento
    public $payments = [];
    public $total_payments_amount = 0;
    public $paymentMethods = []; 
    
    // Lista aliquote IVA
    public $vatRatesList = [];
    
    protected $rules = [
        'id_ownership' => 'required|exists:ownership,id_proprieta',
        'type_invoice' => 'required|string|max:10',
        'n_invoice' => 'required|string|max:100',
        'data_invoice' => 'required|date',
        'importo_totale' => 'nullable|numeric|min:0',
        'causale' => 'nullable|string',
        'selectedSupplierId' => 'required|exists:entities,id_cliente',
    ];
    
    public function mount($id)
    {
        $this->invoiceId = $id;
        $this->loadVatRates();
        $this->loadUnitMeasures();
        $this->loadPaymentMethods();
        // $this->loadAllData();
        $this->loadInvoice();
    }

    public bool $updatingFromSearch = false;

    public function hydrate()
    {
        // Non fare nulla durante l'idratazione per evitare loop
        // I dati necessari sono già stati caricati in mount()
    }
    
    // AGGIUNGI QUESTO METODO PER GESTIRE I DATI CORROTTI
    public function recoverFromCorruptData()
    {
        try {
            $this->loadAllData();
            $this->loadInvoice();
            $this->dispatch('alert', type: 'success', message: 'Componente ripristinato con successo');
        } catch (\Exception $e) {
            $this->dispatch('alert', type: 'error', message: 'Errore nel ripristino: ' . $e->getMessage());
        }
    }

    public function loadAllData()
    {
        $this->all_costCenters = CostCenter::where('valid', 1)
            ->select('id', 'Nome as name')
            ->orderBy('Nome')
            ->get()
            ->map(fn($item) => ['id' => $item->id, 'name' => $item->name])
            ->toArray();

        $this->all_vehicles_list = DB::table('vehicles')
            ->where('valid', 1)
            ->select('id', 'marca', 'modello', 'targa')
            ->get()
            ->map(fn($item) => [
                'id' => $item->id,
                'name' => trim($item->marca . ' ' . $item->modello . ' (' . $item->targa . ')'),
            ])
            ->toArray();
    }

    public function loadVatRates()
    {
        $this->vatRatesList = DB::table('vat_rates')
            ->where('is_active', 1)
            ->orderBy('rate', 'desc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'code' => trim($item->code),
                    'description' => $item->description,
                    'rate' => (float)$item->rate,           // decimale es. 0.22
                    'rate_percent' => (float)$item->rate * 100, // percentuale es. 22
                    'sdi_nature' => $item->sdi_nature,
                    'is_zero_rate' => ($item->rate == 0),
                ];
            })
            ->toArray();
    }

    public function loadUnitMeasures()
    {
        $this->unitMeasureList = DB::table('unita_misura')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id_um,
                    'nome' => $item->nome,
                    'codice' => $item->codice,
                    'categoria' => $item->categoria,
                ];
            })
            ->toArray();
    }

    public function loadPaymentMethods()
    {
        $this->paymentMethods = PaymentMethod::getSelectArray();
    }

    public function loadInvoice()
    {
        $invoice = InvoiceReceived::with([
            'entity', 
            'ownership', 
            'rows.costCenter', 
            'rows.vehicle', 
            'payments', 
            'vatSummaries'
        ])->findOrFail($this->invoiceId);
        
        $this->is_manual = $invoice->is_manual ?? false;
        $this->isReadonly = !$this->is_manual;
        
        // Campi base
        $this->id_ownership = $invoice->id_ownership;
        $this->type_invoice = $invoice->type_invoice;
        $this->n_invoice = $invoice->n_invoice;
        $this->data_invoice = $invoice->data_invoice->format('Y-m-d');
        $this->importo_totale = $invoice->importo_totale;
        $this->causale = $invoice->causale ?? '';
        
        // Carica il fornitore
        if ($invoice->entity) {
            $this->selectedSupplierId = $invoice->id_entities;
            $this->selectedSupplierName = $invoice->entity->ragione_sociale 
                ?: trim($invoice->entity->nome . ' ' . $invoice->entity->cognome);
            $this->supplierSearch = $this->selectedSupplierName;
        }
        
        // Pulisci gli array
        $this->rows = [];
        $this->costCenterSearch = [];
        $this->costCenterResults = [];
        $this->showCostCenterDropdown = [];
        $this->selectedCostCenterId = [];
        $this->selectedCostCenterName = [];
        $this->vehicleSearch = [];
        $this->vehicleResults = [];
        $this->showVehicleDropdown = [];
        $this->selectedVehicleId = [];
        $this->selectedVehicleName = [];

        // Prepara una mappa dell'IVA dai vatSummaries per le fatture importate
        $vatSummaryMap = [];
        foreach ($invoice->vatSummaries as $vs) {
            $key = (string)(float)$vs->tax_rate;
            if (!isset($vatSummaryMap[$key])) {
                $vatSummaryMap[$key] = [
                    'rate_percent' => (float)$vs->tax_rate,
                    'sdi_nature' => $vs->sdi_nature,
                    'taxable_amount' => $vs->taxable_amount,
                    'tax_amount' => $vs->tax_amount,
                ];
            }
        }
        
        // Carica le righe
        foreach ($invoice->rows as $index => $row) {
            $vatRateRaw = floatval($row->vat_rate ?? 0);

            // Converte da decimale a percentuale se necessario
            if ($vatRateRaw > 0 && $vatRateRaw <= 1) {
                $vatRatePercent = round($vatRateRaw * 100, 2);
            } elseif ($vatRateRaw > 1) {
                $vatRatePercent = $vatRateRaw;
            } else {
                $vatRatePercent = 0;
            }

            // Tentativo 1: da vat_rate_id
            $natureCode = null;
            $vatInfo = null;
            
            if ($row->vat_rate_id) {
                $vatInfo = collect($this->vatRatesList)->firstWhere('id', $row->vat_rate_id);
                if ($vatInfo) {
                    $vatRatePercent = $vatInfo['rate_percent'];
                    // if ($vatRatePercent == 0 && !empty($vatInfo['sdi_nature'])) {
                    //     $natureCode = $vatInfo['sdi_nature'];
                    // }
                    // ✅ Salva sempre la natura se presente, indipendentemente dall'aliquota
                    $natureCode = !empty($vatInfo['sdi_nature']) ? $vatInfo['sdi_nature'] : null;
                }
            }
            
            // Tentativo 2: cerca nei vatSummaries se abbiamo solo un'aliquota
            if ($vatRatePercent == 0 && !$natureCode) {
                $positiveSummaries = collect($invoice->vatSummaries)
                    ->where('tax_rate', '>', 0);
                if ($positiveSummaries->count() === 1) {
                    $vatRatePercent = (float) $positiveSummaries->first()->tax_rate;
                }
                
                // Cerca anche nature per IVA 0%
                $zeroSummaries = collect($invoice->vatSummaries)
                    ->where('tax_rate', '==', 0);
                if ($zeroSummaries->count() === 1 && !empty($zeroSummaries->first()->sdi_nature)) {
                    $natureCode = $zeroSummaries->first()->sdi_nature;
                    $vatRatePercent = 0;
                }
            }
            
            // Tentativo 3: cerca nella lista delle aliquote per trovare una corrispondenza
            if (!$vatInfo) {
                $vatInfo = collect($this->vatRatesList)->first(function($v) use ($vatRatePercent) {
                    return abs($v['rate_percent'] - $vatRatePercent) < 0.01;
                });
                if ($vatInfo && $vatRatePercent == 0 && !empty($vatInfo['sdi_nature'])) {
                    $natureCode = $vatInfo['sdi_nature'];
                }
            }
            
            // Calcola l'imponibile per la riga
            $grossAmount = $row->quantity * $row->unit_price;
            $discountAmount = $grossAmount * ($row->discount_percentage / 100);
            $taxable = $grossAmount - $discountAmount;
            
            $vehicleName = '';
            if ($row->vehicle) {
                $vehicleName = trim($row->vehicle->marca . ' ' . $row->vehicle->modello . ' - ' . $row->vehicle->targa);
            }

            $this->rows[] = [
                'id' => $row->id,
                'code' => $row->code ?? '',
                'description' => $row->description,
                'quantity' => $row->quantity,
                'unit_price' => $row->unit_price,
                'unit_measure' => $row->unit_measure ?? '',
                'discount_percentage' => $row->discount_percentage,
                'vat_rate_id' => $row->vat_rate_id, 
                'vat_rate' => $vatRatePercent,
                'nature_code' => $natureCode,  // Salva il codice natura per IVA 0%
                'id_cost_center' => $row->id_cost_center,
                'cost_center_name' => $row->costCenter ? $row->costCenter->Nome : '',
                'id_vehicle' => $row->id_vehicle,
                'vehicle_name' => $vehicleName,
                'taxable_amount' => $taxable,
                'vat_amount' => $taxable * ($vatRatePercent / 100),
            ];
            
            // Centro di costo
            $this->costCenterSearch[$index] = $row->costCenter ? $row->costCenter->Nome : '';
            $this->costCenterResults[$index] = [];
            $this->showCostCenterDropdown[$index] = false;
            $this->selectedCostCenterId[$index] = $row->id_cost_center ?? '';
            $this->selectedCostCenterName[$index] = $row->costCenter ? $row->costCenter->Nome : '';
            
            // Mezzo
            $this->vehicleSearch[$index] = $vehicleName;
            $this->vehicleResults[$index] = [];
            $this->showVehicleDropdown[$index] = false;
            $this->selectedVehicleId[$index] = $row->id_vehicle ?? '';
            $this->selectedVehicleName[$index] = $vehicleName;
        }
        
        // Se non ci sono righe, aggiungine una
        if (empty($this->rows)) {
            $this->addRow();
        }
        
        // Scadenze — includi SEMPRE status
        $this->payments = [];
        foreach ($invoice->payments as $payment) {
            $this->payments[] = [
                'id' => $payment->id,
                'due_date' => $payment->due_date ? $payment->due_date->format('Y-m-d') : '',
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method ?? '',
                'iban' => $payment->iban ?? '',
                'status' => $payment->status ?? 'issued',
            ];
        }
        
        if (empty($this->payments)) {
            $this->addPayment();
        }
        
        $this->calculateTotals();
        $this->calculatePaymentsTotal();

        // Per fatture importate, il totale ufficiale è quello del DB
        if (!$this->is_manual) {
            $this->importo_totale = (float) $invoice->importo_totale;
            if (count($this->payments) > 0) {
                $this->payments[0]['amount'] = $this->importo_totale;
                $this->calculatePaymentsTotal();
            }
        }
    }
        
    public function addRow()
    {
        $index = count($this->rows);
        $this->rows[] = [
            'id' => null,
            'code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'unit_measure' => '',
            'discount_percentage' => 0,
            'vat_rate_id' => null,
            'vat_rate' => 22,  // percentuale
            'nature_code' => null,  // Per IVA 0%
            'id_cost_center' => null,
            'cost_center_name' => '',
            'id_vehicle' => null,
            'vehicle_name' => '',
            'taxable_amount' => 0,
            'vat_amount' => 0,
        ];
        
        $this->costCenterSearch[$index] = '';
        $this->costCenterResults[$index] = [];
        $this->showCostCenterDropdown[$index] = false;
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';
        
        $this->vehicleSearch[$index] = '';
        $this->vehicleResults[$index] = [];
        $this->showVehicleDropdown[$index] = false;
        $this->selectedVehicleId[$index] = '';
        $this->selectedVehicleName[$index] = '';
        
        $this->calculateTotals();
    }
    
    public function addPayment()
    {
        $this->payments[] = [
            'id' => null,
            'due_date' => $this->data_invoice ?: date('Y-m-d'),
            'amount' => $this->importo_totale,
            'payment_method' => 'MP05',
            'iban' => '',
            'status' => 'issued',
        ];
        $this->calculatePaymentsTotal();
    }
    
    public function removeRow($index)
    {
        if ($this->isReadonly) {
            $this->dispatch('alert', type: 'warning', message: 'Non puoi rimuovere righe da una fattura importata da XML');
            return;
        }
        
        if (count($this->rows) > 1) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
            
            unset($this->costCenterSearch[$index]);
            unset($this->costCenterResults[$index]);
            unset($this->showCostCenterDropdown[$index]);
            unset($this->selectedCostCenterId[$index]);
            unset($this->selectedCostCenterName[$index]);
            
            unset($this->vehicleSearch[$index]);
            unset($this->vehicleResults[$index]);
            unset($this->showVehicleDropdown[$index]);
            unset($this->selectedVehicleId[$index]);
            unset($this->selectedVehicleName[$index]);
            
            $this->costCenterSearch = array_values($this->costCenterSearch);
            $this->costCenterResults = array_values($this->costCenterResults);
            $this->showCostCenterDropdown = array_values($this->showCostCenterDropdown);
            $this->selectedCostCenterId = array_values($this->selectedCostCenterId);
            $this->selectedCostCenterName = array_values($this->selectedCostCenterName);
            $this->vehicleSearch = array_values($this->vehicleSearch);
            $this->vehicleResults = array_values($this->vehicleResults);
            $this->showVehicleDropdown = array_values($this->showVehicleDropdown);
            $this->selectedVehicleId = array_values($this->selectedVehicleId);
            $this->selectedVehicleName = array_values($this->selectedVehicleName);
            
            $this->calculateTotals();
        } else {
            $this->dispatch('alert', type: 'warning', message: 'Devi avere almeno una riga');
        }
    }
    
    public function removePayment($index)
    {
        if ($this->isReadonly) {
            $this->dispatch('alert', type: 'warning', message: 'Non puoi rimuovere scadenze da una fattura importata da XML');
            return;
        }
        
        if (count($this->payments) > 1) {
            unset($this->payments[$index]);
            $this->payments = array_values($this->payments);
            $this->calculatePaymentsTotal();
        } else {
            $this->dispatch('alert', type: 'warning', message: 'Devi avere almeno una scadenza');
        }
    }
    
    public function updatedRows()
    {
        $this->calculateTotals();
    }
    
    public function updatedRowsQuantity($value, $key)
    {
        $this->calculateTotals();
    }
    
    public function updatedRowsUnitPrice($value, $key)
    {
        $this->calculateTotals();
    }
    
    public function updatedRowsDiscountPercentage($value, $key)
    {
        $this->calculateTotals();
    }
    
    public function updatedRowsVatRate($value, $key)
    {
        $this->calculateTotals();
    }
    
    public function updatedPayments()
    {
        $this->calculatePaymentsTotal();
    }
    
    public function updatedPaymentsAmount($value, $key)
    {
        $this->calculatePaymentsTotal();
    }
    
    public function calculatePaymentsTotal()
    {
        $total = 0;
        foreach ($this->payments as $payment) {
            $total += floatval($payment['amount'] ?? 0);
        }
        $this->total_payments_amount = $total;
    }
    
    /**
     * calculateTotals — vat_rate nelle righe è SEMPRE in percentuale (es. 22)
     */
    public function calculateTotals(): void
    {
        $totalTaxable  = 0;
        $totalVat      = 0;
        $totalDiscount = 0;
        $vatGroup      = [];

        foreach ($this->rows as $index => &$row) {
            $quantity           = floatval($row['quantity'] ?? 1);
            $unitPrice          = floatval($row['unit_price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);
            $vatRatePercent     = floatval($row['vat_rate'] ?? 0);
            $vatRateDecimal     = $vatRatePercent / 100;

            $grossAmount    = round($quantity * $unitPrice, 4);
            $discountAmount = round($grossAmount * ($discountPercentage / 100), 4);
            $taxable        = round($grossAmount - $discountAmount, 2);
            $vatAmount      = round($taxable * $vatRateDecimal, 2);

            $totalDiscount += $discountAmount;
            $totalTaxable  += $taxable;
            $totalVat      += $vatAmount;

            $row['taxable_amount'] = $taxable;
            $row['vat_amount']     = $vatAmount;

            // ✅ Leggi la natura DALLA RIGA, non dalla lista globale
            $natureCode = ($vatRatePercent == 0 && !empty($row['nature_code']))
                ? (string) $row['nature_code']
                : null;

            if (!empty($row['vat_rate_id'])) {
                $vatRecord = collect($this->vatRatesList)->firstWhere('id', (int)$row['vat_rate_id']);
                if ($vatRecord) {
                    $row['vat_rate']    = $vatRecord['rate_percent'];
                    $row['nature_code'] = ($vatRecord['rate_percent'] == 0 && !empty($vatRecord['sdi_nature']))
                        ? $vatRecord['sdi_nature']
                        : null;
                }
            }

            $vatRatePercent = floatval($row['vat_rate'] ?? 0);

            // Cerca vatInfo solo per la descrizione, non per determinare la natura
            $vatInfo = null;
            if ($vatRatePercent == 0 && $natureCode) {
                // Cerca il record che corrisponde ESATTAMENTE a questa natura
                $vatInfo = collect($this->vatRatesList)->first(function($v) use ($natureCode) {
                    return $v['rate_percent'] == 0 && $v['sdi_nature'] === $natureCode;
                });
            } elseif ($vatRatePercent > 0) {
                $vatInfo = collect($this->vatRatesList)->first(function($v) use ($vatRatePercent) {
                    return abs($v['rate_percent'] - $vatRatePercent) < 0.01;
                });
            }

            // Chiave univoca per gruppo: percentuale + natura
            $key = $vatRatePercent . '_' . ($natureCode ?? '');

            if (!isset($vatGroup[$key])) {
                $vatGroup[$key] = [
                    'rate'           => $vatRateDecimal,
                    'rate_percent'   => number_format($vatRatePercent, 2),
                    'description'    => $this->getVatDescription($vatRatePercent, $natureCode, $vatInfo),
                    'nature_code'    => $natureCode,
                    'taxable_amount' => 0,
                    'vat_amount'     => 0,
                ];
            }

            $vatGroup[$key]['taxable_amount'] += $taxable;
            $vatGroup[$key]['vat_amount']     += $vatAmount;
        }

        uksort($vatGroup, function($a, $b) {
            $aPercent = floatval(explode('_', $a)[0]);
            $bPercent = floatval(explode('_', $b)[0]);
            return $bPercent <=> $aPercent;
        });

        $this->vatSummary     = array_values($vatGroup);
        $this->total_taxable  = round($totalTaxable, 2);
        $this->total_vat      = round($totalVat, 2);
        $this->total_discount = round($totalDiscount, 2);
        $this->importo_totale = round($totalTaxable + $totalVat, 2);

        $this->syncFirstPaymentWithTotal();
    }

    /**
     * Ottiene la descrizione corretta per l'aliquota IVA
     */
    protected function getVatDescription($vatRatePercent, $natureCode, $vatInfo)
    {
        if ($vatRatePercent > 0) {
            return 'IVA ' . number_format($vatRatePercent, 2) . '%';
        }
        
        // IVA 0% con natura specifica
        if (!empty($natureCode)) {
            // Cerca la descrizione completa dal rate
            if ($vatInfo && !empty($vatInfo['description'])) {
                return $vatInfo['description'];
            }
            
            // Mappa dei codici natura più comuni
            $natureMap = [
                'N1' => 'Escluse ex art. 15',
                'N2.1' => 'Non soggette UE/extra-UE',
                'N2.2' => 'Non soggette',
                'N3.1' => 'Non imponibili esportazioni',
                'N3.2' => 'Non imponibili cessioni intra-UE',
                'N3.3' => 'Non imponibili servizi internazionali',
                'N3.4' => 'Non imponibili servizi finanziari',
                'N3.5' => 'Non imponibili operazioni triangolari',
                'N3.6' => 'Non imponibili altre',
                'N4' => 'Esenti',
                'N5' => 'Regime del margine',
                'N6.1' => 'Reverse charge - edilizia',
                'N6.2' => 'Reverse charge - settore energetico',
                'N6.3' => 'Reverse charge - altri settori',
                'N6.4' => 'Reverse charge - subappalti',
                'N6.5' => 'Reverse charge - rottami',
                'N6.6' => 'Reverse charge - legname',
                'N6.7' => 'Reverse charge - telefonia',
                'N6.8' => 'Reverse charge - altri',
            ];
            
            return $natureMap[$natureCode] ?? 'IVA 0% (' . $natureCode . ')';
        }
        
        return 'IVA 0%';
    }
    
    protected function syncFirstPaymentWithTotal()
    {
        if (count($this->payments) === 1) {
            $this->payments[0]['amount'] = $this->importo_totale;
        }
        $this->calculatePaymentsTotal();
    }
    
    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch()
    {
        if ($this->isReadonly) return;
        
        if (strlen($this->supplierSearch) < 2) {
            $this->supplierResults = [];
            $this->showSupplierDropdown = false;
            return;
        }
        
        $results = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->supplierSearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->supplierSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);
        
        $this->supplierResults = $results->toArray();
        $this->showSupplierDropdown = count($this->supplierResults) > 0;
    }
    
    public function selectSupplier($id, $name)
    {
        if ($this->isReadonly) return;
        $this->selectedSupplierId = $id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;
        $this->showSupplierDropdown = false;
    }
    
    public function clearSupplier()
    {
        if ($this->isReadonly) return;
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
    }
    
    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch($value, $index)
    {
        // Reset se l'utente ha cancellato il testo
        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index] && 
            $value !== $this->selectedCostCenterName[$index]) {
            $this->rows[$index]['id_cost_center'] = null;
            $this->rows[$index]['cost_center_name'] = '';
            $this->selectedCostCenterId[$index] = '';
            $this->selectedCostCenterName[$index] = '';
        }
        
        // Se il valore corrisponde esattamente al nome selezionato, non cercare
        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index] && 
            $value === $this->selectedCostCenterName[$index]) {
            $this->showCostCenterDropdown[$index] = false;
            return;
        }
        
        // Cerca solo se almeno 2 caratteri
        if (strlen(trim($value)) < 2) {
            $this->costCenterResults[$index] = [];
            $this->showCostCenterDropdown[$index] = false;
            return;
        }
        
        $results = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $value . '%')
            ->limit(10)
            ->get(['id', 'Nome as name']);
        
        $this->costCenterResults[$index] = $results->toArray();
        $this->showCostCenterDropdown[$index] = count($this->costCenterResults[$index]) > 0;
        
        // Forza l'aggiornamento della vista
        $this->dispatch('refresh-autocomplete');
    }
    
    public function selectCostCenter($id, $name, $index)
    {
        $this->rows[$index]['id_cost_center'] = $id;
        $this->rows[$index]['cost_center_name'] = $name;
        $this->selectedCostCenterId[$index] = $id;
        $this->selectedCostCenterName[$index] = $name;
        $this->costCenterSearch[$index] = $name;
        $this->showCostCenterDropdown[$index] = false;
    }
    
    public function clearCostCenter($index)
    {
        $this->rows[$index]['id_cost_center'] = null;
        $this->rows[$index]['cost_center_name'] = '';
        $this->costCenterSearch[$index] = '';
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';
        $this->showCostCenterDropdown[$index] = false;
    }

    public function updatedCostCenterAllSearch()
    {
        if ($this->updatingFromSearch) return;
        $this->updatingFromSearch = true;
        
        try {
            if (empty($this->cost_center_all_search) || strlen($this->cost_center_all_search) < 2) {
                $this->cost_center_all_results = [];
                $this->show_cost_center_all_dropdown = false;
                return;
            }
    
            $this->cost_center_all_results = CostCenter::where('valid', 1)
                ->where('Nome', 'like', '%' . $this->cost_center_all_search . '%')
                ->limit(10)
                ->get(['id', 'Nome as name'])
                ->toArray();
    
            $this->show_cost_center_all_dropdown = !empty($this->cost_center_all_results);
        } finally {
            $this->updatingFromSearch = false;
        }
    }

    public function applyCostCenterToAllRows($id)
    {
        $cc = collect($this->all_costCenters)->firstWhere('id', (int)$id);
        if ($cc) {
            foreach ($this->rows as $index => $row) {
                $this->rows[$index]['id_cost_center'] = (int)$id;
                $this->rows[$index]['cost_center_name'] = $cc['name'];
                $this->costCenterSearch[$index] = $cc['name'];
                $this->costCenterResults[$index] = [];
                $this->showCostCenterDropdown[$index] = false;
                $this->selectedCostCenterId[$index] = (int)$id;
                $this->selectedCostCenterName[$index] = $cc['name'];
            }
            $this->cost_center_all_search = $cc['name'];
            $this->cost_center_all_results = [];
            $this->show_cost_center_all_dropdown = false;
            $this->dispatch('alert', type: 'success', message: "Centro di costo '{$cc['name']}' applicato a tutte le " . count($this->rows) . " righe");
        }
    }

    public function updatedVehicleAllSearch()
    {
        if ($this->updatingFromSearch) return;
        $this->updatingFromSearch = true;
        
        try {
            if (empty($this->vehicle_all_search) || strlen($this->vehicle_all_search) < 2) {
                $this->vehicle_all_results = [];
                $this->show_vehicle_all_dropdown = false;
                return;
            }
    
            $this->vehicle_all_results = DB::table('vehicles')
                ->where('valid', 1)
                ->where(function($q) {
                    $q->where('targa', 'like', '%' . $this->vehicle_all_search . '%')
                    ->orWhere('marca', 'like', '%' . $this->vehicle_all_search . '%')
                    ->orWhere('modello', 'like', '%' . $this->vehicle_all_search . '%');
                })
                ->limit(10)
                ->get(['id', DB::raw("TRIM(CONCAT(marca, ' ', modello, ' (', targa, ')')) as name")])
                ->map(fn($v) => ['id' => $v->id, 'name' => $v->name])
                ->toArray();
    
            $this->show_vehicle_all_dropdown = !empty($this->vehicle_all_results);
        } finally {
            $this->updatingFromSearch = false;
        }
    }


    public function applyVehicleToAllRows($id)
    {
        $vehicle = collect($this->all_vehicles_list)->firstWhere('id', (int)$id);
        if ($vehicle) {
            foreach ($this->rows as $index => $row) {
                $this->rows[$index]['id_vehicle'] = (int)$id;
                $this->rows[$index]['vehicle_name'] = $vehicle['name'];
                $this->vehicleSearch[$index] = $vehicle['name'];
                $this->vehicleResults[$index] = [];
                $this->showVehicleDropdown[$index] = false;
                $this->selectedVehicleId[$index] = (int)$id;
                $this->selectedVehicleName[$index] = $vehicle['name'];
            }
            $this->vehicle_all_search = $vehicle['name'];
            $this->vehicle_all_results = [];
            $this->show_vehicle_all_dropdown = false;
            $this->dispatch('alert', type: 'success', message: "Mezzo '{$vehicle['name']}' applicato a tutte le " . count($this->rows) . " righe");
        }
    }
    
    // ==================== AUTOCOMPLETE MEZZI ====================
    public function updatedVehicleSearch($value, $index)
    {
        // Reset se l'utente ha cancellato il testo
        if (isset($this->selectedVehicleId[$index]) && $this->selectedVehicleId[$index] && 
            $value !== $this->selectedVehicleName[$index]) {
            $this->rows[$index]['id_vehicle'] = null;
            $this->rows[$index]['vehicle_name'] = '';
            $this->selectedVehicleId[$index] = '';
            $this->selectedVehicleName[$index] = '';
        }
        
        // Se il valore corrisponde esattamente al nome selezionato, non cercare
        if (isset($this->selectedVehicleId[$index]) && $this->selectedVehicleId[$index] && 
            $value === $this->selectedVehicleName[$index]) {
            $this->showVehicleDropdown[$index] = false;
            return;
        }
        
        // Cerca solo se almeno 2 caratteri
        if (strlen(trim($value)) < 2) {
            $this->vehicleResults[$index] = [];
            $this->showVehicleDropdown[$index] = false;
            return;
        }
        
        $results = Vehicles::where('valid', 1)
            ->where(function($q) use ($value) {
                $q->where('targa', 'like', '%' . $value . '%')
                ->orWhere('modello', 'like', '%' . $value . '%')
                ->orWhere('marca', 'like', '%' . $value . '%');
            })
            ->limit(10)
            ->get(['id', DB::raw("CONCAT(marca, ' ', modello, ' - ', targa) as name"), 'targa as plate']);
        
        $this->vehicleResults[$index] = $results->toArray();
        $this->showVehicleDropdown[$index] = count($this->vehicleResults[$index]) > 0;
        
        // Forza l'aggiornamento della vista
        $this->dispatch('refresh-autocomplete');
    }
    
    public function selectVehicle($id, $name, $index)
    {
        $this->rows[$index]['id_vehicle'] = $id;
        $this->rows[$index]['vehicle_name'] = $name;
        $this->selectedVehicleId[$index] = $id;
        $this->selectedVehicleName[$index] = $name;
        $this->vehicleSearch[$index] = $name;
        $this->showVehicleDropdown[$index] = false;
    }
    
    public function clearVehicle($index)
    {
        $this->rows[$index]['id_vehicle'] = null;
        $this->rows[$index]['vehicle_name'] = '';
        $this->vehicleSearch[$index] = '';
        $this->selectedVehicleId[$index] = '';
        $this->selectedVehicleName[$index] = '';
        $this->showVehicleDropdown[$index] = false;
    }
    
    // ==================== MODALE NUOVO FORNITORE ====================
    public function openSupplierModal()
    {
        if ($this->isReadonly) return;
        $this->showSupplierModal = true;
    }
    
    public function closeSupplierModal()
    {
        $this->showSupplierModal = false;
        $this->resetNewSupplierFields();
    }
    
    public function resetNewSupplierFields()
    {
        $this->newSupplierName = '';
        $this->newSupplierPiva = '';
        $this->newSupplierCf = '';
        $this->newSupplierEmail = '';
        $this->newSupplierPhone = '';
        $this->newSupplierAddress = '';
        $this->newSupplierCap = '';
        $this->newSupplierCity = '';
        $this->newSupplierProvince = '';
    }
    
    public function createSupplier()
    {
        if ($this->isReadonly) return;
        
        $this->validate([
            'newSupplierName' => 'required|string|max:255',
        ]);
        
        try {
            $entity = Entity::create([
                'entity_type' => 'fornitore',
                'ragione_sociale' => $this->newSupplierName,
                'nome' => $this->newSupplierName,
                'partita_iva' => $this->newSupplierPiva,
                'codice_fiscale' => $this->newSupplierCf,
                'email' => $this->newSupplierEmail,
                'telefono' => $this->newSupplierPhone,
                'indirizzo' => $this->newSupplierAddress,
                'cap' => $this->newSupplierCap,
                'comune' => $this->newSupplierCity,
                'provincia' => $this->newSupplierProvince,
                'valid' => 1,
                'created_by_system' => 0,
            ]);
            
            $this->selectedSupplierId = $entity->id_cliente;
            $this->selectedSupplierName = $this->newSupplierName;
            $this->supplierSearch = $this->newSupplierName;
            $this->showSupplierModal = false;
            $this->resetNewSupplierFields();
            
            $this->dispatch('alert', type: 'success', message: 'Fornitore creato con successo!');
            
        } catch (\Exception $e) {
            Log::error('Errore creazione fornitore: ' . $e->getMessage());
            $this->dispatch('alert', type: 'error', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function update()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();
            
            $invoice = InvoiceReceived::findOrFail($this->invoiceId);
            
            if (!$this->is_manual) {
                // Fattura importata: aggiorna solo causale, centri costo e mezzi
                $invoice->update([
                    'causale' => $this->causale,
                    'updated_by' => Auth::guard('admin')->id(),
                ]);
                
                foreach ($this->rows as $index => $row) {
                    if (isset($row['id']) && $row['id']) {
                        $updateData = [];
                        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index]) {
                            $updateData['id_cost_center'] = $this->selectedCostCenterId[$index];
                        } else {
                            $updateData['id_cost_center'] = $row['id_cost_center'] ?? null;
                        }
                        if (isset($this->selectedVehicleId[$index]) && $this->selectedVehicleId[$index]) {
                            $updateData['id_vehicle'] = $this->selectedVehicleId[$index];
                        } else {
                            $updateData['id_vehicle'] = $row['id_vehicle'] ?? null;
                        }
                        InvoiceRow::where('id', $row['id'])->update($updateData);
                    }
                }
            } else {
                // Fattura manuale: aggiorna tutti i campi
                $invoice->update([
                    'id_ownership' => $this->id_ownership,
                    'id_entities' => $this->selectedSupplierId,
                    'type_invoice' => $this->type_invoice,
                    'n_invoice' => $this->n_invoice,
                    'data_invoice' => $this->data_invoice,
                    'importo_totale' => $this->importo_totale,
                    'causale' => $this->causale,
                    'updated_by' => Auth::guard('admin')->id(),
                ]);
                
                $existingRowIds = [];
                
                foreach ($this->rows as $row) {
                    $vatRatePercent = floatval($row['vat_rate'] ?? 0);

                    // Cerca l'aliquota corrispondente
                    $vatRateRecord = collect($this->vatRatesList)->first(function($v) use ($vatRatePercent, $row) {
                        // Per IVA 0%, dobbiamo anche considerare se c'è una natura specifica
                        if ($vatRatePercent == 0 && isset($row['nature_code'])) {
                            return $v['rate_percent'] == 0 && $v['sdi_nature'] == $row['nature_code'];
                        }
                        return abs($v['rate_percent'] - $vatRatePercent) < 0.01;
                    });

                    $vatRateId      = !empty($row['vat_rate_id']) ? (int)$row['vat_rate_id'] : null;
                    $vatRatePercent = floatval($row['vat_rate'] ?? 0);
                    $vatRateDecimal = $vatRatePercent / 100;

                    $rowData = [
                        'document_id' => $invoice->id,
                        'document_type' => 'invoice_received',
                        'id_cost_center' => $row['id_cost_center'] ?? null,
                        'id_vehicle' => $row['id_vehicle'] ?? null,
                        'code' => $row['code'] ?? null,
                        'description' => $row['description'],
                        'quantity' => floatval($row['quantity'] ?? 1),
                        'unit_price' => floatval($row['unit_price'] ?? 0),
                        'unit_measure' => $row['unit_measure'] ?? null,
                        'discount_percentage' => floatval($row['discount_percentage'] ?? 0),
                        'vat_rate' => $vatRateDecimal,
                        'vat_rate_id' => $vatRateId,  // Aggiungi questo campo
                        'total' => floatval($row['taxable_amount'] ?? 0),
                    ];
                    
                    if (isset($row['id']) && $row['id']) {
                        $invoiceRow = InvoiceRow::find($row['id']);
                        if ($invoiceRow) {
                            $invoiceRow->update($rowData);
                            $existingRowIds[] = $row['id'];
                        }
                    } else {
                        $newRow = InvoiceRow::create($rowData);
                        $existingRowIds[] = $newRow->id;
                    }
                }
                
                InvoiceRow::where('document_id', $invoice->id)
                    ->where('document_type', 'invoice_received')
                    ->whereNotIn('id', $existingRowIds)
                    ->delete();
                
                // Aggiorna scadenze
                $existingPaymentIds = [];
                
                foreach ($this->payments as $payment) {
                    $paymentData = [
                        'due_date' => $payment['due_date'],
                        'amount' => floatval($payment['amount']),
                        'payment_method' => $payment['payment_method'] ?? null,
                        'iban' => $payment['iban'] ?? null,
                        'status' => $payment['status'] ?? 'issued',
                    ];
                    
                    if (isset($payment['id']) && $payment['id']) {
                        $invoicePayment = $invoice->payments()->find($payment['id']);
                        if ($invoicePayment) {
                            $invoicePayment->update($paymentData);
                            $existingPaymentIds[] = $payment['id'];
                        }
                    } else {
                        $newPayment = $invoice->payments()->create($paymentData);
                        $existingPaymentIds[] = $newPayment->id;
                    }
                }
                
                $invoice->payments()->whereNotIn('id', $existingPaymentIds)->delete();

                // Aggiorna i riepiloghi IVA
                $invoice->vatSummaries()->delete();
                foreach ($this->vatSummary as $vat) {
                    $invoice->vatSummaries()->create([
                        'tax_rate' => $vat['rate'] * 100, // salva in percentuale
                        'sdi_nature' => $vat['nature_code'] ?? null,
                        'taxable_amount' => $vat['taxable_amount'],
                        'tax_amount' => $vat['vat_amount'],
                        'esigibilita_iva' => 'I',
                    ]);
                }
            }
            
            DB::commit();
            
            session()->flash('success', 'Fattura aggiornata con successo!');
            return redirect()->route('admin.invoices-received.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore aggiornamento fattura: ' . $e->getMessage());
            $this->dispatch('alert', type: 'error', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function getOwnershipsProperty()
    {
        return Ownership::where('valid', 1)
            ->orderBy('RagAbbrev')
            ->get();
    }
    
    public function getTypeDocumentsProperty()
    {
        return config('gestionale.tipo_documento', []);
    }
    
    public function render()
    {
        return view('livewire.admin.invoice-received-edit', [
            'ownerships' => $this->ownerships,
            'typeDocuments' => $this->typeDocuments,
            'vatRatesList' => $this->vatRatesList,
            'paymentMethods' => $this->paymentMethods,
            'unitMeasureList' => $this->unitMeasureList,
        ]);
    }
}