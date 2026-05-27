<?php
// app/Livewire/Admin/InvoiceSalesCreate.php (COMPLETATO)

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceSales;
use App\Models\InvoiceRow;
use App\Models\InvoiceSeries;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\Vehicles;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceSalesCreate extends Component
{
    // Dati fattura
    public $id_ownership = '';
    public $type_invoice = '';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    
    // Sezionale
    public $availableSeries = [];
    public $selectedSeriesId = '';
    public $seriesCode = '';
    public $seriesNumber = '';
    
    // Totali aggregati
    public $total_taxable = 0;
    public $total_vat = 0;
    public $total_discount = 0;
    public $vatSummary = [];
    
    // Cliente
    public $customerSearch = '';
    public $customerResults = [];
    public $selectedCustomerId = '';
    public $selectedCustomerName = '';
    public $showCustomerDropdown = false;
    
    // Nuovo cliente (modal)
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
    
    // Righe fattura
    public $rows = [];

    // Unità di Misura
    public $unitMeasureList = [];
    
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
        'selectedSeriesId' => 'required|exists:invoice_series,id',
        'data_invoice' => 'required|date',
        'importo_totale' => 'nullable|numeric|min:0',
        'causale' => 'nullable|string',
        'selectedCustomerId' => 'required|exists:entities,id_cliente',
        'rows.*.code' => 'nullable|string|max:50',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
        'rows.*.unit_measure' => 'nullable|string|max:10',
        'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        'rows.*.vat_rate' => 'nullable|numeric|min:0',
        'rows.*.id_cost_center' => 'nullable|exists:cost_centers,id',
        'rows.*.id_vehicle' => 'nullable|exists:vehicles,id',
        'payments.*.due_date' => 'nullable|date',
        'payments.*.amount' => 'nullable|numeric|min:0',
        'payments.*.payment_method' => 'nullable|string|max:10',
        'payments.*.iban' => 'nullable|string|max:50',
    ];
    
    public function mount()
    {
        $this->data_invoice = date('Y-m-d');
        $this->loadVatRates();
        $this->loadUnitMeasures();
        $this->loadPaymentMethods();
        $this->addRow();
        $this->calculateTotals();
        $this->addPayment();
        $this->calculatePaymentsTotal();
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
                    'rate' => $item->rate,
                    'description' => $item->description ?? 'IVA ' . $item->rate . '%'
                ];
            })->toArray();
    }
    
    public function loadUnitMeasures()
    {
        $this->unitMeasureList = DB::table('unit_measures')
            ->where('is_active', 1)
            ->orderBy('name')
            ->pluck('code', 'code')
            ->toArray();
            
        if (empty($this->unitMeasureList)) {
            $this->unitMeasureList = ['pz' => 'pezzi', 'ore' => 'ore', 'm' => 'metri', 'kg' => 'chilogrammi'];
        }
    }
    
    public function loadPaymentMethods()
    {
        $this->paymentMethods = PaymentMethod::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->toArray();
    }
    
    public function updatedIdOwnership()
    {
        $this->loadAvailableSeries();
        $this->selectedSeriesId = '';
        $this->seriesCode = '';
        $this->seriesNumber = '';
    }
    
    public function loadAvailableSeries()
    {
        if (!$this->id_ownership) {
            $this->availableSeries = [];
            return;
        }
        
        $this->availableSeries = InvoiceSeries::where('id_ownership', $this->id_ownership)
            ->where('year', date('Y'))
            ->get()
            ->toArray();
    }
    
    public function updatedSelectedSeriesId()
    {
        $series = collect($this->availableSeries)->firstWhere('id', $this->selectedSeriesId);
        if ($series) {
            $this->seriesCode = $series['code'];
            $this->seriesNumber = $series['last_number'] + 1;
            $this->n_invoice = $series['code'] . '/' . ($series['last_number'] + 1) . '/' . $series['year'];
        }
    }
    
    // ==================== AUTOCOMPLETE CLIENTE ====================
    public function updatedCustomerSearch()
    {
        if ($this->selectedCustomerId && $this->customerSearch === $this->selectedCustomerName) {
            $this->showCustomerDropdown = false;
            return;
        }
        
        if ($this->selectedCustomerId) {
            $this->selectedCustomerId = '';
            $this->selectedCustomerName = '';
        }
        
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
            ->get()
            ->toArray();
        
        $this->showCustomerDropdown = !empty($this->customerResults);
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
        $this->showCustomerDropdown = false;
    }
    
    // ==================== MODAL NUOVO CLIENTE ====================
    public function openCustomerModal()
    {
        $this->resetNewCustomerForm();
        $this->showCustomerModal = true;
    }
    
    public function resetNewCustomerForm()
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
            DB::beginTransaction();
            
            $customer = Entity::create([
                'ragione_sociale' => $this->newCustomerName,
                'partita_iva' => $this->newCustomerPiva,
                'codice_fiscale' => $this->newCustomerCf,
                'email' => $this->newCustomerEmail,
                'telefono' => $this->newCustomerPhone,
                'indirizzo' => $this->newCustomerAddress,
                'cap' => $this->newCustomerCap,
                'citta' => $this->newCustomerCity,
                'provincia' => $this->newCustomerProvince,
                'entity_type' => 'cliente',
                'valid' => 1,
            ]);
            
            DB::commit();
            
            $this->selectCustomer($customer->id_cliente, $customer->ragione_sociale);
            $this->showCustomerModal = false;
            $this->dispatch('showSuccess', message: 'Cliente creato con successo!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    // ==================== GESTIONE RIGHE ====================
    public function addRow()
    {
        $this->rows[] = [
            'id' => uniqid(),
            'code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'unit_measure' => 'pz',
            'discount_percentage' => 0,
            'vat_rate' => 22,
            'id_cost_center' => '',
            'id_vehicle' => '',
            'taxable_amount' => 0,
            'vat_amount' => 0,
            'total' => 0,
        ];
        
        // Inizializza array per questa riga
        $index = count($this->rows) - 1;
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
    }
    
    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        $this->calculateTotals();
    }
    
    public function updatedRows($value, $key)
    {
        $this->calculateRowTotals($this->getRowIndexFromKey($key));
        $this->calculateTotals();
    }
    
    private function getRowIndexFromKey($key)
    {
        preg_match('/^(\d+)\./', $key, $matches);
        return $matches[1] ?? null;
    }
    
    private function calculateRowTotals($index)
    {
        if ($index === null || !isset($this->rows[$index])) return;
        
        $row = &$this->rows[$index];
        $quantity = floatval($row['quantity'] ?? 0);
        $unitPrice = floatval($row['unit_price'] ?? 0);
        $discount = floatval($row['discount_percentage'] ?? 0);
        $vatRate = floatval($row['vat_rate'] ?? 0);
        
        $subtotal = $quantity * $unitPrice;
        $discountAmount = $subtotal * ($discount / 100);
        $taxable = $subtotal - $discountAmount;
        $vatAmount = $taxable * ($vatRate / 100);
        
        $row['taxable_amount'] = round($taxable, 2);
        $row['vat_amount'] = round($vatAmount, 2);
        $row['total'] = round($taxable + $vatAmount, 2);
    }
    
    public function calculateTotals()
    {
        $this->total_taxable = 0;
        $this->total_vat = 0;
        $this->total_discount = 0;
        $this->importo_totale = 0;
        
        $vatSummary = [];
        
        foreach ($this->rows as $row) {
            $this->total_taxable += $row['taxable_amount'];
            $this->total_vat += $row['vat_amount'];
            $this->importo_totale += $row['total'];
            
            $vatRate = $row['vat_rate'];
            if (!isset($vatSummary[$vatRate])) {
                $vatSummary[$vatRate] = [
                    'rate' => $vatRate,
                    'taxable' => 0,
                    'vat' => 0,
                ];
            }
            $vatSummary[$vatRate]['taxable'] += $row['taxable_amount'];
            $vatSummary[$vatRate]['vat'] += $row['vat_amount'];
        }
        
        $this->vatSummary = array_values($vatSummary);
        $this->importo_totale = round($this->importo_totale, 2);
    }
    
    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch($value, $index)
    {
        if ($this->selectedCostCenterId[$index] && $value === $this->selectedCostCenterName[$index]) {
            $this->showCostCenterDropdown[$index] = false;
            return;
        }
        
        if ($this->selectedCostCenterId[$index]) {
            $this->selectedCostCenterId[$index] = '';
            $this->selectedCostCenterName[$index] = '';
            $this->rows[$index]['id_cost_center'] = '';
        }
        
        if (strlen($value) < 2) {
            $this->costCenterResults[$index] = [];
            $this->showCostCenterDropdown[$index] = false;
            return;
        }
        
        $this->costCenterResults[$index] = CostCenter::where('Nome', 'like', '%' . $value . '%')
            ->limit(10)
            ->get()
            ->toArray();
        
        $this->showCostCenterDropdown[$index] = !empty($this->costCenterResults[$index]);
    }
    
    public function selectCostCenter($index, $id, $name)
    {
        $this->selectedCostCenterId[$index] = $id;
        $this->selectedCostCenterName[$index] = $name;
        $this->costCenterSearch[$index] = $name;
        $this->rows[$index]['id_cost_center'] = $id;
        $this->showCostCenterDropdown[$index] = false;
    }
    
    public function clearCostCenter($index)
    {
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';
        $this->costCenterSearch[$index] = '';
        $this->rows[$index]['id_cost_center'] = '';
        $this->showCostCenterDropdown[$index] = false;
    }
    
    // ==================== AUTOCOMPLETE MEZZO ====================
    public function updatedVehicleSearch($value, $index)
    {
        if ($this->selectedVehicleId[$index] && $value === $this->selectedVehicleName[$index]) {
            $this->showVehicleDropdown[$index] = false;
            return;
        }
        
        if ($this->selectedVehicleId[$index]) {
            $this->selectedVehicleId[$index] = '';
            $this->selectedVehicleName[$index] = '';
            $this->rows[$index]['id_vehicle'] = '';
        }
        
        if (strlen($value) < 2) {
            $this->vehicleResults[$index] = [];
            $this->showVehicleDropdown[$index] = false;
            return;
        }
        
        $this->vehicleResults[$index] = Vehicles::where('targa', 'like', '%' . $value . '%')
            ->orWhere('marca', 'like', '%' . $value . '%')
            ->orWhere('modello', 'like', '%' . $value . '%')
            ->limit(10)
            ->get()
            ->toArray();
        
        $this->showVehicleDropdown[$index] = !empty($this->vehicleResults[$index]);
    }
    
    public function selectVehicle($index, $id, $name)
    {
        $this->selectedVehicleId[$index] = $id;
        $this->selectedVehicleName[$index] = $name;
        $this->vehicleSearch[$index] = $name;
        $this->rows[$index]['id_vehicle'] = $id;
        $this->showVehicleDropdown[$index] = false;
    }
    
    public function clearVehicle($index)
    {
        $this->selectedVehicleId[$index] = '';
        $this->selectedVehicleName[$index] = '';
        $this->vehicleSearch[$index] = '';
        $this->rows[$index]['id_vehicle'] = '';
        $this->showVehicleDropdown[$index] = false;
    }
    
    // ==================== GESTIONE PAGAMENTI ====================
    public function addPayment()
    {
        $this->payments[] = [
            'id' => uniqid(),
            'due_date' => null,
            'amount' => 0,
            'payment_method' => '',
            'iban' => '',
        ];
        $this->calculatePaymentsTotal();
    }
    
    public function removePayment($index)
    {
        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);
        $this->calculatePaymentsTotal();
    }
    
    public function calculatePaymentsTotal()
    {
        $this->total_payments_amount = array_sum(array_column($this->payments, 'amount'));
    }
    
    public function updatedPayments()
    {
        $this->calculatePaymentsTotal();
    }
    
    // ==================== SALVATAGGIO ====================
    public function save()
    {
        $this->validate();
        
        // Verifica che i pagamenti coprano l'importo totale
        if (round($this->total_payments_amount, 2) != round($this->importo_totale, 2)) {
            $this->dispatch('showError', message: 'La somma delle scadenze (' . number_format($this->total_payments_amount, 2) . '€) non corrisponde al totale fattura (' . number_format($this->importo_totale, 2) . '€)');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Aggiorna il sezionale
            $series = InvoiceSeries::find($this->selectedSeriesId);
            if (!$series) {
                throw new \Exception('Sezionale non trovato');
            }
            $series->last_number += 1;
            $series->save();
            
            // Crea la fattura
            $invoice = InvoiceSales::create([
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->selectedCustomerId,
                'id_invoice_series' => $this->selectedSeriesId,
                'type_invoice' => $this->type_invoice,
                'n_invoice' => $this->n_invoice,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'divisa' => 'EUR',
                'status' => 'issued',
                'is_manual' => true,
                'created_by' => Auth::guard('admin')->id(),
            ]);
            
            // Crea le righe
            foreach ($this->rows as $row) {
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_sales',
                    'code' => $row['code'] ?? null,
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'unit_measure' => $row['unit_measure'] ?? 'pz',
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'vat_rate' => $row['vat_rate'] ?? 0,
                    'taxable_amount' => $row['taxable_amount'],
                    'vat_amount' => $row['vat_amount'],
                    'total' => $row['total'],
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'id_vehicle' => $row['id_vehicle'] ?? null,
                ]);
            }
            
            // Crea i pagamenti
            foreach ($this->payments as $payment) {
                if ($payment['amount'] > 0) {
                    $invoice->payments()->create([
                        'due_date' => $payment['due_date'],
                        'amount' => $payment['amount'],
                        'paid_amount' => 0,
                        'payment_method' => $payment['payment_method'] ?? null,
                        'iban' => $payment['iban'] ?? null,
                        'status' => 'pending',
                    ]);
                }
            }
            
            // Crea i riepiloghi IVA
            foreach ($this->vatSummary as $vat) {
                $invoice->vatSummaries()->create([
                    'vat_rate' => $vat['rate'],
                    'taxable_amount' => $vat['taxable'],
                    'vat_amount' => $vat['vat'],
                ]);
            }
            
            DB::commit();
            
            $this->dispatch('showSuccess', message: "Fattura {$this->n_invoice} creata con successo!");
            
            // Redirect alla lista
            return redirect()->route('admin.invoice-sales.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione fattura: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.admin.invoice-sales-create', [
            'ownerships' => Ownership::where('valid', 1)->orderBy('Rag_Soc_intest')->get(),
            'documentTypes' => config('gestionale.tipo_documento', []),
            'vatRates' => $this->vatRatesList,
            'unitMeasures' => $this->unitMeasureList,
            'paymentMethodList' => $this->paymentMethods,
        ]);
    }
}