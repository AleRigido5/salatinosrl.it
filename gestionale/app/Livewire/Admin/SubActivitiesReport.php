<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ActivityCoordinate;
use App\Models\CostCenter;
use App\Models\Service;
use App\Models\Entity;
use Carbon\Carbon;

class SubActivitiesReport extends Component
{
    // Filtri
    public $search = '';

    public $costCenterFilter = '';
    public $costCenterSearch = '';
    public $costCenterName = '';

    public $serviceFilter = '';
    public $serviceSearch = '';
    public $serviceName = '';

    public $entityFilter = '';
    public $entitySearch = '';
    public $entityName = '';

    public $dateFrom = '';
    public $dateTo = '';

    // Filtri identici alla vista Attività (per coerenza UI, riusati dal blade)
    public $positionFilter = '';
    public $perPage = 10000;

    // Autocomplete
    public $showCostCenterDropdown = false;
    public $showServiceDropdown = false;
    public $showEntityDropdown = false;

    // Risultati
    public $hasSearched = false;
    public $groups = [];
    public $grandTotalHa = 0;

    protected $queryString = [
        'search' => ['except' => ''],
        'costCenterFilter' => ['except' => ''],
        'serviceFilter' => ['except' => ''],
        'entityFilter' => ['except' => ''],
        'positionFilter' => ['except' => ''],
    ];

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount()
    {
        $now = Carbon::now();
        // Default: intero anno corrente
        $this->dateFrom = $now->copy()->startOfYear()->format('Y-m-d');
        $this->dateTo = $now->copy()->endOfYear()->format('Y-m-d');

        // Se il cliente è già presente (es. dopo un reload causato dal pulsante
        // "Applica" del componente date-range-filter, che porta con sé anche
        // gli altri filtri tramite query string), esegue subito la ricerca.
        $this->autoApplyIfReady();
    }

