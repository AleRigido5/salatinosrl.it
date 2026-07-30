<?php
// app/Livewire/Admin/VehicleCalendar.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vehicles;
use App\Models\Expiration;
use App\Models\Setting;
use App\Models\Ownership;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class VehicleCalendar extends Component
{
    use WithPagination;

    public $view = 'calendar';
    public $currentDate;
    public $currentMonth;
    public $currentYear;
    public $selectedDate = null;
    
    // Filtri
    public Collection $expirationTypes;
    public string $selectedType = '';
    public string $vehicleSearch = '';
    public Collection $vehicleResults;
    public string $selectedVehicleId = '';
    public string $selectedVehicleName = '';
    public bool $showVehicleDropdown = false;
    public string $ownershipFilter = '';
    public string $expirationStatus = '';
    
    // Vista tabellare
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 100;
    public string $sortField = 'data_fine';
    public string $sortDirection = 'asc';
    
    protected $listeners = [
        'refreshCalendar' => '$refresh',
        'dateRangeUpdated' => 'updateDateRange',
    ];
    
    public function mount()
    {
        Carbon::setLocale('it');
        
        $this->currentDate = Carbon::now();
        $this->currentMonth = $this->currentDate->month;
        $this->currentYear = $this->currentDate->year;
        
        $this->expirationTypes = new Collection();
        $this->vehicleResults = new Collection();
        
        // Carica i tipi di scadenza per veicoli dalla tabella settings
        $types = Setting::where('tabella_riferimento', 'vehicles_expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
        
        if ($types->isNotEmpty()) {
            $this->expirationTypes = $types->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'label' => $item->valore,
                    'type' => $item->tipologia ?? 'scadenza'
                ];
            });
        } else {
            // Tipi predefiniti per veicoli
            $this->expirationTypes = collect([
                (object)['id' => 10, 'label' => 'Revisione', 'type' => 'revisione'],
                (object)['id' => 11, 'label' => 'Assicurazione', 'type' => 'assicurazione'],
                (object)['id' => 12, 'label' => 'Tagliando', 'type' => 'tagliando'],
                (object)['id' => 13, 'label' => 'Bollo', 'type' => 'bollo'],
                (object)['id' => 14, 'label' => 'Altro', 'type' => 'altro'],
            ]);
        }
        
        $this->selectedType = '';
    }
    
    public function updateDateRange($data)
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->resetPage();
    }
    
    public function previousMonth()
    {
        $newDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $newDate->month;
        $this->currentYear = $newDate->year;
        $this->currentDate = $newDate;
        $this->selectedDate = null;
        $this->resetPage();
    }
    
    public function nextMonth()
    {
        $newDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $newDate->month;
        $this->currentYear = $newDate->year;
        $this->currentDate = $newDate;
        $this->selectedDate = null;
        $this->resetPage();
    }
    
    public function goToToday()
    {
        $this->currentDate = Carbon::now();
        $this->currentMonth = $this->currentDate->month;
        $this->currentYear = $this->currentDate->year;
        $this->selectedDate = null;
        $this->resetPage();
    }
    
    public function selectDate($date)
    {
        $this->selectedDate = $date;
    }
    
    public function toggleType($typeId)
    {
        if ($this->selectedType == $typeId) {
            $this->selectedType = '';
        } else {
            $this->selectedType = (string)$typeId;
        }
        $this->resetPage();
        $this->dispatch('refreshCalendar');
    }
    
    public function selectAllTypes()
    {
        $this->selectedType = '';
        $this->resetPage();
        $this->dispatch('refreshCalendar');
    }
    
    public function clearTypes()
    {
        $this->selectedType = '';
        $this->resetPage();
        $this->dispatch('refreshCalendar');
    }
    
    // ==================== AUTOCOMPLETE VEICOLI ====================
    public function updatedVehicleSearch()
    {
        if ($this->selectedVehicleId && $this->vehicleSearch === $this->selectedVehicleName) {
            $this->showVehicleDropdown = false;
            return;
        }

        if ($this->selectedVehicleId) {
            $this->selectedVehicleId = '';
            $this->selectedVehicleName = '';
            $this->resetPage();
        }

        if (strlen($this->vehicleSearch) < 2) {
            $this->vehicleResults = new Collection();
            $this->showVehicleDropdown = false;
            return;
        }

        // Cerca nei veicoli (inclusi quelli disattivati)
        $this->vehicleResults = Vehicles::where(function($q) {
                $q->where('targa', 'like', '%' . $this->vehicleSearch . '%')
                  ->orWhere('marca', 'like', '%' . $this->vehicleSearch . '%')
                  ->orWhere('modello', 'like', '%' . $this->vehicleSearch . '%')
                  ->orWhereRaw("CONCAT(marca, ' ', modello) LIKE ?", ['%' . $this->vehicleSearch . '%'])
                  ->orWhereRaw("CONCAT(targa, ' - ', marca, ' ', modello) LIKE ?", ['%' . $this->vehicleSearch . '%']);
            })
            ->limit(10)
            ->get(['id', 'targa', 'marca', 'modello'])
            ->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'name' => $item->targa . ' - ' . $item->marca . ' ' . $item->modello
                ];
            });
        
        $this->showVehicleDropdown = $this->vehicleResults->isNotEmpty();
    }
    
    public function selectVehicle($id, $name)
    {
        $this->selectedVehicleId = (string)$id;
        $this->selectedVehicleName = $name;
        $this->vehicleSearch = $name;
        $this->showVehicleDropdown = false;
        $this->resetPage();
        $this->dispatch('refreshCalendar');
    }
    
    public function clearVehicle()
    {
        $this->selectedVehicleId = '';
        $this->selectedVehicleName = '';
        $this->vehicleSearch = '';
        $this->resetPage();
        $this->dispatch('refreshCalendar');
    }
    
    /**
     * Ottiene la label della tipologia dalla scadenza
     * Se ha un setting associato usa quello, altrimenti estrae dal titolo
     */
    public function getTypeLabelFromExpiration($expiration)
    {
        if ($expiration->setting) {
            return $expiration->setting->valore;
        }
        
        $titolo = $expiration->titolo ?? 'Scadenza';
        
        $keywords = ['Revisione', 'Assicurazione', 'Tagliando', 'Bollo', 'RCA', 'RCT', 'I/F', 'RC'];
        foreach ($keywords as $keyword) {
            if (stripos($titolo, $keyword) !== false) {
                return $keyword;
            }
        }
        
        return 'Scadenza';
    }
    
    /**
     * Ottiene il colore della tipologia dalla scadenza
     */
    public function getTypeColorFromExpiration($expiration)
    {
        $titolo = $expiration->titolo ?? '';
        
        if (stripos($titolo, 'Revisione') !== false) {
            return 'bg-blue-100 text-blue-800 border-blue-200';
        }
        if (stripos($titolo, 'Assicurazione') !== false || stripos($titolo, 'RCA') !== false || stripos($titolo, 'RCT') !== false) {
            return 'bg-red-100 text-red-800 border-red-200';
        }
        if (stripos($titolo, 'Tagliando') !== false) {
            return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        }
        if (stripos($titolo, 'Bollo') !== false) {
            return 'bg-purple-100 text-purple-800 border-purple-200';
        }
        if (stripos($titolo, 'I/F') !== false) {
            return 'bg-indigo-100 text-indigo-800 border-indigo-200';
        }
        
        return 'bg-gray-100 text-gray-800 border-gray-200';
    }
    
    /**
     * Ottiene la label della tipologia dall'ID settings
     */
    public function getTypeLabel($settingId)
    {
        $type = $this->expirationTypes->firstWhere('id', $settingId);
        return $type ? $type->label : 'Scadenza';
    }
    
    /**
     * Ottiene il colore della tipologia dall'ID settings
     */
    public function getTypeColor($settingId)
    {
        $colors = [
            10 => 'bg-blue-100 text-blue-800 border-blue-200',
            11 => 'bg-red-100 text-red-800 border-red-200',
            12 => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            13 => 'bg-purple-100 text-purple-800 border-purple-200',
            14 => 'bg-gray-100 text-gray-800 border-gray-200',
        ];
        return $colors[$settingId] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }

    /**
     * Ottiene il colore da usare per il badge di una scadenza, dando priorità
     * allo stato "renewed" (sempre verde) rispetto alla tipologia.
     */
    public function getBadgeColorForExpiration($expiration)
    {
        if ($expiration->status === 'renewed') {
            return 'bg-green-100 text-green-800 border-green-200';
        }

        return $expiration->setting
            ? $this->getTypeColor($expiration->id_settings)
            : $this->getTypeColorFromExpiration($expiration);
    }

    /**
     * Ottiene il nome della proprietà (ownership) del veicolo collegato alla scadenza
     */
    public function getOwnershipNameForExpiration($expiration)
    {
        $vehicle = $expiration->vehicles->first();
        if ($vehicle && $vehicle->ownership) {
            return $vehicle->ownership->RagAbbrev ?? $vehicle->ownership->Rag_Soc_intest ?? null;
        }
        return null;
    }
    
    public function getExpirationsProperty()
    {
        $query = Expiration::query()
            ->with(['vehicles.ownership', 'setting'])
            ->where('table_references', 'vehicles')
            ->whereNotNull('data_fine');
        
        // Filtro per stato scadenza
        if ($this->expirationStatus === 'expired') {
            $query->whereDate('data_fine', '<', now());
        } elseif ($this->expirationStatus === 'expiring') {
            $query->whereDate('data_fine', '>=', now())
                ->whereDate('data_fine', '<=', now()->addDays(30));
        } elseif ($this->expirationStatus === 'valid') {
            $query->whereDate('data_fine', '>', now()->addDays(30));
        } elseif ($this->expirationStatus === 'renewed') {
            $query->where('status', 'renewed');
        }
        
        // Filtro per tipologia (id_settings)
        if (!empty($this->selectedType)) {
            $query->where('id_settings', $this->selectedType);
        }
        
        // Filtro per veicolo specifico (id_references)
        if (!empty($this->selectedVehicleId)) {
            $query->where('id_references', $this->selectedVehicleId);
        }
        
        // Filtro per proprietà usando la relazione vehicles
        if (!empty($this->ownershipFilter)) {
            $query->whereHas('vehicles', function($q) {
                $q->where('id_ownership', $this->ownershipFilter);
            });
        }
        
        // Filtro per date
        if ($this->dateFrom) {
            $query->whereDate('data_fine', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('data_fine', '<=', $this->dateTo);
        }
        
        if ($this->view === 'calendar') {
            $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth();
            $query->whereBetween('data_fine', [$startOfMonth, $endOfMonth]);
        }
        
        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }
    
    public function getPaginatedExpirationsProperty()
    {
        $query = Expiration::query()
            ->with(['vehicles.ownership', 'setting'])
            ->where('table_references', 'vehicles')
            ->whereNotNull('data_fine');
        
        // Filtro per stato scadenza
        if ($this->expirationStatus === 'expired') {
            $query->whereDate('data_fine', '<', now());
        } elseif ($this->expirationStatus === 'expiring') {
            $query->whereDate('data_fine', '>=', now())
                ->whereDate('data_fine', '<=', now()->addDays(30));
        } elseif ($this->expirationStatus === 'valid') {
            $query->whereDate('data_fine', '>', now()->addDays(30));
        } elseif ($this->expirationStatus === 'renewed') {
            $query->where('status', 'renewed');
        }

        // Filtro per tipologia (id_settings)
        if (!empty($this->selectedType)) {
            $query->where('id_settings', $this->selectedType);
        }
        
        // Filtro per veicolo specifico (id_references)
        if (!empty($this->selectedVehicleId)) {
            $query->where('id_references', $this->selectedVehicleId);
        }
        
        // Filtro per proprietà usando la relazione vehicles
        if (!empty($this->ownershipFilter)) {
            $query->whereHas('vehicles', function($q) {
                $q->where('id_ownership', $this->ownershipFilter);
            });
        }
        
        // Filtro per date
        if ($this->dateFrom) {
            $query->whereDate('data_fine', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('data_fine', '<=', $this->dateTo);
        }
        
        return $query->orderBy($this->sortField, $this->sortDirection)->paginate($this->perPage);
    }
    
    public function getCalendarDaysProperty()
    {
        $date = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1);
        $daysInMonth = $date->daysInMonth;
        $firstDayOfWeek = $date->dayOfWeek;
        
        $startOffset = ($firstDayOfWeek == 0 ? 6 : $firstDayOfWeek - 1);
        
        $calendarDays = [];
        
        $prevMonth = $date->copy()->subMonth();
        $daysInPrevMonth = $prevMonth->daysInMonth;
        for ($i = $startOffset - 1; $i >= 0; $i--) {
            $day = $daysInPrevMonth - $i;
            $calendarDays[] = (object)[
                'date' => Carbon::createFromDate($prevMonth->year, $prevMonth->month, $day),
                'current_month' => false,
                'expirations' => collect([])
            ];
        }
        
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = Carbon::createFromDate($this->currentYear, $this->currentMonth, $day);
            $calendarDays[] = (object)[
                'date' => $currentDate,
                'current_month' => true,
                'expirations' => collect([])
            ];
        }
        
        $remainingDays = 42 - count($calendarDays);
        $nextMonth = $date->copy()->addMonth();
        for ($day = 1; $day <= $remainingDays; $day++) {
            $calendarDays[] = (object)[
                'date' => Carbon::createFromDate($nextMonth->year, $nextMonth->month, $day),
                'current_month' => false,
                'expirations' => collect([])
            ];
        }
        
        $expirations = $this->expirations;
        foreach ($calendarDays as $calendarDay) {
            $dayExpirations = $expirations->filter(function($exp) use ($calendarDay) {
                return Carbon::parse($exp->data_fine)->format('Y-m-d') === $calendarDay->date->format('Y-m-d');
            });
            $calendarDay->expirations = $dayExpirations;
        }
        
        return collect($calendarDays);
    }
    
    public function getVehicleListProperty()
    {
        return Vehicles::where('valid', 1)
            ->orderBy('targa')
            ->get(['id', 'targa', 'marca', 'modello']);
    }
    
    public function getOwnershipsProperty()
    {
        return Ownership::where('valid', 1)
            ->orderBy('RagAbbrev')
            ->get();
    }
    
    public function getExpirationStatus($date)
    {
        $carbonDate = Carbon::parse($date);
        $now = Carbon::now();
        
        if ($carbonDate->isPast()) {
            return (object)['class' => 'text-red-600 font-bold', 'icon' => 'fa-circle-exclamation'];
        } elseif ($now->diffInDays($carbonDate) <= 30) {
            return (object)['class' => 'text-yellow-600 font-semibold', 'icon' => 'fa-clock'];
        }
        return (object)['class' => 'text-green-600', 'icon' => 'fa-check-circle'];
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
    
    public function resetTableFilters()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->selectedVehicleId = '';
        $this->selectedVehicleName = '';
        $this->vehicleSearch = '';
        $this->expirationStatus = '';
        $this->selectedType = '';
        $this->ownershipFilter = '';
        $this->resetPage();
        
        // Dispatch per resettare il componente date-range-filter
        $this->dispatch('resetDateRangeFilterWithoutApply');
    }
    
    public function clearDates()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDateRangeFilterWithoutApply');
    }
    
    public function getExportPdfUrl()
    {
        $params = [];
        
        if ($this->dateFrom) $params['date_from'] = $this->dateFrom;
        if ($this->dateTo) $params['date_to'] = $this->dateTo;
        if ($this->selectedVehicleId) $params['vehicle_id'] = $this->selectedVehicleId;
        if ($this->expirationStatus) $params['status'] = $this->expirationStatus;
        if (!empty($this->selectedType)) $params['type_id'] = $this->selectedType;
        if (!empty($this->ownershipFilter)) $params['ownership_id'] = $this->ownershipFilter;
        
        return route('admin.vehicles.calendar.export-pdf', $params);
    }
    
    public function getExportExcelUrl()
    {
        $params = [];
        
        if ($this->dateFrom) $params['date_from'] = $this->dateFrom;
        if ($this->dateTo) $params['date_to'] = $this->dateTo;
        if ($this->selectedVehicleId) $params['vehicle_id'] = $this->selectedVehicleId;
        if ($this->expirationStatus) $params['status'] = $this->expirationStatus;
        if (!empty($this->selectedType)) $params['type_id'] = $this->selectedType;
        if (!empty($this->ownershipFilter)) $params['ownership_id'] = $this->ownershipFilter;
        
        return route('admin.vehicles.calendar.export-excel', $params);
    }
    
    public function render()
    {
        return view('livewire.admin.vehicles.vehicle-calendar', [
            'expirations' => $this->expirations,
            'paginatedExpirations' => $this->paginatedExpirations,
            'calendarDays' => $this->calendarDays,
            'vehicleList' => $this->vehicleList,
            'ownerships' => $this->ownerships,
        ]);
    }
}