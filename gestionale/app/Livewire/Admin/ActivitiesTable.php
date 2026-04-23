<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Activity;
use App\Models\CostCenter;
use App\Models\Service;
use App\Models\Entity;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ActivitiesTable extends Component
{
    use WithPagination;

    // Filtri
    public $search = '';
    public $costCenterFilter = '';
    public $serviceFilter = '';
    public $entityFilter = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 15;
    public $sortField = 'data_activities';
    public $sortDirection = 'desc';
    
    // Autocomplete properties
    public $costCenterSearch = '';
    public $serviceSearch = '';
    public $entitySearch = '';
    public $showCostCenterDropdown = false;
    public $showServiceDropdown = false;
    public $showEntityDropdown = false;
    
    // Calendar management
    public $selectedMonth = '';
    public $selectedYear = '';
    public $useDateFilter = true;
    public $monthDisplay = 'Tutte le attività';
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingActivity = null;
    
    // Modal modifica
    public $showEditModal = false;
    public $editingActivity = null;
    public $editingId = null;
    
    // Form fields per modifica
    public $editCostCenter = '';
    public $editService = '';
    public $editEntity = '';
    public $editDate = '';
    public $editNote = '';
    public $editInvoiceRef = '';
    public $editImponibile = '';
    public $editCostiMat = '';
    
    // Modal creazione
    public $showCreateModal = false;
    
    // Form fields per creazione
    public $createCostCenter = '';
    public $createService = '';
    public $createEntity = '';
    public $createDate = '';
    public $createNote = '';
    public $createInvoiceRef = '';
    public $createImponibile = '';
    public $createCostiMat = '';
    
    protected $paginationTheme = 'tailwind';
    
    // Query string parameters
    protected $queryString = [
        'search' => ['except' => ''],
        'costCenterFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'entityFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'selectedMonth' => ['except' => ''],
        'selectedYear' => ['except' => ''],
        'useDateFilter' => ['except' => true],
        'sortField' => ['except' => 'data_activities'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];
    
    protected $listeners = [
        'openCreateModal' => 'openCreateModal',
        'refreshActivities' => '$refresh'
    ];
    
    public function mount()
    {
        // Set current month and year
        $now = Carbon::now();
        $this->selectedMonth = $now->format('m');
        $this->selectedYear = $now->format('Y');
        
        // Set current month range
        $this->setCurrentMonthRange();
        $this->updateMonthDisplay();
        
        // Load filters from session if exist
        if (session()->has('activities_filters')) {
            $filters = session('activities_filters');
            $this->search = $filters['search'] ?? '';
            $this->costCenterFilter = $filters['costCenterFilter'] ?? '';
            $this->serviceFilter = $filters['serviceFilter'] ?? '';
            $this->entityFilter = $filters['entityFilter'] ?? '';
            $this->dateFrom = $filters['dateFrom'] ?? $this->dateFrom;
            $this->dateTo = $filters['dateTo'] ?? $this->dateTo;
            $this->selectedMonth = $filters['selectedMonth'] ?? $this->selectedMonth;
            $this->selectedYear = $filters['selectedYear'] ?? $this->selectedYear;
            $this->useDateFilter = $filters['useDateFilter'] ?? true;
            $this->sortField = $filters['sortField'] ?? 'data_activities';
            $this->sortDirection = $filters['sortDirection'] ?? 'desc';
            $this->perPage = $filters['perPage'] ?? 15;
            
            // Set autocomplete search values from selected filters
            if ($this->costCenterFilter) {
                $cc = CostCenter::find($this->costCenterFilter);
                if ($cc) $this->costCenterSearch = $cc->Nome;
            }
            if ($this->serviceFilter) {
                $service = Service::find($this->serviceFilter);
                if ($service) $this->serviceSearch = $service->Titolo;
            }
            if ($this->entityFilter) {
                $entity = Entity::find($this->entityFilter);
                if ($entity) {
                    $this->entitySearch = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                }
            }
            
            $this->updateMonthDisplay();
            session()->forget('activities_filters');
        }
    }
    
    // ==================== AUTOCOMPLETE METHODS ====================
    
    public function updatedCostCenterSearch()
    {
        $this->showCostCenterDropdown = !empty($this->costCenterSearch);
        if (empty($this->costCenterSearch)) {
            $this->costCenterFilter = '';
        }
        $this->resetPage();
    }

    public function updatedServiceSearch()
    {
        $this->showServiceDropdown = !empty($this->serviceSearch);
        if (empty($this->serviceSearch)) {
            $this->serviceFilter = '';
        }
        $this->resetPage();
    }

    public function updatedEntitySearch()
    {
        $this->showEntityDropdown = !empty($this->entitySearch);
        if (empty($this->entitySearch)) {
            $this->entityFilter = '';
        }
        $this->resetPage();
    }

    public function selectCostCenter($id, $name)
    {
        $this->costCenterFilter = $id;
        $this->costCenterSearch = $name;
        $this->showCostCenterDropdown = false;
        $this->resetPage();
    }

    public function selectService($id, $name)
    {
        $this->serviceFilter = $id;
        $this->serviceSearch = $name;
        $this->showServiceDropdown = false;
        $this->resetPage();
    }

    public function selectEntity($id, $name)
    {
        $this->entityFilter = $id;
        $this->entitySearch = $name;
        $this->showEntityDropdown = false;
        $this->resetPage();
    }

    public function clearCostCenter()
    {
        $this->costCenterFilter = '';
        $this->costCenterSearch = '';
        $this->showCostCenterDropdown = false;
        $this->resetPage();
    }

    public function clearService()
    {
        $this->serviceFilter = '';
        $this->serviceSearch = '';
        $this->showServiceDropdown = false;
        $this->resetPage();
    }

    public function clearEntity()
    {
        $this->entityFilter = '';
        $this->entitySearch = '';
        $this->showEntityDropdown = false;
        $this->resetPage();
    }

    public function getFilteredCostCentersProperty()
    {
        if (empty($this->costCenterSearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->costCenterSearch . '%';
        return CostCenter::where('valid', 1)
            ->where('Nome', 'like', $searchTerm)
            ->orderBy('Nome', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredServicesProperty()
    {
        if (empty($this->serviceSearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->serviceSearch . '%';
        return Service::where('Stato', 1)
            ->where('Titolo', 'like', $searchTerm)
            ->orderBy('Titolo', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredEntitiesProperty()
    {
        if (empty($this->entitySearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->entitySearch . '%';
        return Entity::where('valid', 1)
            ->where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm);
            })
            ->orderBy('ragione_sociale')
            ->orderBy('nome')
            ->limit(10)
            ->get();
    }
    
    // ==================== CALENDAR METHODS ====================
    
    public function updateMonthDisplay()
    {
        if ($this->useDateFilter && $this->dateFrom && $this->dateTo) {
            $from = Carbon::parse($this->dateFrom);
            $to = Carbon::parse($this->dateTo);
            
            if ($from->format('Y-m') === $to->format('Y-m')) {
                $this->monthDisplay = $from->translatedFormat('F Y');
            } else {
                $this->monthDisplay = $from->translatedFormat('d/m/Y') . ' - ' . $to->translatedFormat('d/m/Y');
            }
        } else {
            $this->monthDisplay = 'Tutte le attività';
        }
    }
    
    public function setCurrentMonthRange()
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1);
        $this->dateFrom = $date->copy()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $date->copy()->endOfMonth()->format('Y-m-d');
        $this->useDateFilter = true;
        $this->updateMonthDisplay();
        $this->resetPage();
    }
    
    public function updateMonthYear()
    {
        if ($this->selectedMonth && $this->selectedYear) {
            $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1);
            $this->dateFrom = $date->copy()->startOfMonth()->format('Y-m-d');
            $this->dateTo = $date->copy()->endOfMonth()->format('Y-m-d');
            $this->useDateFilter = true;
            $this->updateMonthDisplay();
            $this->resetPage();
        }
    }
    
    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1);
        $date = $date->subMonth();
        $this->selectedMonth = $date->format('m');
        $this->selectedYear = $date->format('Y');
        $this->updateMonthYear();
    }
    
    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1);
        $date = $date->addMonth();
        $this->selectedMonth = $date->format('m');
        $this->selectedYear = $date->format('Y');
        $this->updateMonthYear();
    }
    
    public function showAllActivities()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->useDateFilter = false;
        $this->updateMonthDisplay();
        $this->resetPage();
    }
    
    public function applyCustomDateRange()
    {
        $this->validate([
            'dateFrom' => 'nullable|date',
            'dateTo' => 'nullable|date|after_or_equal:dateFrom',
        ]);
        
        if ($this->dateFrom && $this->dateTo) {
            $from = Carbon::parse($this->dateFrom);
            $to = Carbon::parse($this->dateTo);
            
            if ($from->format('Y-m') === $to->format('Y-m') && 
                $from->day == 1 && 
                $to->day == $to->daysInMonth) {
                $this->selectedMonth = $from->format('m');
                $this->selectedYear = $from->format('Y');
            }
        }
        
        $this->useDateFilter = true;
        $this->updateMonthDisplay();
        $this->resetPage();
    }
    
    public function saveFiltersToSession()
    {
        session(['activities_filters' => [
            'search' => $this->search,
            'costCenterFilter' => $this->costCenterFilter,
            'serviceFilter' => $this->serviceFilter,
            'entityFilter' => $this->entityFilter,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'selectedMonth' => $this->selectedMonth,
            'selectedYear' => $this->selectedYear,
            'useDateFilter' => $this->useDateFilter,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'perPage' => $this->perPage
        ]]);
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    // Reset page on filter changes
    public function updatingSearch() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); $this->useDateFilter = true; $this->updateMonthDisplay(); }
    public function updatingDateTo() { $this->resetPage(); $this->useDateFilter = true; $this->updateMonthDisplay(); }
    public function updatingPerPage() { $this->resetPage(); }
    
    /**
     * Get activities with optimized query
     */
    public function getActivitiesProperty()
    {
        $query = Activity::query();
        
        // Apply date filter ONLY if useDateFilter is true AND we have dates
        if ($this->useDateFilter && $this->dateFrom && $this->dateTo) {
            $query->whereBetween('data_activities', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->useDateFilter && $this->dateFrom) {
            $query->whereDate('data_activities', '>=', $this->dateFrom);
        } elseif ($this->useDateFilter && $this->dateTo) {
            $query->whereDate('data_activities', '<=', $this->dateTo);
        }
        
        // Apply cost center filter
        if ($this->costCenterFilter) {
            $query->where('id_cost_centers', $this->costCenterFilter);
        }
        
        // Apply service filter
        if ($this->serviceFilter) {
            $query->where('id_services', $this->serviceFilter);
        }
        
        // Apply entity filter
        if ($this->entityFilter) {
            $query->where('id_entities', $this->entityFilter);
        }
        
        // Apply search
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_references', 'like', $searchTerm)
                  ->orWhere('note', 'like', $searchTerm)
                  ->orWhere('ordine', 'like', $searchTerm);
            });
        }
        
        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);
        
        // Eager load relationships with limit for performance
        return $query->with([
            'costCenter', 
            'service', 
            'entity',
            'staffDetails' => function($q) {
                $q->with('staff')->limit(5);
            }
        ])->paginate($this->perPage);
    }
    
    public function getStaffListProperty()
    {
        return Staff::where('valid', 1)
            ->select('id_personale', 'NomePers', 'CognomePers')
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();
    }
    
    public function getAvailableYearsProperty()
    {
        $years = [];
        $currentYear = Carbon::now()->year;
        for ($i = $currentYear - 10; $i <= $currentYear + 1; $i++) {
            $years[] = $i;
        }
        return $years;
    }
    
    public function getMonthsListProperty()
    {
        return [
            '01' => 'Gennaio',
            '02' => 'Febbraio',
            '03' => 'Marzo',
            '04' => 'Aprile',
            '05' => 'Maggio',
            '06' => 'Giugno',
            '07' => 'Luglio',
            '08' => 'Agosto',
            '09' => 'Settembre',
            '10' => 'Ottobre',
            '11' => 'Novembre',
            '12' => 'Dicembre',
        ];
    }
    
    public function getTotalCountProperty()
    {
        $query = Activity::query();
        
        if ($this->useDateFilter && $this->dateFrom && $this->dateTo) {
            $query->whereBetween('data_activities', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->useDateFilter && $this->dateFrom) {
            $query->whereDate('data_activities', '>=', $this->dateFrom);
        } elseif ($this->useDateFilter && $this->dateTo) {
            $query->whereDate('data_activities', '<=', $this->dateTo);
        }
        
        if ($this->costCenterFilter) {
            $query->where('id_cost_centers', $this->costCenterFilter);
        }
        
        if ($this->serviceFilter) {
            $query->where('id_services', $this->serviceFilter);
        }
        
        if ($this->entityFilter) {
            $query->where('id_entities', $this->entityFilter);
        }
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_references', 'like', $searchTerm)
                  ->orWhere('note', 'like', $searchTerm)
                  ->orWhere('ordine', 'like', $searchTerm);
            });
        }
        
        return $query->count();
    }
    
    // ==================== CRUD METHODS ====================
    
    public function viewActivity($id)
    {
        try {
            $activity = Activity::with(['costCenter', 'service', 'entity', 'staffDetails.staff', 'staffDetails.ownership'])
                ->find($id);
            
            if (!$activity) {
                $this->dispatch('showError', message: 'Attività non trovata');
                return;
            }
            $this->viewingActivity = $activity;
            $this->showViewModal = true;
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingActivity = null;
    }
    
    public function editActivity($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $activity = Activity::find($id);
            if (!$activity) {
                $this->dispatch('showError', message: 'Attività non trovata');
                return;
            }
            
            $this->editingId = $id;
            $this->editingActivity = $activity;
            $this->editCostCenter = $activity->id_cost_centers;
            $this->editService = $activity->id_services;
            $this->editEntity = $activity->id_entities;
            $this->editDate = $activity->data_activities ? $activity->data_activities->format('Y-m-d') : '';
            $this->editNote = $activity->note;
            $this->editInvoiceRef = $activity->invoice_references;
            $this->editImponibile = $activity->imponibile;
            $this->editCostiMat = $activity->costi_mat;
            
            $this->showEditModal = true;
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingActivity = null;
        $this->editingId = null;
        $this->resetEditForm();
    }
    
    public function resetEditForm()
    {
        $this->editCostCenter = '';
        $this->editService = '';
        $this->editEntity = '';
        $this->editDate = '';
        $this->editNote = '';
        $this->editInvoiceRef = '';
        $this->editImponibile = '';
        $this->editCostiMat = '';
    }
    
    public function updateActivity()
    {
        $this->validate([
            'editCostCenter' => 'required|exists:cost_centers,id',
            'editService' => 'required|exists:services,id',
            'editEntity' => 'required|exists:entities,id_cliente',
            'editDate' => 'nullable|date',
        ]);
        
        try {
            $activity = Activity::find($this->editingId);
            if ($activity) {
                $activity->update([
                    'id_cost_centers' => $this->editCostCenter,
                    'id_services' => $this->editService,
                    'id_entities' => $this->editEntity,
                    'data_activities' => $this->editDate ?: null,
                    'note' => $this->editNote,
                    'invoice_references' => $this->editInvoiceRef,
                    'imponibile' => $this->editImponibile ?: null,
                    'costi_mat' => $this->editCostiMat ?: null,
                ]);
                
                $this->closeEditModal();
                $this->dispatch('showSuccess', message: 'Attività aggiornata!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function openCreateModal()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        $this->resetCreateForm();
        $this->createDate = Carbon::now()->format('Y-m-d');
        $this->showCreateModal = true;
    }
    
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }
    
    public function resetCreateForm()
    {
        $this->createCostCenter = '';
        $this->createService = '';
        $this->createEntity = '';
        $this->createDate = '';
        $this->createNote = '';
        $this->createInvoiceRef = '';
        $this->createImponibile = '';
        $this->createCostiMat = '';
    }
    
    public function saveActivity()
    {
        $this->validate([
            'createCostCenter' => 'required|exists:cost_centers,id',
            'createService' => 'required|exists:services,id',
            'createEntity' => 'required|exists:entities,id_cliente',
            'createDate' => 'nullable|date',
        ]);
        
        try {
            Activity::create([
                'id_cost_centers' => $this->createCostCenter,
                'id_services' => $this->createService,
                'id_entities' => $this->createEntity,
                'data_activities' => $this->createDate ?: null,
                'note' => $this->createNote,
                'invoice_references' => $this->createInvoiceRef,
                'imponibile' => $this->createImponibile ?: null,
                'costi_mat' => $this->createCostiMat ?: null,
            ]);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Attività creata!');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function deleteActivity($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $activity = Activity::find($id);
            if ($activity) {
                $activity->delete();
                $this->dispatch('showSuccess', message: 'Attività eliminata!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function resetFilters()
    {
        $this->reset([
            'search', 
            'costCenterFilter', 
            'serviceFilter', 
            'entityFilter',
            'costCenterSearch',
            'serviceSearch',
            'entitySearch'
        ]);
        $this->setCurrentMonthRange();
        $this->resetPage();
    }
    
    public function formatDate($date)
    {
        if (!$date) return '-';
        return Carbon::parse($date)->format('d/m/Y');
    }
    
    public function formatCurrency($amount)
    {
        if (!$amount) return '-';
        return '€ ' . number_format($amount, 2, ',', '.');
    }

    public function render()
    {
        return view('livewire.admin.activities-table', [
            'activities' => $this->activities,
            'filteredCostCenters' => $this->filteredCostCenters,
            'filteredServices' => $this->filteredServices,
            'filteredEntities' => $this->filteredEntities,
            'staffList' => $this->staffList,
            'availableYears' => $this->availableYears,
            'totalCount' => $this->totalCount,
            'costCenterName' => $this->costCenterFilter ? optional(CostCenter::find($this->costCenterFilter))->Nome : null,
            'serviceName' => $this->serviceFilter ? optional(Service::find($this->serviceFilter))->Titolo : null,
            'entityName' => $this->entityFilter ? optional(Entity::find($this->entityFilter))->ragione_sociale : null,
        ]);
    }
}