<?php
// app/Livewire/Admin/StaffCalendar.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Staff;
use App\Models\Expiration;
use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffCalendar extends Component
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
    
    // Autocomplete Personale
    public string $staffSearch = '';
    public Collection $staffResults;
    public string $selectedStaffId = '';
    public string $selectedStaffName = '';
    public bool $showStaffDropdown = false;
    
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
        
        // Inizializza le Collection
        $this->expirationTypes = new Collection();
        $this->staffResults = new Collection();
        
        // Carica i tipi di scadenza disponibili
        $types = Setting::where('tabella_riferimento', 'staff_expiration')
            ->orWhere(function($q) {
                $q->where('tabella_riferimento', 'staff')
                  ->whereIn('valore', ['Assunzione', 'Visita medica']);
            })
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
            $this->expirationTypes = collect([
                (object)['id' => 7, 'label' => 'Assunzione', 'type' => 'scadenza'],
                (object)['id' => 8, 'label' => 'Visita medica', 'type' => 'scadenza'],
            ]);
        }
        
        $this->selectedType = '';
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

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
        $this->resetPage();
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
    
    // ==================== AUTOCOMPLETE PERSONALE ====================
    public function updatedStaffSearch(): void
    {
        if ($this->selectedStaffId && $this->staffSearch === $this->selectedStaffName) {
            $this->showStaffDropdown = false;
            return;
        }

        if ($this->selectedStaffId) {
            $this->selectedStaffId = '';
            $this->selectedStaffName = '';
            $this->resetPage();
        }

        if (strlen($this->staffSearch) < 2) {
            $this->staffResults = new Collection();
            $this->showStaffDropdown = false;
            return;
        }

        $this->staffResults = Staff::where('valid', 1)
            ->where(function($q) {
                $q->where('NomePers', 'like', '%' . $this->staffSearch . '%')
                  ->orWhere('CognomePers', 'like', '%' . $this->staffSearch . '%')
                  ->orWhereRaw("CONCAT(NomePers, ' ', CognomePers) LIKE ?", ['%' . $this->staffSearch . '%'])
                  ->orWhereRaw("CONCAT(CognomePers, ' ', NomePers) LIKE ?", ['%' . $this->staffSearch . '%']);
            })
            ->limit(10)
            ->get(['id_personale', 'NomePers', 'CognomePers']);
        
        $this->showStaffDropdown = $this->staffResults->isNotEmpty();
    }

    public function selectStaff($id, $name): void
    {
        $this->selectedStaffId = (string)$id;
        $this->selectedStaffName = $name;
        $this->staffSearch = $name;
        $this->showStaffDropdown = false;
        $this->resetPage();
        $this->dispatch('refreshCalendar');
    }

    public function clearStaff(): void
    {
        $this->selectedStaffId = '';
        $this->selectedStaffName = '';
        $this->staffSearch = '';
        $this->resetPage();
        $this->dispatch('refreshCalendar');
        $this->dispatch('clearStaffInput');
    }
    
    public function getExpirationsProperty()
    {
        $query = Expiration::query()
            ->with(['staff', 'setting', 'ownershipLegacy'])
            ->where('table_references', 'staff')
            ->whereNotNull('data_fine');

        if ($this->expirationStatus === 'expired') {
            $query->whereDate('data_fine', '<', now());
        } elseif ($this->expirationStatus === 'expiring') {
            $query->whereDate('data_fine', '>=', now())
                ->whereDate('data_fine', '<=', now()->addDays(30));
        } elseif ($this->expirationStatus === 'valid') {
            $query->whereDate('data_fine', '>', now()->addDays(30));
        }

        if (!empty($this->selectedType)) {
            $query->where('id_settings', $this->selectedType);
        }

        if (!empty($this->selectedStaffId)) {
            $query->where('id_references', $this->selectedStaffId);
        }

        if ($this->view === 'calendar') {
            $startOfMonth = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->startOfMonth();
            $endOfMonth   = Carbon::createFromDate($this->currentYear, $this->currentMonth, 1)->endOfMonth();
            $query->whereBetween('data_fine', [$startOfMonth, $endOfMonth]);
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function getPaginatedExpirationsProperty()
    {
        $query = Expiration::query()
            ->with(['staff', 'setting', 'ownershipLegacy'])
            ->where('table_references', 'staff')
            ->whereNotNull('data_fine');

        if ($this->expirationStatus === 'expired') {
            $query->whereDate('data_fine', '<', now());
        } elseif ($this->expirationStatus === 'expiring') {
            $query->whereDate('data_fine', '>=', now())
                ->whereDate('data_fine', '<=', now()->addDays(30));
        } elseif ($this->expirationStatus === 'valid') {
            $query->whereDate('data_fine', '>', now()->addDays(30));
        }

        if (!empty($this->selectedType)) {
            $query->where('id_settings', $this->selectedType);
        }

        if (!empty($this->selectedStaffId)) {
            $query->where('id_references', $this->selectedStaffId);
        }

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
    
    public function getTypeLabel($settingId)
    {
        $type = $this->expirationTypes->firstWhere('id', $settingId);
        return $type ? $type->label : 'Scadenza';
    }
    
    public function getTypeColor($settingId)
    {
        $colors = [
            7 => 'bg-blue-100 text-blue-800 border-blue-200',
            8 => 'bg-red-100 text-red-800 border-red-200',
        ];
        return $colors[$settingId] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }
    
    public function getStaffGroupsProperty()
    {
        return Setting::where('tabella_riferimento', 'staff')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    public function getStaffListProperty()
    {
        return Staff::where('valid', 1)
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get(['id_personale', 'NomePers', 'CognomePers']);
    }
    
    public function formatExpirationDate($date)
    {
        if (!$date) return '-';
        return Carbon::parse($date)->format('d/m/Y');
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
    
    public function clearDates(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDates'); 
    }

    public function resetTableFilters(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->selectedStaffId = '';
        $this->selectedStaffName = '';
        $this->staffSearch = '';
        $this->expirationStatus = '';
        $this->selectedType = '';
        $this->resetPage();
        $this->dispatch('resetDates');  
    }
    
    public function getExportPdfUrl()
    {
        $params = [];
        
        if ($this->dateFrom) $params['date_from'] = $this->dateFrom;
        if ($this->dateTo) $params['date_to'] = $this->dateTo;
        if ($this->selectedStaffId) $params['staff_id'] = $this->selectedStaffId;
        if ($this->expirationStatus) $params['status'] = $this->expirationStatus;
        if (!empty($this->selectedType)) $params['type_id'] = $this->selectedType;
        
        return route('admin.staff.calendar.export-pdf', $params);
    }
    
    public function getExportExcelUrl()
    {
        $params = [];
        
        if ($this->dateFrom) $params['date_from'] = $this->dateFrom;
        if ($this->dateTo) $params['date_to'] = $this->dateTo;
        if ($this->selectedStaffId) $params['staff_id'] = $this->selectedStaffId;
        if ($this->expirationStatus) $params['status'] = $this->expirationStatus;
        if (!empty($this->selectedType)) $params['type_id'] = $this->selectedType;
        
        return route('admin.staff.calendar.export-excel', $params);
    }
    
    public function render()
    {
        Carbon::setLocale('it');
        
        return view('livewire.admin.staff.staff-calendar', [
            'expirations' => $this->expirations,
            'paginatedExpirations' => $this->paginatedExpirations,
            'calendarDays' => $this->calendarDays,
            'staffList' => $this->staffList,
            'staffGroups' => $this->staffGroups,
        ]);
    }
}