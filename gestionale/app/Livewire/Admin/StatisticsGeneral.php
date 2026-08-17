<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class StatisticsGeneral extends Component
{
    // Autocomplete Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;

    // Date
    public string $dateFrom = '';
    public string $dateTo = '';

    // Selettore "Anno": è solo un helper lato client (Alpine/JS) che
    // compila i campi Dal/Al — non è collegato a wire:model, quindi
    // cambiarlo NON invia nulla al server né aggiorna la tabella.
    // selectedYear serve solo per pre-selezionare l'opzione corretta al
    // primo caricamento della pagina.
    public string $selectedYear = '';
    public array $availableYears = [];

    public Collection $monthlyStatistics;

    // Tipi documento SDI trattati come nota di credito: importo_totale (e i
    // relativi vatSummaries) sono salvati POSITIVI nel DB ma vanno DETRATTI
    // dai totali, non sommati — stessa convenzione già usata nelle altre
    // pagine statistiche (InvoiceSentStatistics, footerTotals di
    // InvoiceSentTable).
    protected const CREDIT_NOTE_TYPES = ['TD04', 'TD08'];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();

        // ============================================
        // DEFAULT: Proprietà "Agr Srl" preselezionata e range data
        // sull'anno 2026 (01/01/2026 - 31/12/2026).
        // ============================================
        $defaultOwnership = Ownership::where(function ($q) {
                $q->whereRaw('LOWER(RagAbbrev) LIKE ?', ['agr%'])
                  ->orWhereRaw('LOWER(Rag_Soc_intest) LIKE ?', ['agr%']);
            })
            ->first();

        if ($defaultOwnership) {
            $this->selectedOwnershipId = (string) $defaultOwnership->id_proprieta;
            $this->selectedOwnershipName = $defaultOwnership->RagAbbrev ?? $defaultOwnership->Rag_Soc_intest;
            $this->ownershipSearch = $this->selectedOwnershipName;
        }

        $this->dateFrom = '2026-01-01';
        $this->dateTo = '2026-12-31';
        $this->selectedYear = '2026';

        $currentYear = (int) now()->format('Y');
        // Range statico di anni selezionabili: dal 2023 all'anno prossimo.
        $this->availableYears = range($currentYear + 1, 2023);

        $this->refreshAllData();
    }

    /**
     * Unico punto di ricalcolo dati: eseguito SOLO al click su "Applica"
     * (wire:click="refreshStats" sul bottone). Cambiare Proprietà, Anno o
     * le date, di per sé, non aggiorna più la tabella.
     */
    public function refreshStats(): void
    {
        $this->refreshAllData();
    }

    protected function refreshAllData(): void
    {
        $this->monthlyStatistics = $this->calculateMonthlyStatistics();
    }

    /**
     * Restituisce "NomeMese Anno" in italiano, indipendentemente dalla
     * locale configurata a livello di server/applicazione — stesso
     * approccio già usato in InvoiceSentStatistics::italianMonthLabel().
     */
    protected function italianMonthLabel(Carbon $date): string
    {
        static $months = [
            1 => 'Gennaio', 2 => 'Febbraio', 3 => 'Marzo', 4 => 'Aprile',
            5 => 'Maggio', 6 => 'Giugno', 7 => 'Luglio', 8 => 'Agosto',
            9 => 'Settembre', 10 => 'Ottobre', 11 => 'Novembre', 12 => 'Dicembre',
        ];

        return $months[$date->month] . ' ' . $date->format('Y');
    }

    protected function monthRange(): array
    {
        $rangeStart = $this->dateFrom
            ? Carbon::parse($this->dateFrom)->startOfMonth()
            : now()->subMonths(11)->startOfMonth();

        $rangeEnd = $this->dateTo
            ? Carbon::parse($this->dateTo)->endOfMonth()
            : now()->endOfMonth();

        if ($rangeStart->gt($rangeEnd)) {
            $rangeStart = $rangeEnd->copy()->startOfMonth();
        }

        return [$rangeStart, $rangeEnd];
    }

    protected function emptyMonthsSkeleton(Carbon $rangeStart, Carbon $rangeEnd): Collection
    {
        $months = collect();
        $cursor = $rangeStart->copy();

        while ($cursor->lte($rangeEnd)) {
            $months->put($cursor->format('Y-m'), (object) [
                'month_key' => $cursor->format('Y-m'),
                'month_label' => $this->italianMonthLabel($cursor),
                'vendite_imponibile' => 0.0,
                'vendite_iva' => 0.0,
                'vendite_totale' => 0.0,
                'acquisti_imponibile' => 0.0,
                'acquisti_iva' => 0.0,
                'acquisti_totale' => 0.0,
                'differenza_iva' => 0.0,
            ]);
            $cursor->addMonth();
        }

        return $months;
    }

    /**
     * Calcola, per ogni mese del range, Imponibile/IVA/Totale sia per le
     * Fatture di Vendita (InvoiceSent) sia per le Fatture di Acquisto
     * (InvoiceReceived), filtrate sulla stessa Proprietà, e la Differenza
     * IVA (IVA vendite - IVA acquisti):
     * - positiva  -> IVA A DEBITO (evidenziata in rosso)
     * - negativa  -> IVA A CREDITO (evidenziata in verde)
     *
     * Le note di credito (TD04/TD08), sia lato vendite che lato acquisti,
     * vengono sottratte anziché sommate — importo_totale è sempre salvato
     * positivo in DB.
     *
     * FIX lato VENDITE: alcune fatture importate da XML con righe "a
     * detrarre" (acconti già fatturati, importo negativo) hanno il
     * riepilogo invoice_vat_summaries INCOMPLETO — le righe negative non
     * vengono scritte in quella tabella, quindi imponibile/IVA calcolati
     * da vatSummaries risultano gonfiati (es. fattura 90/A: 36.423 €
     * invece dei 7.303 € netti). Le righe in invoice_rows sono invece
     * sempre corrette e complete, quindi per le vendite calcoliamo
     * imponibile e IVA direttamente da lì, raggruppando per aliquota come
     * fa InvoiceSentEditController::calculateVatSummaryFromRows.
     *
     * NOTA lato ACQUISTI: il blocco InvoiceReceived qui sotto usa ancora
     * vatSummaries invariato — non è stato ancora verificato se
     * InvoiceReceived abbia una relazione "rows" analoga a InvoiceSent con
     * lo stesso schema (document_id/document_type/total/vat_rate). Prima
     * di applicare la stessa fix anche qui serve il modello
     * InvoiceReceived.php per confermarlo, altrimenti si rischia di
     * rompere il calcolo lato acquisti.
     */
    protected function calculateMonthlyStatistics(): Collection
    {
        [$rangeStart, $rangeEnd] = $this->monthRange();
        $months = $this->emptyMonthsSkeleton($rangeStart, $rangeEnd);

        $sentInvoices = InvoiceSent::query()
            ->with(['rows'])
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->whereDate('data_invoice', '>=', $rangeStart->format('Y-m-d'))
            ->whereDate('data_invoice', '<=', $rangeEnd->format('Y-m-d'))
            ->get();

        foreach ($sentInvoices as $invoice) {
            $monthKey = Carbon::parse($invoice->data_invoice)->format('Y-m');
            if (!$months->has($monthKey)) {
                continue;
            }

            $sign = in_array($invoice->type_invoice, self::CREDIT_NOTE_TYPES) ? -1 : 1;

            $imponibileFattura = (float) $invoice->rows->sum('total');
            $ivaFattura = (float) $invoice->rows->sum(function ($row) {
                return (float) $row->total * ((float) $row->vat_rate / 100);
            });

            $months[$monthKey]->vendite_imponibile += $sign * $imponibileFattura;
            $months[$monthKey]->vendite_iva += $sign * $ivaFattura;
            $months[$monthKey]->vendite_totale += $sign * (float) $invoice->importo_totale;
        }

        $receivedInvoices = InvoiceReceived::query()
            ->with(['vatSummaries'])
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->whereDate('data_invoice', '>=', $rangeStart->format('Y-m-d'))
            ->whereDate('data_invoice', '<=', $rangeEnd->format('Y-m-d'))
            ->get();

        foreach ($receivedInvoices as $invoice) {
            $monthKey = Carbon::parse($invoice->data_invoice)->format('Y-m');
            if (!$months->has($monthKey)) {
                continue;
            }

            $sign = in_array($invoice->type_invoice, self::CREDIT_NOTE_TYPES) ? -1 : 1;

            $months[$monthKey]->acquisti_imponibile += $sign * (float) $invoice->vatSummaries->sum('taxable_amount');
            $months[$monthKey]->acquisti_iva += $sign * (float) $invoice->vatSummaries->sum('tax_amount');
            $months[$monthKey]->acquisti_totale += $sign * (float) $invoice->importo_totale;
        }

        return $months->values()->map(function ($month) {
            $month->differenza_iva = $month->vendite_iva - $month->acquisti_iva;
            return $month;
        });
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    // NOTA: questi metodi aggiornano solo la ricerca/il dropdown delle
    // proprietà — NON richiamano più refreshAllData(). La tabella si
    // aggiorna esclusivamente al click su "Applica" (vedi refreshStats()).
    public function updatedOwnershipSearch(): void
    {
        if (empty(trim($this->ownershipSearch))) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch !== $this->selectedOwnershipName) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
        }

        if (strlen(trim($this->ownershipSearch)) < 2) {
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            return;
        }

        $this->ownershipResults = Ownership::where(function($q) {
                $q->where('RagAbbrev', 'like', '%' . $this->ownershipSearch . '%')
                ->orWhere('Rag_Soc_intest', 'like', '%' . $this->ownershipSearch . '%')
                ->orWhere('RagSocialePr', 'like', '%' . $this->ownershipSearch . '%');
            })
            ->limit(10)
            ->get(['id_proprieta as id', 'RagAbbrev as name', 'Rag_Soc_intest as ragione_sociale', 'valid']);

        $this->showOwnershipDropdown = $this->ownershipResults->isNotEmpty();
    }

    public function selectOwnership($id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
        $this->dispatch('clearOwnershipInput');
    }

    public function render()
    {
        return view('livewire.admin.statistics-general');
    }
}