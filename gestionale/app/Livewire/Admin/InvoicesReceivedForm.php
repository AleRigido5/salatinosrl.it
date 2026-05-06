<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\VatRate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoicesReceivedForm extends Component
{
    public $mode = 'create'; // create o edit
    public $invoiceId = null;
    
    // Dati della fattura
    public $id_ownership = '';
    public $id_entities = '';
    public $type_invoice = 'TD01';
    public $n_invoice = '';
    public $data_invoice = '';
    public $importo_totale = 0;
    public $causale = '';
    public $divisa = 'EUR';
    public $status = 'draft';
    public $sdi_id = '';
    
    // Righe fattura
    public $rows = [];
    
    // Dati per i select
    public $ownerships = [];
    public $entities = [];
    public $costCenters = [];
    public $vatRates = [];
    
    protected $rules = [
        'id_ownership' => 'required|exists:ownership,id_proprieta',
        'id_entities' => 'required|exists:entities,id_cliente',
        'type_invoice' => 'required|string',
        'n_invoice' => 'required|string|max:100',
        'data_invoice' => 'required|date',
        'importo_totale' => 'required|numeric|min:0',
        'causale' => 'nullable|string',
        'divisa' => 'required|string|size:3',
        'status' => 'required|string',
        'sdi_id' => 'nullable|string|max:100',
        'rows' => 'required|array|min:1',
        'rows.*.id_cost_center' => 'required|exists:cost_centers,id',
        'rows.*.description' => 'required|string',
        'rows.*.quantity' => 'required|numeric|min:0',
        'rows.*.unit_price' => 'required|numeric|min:0',
        'rows.*.vat_rate_id' => 'required|exists:vat_rates,id',
        'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
    ];
    
    public function mount($mode = 'create', $invoiceId = null)
    {
        $this->mode = $mode;
        $this->invoiceId = $invoiceId;
        $this->data_invoice = Carbon::now()->format('Y-m-d');
        
        // Carica i dati per i select
        $this->loadSelectData();
        
        // Se è in modalità edit, carica i dati della fattura
        if ($mode === 'edit' && $invoiceId) {
            $this->loadInvoiceData();
        }
        
        // Inizializza almeno una riga vuota
        if (empty($this->rows)) {
            $this->addRow();
        }
    }
    
    public function loadSelectData()
    {
        $this->ownerships = Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();
        $this->entities = Entity::where('valid', 1)->orderBy('ragione_sociale')->orderBy('nome')->get();
        $this->costCenters = CostCenter::where('valid', 1)->orderBy('Nome')->get();
        $this->vatRates = VatRate::where('is_active', 1)->orderBy('rate')->get();
    }
    
    public function loadInvoiceData()
    {
        $invoice = InvoiceReceived::with('rows')->find($this->invoiceId);
        
        if ($invoice) {
            $this->id_ownership = $invoice->id_ownership;
            $this->id_entities = $invoice->id_entities;
            $this->type_invoice = $invoice->type_invoice;
            $this->n_invoice = $invoice->n_invoice;
            $this->data_invoice = $invoice->data_invoice instanceof Carbon ? $invoice->data_invoice->format('Y-m-d') : $invoice->data_invoice;
            $this->importo_totale = $invoice->importo_totale;
            $this->causale = $invoice->causale;
            $this->divisa = $invoice->divisa;
            $this->status = $invoice->status;
            $this->sdi_id = $invoice->sdi_id;
            
            // Carica le righe
            foreach ($invoice->rows as $row) {
                $this->rows[] = [
                    'id' => $row->id,
                    'id_cost_center' => $row->id_cost_center,
                    'description' => $row->description,
                    'quantity' => $row->quantity,
                    'unit_price' => $row->unit_price,
                    'vat_rate_id' => $row->vat_rate_id,
                    'discount_percentage' => $row->discount_percentage,
                ];
            }
        }
    }
    
    public function addRow()
    {
        $this->rows[] = [
            'id' => null,
            'id_cost_center' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0,
            'vat_rate_id' => '',
            'discount_percentage' => 0,
        ];
    }
    
    public function removeRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
        
        if (count($this->rows) == 0) {
            $this->addRow();
        }
    }
    
    public function calculateRowTotal($row)
    {
        $subtotal = $row['quantity'] * $row['unit_price'];
        $discount = $subtotal * ($row['discount_percentage'] / 100);
        return $subtotal - $discount;
    }
    
    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->rows as $row) {
            $total += $this->calculateRowTotal($row);
        }
        $this->importo_totale = $total;
    }
    
    public function updatedRows()
    {
        $this->calculateTotal();
    }
    
    public function save()
    {
        $this->validate();
        
        if ($this->importo_totale <= 0) {
            $this->dispatch('showError', message: 'Il totale della fattura deve essere maggiore di zero');
            return;
        }
        
        try {
            DB::beginTransaction();
            
            // Recupera i dati per i JSON
            $ownership = Ownership::find($this->id_ownership);
            $entity = Entity::find($this->id_entities);
            
            $dataOwnership = [
                'Denominazione' => $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? '',
                'indirizzo' => $ownership->IndirizzoProp ?? '',
                'CAP' => $ownership->cap ?? '',
                'Comune' => $ownership->ComuneProp ?? '',
                'Provincia' => $ownership->ProvProp ?? '',
                'Nazione' => 'IT',
                'CodiceDestinatario' => $ownership->CodiceDestinatario ?? '',
                'PECDestinatario' => $ownership->PECDestinatario ?? '',
            ];
            
            $dataEntities = [
                'Denominazione' => $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome),
                'indirizzo' => $entity->indirizzo ?? '',
                'CAP' => $entity->cap ?? '',
                'Comune' => $entity->citta ?? '',
                'Provincia' => $entity->provincia ?? '',
                'Nazione' => 'IT',
                'CodiceFiscale' => $entity->codice_fiscale ?? $entity->partita_iva ?? '',
                'RegimeFiscale' => 'RF01',
                'Telefono' => $entity->telefono ?? '',
                'Email' => $entity->email ?? '',
            ];
            
            if ($this->mode === 'create') {
                $invoice = InvoiceReceived::create([
                    'id_ownership' => $this->id_ownership,
                    'id_entities' => $this->id_entities,
                    'data_ownership' => json_encode($dataOwnership),
                    'data_entities' => json_encode($dataEntities),
                    'type_invoice' => $this->type_invoice,
                    'n_invoice' => $this->n_invoice,
                    'data_invoice' => $this->data_invoice,
                    'importo_totale' => $this->importo_totale,
                    'causale' => $this->causale,
                    'divisa' => $this->divisa,
                    'status' => $this->status,
                    'sdi_id' => $this->sdi_id,
                ]);
            } else {
                $invoice = InvoiceReceived::find($this->invoiceId);
                $invoice->update([
                    'id_ownership' => $this->id_ownership,
                    'id_entities' => $this->id_entities,
                    'data_ownership' => json_encode($dataOwnership),
                    'data_entities' => json_encode($dataEntities),
                    'type_invoice' => $this->type_invoice,
                    'n_invoice' => $this->n_invoice,
                    'data_invoice' => $this->data_invoice,
                    'importo_totale' => $this->importo_totale,
                    'causale' => $this->causale,
                    'divisa' => $this->divisa,
                    'status' => $this->status,
                    'sdi_id' => $this->sdi_id,
                ]);
                
                // Elimina le righe esistenti
                $invoice->rows()->delete();
            }
            
            // Crea le righe
            foreach ($this->rows as $row) {
                $total = ($row['quantity'] * $row['unit_price']) * (1 - ($row['discount_percentage'] ?? 0) / 100);
                
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => $row['id_cost_center'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'vat_rate_id' => $row['vat_rate_id'],
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'total' => $total,
                ]);
            }
            
            DB::commit();
            
            $message = $this->mode === 'create' ? 'Fattura creata con successo!' : 'Fattura aggiornata con successo!';
            
            return redirect()->route('admin.invoices-received.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function getStatusesProperty()
    {
        return config('gestionale.invoice_status', []);
    }
    
    public function getTypesProperty()
    {
        return config('gestionale.tipo_documento', [
            'TD01' => 'Fattura',
            'TD04' => 'Nota di credito',
            'TD05' => 'Nota di debito',
            'TD20' => 'Autofattura',
        ]);
    }
    
    public function getCurrenciesProperty()
    {
        return config('gestionale.currencies', ['EUR' => 'Euro']);
    }
    
    public function render()
    {
        return view('livewire.admin.invoices-received-form', [
            'statuses' => $this->statuses,
            'types' => $this->types,
            'currencies' => $this->currencies,
        ]);
    }
}