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
    
    // Modal dettagli
    public $selectedInvoice = null;
    public $showModal = false;

    // Modal eliminazione
    public $showDeleteModal = false;
    public $invoiceToDelete = null;
    public $invoiceNameToDelete = '';

    // Modal cestino
    public $showTrashModal = false;
    public $trashSearch = '';
    public $trashSortField = 'deleted_at';
    public $trashSortDirection = 'desc';

    // Contatore cestino
    public $trashCount = 0;

    protected $listeners = [
        'openTrashModal' => 'openTrashModal',
        'closeTrashModal' => 'closeTrashModal',
    ];

    public function mount()
    {
        $this->updateTrashCount();
    }

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
            ->with(['ownership', 'entity', 'rows'])
            ->when($this->search, fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
            ->when($this->id_ownership, fn($q) => $q->where('id_ownership', $this->id_ownership))
            ->when($this->id_entities, fn($q) => $q->where('id_entities', $this->id_entities))
            ->when($this->date_from, fn($q) => $q->whereDate('data_invoice', '>=', $this->date_from))
            ->when($this->date_to, fn($q) => $q->whereDate('data_invoice', '<=', $this->date_to))
            ->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    // ==================== MODAL DETTAGLI ====================

    public function showDetails($id)
    {
        $this->selectedInvoice = InvoiceReceived::with([
            'ownership',
            'entity',
            'rows.costCenter'
        ])->find($id);

        $this->showModal = true;
        $this->dispatch('modal-opened');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedInvoice = null;
        $this->dispatch('modal-closed');
    }

    // ==================== ELIMINAZIONE (SOFT DELETE) ====================

    public function confirmDelete($id)
    {
        $invoice = InvoiceReceived::find($id);
        if (!$invoice) {
            $this->dispatch('showError', message: 'Fattura non trovata');
            return;
        }

        $this->invoiceToDelete = $invoice;
        $this->invoiceNameToDelete = 'Fattura n. ' . $invoice->n_invoice . ' del ' . $invoice->data_invoice->format('d/m/Y');
        $this->showDeleteModal = true;
    }

    public function deleteInvoice()
    {
        try {
            if (!$this->invoiceToDelete) {
                $this->dispatch('showError', message: 'Nessuna fattura selezionata');
                return;
            }

            $name = $this->invoiceNameToDelete;
            // Soft delete: le righe NON vengono eliminate, la fattura va nel cestino
            $this->invoiceToDelete->delete();

            $this->showDeleteModal = false;
            $this->invoiceToDelete = null;
            $this->invoiceNameToDelete = '';

            $this->updateTrashCount();
            $this->dispatch('showSuccess', message: "'{$name}' spostata nel cestino.");

        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
            $this->showDeleteModal = false;
        }
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->invoiceToDelete = null;
        $this->invoiceNameToDelete = '';
    }

    // ==================== CESTINO ====================

    public function updateTrashCount()
    {
        $this->trashCount = InvoiceReceived::onlyTrashed()->count();
        $this->dispatch('trashCountUpdated', count: $this->trashCount);
    }

    public function openTrashModal()
    {
        $this->trashSearch = '';
        $this->trashSortField = 'deleted_at';
        $this->trashSortDirection = 'desc';
        $this->showTrashModal = true;
        $this->updateTrashCount();
    }

    public function closeTrashModal()
    {
        $this->showTrashModal = false;
        $this->trashSearch = '';
        $this->updateTrashCount();
    }

    public function trashSortBy($field)
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
        }
    }

    public function getTrashedInvoicesProperty()
    {
        $query = InvoiceReceived::onlyTrashed()
            ->with(['ownership', 'entity']);

        if ($this->trashSearch) {
            $searchTerm = '%' . $this->trashSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('n_invoice', 'like', $searchTerm)
                  ->orWhereHas('entity', fn($eq) => $eq->where('ragione_sociale', 'like', $searchTerm));
            });
        }

        return $query->orderBy($this->trashSortField, $this->trashSortDirection)->paginate(10);
    }

    public function restoreFromTrash($id)
    {
        try {
            $invoice = InvoiceReceived::onlyTrashed()->find($id);
            if ($invoice) {
                $name = 'Fattura n. ' . $invoice->n_invoice;
                $invoice->restore();
                $this->dispatch('showSuccess', message: "'{$name}' ripristinata con successo!");
                $this->updateTrashCount();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il ripristino: ' . $e->getMessage());
        }
    }

    public function forceDeleteFromTrash($id)
    {
        try {
            $invoice = InvoiceReceived::onlyTrashed()->find($id);
            if ($invoice) {
                $name = 'Fattura n. ' . $invoice->n_invoice;
                // Eliminazione definitiva: rimuove anche le righe
                $invoice->rows()->forceDelete();
                $invoice->forceDelete();
                $this->dispatch('showSuccess', message: "'{$name}' eliminata definitivamente.");
                $this->updateTrashCount();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
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
            ->select('id_proprieta as id', DB::raw("COALESCE(Rag_Soc_intest, RagSocialePr) as label"))
            ->get();

        $suppliers = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->select('id_cliente as id', 'ragione_sociale as label')
            ->get();

        return view('livewire.admin.invoices-received-table', compact(
            'invoices', 'totalImporto', 'statuses', 'typeInvoices', 'ownerships', 'suppliers'
        ))->with('trashedInvoices', $this->trashedInvoices); // ← solo qui, fuori da compact
    }
}