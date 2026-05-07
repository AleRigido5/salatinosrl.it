<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Support\Facades\DB;

class InvoicesReceivedTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $type_invoice = '';
    public $id_ownership = '';
    public $id_entities = '';
    public $date_from = '';
    public $date_to = '';
    public $sortField = 'data_invoice';
    public $sortDirection = 'desc';
    public $perPage = 15;

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->type_invoice = '';
        $this->id_ownership = '';
        $this->id_entities = '';
        $this->date_from = '';
        $this->date_to = '';
        $this->resetPage();
    }

    public function getInvoicesProperty()
    {
        $query = InvoiceReceived::query()
            ->with(['ownership', 'entity'])
            ->when($this->search, function ($q) {
                $q->where('n_invoice', 'like', '%' . $this->search . '%');
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
            ->when($this->id_ownership, fn($q) => $q->where('id_ownership', $this->id_ownership))
            ->when($this->id_entities, fn($q) => $q->where('id_entities', $this->id_entities))
            ->when($this->date_from, fn($q) => $q->whereDate('data_invoice', '>=', $this->date_from))
            ->when($this->date_to, fn($q) => $q->whereDate('data_invoice', '<=', $this->date_to))
            ->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    public function deleteInvoice($id)
    {
        $invoice = InvoiceReceived::find($id);
        if ($invoice) {
            $invoice->rows()->delete();
            $invoice->delete();
            session()->flash('success', 'Fattura eliminata con successo.');
        }
    }

    public function render()
    {
        $invoices = $this->invoices;
        $totalImporto = $invoices->sum('importo_totale');
        
        $statuses = [
            ['value' => 'bozza', 'label' => 'Bozza'],
            ['value' => 'inviata', 'label' => 'Inviata'],
            ['value' => 'consegnata', 'label' => 'Consegnata'],
            ['value' => 'scartata', 'label' => 'Scartata'],
        ];
        
        $typeInvoices = [
            ['value' => 'TD01', 'label' => 'Fattura'],
            ['value' => 'TD04', 'label' => 'Nota di Credito'],
        ];
        
        $ownerships = Ownership::where('valid', 1)
            ->select('id_proprieta as id', 'Rag_Soc_intest as label')
            ->get();
            
        $suppliers = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->select('id_cliente as id', 'ragione_sociale as label')
            ->get();

        return view('livewire.admin.invoices-received-table', compact(
            'invoices', 'totalImporto', 'statuses', 'typeInvoices', 'ownerships', 'suppliers'
        ));
    }
}