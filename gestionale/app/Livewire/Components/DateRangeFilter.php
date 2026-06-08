<?php

namespace App\Livewire\Components;

use Livewire\Component;
use Carbon\Carbon;

class DateRangeFilter extends Component
{
    public string $dateFrom = '';
    public string $dateTo   = '';
    public int $selectedMonth;
    public int $selectedYear;
    public string $singleDate = '';
    public ?string $selectedSeason = null;

    protected $listeners = ['resetDates' => 'resetDates', 'resetDateRangeFilterWithoutApply' => 'resetDateRangeFilterWithoutApply'];

    public function mount(): void
    {
        // Se arrivano date dalla sessione/parent, usale
        if (!empty($this->dateFrom) && !empty($this->dateTo)) {
            $date = Carbon::parse($this->dateFrom);
            $this->selectedMonth = $date->month;
            $this->selectedYear  = $date->year;
            // NON chiamare syncDatesFromSelects() — le date sono già settate
        } else {
            // Comportamento originale: mese corrente
            $this->selectedMonth = Carbon::now()->month;
            $this->selectedYear  = Carbon::now()->year;
            $this->syncDatesFromSelects();
        }
    }

    // Ogni volta che cambia il select mese → aggiorna gli input data
    public function updatedSelectedMonth(): void
    {
        $this->syncDatesFromSelects();
        $this->singleDate = '';
        $this->selectedSeason = null;
    }

    // Ogni volta che cambia l'anno (dalle frecce)
    public function updatedSelectedYear(): void
    {
        $this->syncDatesFromSelects();
        $this->singleDate = '';
        $this->selectedSeason = null;
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear  = $date->year;
        $this->syncDatesFromSelects();
        $this->singleDate = '';
        $this->selectedSeason = null;
        
        $this->dispatch('monthYearUpdated', [
            'month' => $this->selectedMonth,
            'year' => $this->selectedYear
        ]);
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear  = $date->year;
        $this->syncDatesFromSelects();
        $this->singleDate = '';
        $this->selectedSeason = null;
        
        $this->dispatch('monthYearUpdated', [
            'month' => $this->selectedMonth,
            'year' => $this->selectedYear
        ]);
    }

    // I campi di sinistra comandano quelli di destra
    private function syncDatesFromSelects(): void
    {
        $this->dateFrom = Carbon::create($this->selectedYear, $this->selectedMonth, 1)
            ->startOfMonth()
            ->format('Y-m-d');

        $this->dateTo = Carbon::create($this->selectedYear, $this->selectedMonth, 1)
            ->endOfMonth()
            ->format('Y-m-d');
    }

    // Aggiorna quando viene selezionata una data singola
    public function updatedSingleDate(string $value): void
    {
        if (!empty($value)) {
            $dateObj = Carbon::parse($value);
            $this->selectedMonth = $dateObj->month;
            $this->selectedYear = $dateObj->year;
            $this->dateFrom = $value;
            $this->dateTo = $value;
            $this->selectedSeason = null;
        } else {
            $this->syncDatesFromSelects();
        }
    }

    // Aggiorna quando viene selezionata una stagione (anno)
    public function updatedSelectedSeason(?string $value): void
    {
        if (!empty($value)) {
            $newYear = (int)$value;

            $this->dateFrom = Carbon::create($newYear, 1, 1)->startOfYear()->format('Y-m-d');
            $this->dateTo   = Carbon::create($newYear, 12, 31)->endOfYear()->format('Y-m-d');

            $this->singleDate     = '';
            $this->selectedMonth  = 1;
            $this->selectedYear   = $newYear;

        } else {
            $currentDate = Carbon::now();
            $this->selectedMonth = $currentDate->month;
            $this->selectedYear  = $currentDate->year;
            $this->dateFrom      = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->dateTo        = Carbon::now()->endOfMonth()->format('Y-m-d');
            $this->singleDate    = '';
        }
    }

    public function updatedDateFrom(): void
    {
        // opzionale: validazione o sync
    }

    public function updatedDateTo(): void
    {
        // opzionale: validazione o sync
    }

    // Solo questo dispatcha alla tabella
    public function applyFilters(): void
    {
        $this->dispatch('dateRangeUpdated', [
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ]);
    }

    // Chiamato dal parent quando resetta i filtri
    public function resetDates(): void
    {
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear  = Carbon::now()->year;
        $this->singleDate = '';
        $this->selectedSeason = null;
        $this->syncDatesFromSelects();
        $this->applyFilters();
    }

    public function resetDateRangeFilterWithoutApply(): void
    {
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        $this->singleDate = '';
        $this->selectedSeason = null;
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        $this->dispatch('dateRangeUpdated', [
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    public function clearDates(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->singleDate = '';
        $this->selectedSeason = null;
        $this->syncDatesFromSelects();
        $this->applyFilters();
    }

    public function getYearsProperty(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];
        for ($i = 15; $i >= 0; $i--) {
            $years[] = $currentYear - $i;
        }
        return $years;
    }

    public function getSeasonYearsProperty(): array
    {
        $currentYear = Carbon::now()->year;
        $years = [];
        for ($i = 9; $i >= 0; $i--) {
            $years[] = $currentYear - $i;
        }
        return $years;
    }

    public function getMonthsProperty(): array
    {
        return [
            1  => 'Gennaio',
            2  => 'Febbraio',
            3  => 'Marzo',
            4  => 'Aprile',
            5  => 'Maggio',
            6  => 'Giugno',
            7  => 'Luglio',
            8  => 'Agosto',
            9  => 'Settembre',
            10 => 'Ottobre',
            11 => 'Novembre',
            12 => 'Dicembre',
        ];
    }

    public function render()
    {
        return view('components.date-range-filter', [
            'years'  => $this->years,
            'months' => $this->months,
            'seasonYears' => $this->seasonYears,
        ]);
    }
}