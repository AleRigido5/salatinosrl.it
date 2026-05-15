<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceReceivedCreate extends Component
{
    // Dati fattura
    public $id_ownership = '';
    public $type_invoice = '';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    
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
    
    // Centri di costo per righe
    public $costCenterSearch = [];
    public $costCenterResults = [];
    public $showCostCenterDropdown = [];
    public $selectedCostCenterId = [];
    public $selectedCostCenterName = [];
    
    protected $rules = [
        'id_ownership' => 'required|exists:ownership,id_proprieta',
        'type_invoice' => 'required|string|max:10',
        'n_invoice' => 'required|string|max:100',
        'data_invoice' => 'required|date',
        'importo_totale' => 'nullable|numeric|min:0',
        'causale' => 'nullable|string',
        'selectedSupplierId' => 'required|exists:entities,id_cliente',
        'rows.*.code' => 'nullable|string',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
        'rows.*.total_price' => 'required|numeric|min:0',
        'rows.*.unit_measure' => 'nullable|string',
        'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        'rows.*.vat_rate' => 'nullable|numeric|min:0',
        'rows.*.id_cost_center' => 'nullable|exists:cost_centers,id',
    ];
    
    public function mount()
    {
        $this->data_invoice = date('Y-m-d');
        $this->addRow();
    }
    
    public function addRow()
    {
        $index = count($this->rows);
        $this->rows[] = [
            'code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'total_price' => 0,
            'unit_measure' => '',
            'discount_percentage' => 0,
            'vat_rate' => 22,
            'id_cost_center' => null,
            'cost_center_name' => '',
        ];
        $this->costCenterSearch[$index] = '';
        $this->costCenterResults[$index] = [];
        $this->showCostCenterDropdown[$index] = false;
        $this->selectedCostCenterId[$index] = '';
        $this->selectedCostCenterName[$index] = '';
    }
    
    public function removeRow($index)
    {
        if (count($this->rows) > 1) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
            unset($this->costCenterSearch[$index]);
            unset($this->costCenterResults[$index]);
            unset($this->showCostCenterDropdown[$index]);
            unset($this->selectedCostCenterId[$index]);
            unset($this->selectedCostCenterName[$index]);
            $this->calculateTotal();
        } else {
            $this->dispatch('alert', type: 'warning', message: 'Devi avere almeno una riga');
        }
    }
    
    public function updatedRows()
    {
        $this->calculateTotal();
    }
    
    public function updatedRowsTotalPrice($value, $key)
    {
        // Quando il prezzo totale viene modificato, ricalcola il prezzo unitario
        $index = explode('.', $key)[0];
        $quantity = floatval($this->rows[$index]['quantity'] ?? 1);
        if ($quantity > 0) {
            $this->rows[$index]['unit_price'] = round($value / $quantity, 4);
        }
        $this->calculateTotal();
    }
    
    public function updatedRowsQuantity($value, $key)
    {
        // Quando la quantità viene modificata, ricalcola il prezzo totale
        $index = explode('.', $key)[0];
        $totalPrice = floatval($this->rows[$index]['total_price'] ?? 0);
        if ($totalPrice > 0) {
            $this->rows[$index]['unit_price'] = round($totalPrice / $value, 4);
        } else {
            $unitPrice = floatval($this->rows[$index]['unit_price'] ?? 0);
            $this->rows[$index]['total_price'] = round($value * $unitPrice, 2);
        }
        $this->calculateTotal();
    }
    
    public function updatedRowsUnitPrice($value, $key)
    {
        // Quando il prezzo unitario viene modificato, ricalcola il prezzo totale
        $index = explode('.', $key)[0];
        $quantity = floatval($this->rows[$index]['quantity'] ?? 1);
        $this->rows[$index]['total_price'] = round($quantity * $value, 2);
        $this->calculateTotal();
    }
    
    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->rows as $row) {
            $totalPrice = floatval($row['total_price'] ?? 0);
            $discountPercentage = floatval($row['discount_percentage'] ?? 0);
            $vatRate = floatval($row['vat_rate'] ?? 0);
            
            $discount = $totalPrice * ($discountPercentage / 100);
            $taxable = $totalPrice - $discount;
            $vat = $taxable * ($vatRate / 100);
            $total += $taxable + $vat;
        }
        $this->importo_totale = round($total, 2);
    }
    
    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch()
    {
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
        $this->selectedSupplierId = $id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;
        $this->showSupplierDropdown = false;
    }
    
    public function clearSupplier()
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
    }
    
    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch($value, $index)
    {
        // Se c'è già una selezione e il testo corrisponde al nome selezionato, non cercare
        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index] && 
            $value === $this->selectedCostCenterName[$index]) {
            $this->showCostCenterDropdown[$index] = false;
            return;
        }
        
        // Se l'utente modifica il testo dopo una selezione, pulisci la selezione
        if (isset($this->selectedCostCenterId[$index]) && $this->selectedCostCenterId[$index]) {
            $this->rows[$index]['id_cost_center'] = null;
            $this->rows[$index]['cost_center_name'] = '';
            $this->selectedCostCenterId[$index] = '';
            $this->selectedCostCenterName[$index] = '';
            $this->calculateTotal();
        }
        
        if (strlen($value) < 2) {
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
    }
    
    public function selectCostCenter($id, $name, $index)
    {
        $this->rows[$index]['id_cost_center'] = $id;
        $this->rows[$index]['cost_center_name'] = $name;
        $this->selectedCostCenterId[$index] = $id;
        $this->selectedCostCenterName[$index] = $name;
        $this->costCenterSearch[$index] = $name;
        $this->showCostCenterDropdown[$index] = false;
        $this->calculateTotal();
    }
    
    public function clearCostCenter($index)
    {
        $this->rows[$index]['id_cost_center'] = null;
        $this->rows[$index]['cost_center_name'] = '';
        $this->costCenterSearch[$index] = '';
        $this->calculateTotal();
    }
    
    // ==================== MODALE NUOVO FORNITORE ====================
    public function openSupplierModal()
    {
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
        $this->validate([
            'newSupplierName' => 'required|string|max:255',
            'newSupplierPiva' => 'nullable|string|max:20',
            'newSupplierCf' => 'nullable|string|max:20',
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
    
    public function save()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();

            $defaultStatus = 'issued'; 
            
            $invoice = InvoiceReceived::create([
                'id_ownership' => $this->id_ownership,
                'id_entities' => $this->selectedSupplierId,
                'type_invoice' => $this->type_invoice,
                'n_invoice' => $this->n_invoice,
                'data_invoice' => $this->data_invoice,
                'importo_totale' => $this->importo_totale,
                'causale' => $this->causale,
                'divisa' => 'EUR',
                'status' => $defaultStatus,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
                'imported_at' => now(),
            ]);
            
            foreach ($this->rows as $row) {
                $totalPrice = floatval($row['total_price'] ?? 0);
                $discountPercentage = floatval($row['discount_percentage'] ?? 0);
                $discount = $totalPrice * ($discountPercentage / 100);
                $total = $totalPrice - $discount;
                
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => $row['id_cost_center'] ?? null,
                    'description' => $row['description'],
                    'quantity' => floatval($row['quantity'] ?? 1),
                    'unit_price' => floatval($row['unit_price'] ?? 0),
                    'discount_percentage' => $discountPercentage,
                    'total' => $total,
                ]);
            }
            
            DB::commit();
            
            session()->flash('success', 'Fattura creata con successo!');
            return redirect()->route('admin.invoices-received.index');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore salvataggio fattura: ' . $e->getMessage());
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
        return view('livewire.admin.invoice-received-create', [
            'ownerships' => $this->ownerships,
            'typeDocuments' => $this->typeDocuments,
        ]);
    }
}