<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceSent;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class InvoiceSentStatistics extends Component
{
    // Autocomplete Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;

    // Autocomplete Cliente
    public string $customerSearch = '';
    public Collection $customerResults;
    public string $selectedCustomerId = '';
    public string $selectedCustomerName = '';
    public bool $showCustomerDropdown = false;

    // Autocomplete Centro di Costo
    public string $costCenterSearch = '';
    public Collection $costCenterResults;
    public string $selectedCostCenterId = '';
    public string $selectedCostCenterName = '';
    public bool $showCostCenterDropdown = false;

    // Date
    public string $dateFrom = '';
    public string $dateTo = '';

    // Statistiche
    public string $statPeriod = 'monthly';
    public Collection $statistics;
    public string $periodDisplay = '';

    // Statistiche mensili
    public Collection $monthlyStatistics;

    // FIX: totale "vero", calcolato direttamente da importo_totale (stessa
    // fonte usata dall'elenco Fatture di Vendita), non ricostruito
    // sommando le righe. Vedi calculateTotalFatturato() per il motivo.
    public float $totalFatturato = 0;

    // Tipi documento SDI trattati come nota di credito: importo_totale (e i
    // relativi vatSummaries) sono salvati POSITIVI nel DB ma vanno DETRATTI
    // dai totali/statistiche, non sommati — stessa convenzione già usata
    // in InvoiceSentTable (elenco fatture di vendita) per footerTotals.
    protected const CREDIT_NOTE_TYPES = ['TD04', 'TD08'];

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->customerResults = new Collection();
        $this->costCenterResults = new Collection();

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

        $this->refreshAllData();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->refreshAllData();
    }

    // ==================== STATISTICHE ====================
    public function refreshStats(): void
    {
        $this->refreshAllData();
    }

    public function updatedStatPeriod(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->refreshAllData();
        $this->dispatch('resetDates');
    }

    /**
     * Ricalcola tutto ciò che dipende dai filtri correnti in un unico punto,
     * per evitare che una chiamata dimenticata in giro per il componente
     * lasci la pagina con dati non allineati ai filtri (è già successo).
     */
    protected function refreshAllData(): void
    {
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
        $this->monthlyStatistics = $this->calculateMonthlyStatistics();
        $this->totalFatturato = $this->calculateTotalFatturato();
    }

    /**
     * Query di base con i filtri Proprietà / Cliente / Centro di Costo e la
     * data, SENZA alcun filtro di stato: è la stessa identica combinazione
     * di filtri usata da InvoiceSentTable::baseFilteredQuery() per il
     * "Totale Fatturato" dell'elenco fatture. Riutilizzata sia per il
     * totale "vero" sia per la statistica per categoria, così le due
     * pagine partono sempre dallo stesso insieme di fatture.
     */
    protected function baseFilteredInvoiceQuery()
    {
        $query = InvoiceSent::query()
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedCustomerId, fn($q) => $q->where('id_entities', $this->selectedCustomerId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            });

        $this->applyDateFilter($query);

        return $query;
    }

    /**
     * FIX (bug principale segnalato): il "Totale Fatturato" mostrato in
     * pagina veniva ricostruito sommando row->total per ogni riga di ogni
     * fattura (vedi calculateStatistics). Quel valore può divergere da
     * importo_totale per arrotondamenti, sconti applicati diversamente, o
     * righe non perfettamente allineate al totale della fattura.
     * L'elenco Fatture di Vendita invece somma direttamente importo_totale
     * (nessuna ricostruzione dalle righe). Per garantire che i due numeri
     * combacino SEMPRE, calcoliamo qui il totale nello stesso modo
     * dell'elenco: somma diretta di importo_totale sulla stessa query
     * filtrata, senza passare dalle righe.
     */
    protected function calculateTotalFatturato(): float
    {
        return (float) $this->baseFilteredInvoiceQuery()->sum('importo_totale');
    }

    protected function calculateStatistics(): Collection
    {
        $query = InvoiceSent::query()
            ->with(['rows.service.category'])
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedCustomerId, fn($q) => $q->where('id_entities', $this->selectedCustomerId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            });

        $this->applyDateFilter($query);

        $invoices = $query->get();

        $stats = collect();

        foreach ($invoices as $invoice) {
            $rowSum = (float) $invoice->rows->sum('total');
            $invoiceTotal = (float) $invoice->importo_totale;

            // FIX: invece di sommare row->total così com'è, distribuiamo
            // l'importo REALE della fattura (importo_totale) tra le righe in
            // proporzione al peso di ciascuna riga sul totale grezzo delle
            // righe stesse. In condizioni normali (somma righe = importo
            // fattura) questo dà lo stesso risultato di prima. Ma se per una
            // fattura la somma delle righe NON coincide con importo_totale
            // (nota di credito con convenzione di segno diversa a livello
            // riga, sconto globale non ripartito sulle righe, modifica
            // manuale, arrotondamenti) la somma per categoria continua a
            // corrispondere ESATTAMENTE al Totale Fatturato reale, invece di
            // sforare il 100% (come si vedeva: 85.123,00 € / 135.5% contro
            // un totale vero di 62.805,86 €).
            $scaleFactor = ($rowSum != 0.0) ? ($invoiceTotal / $rowSum) : 0.0;

            foreach ($invoice->rows as $row) {
                $categoryName = 'Non categorizzato';

                if ($row->service && $row->service->category) {
                    $categoryName = $row->service->category->valore ?? 'Non categorizzato';
                }

                $contributedAmount = $rowSum != 0.0
                    ? ((float) $row->total) * $scaleFactor
                    : 0.0;

                $existing = $stats->firstWhere('service_category', $categoryName);

                if ($existing) {
                    $existing->total += $contributedAmount;
                    $existing->count += 1;
                } else {
                    $stats->push((object) [
                        'service_category' => $categoryName,
                        'total' => $contributedAmount,
                        'count' => 1,
                    ]);
                }
            }
        }

        return $stats->sortByDesc('total')->values();
    }

    /**
     * Restituisce "NomeMese Anno" in italiano, indipendentemente dalla
     * locale configurata a livello di server/applicazione (non ci si
     * affida a translatedFormat/setLocale, che richiedono i file di
     * traduzione Carbon installati e correttamente attivi).
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

    /**
     * Calcola il fatturato suddiviso per mese: Imponibile, IVA, Totale
     * Fattura e N. Fatture. Rispetta i filtri di Proprietà / Cliente /
     * Centro di Costo. Se sono impostate date personalizzate
     * (dateFrom/dateTo) usa quel range, altrimenti mostra sempre gli
     * ultimi 12 mesi (indipendentemente dal selettore
     * Mensile/Trimestrale/Semestrale/Annuale).
     *
     * Le note di credito (TD04/TD08) vengono sottratte (Imponibile, IVA e
     * Totale Fattura), non sommate — stessa convenzione già applicata in
     * InvoiceSentTable::getFooterTotalsProperty(), così i totali di questa
     * pagina restano coerenti con quelli dell'elenco fatture.
     */
    protected function calculateMonthlyStatistics(): Collection
    {
        $rangeStart = $this->dateFrom
            ? Carbon::parse($this->dateFrom)->startOfMonth()
            : now()->subMonths(11)->startOfMonth();

        $rangeEnd = $this->dateTo
            ? Carbon::parse($this->dateTo)->endOfMonth()
            : now()->endOfMonth();

        // Protezione: se il range personalizzato è invertito o troppo ampio, normalizza
        if ($rangeStart->gt($rangeEnd)) {
            $rangeStart = $rangeEnd->copy()->startOfMonth();
        }

        $query = InvoiceSent::query()
            ->with(['vatSummaries'])
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedCustomerId, fn($q) => $q->where('id_entities', $this->selectedCustomerId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->whereDate('data_invoice', '>=', $rangeStart->format('Y-m-d'))
            ->whereDate('data_invoice', '<=', $rangeEnd->format('Y-m-d'));

        $invoices = $query->get();

        // Prepara tutti i mesi del range, inizializzati a zero (così il grafico è continuo)
        $months = collect();
        $cursor = $rangeStart->copy();
        while ($cursor->lte($rangeEnd)) {
            $months->put($cursor->format('Y-m'), (object) [
                'month_key' => $cursor->format('Y-m'),
                'month_label' => $this->italianMonthLabel($cursor),
                'imponibile' => 0.0,
                'iva' => 0.0,
                'total' => 0.0,
                'count' => 0,
            ]);
            $cursor->addMonth();
        }

        foreach ($invoices as $invoice) {
            $monthKey = Carbon::parse($invoice->data_invoice)->format('Y-m');

            if (!$months->has($monthKey)) {
                continue;
            }

            $isCreditNote = in_array($invoice->type_invoice, self::CREDIT_NOTE_TYPES);
            $sign = $isCreditNote ? -1 : 1;

            // FIX: usa importo_totale/vatSummaries della fattura (come
            // l'elenco fatture) invece di ri-sommare le righe, per lo
            // stesso motivo spiegato in calculateTotalFatturato().
            $months[$monthKey]->imponibile += $sign * (float) $invoice->vatSummaries->sum('taxable_amount');
            $months[$monthKey]->iva += $sign * (float) $invoice->vatSummaries->sum('tax_amount');
            $months[$monthKey]->total += $sign * (float) $invoice->importo_totale;
            $months[$monthKey]->count += 1;
        }

        return $months->values();
    }

    protected function applyDateFilter($query): void
    {
        if ($this->dateFrom) {
            $query->whereDate('data_invoice', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('data_invoice', '<=', $this->dateTo);
        }

        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $now = now();
            switch ($this->statPeriod) {
                case 'monthly':
                    $startDate = $now->copy()->startOfMonth();
                    break;
                case 'quarterly':
                    $startDate = $now->copy()->subMonths(3)->startOfMonth();
                    break;
                case 'semestral':
                    $startDate = $now->copy()->subMonths(6)->startOfMonth();
                    break;
                case 'yearly':
                    $startDate = $now->copy()->subYear()->startOfMonth();
                    break;
                default:
                    $startDate = $now->copy()->startOfMonth();
            }
            $query->whereDate('data_invoice', '>=', $startDate->format('Y-m-d'));
            $query->whereDate('data_invoice', '<=', $now->format('Y-m-d'));
        }
    }

    protected function getPeriodDisplay(): string
    {
        $now = now();

        if (empty($this->dateFrom) && empty($this->dateTo)) {
            switch ($this->statPeriod) {
                case 'monthly':
                    return "Mese corrente: " . $now->format('F Y');
                case 'quarterly':
                    return "Ultimi 3 mesi: da " . $now->copy()->subMonths(3)->format('d/m/Y') . " al " . $now->format('d/m/Y');
                case 'semestral':
                    return "Ultimi 6 mesi: da " . $now->copy()->subMonths(6)->format('d/m/Y') . " al " . $now->format('d/m/Y');
                case 'yearly':
                    return "Ultimo anno: da " . $now->copy()->subYear()->format('d/m/Y') . " al " . $now->format('d/m/Y');
                default:
                    return "Periodo selezionato";
            }
        }

        $from = $this->dateFrom ? date('d/m/Y', strtotime($this->dateFrom)) : '';
        $to = $this->dateTo ? date('d/m/Y', strtotime($this->dateTo)) : '';

        if ($from && $to) {
            return "Dal {$from} al {$to} (personalizzato)";
        }

        return "Periodo selezionato";
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        if (empty(trim($this->ownershipSearch))) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            $this->refreshAllData();
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
        $this->refreshAllData();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
        $this->dispatch('clearOwnershipInput');
        $this->refreshAllData();
    }

    // ==================== AUTOCOMPLETE CLIENTE ====================
    public function updatedCustomerSearch(): void
    {
        if (!empty($this->selectedCustomerId) && $this->customerSearch === $this->selectedCustomerName) {
            $this->showCustomerDropdown = false;
            return;
        }

        if (empty($this->customerSearch)) {
            $this->selectedCustomerId = '';
            $this->selectedCustomerName = '';
            $this->customerResults = new Collection();
            $this->showCustomerDropdown = false;
            $this->refreshAllData();
            return;
        }

        if (!empty($this->selectedCustomerId) && $this->customerSearch !== $this->selectedCustomerName) {
            $this->selectedCustomerId = '';
            $this->selectedCustomerName = '';
        }

        if (strlen($this->customerSearch) < 2) {
            $this->customerResults = new Collection();
            $this->showCustomerDropdown = false;
            return;
        }

        $this->customerResults = Entity::where('valid', 1)
            ->whereIn('entity_type', ['cliente', 'entrambi'])
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('nome', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('cognome', 'like', '%' . $this->customerSearch . '%')
                  ->orWhere('partita_iva', 'like', '%' . $this->customerSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);

        $this->showCustomerDropdown = $this->customerResults->isNotEmpty();
    }

    public function selectCustomer($id, string $name): void
    {
        $this->selectedCustomerId = (string)$id;
        $this->selectedCustomerName = $name;
        $this->customerSearch = $name;
        $this->showCustomerDropdown = false;
        $this->refreshAllData();
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = '';
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
        $this->dispatch('clearCustomerInput');
        $this->refreshAllData();
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch(): void
    {
        if (!empty($this->selectedCostCenterId) && $this->costCenterSearch === $this->selectedCostCenterName) {
            $this->showCostCenterDropdown = false;
            return;
        }

        if (empty($this->costCenterSearch)) {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            $this->refreshAllData();
            return;
        }

        if (!empty($this->selectedCostCenterId) && $this->costCenterSearch !== $this->selectedCostCenterName) {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
        }

        if (strlen($this->costCenterSearch) < 2) {
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            return;
        }

        $this->costCenterResults = CostCenter::where('Nome', 'like', '%' . $this->costCenterSearch . '%')
            ->limit(10)
            ->get(['id', 'Nome', 'Localita']);

        $this->showCostCenterDropdown = $this->costCenterResults->isNotEmpty();
    }

    public function selectCostCenter($id, string $name): void
    {
        $this->selectedCostCenterId = (string)$id;
        $this->selectedCostCenterName = $name;
        $this->costCenterSearch = $name;
        $this->showCostCenterDropdown = false;
        $this->refreshAllData();
    }

    public function clearCostCenter(): void
    {
        $this->selectedCostCenterId = '';
        $this->selectedCostCenterName = '';
        $this->costCenterSearch = '';
        $this->dispatch('clearCostCenterInput');
        $this->refreshAllData();
    }

    public function render()
    {
        return view('livewire.admin.invoice-sent-statistics');
    }
}