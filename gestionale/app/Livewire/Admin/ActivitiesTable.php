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
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ActivitiesTable extends Component
{
    use WithPagination;

    // Filtri
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
    
    // Autocomplete
    public $costCenterSearch = '';
    public $serviceSearch = '';
    public $entitySearch = '';
    public $showCostCenterDropdown = false;
    public $showServiceDropdown = false;
    public $showEntityDropdown = false;
    public $positionFilter = '';
    
    // Modal
    public $showViewModal = false;
    public $viewingActivity = null;
    
    // Loading states
    public $isLoading = false;
    
    protected $paginationTheme = 'tailwind';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'costCenterFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'entityFilter' => ['except' => ''],
        'positionFilter' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'perPage' => ['except' => 10000],
        'sortField' => ['except' => 'data_activities'],
        'sortDirection' => ['except' => 'desc'],
    ];
    
    protected $listeners = [
        'refreshActivities' => 'refreshActivities',
        'loadMore' => 'loadMore',
        'dateRangeUpdated' => 'updateDateRange',  // AGGIUNGI QUESTO
    ];
    
    public function mount()
    {
        $this->perPage = (int) $this->perPage;
        
        // Non impostare date qui se vengono dal componente
        // Se vuoi solo valori iniziali, fallo solo se non ci sono date dalla request
        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $now = Carbon::now();
            $this->dateFrom = $now->copy()->startOfMonth()->format('Y-m-d');
            $this->dateTo = $now->copy()->endOfMonth()->format('Y-m-d');
        }
    }
    
    // AGGIUNGI QUESTO METODO
    public function updateDateRange($data)
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
        $this->resetPage();
    }
    
    /**
     * Query ottimizzata con chunking per range grandi
     */
    public function getActivitiesProperty()
    {
        $query = Activity::query();
        
        // Filtri data
        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('data_activities', [$this->dateFrom, $this->dateTo]);
        } elseif ($this->dateFrom) {
            $query->whereDate('data_activities', '>=', $this->dateFrom);
        } elseif ($this->dateTo) {
            $query->whereDate('data_activities', '<=', $this->dateTo);
        }
        
        // Centro di costo
        if ($this->costCenterFilter) {
            $query->where('id_cost_centers', $this->costCenterFilter);
        }
        
        // Servizio
        if ($this->serviceFilter) {
            $query->where('id_services', $this->serviceFilter);
        }
        
        // Entità
        if ($this->entityFilter) {
            $query->where('id_entities', $this->entityFilter);
        }
        
        // NUOVO FILTRO POSIZIONI
        if ($this->positionFilter === 'aperte') {
            // ATTIVITÀ APERTE: clienti esterni + fattura vuota
            $query->where(function($q) {
                $q->whereNull('activities.invoice_references')
                ->orWhere('activities.invoice_references', '');
            })->whereExists(function($q) {
                $q->select(DB::raw(1))
                ->from('cost_centers')
                ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                ->where('cost_centers.table_references', 'entities');
            });
            
        } elseif ($this->positionFilter === 'interne') {
            // ATTIVITÀ INTERNE: clienti interni (NON esterni)
            $query->whereNotExists(function($q) {
                $q->select(DB::raw(1))
                ->from('cost_centers')
                ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                ->where('cost_centers.table_references', 'entities');
            });
        }
        
        // Ricerca generica
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_references', 'like', $searchTerm)
                ->orWhere('note', 'like', $searchTerm)
                ->orWhere('ha', 'like', $searchTerm)
                ->orWhere('Lat_Long', 'like', $searchTerm);
            });
        }
        
        // Select con total_ore aggregato
        $query->select([
            'activities.*',
            DB::raw('(SELECT SUM(n_ore) FROM activities_staff_lnk WHERE activities_staff_lnk.id_activities = activities.id) as total_ore')
        ]);
        
        // Ordinamento
        $query->orderBy($this->sortField, $this->sortDirection);
        
        // Eager loading ottimizzato
        $query->with([
            'costCenter:id,Nome,Localita,table_references',
            'service:id,Titolo,Descrizione',
            'entity:id_cliente,ragione_sociale,nome,cognome,partita_iva',
            'staffDetails' => function($q) {
                $q->with('staff:id_personale,NomePers,CognomePers')
                ->limit(3);
            }
        ]);
        
        // Paginazione o chunking
        if ($this->perPage == 10000) {
            $allActivities = collect();
            $query->chunk(500, function($chunk) use (&$allActivities) {
                foreach ($chunk as $item) {
                    $allActivities->push($item);
                }
            });
            return $allActivities;
        }
        
        return $query->paginate($this->perPage);
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
        $this->costCenterName = $name;
        $this->showCostCenterDropdown = false;
        $this->resetPage();
    }

    public function selectService($id, $name)
    {
        $this->serviceFilter = $id;
        $this->serviceSearch = $name;
        $this->serviceName = $name;
        $this->showServiceDropdown = false;
        $this->resetPage();
    }

    public function selectEntity($id, $name)
    {
        $this->entityFilter = $id;
        $this->entitySearch = $name;
        $this->entityName = $name;
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
        if (empty($this->costCenterSearch)) return collect();
        $searchTerm = '%' . $this->costCenterSearch . '%';
        return CostCenter::where('valid', 1)
            ->where('Nome', 'like', $searchTerm)
            ->orderBy('Nome')
            ->limit(10)
            ->get();
    }

    public function getFilteredServicesProperty()
    {
        if (empty($this->serviceSearch)) return collect();
        $searchTerm = '%' . $this->serviceSearch . '%';
        return Service::where('Stato', 1)
            ->where('Titolo', 'like', $searchTerm)
            ->orderBy('Titolo')
            ->limit(10)
            ->get();
    }

    public function getFilteredEntitiesProperty()
    {
        if (empty($this->entitySearch)) return collect();
        $searchTerm = '%' . $this->entitySearch . '%';
        return Entity::where('valid', 1)
            ->where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm);
            })
            ->orderBy('ragione_sociale')
            ->limit(10)
            ->get();
    }
    
    // ==================== SORTING ====================
    
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
    
    // ==================== RESET METHODS ====================
    
    public function updatingSearch() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingCostCenterFilter() { $this->resetPage(); }
    public function updatingServiceFilter() { $this->resetPage(); }
    public function updatingEntityFilter() { $this->resetPage(); }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->costCenterFilter = '';
        $this->costCenterSearch = '';
        $this->costCenterName = '';
        $this->serviceFilter = '';
        $this->serviceSearch = '';
        $this->serviceName = '';
        $this->entityFilter = '';
        $this->positionFilter = '';
        $this->entitySearch = '';
        $this->entityName = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        
        // Resetta anche il componente date-range-filter
        $this->dispatch('resetDates');
    }
    
    // ==================== CRUD METHODS ====================
    
    public function updateLatLong($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            Activity::where('id', $id)->update(['Lat_Long' => $value ?: null]);
            $this->dispatch('showSuccess', message: 'Coordinate aggiornate con successo!');
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function updateHa($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            $value = str_replace(',', '.', $value);
            Activity::where('id', $id)->update(['ha' => $value ? floatval($value) : null]);
            $this->dispatch('showSuccess', message: 'Ettari aggiornati con successo!');
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function updateInvoiceRef($id, $value)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            $this->dispatch('showError', message: 'Permessi insufficienti');
            return;
        }
        
        try {
            Activity::where('id', $id)->update(['invoice_references' => $value ?: null]);
            $this->dispatch('showSuccess', message: 'Riferimento fattura aggiornato con successo!');
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function viewActivity($id)
    {
        try {
            $this->viewingActivity = Activity::with([
                'costCenter', 'service', 'entity', 
                'staffDetails.staff', 'staffDetails.ownership'
            ])->find($id);
            $this->showViewModal = true;
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento');
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingActivity = null;
    }
    
    public function refreshActivities()
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }
    
    /**
     * Genera l'URL per l'esportazione PDF con tutti i filtri attivi
     */
    public function getExportPdfUrl()
    {
        $params = [];
        
        // Filtri data
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        
        // Filtro centro di costo
        if ($this->costCenterFilter) {
            $params['cost_center_filter'] = $this->costCenterFilter;
        }
        
        // Filtro servizio
        if ($this->serviceFilter) {
            $params['service_filter'] = $this->serviceFilter;
        }
        
        // Filtro entità (cliente/fornitore)
        if ($this->entityFilter) {
            $params['entity_filter'] = $this->entityFilter;
        }
        
        // Filtro posizioni (aperte/interne)
        if ($this->positionFilter) {
            $params['position_filter'] = $this->positionFilter;
        }
        
        // Ricerca generica
        if ($this->search) {
            $params['search'] = $this->search;
        }
        
        return route('admin.activities.export-pdf', $params);
    }

    /**
     * Genera l'URL per l'esportazione Excel con tutti i filtri attivi
     */
    public function getExportExcelUrl()
    {
        $params = [];
        
        // Filtri data
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        
        // Filtro centro di costo
        if ($this->costCenterFilter) {
            $params['cost_center_filter'] = $this->costCenterFilter;
        }
        
        // Filtro servizio
        if ($this->serviceFilter) {
            $params['service_filter'] = $this->serviceFilter;
        }
        
        // Filtro entità (cliente/fornitore)
        if ($this->entityFilter) {
            $params['entity_filter'] = $this->entityFilter;
        }
        
        // Filtro posizioni (aperte/interne)
        if ($this->positionFilter) {
            $params['position_filter'] = $this->positionFilter;
        }
        
        // Ricerca generica
        if ($this->search) {
            $params['search'] = $this->search;
        }
        
        return route('admin.activities.export-excel', $params);
    }

    public function formatDate($date)
    {
        if (!$date) return '-';
        return Carbon::parse($date)->format('d/m/Y');
    }

    public function render()
    {
        return view('livewire.admin.activities-table', [
            'activities' => $this->activities,
            'filteredCostCenters' => $this->filteredCostCenters,
            'filteredServices' => $this->filteredServices,
            'filteredEntities' => $this->filteredEntities,
        ]);
    }
}