    /**
     * Riceve l'aggiornamento dal componente components.date-range-filter
     * (stessa logica usata da ActivitiesTable) e, se un cliente è già
     * impostato, applica subito i filtri con il nuovo range di date.
     */
    public function updateDateRange($data)
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
        $this->autoApplyIfReady();
    }

    /**
     * Esegue la ricerca automaticamente quando un cliente è impostato;
     * se il cliente viene svuotato, nasconde i risultati precedenti.
     */
    private function autoApplyIfReady()
    {
        if (!empty($this->entityFilter)) {
            $this->applyFilters();
        } else {
            $this->hasSearched = false;
            $this->groups = [];
            $this->grandTotalHa = 0;
        }
    }

    public function updatedSearch()
    {
        $this->autoApplyIfReady();
    }

    public function updatedCostCenterFilter()
    {
        $this->autoApplyIfReady();
    }

    public function updatedServiceFilter()
    {
        $this->autoApplyIfReady();
    }

    public function updatedPositionFilter()
    {
        $this->autoApplyIfReady();
    }

    public function updatedEntityFilter()
    {
        $this->autoApplyIfReady();
    }

    /**
     * No-op: richiesto dal markup JS riusato dalla vista Attività
     * (le voci degli autocomplete chiamano @this.call('resetPage')).
     * Questo report non pagina, quindi non c'è nulla da fare.
     */
    public function resetPage()
    {
        //
    }

    // ==================== AUTOCOMPLETE ====================

    public function updatedCostCenterSearch()
    {
        $this->showCostCenterDropdown = !empty($this->costCenterSearch);
        if (empty($this->costCenterSearch)) {
            $this->costCenterFilter = '';
        }
    }

    public function updatedServiceSearch()
    {
        $this->showServiceDropdown = !empty($this->serviceSearch);
        if (empty($this->serviceSearch)) {
            $this->serviceFilter = '';
        }
    }

    public function updatedEntitySearch()
    {
        $this->showEntityDropdown = !empty($this->entitySearch);
        if (empty($this->entitySearch)) {
            $this->entityFilter = '';
        }
    }

    public function selectCostCenter($id, $name)
    {
        $this->costCenterFilter = $id;
        $this->costCenterSearch = $name;
        $this->costCenterName = $name;
        $this->showCostCenterDropdown = false;
    }

    public function selectService($id, $name)
    {
        $this->serviceFilter = $id;
        $this->serviceSearch = $name;
        $this->serviceName = $name;
        $this->showServiceDropdown = false;
    }

    public function selectEntity($id, $name)
    {
        $this->entityFilter = $id;
        $this->entitySearch = $name;
        $this->entityName = $name;
        $this->showEntityDropdown = false;
    }

    public function clearCostCenter()
    {
        $this->costCenterFilter = '';
        $this->costCenterSearch = '';
        $this->costCenterName = '';
        $this->autoApplyIfReady();
    }

    public function clearService()
    {
        $this->serviceFilter = '';
        $this->serviceSearch = '';
        $this->serviceName = '';
        $this->autoApplyIfReady();
    }

    public function clearEntity()
    {
        $this->entityFilter = '';
        $this->entitySearch = '';
        $this->entityName = '';
        $this->autoApplyIfReady();
    }

    public function getFilteredCostCentersProperty()
    {
        if (empty($this->costCenterSearch)) return collect();
        $term = '%' . $this->costCenterSearch . '%';
        return CostCenter::where('valid', 1)
            ->where('Nome', 'like', $term)
            ->orderBy('Nome')
            ->limit(10)
            ->get();
    }

    public function getFilteredServicesProperty()
    {
        if (empty($this->serviceSearch)) return collect();
        $term = '%' . $this->serviceSearch . '%';
        return Service::where('Stato', 1)
            ->where('Titolo', 'like', $term)
            ->orderBy('Titolo')
            ->limit(10)
            ->get();
    }

    public function getFilteredEntitiesProperty()
    {
        if (empty($this->entitySearch)) return collect();
        $term = '%' . $this->entitySearch . '%';
        return Entity::where('valid', 1)
            ->where(function ($q) use ($term) {
                $q->where('ragione_sociale', 'like', $term)
                  ->orWhere('nome', 'like', $term)
                  ->orWhere('cognome', 'like', $term);
            })
            ->orderBy('ragione_sociale')
            ->limit(10)
            ->get();
    }

    // ==================== RICERCA (solo su "Applica") ====================

    /**
     * Esegue la ricerca delle sotto-attività. Richiede obbligatoriamente un cliente/fornitore.
     * Viene chiamata SOLO dal pulsante "Applica" — nessun filtro è reattivo/live.
     */
    public function applyFilters()
    {
        if (empty($this->entityFilter)) {
            $this->dispatch('showError', message: 'Seleziona almeno un cliente/fornitore prima di applicare i filtri.');
            $this->hasSearched = false;
            $this->groups = [];
            $this->grandTotalHa = 0;
            return;
        }

        $coordinates = ActivityCoordinate::query()
            ->whereHas('activity', function ($q) {
                $q->where('id_entities', $this->entityFilter);

                if ($this->dateFrom && $this->dateTo) {
                    $q->whereBetween('data_activities', [$this->dateFrom, $this->dateTo]);
                } elseif ($this->dateFrom) {
                    $q->whereDate('data_activities', '>=', $this->dateFrom);
                } elseif ($this->dateTo) {
                    $q->whereDate('data_activities', '<=', $this->dateTo);
                }

                if ($this->costCenterFilter) {
                    $q->where('id_cost_centers', $this->costCenterFilter);
                }

                if ($this->serviceFilter) {
                    $q->where('id_services', $this->serviceFilter);
                }

                // Filtro Posizioni (stessa logica di ActivitiesTable)
                if ($this->positionFilter === 'aperte') {
                    $q->where(function ($qq) {
                        $qq->whereNull('activities.invoice_references')
                           ->orWhere('activities.invoice_references', '');
                    })->whereExists(function ($qq) {
                        $qq->select(\Illuminate\Support\Facades\DB::raw(1))
                           ->from('cost_centers')
                           ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                           ->where('cost_centers.table_references', 'entities');
                    });
                } elseif ($this->positionFilter === 'interne') {
                    $q->whereNotExists(function ($qq) {
                        $qq->select(\Illuminate\Support\Facades\DB::raw(1))
                           ->from('cost_centers')
                           ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                           ->where('cost_centers.table_references', 'entities');
                    });
                }
            })
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('NoteAtt', 'like', $term)
                       ->orWhere('Lat_inizio', 'like', $term)
                       ->orWhere('Lat_fine', 'like', $term);
                });
            })
            ->with(['activity.entity', 'activity.costCenter', 'activity.service'])
            ->get()
            ->filter(fn ($coord) => $coord->activity !== null)
            ->sortBy([
                fn ($a, $b) => $a->activity->data_activities <=> $b->activity->data_activities,
                fn ($a, $b) => $a->id_att_LatLong <=> $b->id_att_LatLong,
            ])
            ->values();

        // Raggruppa per Cliente + Centro di Costo + Servizio (un blocco/tabella per combinazione)
        $grouped = $coordinates->groupBy(function ($coord) {
            return $coord->activity->id_entities . '|' . $coord->activity->id_cost_centers . '|' . $coord->activity->id_services;
        });

        $this->groups = $grouped->map(function ($items) {
            $first = $items->first()->activity;

            $totalHa = $items->sum(function ($coord) {
                if (!$coord->ha) return 0;
                return floatval(str_replace(',', '.', $coord->ha));
            });

            return [
                'entity_name' => optional($first->entity)->ragione_sociale
                    ?: trim(optional($first->entity)->nome . ' ' . optional($first->entity)->cognome),
                'cost_center_name' => optional($first->costCenter)->Nome ?? '-',
                'service_name' => optional($first->service)->Titolo ?? '-',
                'rows' => $items,
                'total_ha' => $totalHa,
            ];
        })->values()->all();

        $this->grandTotalHa = collect($this->groups)->sum('total_ha');
        $this->hasSearched = true;
    }

    public function resetFilters()
    {
        $now = Carbon::now();

        $this->search = '';
        $this->costCenterFilter = '';
        $this->costCenterSearch = '';
        $this->costCenterName = '';
        $this->serviceFilter = '';
        $this->serviceSearch = '';
        $this->serviceName = '';
        $this->entityFilter = '';
        $this->entitySearch = '';
        $this->entityName = '';
        $this->positionFilter = '';
        $this->dateFrom = $now->copy()->startOfYear()->format('Y-m-d');
        $this->dateTo = $now->copy()->endOfYear()->format('Y-m-d');

        $this->hasSearched = false;
        $this->groups = [];
        $this->grandTotalHa = 0;

        $this->dispatch('resetDates');
    }

    public function formatDate($date)
    {
        if (!$date) return '-';
        return Carbon::parse($date)->format('d/m/Y');
    }

    public function render()
    {
        // Nota: nessun ->layout() qui — il layout/menu è già fornito dalla view
        // del controller (admin.activities.sub-activities) che include questo
        // componente tramite @livewire('admin.sub-activities-report').
        return view('livewire.admin.sub-activities-report', [
            'filteredCostCenters' => $this->filteredCostCenters,
            'filteredServices' => $this->filteredServices,
            'filteredEntities' => $this->filteredEntities,
        ]);
    }
}