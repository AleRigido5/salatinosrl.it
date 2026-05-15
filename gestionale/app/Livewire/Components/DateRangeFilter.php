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

    protected $listeners = ['resetDates' => 'resetDates', 'resetDateRangeFilterWithoutApply' => 'resetDateRangeFilterWithoutApply'];

    public function mount(): void
    {
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear  = Carbon::now()->year;
        $this->syncDatesFromSelects();
    }

    // Ogni volta che cambia il select mese → aggiorna gli input data
    public function updatedSelectedMonth(): void
    {
        $this->syncDatesFromSelects();
    }

    // Ogni volta che cambia il select anno → aggiorna gli input data
    public function updatedSelectedYear(): void
    {
        $this->syncDatesFromSelects();
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->subMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear  = $date->year;
        $this->syncDatesFromSelects();
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->addMonth();
        $this->selectedMonth = $date->month;
        $this->selectedYear  = $date->year;
        $this->syncDatesFromSelects();
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
        $this->syncDatesFromSelects();
        $this->applyFilters();
    }

    public function resetDateRangeFilterWithoutApply(): void
    {
        // Resetta al mese corrente
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        
        // NON chiamare applyFilters() per non riattivare il filtro!
        // Invia comunque le date vuote alla tabella principale
        $this->dispatch('dateRangeUpdated', [
            'date_from' => '',
            'date_to' => '',
        ]);
    }

    public function clearDates(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
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
        ]);
    }
}