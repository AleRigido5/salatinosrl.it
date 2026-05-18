<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoicesReceivedTable extends Component
{
    use WithPagination;

    // Filtri ricerca
    public string $search = '';
    public string $status = '';
    public string $type_invoice = '';
    
    // Autocomplete Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;
    
    // Autocomplete Fornitore
    public string $supplierSearch = '';
    public Collection $supplierResults;
    public string $selectedSupplierId = '';
    public string $selectedSupplierName = '';
    public bool $showSupplierDropdown = false;
    
    // Date
    public string $dateFrom = '';
    public string $dateTo = '';
    
    // Ordinamento
    public string $sortField = 'data_invoice';
    public string $sortDirection = 'desc';
    public int $perPage = 15;
    
    // Modal
    public $selectedInvoice = null;
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public $invoiceToDelete = null;
    public string $invoiceNameToDelete = '';
    public bool $showTrashModal = false;
    public string $trashSearch = '';
    public string $trashSortField = 'deleted_at';
    public string $trashSortDirection = 'desc';
    public int $trashCount = 0;

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->supplierResults = new Collection();
        $this->updateTrashCount();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        // Se c'è già una selezione e il testo corrisponde al nome selezionato, non cercare
        if ($this->selectedOwnershipId && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        // Se l'utente modifica il testo dopo una selezione, pulisci la selezione
        if ($this->selectedOwnershipId) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->resetPage();
        }

        if (strlen($this->ownershipSearch) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        $this->ownershipResults = Ownership::where(function($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%')
                ->orWhere('RagSocialePr', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name', 'Rag_Soc_intest as ragione_sociale', 'valid']);
        
        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership(int $id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;  // Questo compila l'input
        $this->showOwnershipDropdown = false;
        $this->resetPage();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';  // Questo svuota l'input
        $this->resetPage();
        $this->dispatch('clearOwnershipInput');
    }

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch(): void
    {
        // Se c'è già una selezione e il testo corrisponde al nome selezionato, non cercare
        if ($this->selectedSupplierId && $this->supplierSearch === $this->selectedSupplierName) {
            $this->showSupplierDropdown = false;
            return;
        }

        // Se l'utente modifica il testo dopo una selezione, pulisci la selezione
        if ($this->selectedSupplierId) {
            $this->selectedSupplierId = '';
            $this->selectedSupplierName = '';
            $this->resetPage();
        }

        if (strlen($this->supplierSearch) < 2) {
            $this->supplierResults = new Collection();
            $this->showSupplierDropdown = false;
            return;
        }

        $this->supplierResults = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->supplierSearch . '%')
                ->orWhere('nome', 'like', '%' . $this->supplierSearch . '%')
                ->orWhere('cognome', 'like', '%' . $this->supplierSearch . '%')
                ->orWhere('partita_iva', 'like', '%' . $this->supplierSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);
        
        $this->showSupplierDropdown = $this->supplierResults->isNotEmpty();
    }

    public function selectSupplier(int $id, string $name): void
    {
        $this->selectedSupplierId = (string)$id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;  // Questo compila l'input
        $this->showSupplierDropdown = false;
        $this->resetPage();
    }

    public function clearSupplier(): void
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';  // Questo svuota l'input
        $this->resetPage();
        $this->dispatch('clearSupplierInput');
    }

    // ==================== FILTRI E QUERY ====================
    
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->status = '';
        $this->type_invoice = '';
        $this->clearOwnership();
        $this->clearSupplier();
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDates');
        $this->dispatch('resetDateRangeFilterWithoutApply');  
        $this->dispatch('resetAllFilters');
    }

    // Metodo per pulire lo stato
    public function clearStatus(): void
    {
        $this->status = '';
        $this->resetPage();
        $this->dispatch('clearStatusFilter');
    }

    // Metodo per pulire il tipo documento
    public function clearTypeInvoice(): void
    {
        $this->type_invoice = '';
        $this->resetPage();
        $this->dispatch('clearTypeInvoiceFilter');
    }

    // Metodo per pulire la ricerca
    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
        $this->dispatch('clearSearchFilter');
    }

    // Metodo per pulire la data
    public function clearDates(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        // Questo evento resetta anche il componente date-range-filter
        $this->dispatch('resetDateRangeFilterWithoutApply');
    }

    public function getInvoicesProperty()
    {
        $query = InvoiceReceived::query()
            ->with(['ownership', 'entity', 'rows.costCenter'])
            ->when($this->search, fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedSupplierId, fn($q) => $q->where('id_entities', $this->selectedSupplierId))
            ->when($this->dateFrom, fn($q) => $q->whereDate('data_invoice', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('data_invoice', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    // ==================== MODAL DETTAGLI ====================
    
    public function showDetails(int $id): void
    {
        $this->selectedInvoice = InvoiceReceived::with([
            'ownership', 
            'entity', 
            'rows.costCenter', 
            'rows.vehicle',
            'payments', 
            'creator', 
            'updater', 
            'vatSummaries'
        ])->find($id);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedInvoice = null;
    }

    // ==================== ELIMINAZIONE ====================
    
    public function confirmDelete(int $id): void
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

    public function deleteInvoice(): void
    {
        try {
            if (!$this->invoiceToDelete) {
                $this->dispatch('showError', message: 'Nessuna fattura selezionata');
                return;
            }
            $name = $this->invoiceNameToDelete;
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

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->invoiceToDelete = null;
        $this->invoiceNameToDelete = '';
    }

    // ==================== CESTINO ====================
    
    public function updateTrashCount(): void
    {
        $this->trashCount = InvoiceReceived::onlyTrashed()->count();
    }

    public function openTrashModal(): void
    {
        $this->trashSearch = '';
        $this->showTrashModal = true;
        $this->updateTrashCount();
    }

    public function closeTrashModal(): void
    {
        $this->showTrashModal = false;
        $this->trashSearch = '';
        $this->updateTrashCount();
    }

    public function trashSortBy(string $field): void
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
        $query = InvoiceReceived::onlyTrashed()->with(['ownership', 'entity']);
        if ($this->trashSearch) {
            $searchTerm = '%' . $this->trashSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('n_invoice', 'like', $searchTerm)
                  ->orWhereHas('entity', fn($eq) => $eq->where('ragione_sociale', 'like', $searchTerm));
            });
        }
        return $query->orderBy($this->trashSortField, $this->trashSortDirection)->paginate(10);
    }

    public function restoreFromTrash(int $id): void
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
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function forceDeleteFromTrash(int $id): void
    {
        try {
            $invoice = InvoiceReceived::onlyTrashed()->find($id);
            if ($invoice) {
                $name = 'Fattura n. ' . $invoice->n_invoice;
                $invoice->rows()->forceDelete();
                $invoice->forceDelete();
                $this->dispatch('showSuccess', message: "'{$name}' eliminata definitivamente.");
                $this->updateTrashCount();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function getStatusesProperty(): array
    {
        return [
            ['value' => 'bozza', 'label' => 'Bozza'],
            ['value' => 'inviata', 'label' => 'Inviata'],
            ['value' => 'consegnata', 'label' => 'Consegnata'],
            ['value' => 'scartata', 'label' => 'Scartata'],
        ];
    }

    public function getTypeDocumentsProperty(): array
    {
        return config('gestionale.tipo_documento', []);
    }

    public function render()
    {
        return view('livewire.admin.invoices-received-table', [
            'invoices' => $this->invoices,
            'statuses' => $this->statuses,
            'typeDocuments' => $this->typeDocuments,
            'trashedInvoices' => $this->trashedInvoices,
            'trashCount' => $this->trashCount,
        ]);
    }
}