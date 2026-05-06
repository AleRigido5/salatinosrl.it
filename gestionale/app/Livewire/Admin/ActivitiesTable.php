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

    // Filtri (mantenuti originali)
    public $search = '';
    public $costCenterFilter = '';
    public $serviceFilter = '';
    public $entityFilter = '';
    public $costCenterName = '';
    public $serviceName = '';
    public $entityName = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $perPage = 10000;
    public $sortField = 'data_activities';
    public $sortDirection = 'desc';
    
    // Autocomplete properties per filtri
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
    
    // Dropdown data
    public $costCenters = [];
    public $services = [];
    public $entities = [];
    
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
    
    // Autocomplete properties per modifica
    public $editCostCenterSearch = '';
    public $editServiceSearch = '';
    public $editEntitySearch = '';
    public $showEditCostCenterDropdown = false;
    public $showEditServiceDropdown = false;
    public $showEditEntityDropdown = false;
    
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
    
    // Autocomplete properties per creazione
    public $createCostCenterSearch = '';
    public $createServiceSearch = '';
    public $createEntitySearch = '';
    public $showCreateCostCenterDropdown = false;
    public $showCreateServiceDropdown = false;
    public $showCreateEntityDropdown = false;
    
    protected $paginationTheme = 'tailwind';
    
    // Query string parameters (solo per i filtri che rimangono Livewire)
    protected $queryString = [
        'search' => ['except' => ''],
        'costCenterFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'entityFilter' => ['except' => ''],
        'perPage' => ['except' => 15],
        'sortField' => ['except' => 'data_activities'],
        'sortDirection' => ['except' => 'desc'],
        // dateFrom e dateTo NON sono più in queryString perché gestiti via redirect
    ];
    
    protected $listeners = [
        'openCreateModal' => 'openCreateModal',
        'refreshActivities' => 'refreshActivities',
        'refreshActivities' => '$refresh'
    ];
    
    public function mount()
    {
        $this->perPage = (int) $this->perPage;   

        // Leggi le date dalla request se presenti
        if (request()->has('date_from')) {
            $this->dateFrom = request('date_from');
        }
        if (request()->has('date_to')) {
            $this->dateTo = request('date_to');
        }
        
        // Se non ci sono date nella request, usa il mese corrente
        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $now = Carbon::now();
            $this->selectedMonth = $now->format('m');
            $this->selectedYear = $now->format('Y');
            $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
            $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
            $this->useDateFilter = true;
        } else {
            // Forza l'uso del filtro data
            $this->useDateFilter = true;
            
            // Prova a estrarre mese e anno dalle date per i select
            try {
                $fromDate = Carbon::parse($this->dateFrom);
                $toDate = Carbon::parse($this->dateTo);
                
                // Verifica se è un mese intero
                if ($fromDate->day == 1 && $toDate->day == $toDate->daysInMonth && 
                    $fromDate->format('Y-m') === $toDate->format('Y-m')) {
                    $this->selectedMonth = $fromDate->format('m');
                    $this->selectedYear = $fromDate->format('Y');
                } else {
                    $this->selectedMonth = '';
                    $this->selectedYear = '';
                }
            } catch (\Exception $e) {
                $this->selectedMonth = '';
                $this->selectedYear = '';
            }
        }
        
        $this->updateMonthDisplay();
        $this->loadDropdownData();
    }
        
    // ==================== DROPDOWN DATA METHODS ====================
    
    public function loadDropdownData()
    {
        $this->costCenters = CostCenter::where('valid', 1)
            ->orderBy('Nome', 'asc')
            ->get();
        
        $this->services = Service::where('Stato', 1)
            ->orderBy('Titolo', 'asc')
            ->get();
        
        $this->entities = Entity::where('valid', 1)
            ->orderBy('ragione_sociale')
            ->orderBy('nome')
            ->get();
    }
    
    // ==================== AUTOCOMPLETE METHODS PER FILTRI ====================
    
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
        $this->costCenterName = '';  
        $this->showCostCenterDropdown = false;
        $this->resetPage();
    }

    public function clearService()
    {
        $this->serviceFilter = '';
        $this->serviceSearch = '';
        $this->serviceName = '';
        $this->showServiceDropdown = false;
        $this->resetPage();
    }

    public function clearEntity()
    {
        $this->entityFilter = '';
        $this->entitySearch = '';
        $this->entityName = '';
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
    
    // ==================== AUTOCOMPLETE METHODS PER MODIFICA ====================
    
    public function updatedEditCostCenterSearch()
    {
        $this->showEditCostCenterDropdown = !empty($this->editCostCenterSearch);
        if (empty($this->editCostCenterSearch)) {
            $this->editCostCenter = '';
        }
    }

    public function updatedEditServiceSearch()
    {
        $this->showEditServiceDropdown = !empty($this->editServiceSearch);
        if (empty($this->editServiceSearch)) {
            $this->editService = '';
        }
    }

    public function updatedEditEntitySearch()
    {
        $this->showEditEntityDropdown = !empty($this->editEntitySearch);
        if (empty($this->editEntitySearch)) {
            $this->editEntity = '';
        }
    }

    public function getFilteredEditCostCentersProperty()
    {
        if (empty($this->editCostCenterSearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->editCostCenterSearch . '%';
        return CostCenter::where('valid', 1)
            ->where('Nome', 'like', $searchTerm)
            ->orderBy('Nome', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredEditServicesProperty()
    {
        if (empty($this->editServiceSearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->editServiceSearch . '%';
        return Service::where('Stato', 1)
            ->where('Titolo', 'like', $searchTerm)
            ->orderBy('Titolo', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredEditEntitiesProperty()
    {
        if (empty($this->editEntitySearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->editEntitySearch . '%';
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

    public function clearEditCostCenter()
    {
        $this->editCostCenter = '';
        $this->editCostCenterSearch = '';
        $this->showEditCostCenterDropdown = false;
    }

    public function clearEditService()
    {
        $this->editService = '';
        $this->editServiceSearch = '';
        $this->showEditServiceDropdown = false;
    }

    public function clearEditEntity()
    {
        $this->editEntity = '';
        $this->editEntitySearch = '';
        $this->showEditEntityDropdown = false;
    }
    
    // ==================== AUTOCOMPLETE METHODS PER CREAZIONE ====================
    
    public function updatedCreateCostCenterSearch()
    {
        $this->showCreateCostCenterDropdown = !empty($this->createCostCenterSearch);
        if (empty($this->createCostCenterSearch)) {
            $this->createCostCenter = '';
        }
    }

    public function updatedCreateServiceSearch()
    {
        $this->showCreateServiceDropdown = !empty($this->createServiceSearch);
        if (empty($this->createServiceSearch)) {
            $this->createService = '';
        }
    }

    public function updatedCreateEntitySearch()
    {
        $this->showCreateEntityDropdown = !empty($this->createEntitySearch);
        if (empty($this->createEntitySearch)) {
            $this->createEntity = '';
        }
    }

    public function getFilteredCreateCostCentersProperty()
    {
        if (empty($this->createCostCenterSearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->createCostCenterSearch . '%';
        return CostCenter::where('valid', 1)
            ->where('Nome', 'like', $searchTerm)
            ->orderBy('Nome', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredCreateServicesProperty()
    {
        if (empty($this->createServiceSearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->createServiceSearch . '%';
        return Service::where('Stato', 1)
            ->where('Titolo', 'like', $searchTerm)
            ->orderBy('Titolo', 'asc')
            ->limit(10)
            ->get();
    }

    public function getFilteredCreateEntitiesProperty()
    {
        if (empty($this->createEntitySearch)) {
            return collect();
        }
        
        $searchTerm = '%' . $this->createEntitySearch . '%';
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

    public function clearCreateCostCenter()
    {
        $this->createCostCenter = '';
        $this->createCostCenterSearch = '';
        $this->showCreateCostCenterDropdown = false;
    }

    public function clearCreateService()
    {
        $this->createService = '';
        $this->createServiceSearch = '';
        $this->showCreateServiceDropdown = false;
    }

    public function clearCreateEntity()
    {
        $this->createEntity = '';
        $this->createEntitySearch = '';
        $this->showCreateEntityDropdown = false;
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
    
    // Metodo per resettare i filtri (incluso il redirect per le date)
    public function resetFilters()
    {
        $this->reset([
            'search', 
            'costCenterFilter', 
            'serviceFilter', 
            'entityFilter',
            'costCenterSearch',
            'serviceSearch',
            'entitySearch',
            'costCenterName',  
            'serviceName',     
            'entityName' 
        ]);
        $this->setCurrentMonthRange();
        $this->resetPage();
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
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingCostCenterFilter() { $this->resetPage(); }
    public function updatingServiceFilter() { $this->resetPage(); }
    public function updatingEntityFilter() { $this->resetPage(); }
    
    /**
     * Get activities with optimized query
     */
    public function getActivitiesProperty()
    {
        $query = Activity::query();
        
        // Date filter
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
                ->orWhere('ha', 'like', $searchTerm)
                ->orWhere('Lat_Long', 'like', $searchTerm);
            });
        }
        
        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);
        
        // Eager load relationships
        $query->with([
            'costCenter', 
            'service', 
            'entity',
            'staffDetails' => function($q) {
                $q->with('staff')->limit(5);
            }
        ]);
        
        // Gestione "Tutti": se perPage è 10000, prendi TUTTI i record
        if ($this->perPage == 10000) {
            return $query->get();
        }
        
        return $query->paginate($this->perPage);
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
        for ($i = $currentYear - 10; $i <= $currentYear + 5; $i++) {
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
    
    // ==================== CRUD METHODS ====================
    
    /**
     * Aggiorna Lat/Long direttamente dalla tabella
     */
    public function updateLatLong($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $activity = Activity::find($id);
            if ($activity) {
                $activity->update([
                    'Lat_Long' => $value ?: null,
                    'updated_by' => Auth::guard('admin')->id()
                ]);
                $this->dispatch('showSuccess', message: 'Coordinate aggiornate con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    /**
     * Aggiorna Ettari (ha) direttamente dalla tabella
     */
    public function updateHa($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $activity = Activity::find($id);
            if ($activity) {
                $value = str_replace(',', '.', $value);
                $activity->update([
                    'ha' => $value ? floatval($value) : null,
                    'updated_by' => Auth::guard('admin')->id()
                ]);
                $this->dispatch('showSuccess', message: 'Ettari aggiornati con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    /**
     * Aggiorna Nota direttamente dalla tabella
     */
    public function updateNote($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $activity = Activity::find($id);
            if ($activity) {
                $activity->update([
                    'note' => $value ?: null,
                    'updated_by' => Auth::guard('admin')->id()
                ]);
                $this->dispatch('showSuccess', message: 'Nota aggiornata con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    /**
     * Aggiorna Riferimento Fattura direttamente dalla tabella
     */
    public function updateInvoiceRef($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $activity = Activity::find($id);
            if ($activity) {
                $activity->update([
                    'invoice_references' => $value ?: null,
                    'updated_by' => Auth::guard('admin')->id()
                ]);
                $this->dispatch('showSuccess', message: 'Riferimento fattura aggiornato con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
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
            $activity = Activity::with(['costCenter', 'service', 'entity'])->find($id);
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
            
            // Inizializza i campi di ricerca con i nomi correnti
            if ($activity->costCenter) {
                $this->editCostCenterSearch = $activity->costCenter->Nome;
            }
            if ($activity->service) {
                $this->editServiceSearch = $activity->service->Titolo;
            }
            if ($activity->entity) {
                $this->editEntitySearch = $activity->entity->ragione_sociale ?: ($activity->entity->nome . ' ' . $activity->entity->cognome);
            }
            
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
        $this->editCostCenterSearch = '';
        $this->editService = '';
        $this->editServiceSearch = '';
        $this->editEntity = '';
        $this->editEntitySearch = '';
        $this->editDate = '';
        $this->editNote = '';
        $this->editInvoiceRef = '';
        $this->editImponibile = '';
        $this->editCostiMat = '';
        $this->showEditCostCenterDropdown = false;
        $this->showEditServiceDropdown = false;
        $this->showEditEntityDropdown = false;
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
                $this->dispatch('showSuccess', message: 'Attività aggiornata con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
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
        $this->createCostCenterSearch = '';
        $this->createService = '';
        $this->createServiceSearch = '';
        $this->createEntity = '';
        $this->createEntitySearch = '';
        $this->createDate = '';
        $this->createNote = '';
        $this->createInvoiceRef = '';
        $this->createImponibile = '';
        $this->createCostiMat = '';
        $this->showCreateCostCenterDropdown = false;
        $this->showCreateServiceDropdown = false;
        $this->showCreateEntityDropdown = false;
    }

    public function refreshActivities()
    {
        // Questo metodo forza il refresh del componente
        $this->resetPage();
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
            $this->dispatch('showSuccess', message: 'Attività creata con successo!');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante la creazione: ' . $e->getMessage());
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
                $this->dispatch('showSuccess', message: 'Attività eliminata con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    public function formatDate($date)
    {
        if (!$date) return '-';
        return Carbon::parse($date)->format('d/m/Y');
    }
    
    public function formatCurrency($amount)
    {
        if (!$amount || $amount == 0) return '€ 0,00';
        return '€ ' . number_format($amount, 2, ',', '.');
    }

    public function render()
    {
        return view('livewire.admin.activities-table', [
            'activities' => $this->activities,
            'filteredCostCenters' => $this->filteredCostCenters,
            'filteredServices' => $this->filteredServices,
            'filteredEntities' => $this->filteredEntities,
            'filteredEditCostCenters' => $this->filteredEditCostCenters,
            'filteredEditServices' => $this->filteredEditServices,
            'filteredEditEntities' => $this->filteredEditEntities,
            'filteredCreateCostCenters' => $this->filteredCreateCostCenters,
            'filteredCreateServices' => $this->filteredCreateServices,
            'filteredCreateEntities' => $this->filteredCreateEntities,
            'staffList' => $this->staffList,
            'availableYears' => $this->availableYears,
            'costCenterName' => $this->costCenterFilter ? optional(CostCenter::find($this->costCenterFilter))->Nome : null,
            'serviceName' => $this->serviceFilter ? optional(Service::find($this->serviceFilter))->Titolo : null,
            'entityName' => $this->entityFilter ? optional(Entity::find($this->entityFilter))->ragione_sociale : null,
        ]);
    }
}