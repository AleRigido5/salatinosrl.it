<?php
// app/Livewire/Admin/InvoiceSalesTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoiceSales;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceSalesTable extends Component
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
    
    // Autocomplete Cliente
    public string $customerSearch = '';
    public Collection $customerResults;
    public string $selectedCustomerId = '';
    public string $selectedCustomerName = '';
    public bool $showCustomerDropdown = false;
    
    // Autocomplete Centro di Costo
    public string $costCenterSearch = '';
    public Collection $costCenterResults;
    public string $selectedCostCenterId = '';
    public string $selectedCostCenterName = '';
    public bool $showCostCenterDropdown = false;
    
    // Date
    public string $dateFrom = '';
    public string $dateTo = '';
    
    // Ordinamento
    public string $sortField = 'data_invoice';
    public string $sortDirection = 'desc';
    public int $perPage = 100;
    
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
        $this->customerResults = new Collection();
        $this->costCenterResults = new Collection();
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
        if ($this->selectedOwnershipId && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

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

        $this->ownershipResults = Ownership::where('valid', 1)
            ->where(function($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%')
                  ->orWhere('RagSocialePr', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name']);
        
        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership(int $id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
        $this->resetPage();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->resetPage();
        $this->dispatch('clearOwnershipInput');
    }

    // ==================== AUTOCOMPLETE CLIENTE ====================
    public function updatedCustomerSearch(): void
    {
        if ($this->selectedCustomerId && $this->customerSearch === $this->selectedCustomerName) {
            $this->showCustomerDropdown = false;
            return;
        }

        if ($this->selectedCustomerId) {
            $this->selectedCustomerId = '';
            $this->selectedCustomerName = '';
            $this->resetPage();
        }

        if (strlen($this->customerSearch) < 2) {
            $this->customerResults = new Collection();
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
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);
        
        $this->showCustomerDropdown = $this->customerResults->isNotEmpty();
    }

    public function selectCustomer(int $id, string $name): void
    {
        $this->selectedCustomerId = (string)$id;
        $this->selectedCustomerName = $name;
        $this->customerSearch = $name;
        $this->showCustomerDropdown = false;
        $this->resetPage();
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = '';
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
        $this->resetPage();
        $this->dispatch('clearCustomerInput');
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch(): void
    {
        if ($this->selectedCostCenterId && $this->costCenterSearch === $this->selectedCostCenterName) {
            $this->showCostCenterDropdown = false;
            return;
        }

        if ($this->selectedCostCenterId) {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
            $this->resetPage();
        }

        if (strlen($this->costCenterSearch) < 2) {
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            return;
        }

        $this->costCenterResults = CostCenter::where('Nome', 'like', '%' . $this->costCenterSearch . '%')
            ->limit(10)
            ->get(['id', 'Nome', 'Localita']);
        
        $this->showCostCenterDropdown = $this->costCenterResults->isNotEmpty();
    }

    public function selectCostCenter(int $id, string $name): void
    {
        $this->selectedCostCenterId = (string)$id;
        $this->selectedCostCenterName = $name;
        $this->costCenterSearch = $name;
        $this->showCostCenterDropdown = false;
        $this->resetPage();
    }

    public function clearCostCenter(): void
    {
        $this->selectedCostCenterId = '';
        $this->selectedCostCenterName = '';
        $this->costCenterSearch = '';
        $this->resetPage();
        $this->dispatch('clearCostCenterInput');
    }

    // ==================== AGGIORNAMENTO STATO ====================
    public function updateInvoiceStatus(int $id, string $newStatus): void
    {
        try {
            $invoice = InvoiceSales::find($id);
            if (!$invoice) {
                $this->dispatch('showError', message: 'Fattura non trovata');
                return;
            }

            DB::beginTransaction();
            $invoice->status = $newStatus;
            $invoice->save();

            // Aggiorna lo stato dei pagamenti associati
            if ($newStatus === 'issued') {
                $invoice->payments()->update(['status' => 'issued']);
            }

            DB::commit();

            $statusLabel = $newStatus === 'issued' ? 'Emessa' : 'Pagata';
            $this->dispatch('showSuccess', message: "Stato fattura aggiornato a '{$statusLabel}'");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
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
        $this->clearCustomer();
        $this->clearCostCenter();
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDates');
    }

    public function clearStatus(): void
    {
        $this->status = '';
        $this->resetPage();
    }

    public function clearTypeInvoice(): void
    {
        $this->type_invoice = '';
        $this->resetPage();
    }

    public function getInvoicesProperty()
    {
        $query = InvoiceSales::query()
            ->with(['ownership', 'entity', 'rows.costCenter'])
            ->when($this->search, fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedCustomerId, fn($q) => $q->where('id_entities', $this->selectedCustomerId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('data_invoice', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('data_invoice', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        if ($this->perPage == 100000) {
            $results = $query->get();
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $perPage = $results->count();
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $results->forPage($page, $perPage),
                $results->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }
        
        return $query->paginate($this->perPage);
    }

    // ==================== MODAL DETTAGLI ====================
    public function showDetails(int $id): void
    {
        $this->selectedInvoice = InvoiceSales::with([
            'ownership', 'entity', 'rows.costCenter', 'rows.vehicle',
            'payments', 'creator', 'updater', 'vatSummaries', 'invoiceSeries'
        ])->find($id);

        if ($this->selectedInvoice) {
            $this->selectedInvoice->attachments_list = $this->selectedInvoice->getAllAttachmentsAttribute();
        }

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
        $invoice = InvoiceSales::find($id);
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
            $this->updateTrashCount();
            $this->dispatch('showSuccess', message: "'{$name}' spostata nel cestino.");
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->invoiceToDelete = null;
    }

    // ==================== CESTINO ====================
    public function updateTrashCount(): void
    {
        $this->trashCount = InvoiceSales::onlyTrashed()->count();
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
        $query = InvoiceSales::onlyTrashed()->with(['ownership', 'entity']);
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
            $invoice = InvoiceSales::onlyTrashed()->find($id);
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
            $invoice = InvoiceSales::onlyTrashed()->find($id);
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
        return (array) config('gestionale.invoice_status', []);
    }

    public function getTypeDocumentsProperty(): array
    {
        return config('gestionale.tipo_documento', []);
    }

    public function render()
    {
        return view('livewire.admin.invoice-sales-table', [
            'invoices' => $this->invoices,
            'statuses' => $this->statuses,
            'typeDocuments' => $this->typeDocuments,
            'trashedInvoices' => $this->trashedInvoices,
            'trashCount' => $this->trashCount,
        ]);
    }
}