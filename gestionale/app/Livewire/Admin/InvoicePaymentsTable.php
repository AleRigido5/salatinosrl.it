<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InvoicePaymentsTable extends Component
{
    use WithPagination;

    // Filtri ricerca
    public string $search = '';
    public string $status = '';
    
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
    public string $sortField = 'due_date';
    public string $sortDirection = 'asc';
    public int $perPage = 100;
    
    // Modal
    public $selectedPayment = null;
    public bool $showModal = false;

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->supplierResults = new Collection();
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

        $this->ownershipResults = Ownership::where(function($q) {
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

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch(): void
    {
        if ($this->selectedSupplierId && $this->supplierSearch === $this->selectedSupplierName) {
            $this->showSupplierDropdown = false;
            return;
        }

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
                ->orWhere('cognome', 'like', '%' . $this->supplierSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name']);
        
        $this->showSupplierDropdown = $this->supplierResults->isNotEmpty();
    }

    public function selectSupplier(int $id, string $name): void
    {
        $this->selectedSupplierId = (string)$id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;
        $this->showSupplierDropdown = false;
        $this->resetPage();
    }

    public function clearSupplier(): void
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
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
        $this->clearOwnership();
        $this->clearSupplier();
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

    public function getPaymentsProperty()
    {
        $query = InvoicePayment::query()
            ->with(['payable' => function($q) {
                $q->with(['ownership', 'entity']);
            }])
            ->when($this->search, function($q) {
                $q->whereHas('payable', function($sq) {
                    $sq->where('n_invoice', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->selectedOwnershipId, function($q) {
                $q->whereHas('payable', fn($sq) => $sq->where('id_ownership', $this->selectedOwnershipId));
            })
            ->when($this->selectedSupplierId, function($q) {
                $q->whereHas('payable', fn($sq) => $sq->where('id_entities', $this->selectedSupplierId));
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('due_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('due_date', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        return $query->paginate($this->perPage);
    }

    // ==================== MODAL DETTAGLI ====================
    
    public function showDetails(int $id): void
    {
        $this->selectedPayment = InvoicePayment::with(['payable.ownership', 'payable.entity'])->find($id);
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedPayment = null;
    }

    public function getStatusesProperty(): array
    {
        return [
            'pending' => ['label' => 'In attesa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            'paid' => ['label' => 'Pagato', 'badge_class' => 'bg-green-100 text-green-800'],
            'overdue' => ['label' => 'Scaduto', 'badge_class' => 'bg-red-100 text-red-800'],
            'cancelled' => ['label' => 'Annullato', 'badge_class' => 'bg-gray-100 text-gray-800'],
        ];
    }

    public function render()
    {
        return view('livewire.admin.invoice-payments-table', [
            'payments' => $this->payments,
            'statuses' => $this->statuses,
        ]);
    }
}