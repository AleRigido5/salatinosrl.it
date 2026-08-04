<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Communication;
use App\Models\Entity;
use Illuminate\Support\Collection;

class CommunicationsTable extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    // Filtri
    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    // Autocomplete Entità (Cliente/Fornitore)
    public string $entitySearch = '';
    public Collection $entityResults;
    public string $selectedEntityId = '';
    public string $selectedEntityName = '';
    public bool $showEntityDropdown = false;

    public int $perPage = 100;
    public string $sortField = 'data';
    public string $sortDirection = 'desc';

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->entityResults = new Collection();

        // Di default: tutte le comunicazioni del mese corrente
        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
            $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE ENTITÀ (Cliente/Fornitore) ====================

    public function updatedEntitySearch(): void
    {
        if (!empty($this->selectedEntityId) && $this->entitySearch === $this->selectedEntityName) {
            $this->showEntityDropdown = false;
            return;
        }

        if (empty($this->entitySearch)) {
            $this->selectedEntityId = '';
            $this->selectedEntityName = '';
            $this->entityResults = new Collection();
            $this->showEntityDropdown = false;
            $this->resetPage();
            return;
        }

        if (!empty($this->selectedEntityId) && $this->entitySearch !== $this->selectedEntityName) {
            $this->selectedEntityId = '';
            $this->selectedEntityName = '';
            $this->resetPage();
        }

        if (strlen($this->entitySearch) < 2) {
            $this->entityResults = new Collection();
            $this->showEntityDropdown = false;
            return;
        }

        $this->entityResults = Entity::where(function ($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->entitySearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->entitySearch . '%');
            })
            ->orderBy('ragione_sociale')
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale', 'nome', 'cognome', 'partita_iva']);

        $this->showEntityDropdown = $this->entityResults->isNotEmpty();
    }

    public function selectEntity($id, string $name): void
    {
        $this->selectedEntityId = (string) $id;
        $this->selectedEntityName = $name;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
        $this->resetPage();
    }

    public function clearEntity(): void
    {
        $this->selectedEntityId = '';
        $this->selectedEntityName = '';
        $this->entitySearch = '';
        $this->entityResults = new Collection();
        $this->showEntityDropdown = false;
        $this->resetPage();
        $this->dispatch('clearEntityInput');
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearDates(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
        $this->dispatch('resetDates');
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->clearEntity();
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
        $this->dispatch('resetDates');
    }

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

    public function getCommunicationsProperty()
    {
        $query = Communication::query()
            ->with(['entity', 'createdBy']);

        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('testo', 'like', $searchTerm)
                  ->orWhere('contatto', 'like', $searchTerm)
                  ->orWhere('mittente', 'like', $searchTerm)
                  ->orWhereHas('entity', function ($eq) use ($searchTerm) {
                      $eq->where('ragione_sociale', 'like', $searchTerm)
                         ->orWhere('nome', 'like', $searchTerm)
                         ->orWhere('cognome', 'like', $searchTerm);
                  });
            });
        }

        if ($this->selectedEntityId) {
            $query->where('id_entities', $this->selectedEntityId);
        }

        if ($this->dateFrom) {
            $query->whereDate('data', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('data', '<=', $this->dateTo);
        }

        $query->orderBy($this->sortField, $this->sortDirection)
              ->orderBy('created_at', 'desc');

        return $query->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.admin.communications-table', [
            'communications' => $this->communications,
        ]);
    }
}