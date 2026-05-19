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
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear  = Carbon::now()->year;
        $this->syncDatesFromSelects();
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
        
        // Forza l'aggiornamento della select
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
        
        // Forza l'aggiornamento della select
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
            $year = (int)$value;
            
            // Mantieni il mese corrente invece di forzare a Gennaio
            $currentMonth = Carbon::now()->month;
            $currentDay = Carbon::now()->day;
            
            $this->selectedYear = $year;
            $this->selectedMonth = $currentMonth;
            $this->singleDate = '';
            
            // Calcola le nuove date mantenendo lo stesso giorno/mese dell'anno selezionato
            try {
                // Cerca di mantenere lo stesso giorno e mese nell'anno selezionato
                $newDate = Carbon::create($year, $currentMonth, $currentDay);
                
                // Se la data non esiste (es. 31 febbraio), prendi l'ultimo giorno del mese
                if (!$newDate->isValid()) {
                    $newDate = Carbon::create($year, $currentMonth, 1)->endOfMonth();
                }
                
                // Imposta dateFrom e dateTo con la stessa data (modalità data singola)
                $this->dateFrom = $newDate->format('Y-m-d');
                $this->dateTo = $newDate->format('Y-m-d');
            } catch (\Exception $e) {
                // Fallback: primo giorno del mese selezionato
                $this->dateFrom = Carbon::create($year, $currentMonth, 1)->format('Y-m-d');
                $this->dateTo = Carbon::create($year, $currentMonth, 1)->format('Y-m-d');
            }
            
        } else {
            // Se viene deselezionata la stagione, resetta ai valori attuali (oggi)
            $currentDate = Carbon::now();
            $this->selectedMonth = $currentDate->month;
            $this->selectedYear = $currentDate->year;
            $this->dateFrom = $currentDate->format('Y-m-d');
            $this->dateTo = $currentDate->format('Y-m-d');
            $this->singleDate = '';
        }
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
        // Resetta al mese corrente
        $this->selectedMonth = Carbon::now()->month;
        $this->selectedYear = Carbon::now()->year;
        $this->singleDate = '';
        $this->selectedSeason = null;
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

    // Ultimi 10 anni per la stagione
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