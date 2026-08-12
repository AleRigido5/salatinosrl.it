<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;

class InvoicesReceivedStatistics extends Component
{
    // Autocomplete Proprietà
    public string $ownershipSearch = '';
    public Collection $ownershipResults;
    public string $selectedOwnershipId = '';
    public string $selectedOwnershipName = '';
    public bool $showOwnershipDropdown = false;

    // Autocomplete Fornitore
    public string $supplierSearch = '';
    public Collection $supplierResults;
    public string $selectedSupplierId = '';
    public string $selectedSupplierName = '';
    public bool $showSupplierDropdown = false;

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
    public bool $excludeCreditNotes = false;

    // FIX: totale "vero", calcolato direttamente da importo_totale (stessa
    // fonte usata dall'elenco Fatture di Acquisto), non ricostruito
    // sommando le righe. Vedi calculateTotalFatturato().
    public float $totalFatturato = 0;

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->supplierResults = new Collection();
        $this->costCenterResults = new Collection();

        $this->refreshAllData();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->refreshAllData();
    }

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
        $this->dispatch('resetDateRangeFilterWithoutApply');
    }

    public function updatedExcludeCreditNotes(): void
    {
        $this->refreshAllData();
    }

    /**
     * Ricalcola tutto ciò che dipende dai filtri correnti in un unico punto.
     */
    protected function refreshAllData(): void
    {
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
        $this->totalFatturato = $this->calculateTotalFatturato();
    }

    /**
     * Query di base con i filtri Proprietà / Fornitore / Centro di Costo /
     * data / esclusione note di credito, SENZA alcun filtro di stato: è la
     * stessa combinazione di filtri usata da InvoicesReceivedTable per il
     * "Totale Fatturato" dell'elenco fatture di acquisto (che non applica
     * whereIn('status', ...) — il fatturato è indipendente dal fatto che
     * la fattura sia già stata pagata).
     *
     * FIX: "escludi note di credito" ora esclude sia le NC esplicite
     * (type_invoice = TD04) sia le fatture-credito "sostitutive" con
     * importo_totale negativo ma senza TD04 (es. FIORINO GROUP), per
     * coerenza con il resto del gestionale.
     */
    protected function baseFilteredInvoiceQuery()
    {
        $query = InvoiceReceived::query()
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedSupplierId, fn($q) => $q->where('id_entities', $this->selectedSupplierId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->when($this->excludeCreditNotes, function($q) {
                $q->where('type_invoice', '!=', 'TD04')
                  ->where('importo_totale', '>=', 0);
            });

        $this->applyDateFilter($query);

        return $query;
    }

    /**
     * FIX (bug segnalato): il "Totale Fatturato" veniva ricostruito
     * sommando le righe (con segno invertito per le NC), non da
     * importo_totale. L'elenco Fatture di Acquisto invece somma
     * direttamente importo_totale, senza filtro di stato. Per garantire
     * che i due numeri combacino sempre, calcoliamo qui il totale nello
     * stesso identico modo dell'elenco.
     */
    protected function calculateTotalFatturato(): float
    {
        return (float) $this->baseFilteredInvoiceQuery()->sum('importo_totale');
    }

    protected function calculateStatistics(): Collection
    {
        $query = InvoiceReceived::query()
            ->with(['rows.costCenter'])
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedSupplierId, fn($q) => $q->where('id_entities', $this->selectedSupplierId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->when($this->excludeCreditNotes, function($q) {
                $q->where('type_invoice', '!=', 'TD04')
                  ->where('importo_totale', '>=', 0);
            });

        $this->applyDateFilter($query);

        $invoices = $query->get();

        $stats = collect();

        foreach ($invoices as $invoice) {
            $importoTotale = (float) $invoice->importo_totale;

            // Una fattura è "di credito" se è esplicitamente TD04, oppure se
            // ha importo_totale negativo anche senza essere TD04 (fatture
            // sostitutive di nota di credito, es. FIORINO GROUP).
            $isCreditNote = $invoice->type_invoice === 'TD04' || $importoTotale < 0;

            // Importo "vero" della fattura con il segno corretto per il
            // riepilogo: le NC esplicite (TD04) vengono forzate negative
            // anche se importo_totale fosse salvato positivo; le fatture
            // già negative restano tali.
            $signedInvoiceTotal = $isCreditNote ? -abs($importoTotale) : $importoTotale;

            $rowSum = (float) $invoice->rows->sum('total');

            // FIX: come per le Vendite, distribuiamo l'importo REALE e
            // firmato della fattura tra le righe in proporzione al peso di
            // ciascuna riga, invece di sommare row->total con un segno
            // applicato "a mano". Così la somma per centro di costo
            // coincide SEMPRE con il Totale Fatturato reale, anche quando
            // la somma grezza delle righe non coincide con importo_totale.
            $scaleFactor = ($rowSum != 0.0) ? ($signedInvoiceTotal / $rowSum) : 0.0;

            foreach ($invoice->rows as $row) {
                $costCenterName = 'Non assegnato';

                if ($row->costCenter) {
                    $costCenterName = $row->costCenter->Nome ?? 'Non assegnato';
                }

                $contributedAmount = $rowSum != 0.0
                    ? ((float) $row->total) * $scaleFactor
                    : 0.0;

                $existing = $stats->firstWhere('cost_center', $costCenterName);

                if ($existing) {
                    $existing->total += $contributedAmount;
                    $existing->count += 1;
                    if ($isCreditNote) {
                        $existing->credit_count += 1;
                    } else {
                        $existing->debit_count += 1;
                    }
                } else {
                    $stats->push((object) [
                        'cost_center' => $costCenterName,
                        'total' => $contributedAmount,
                        'count' => 1,
                        'credit_count' => $isCreditNote ? 1 : 0,
                        'debit_count' => $isCreditNote ? 0 : 1,
                    ]);
                }
            }
        }

        return $stats->sortByDesc('total')->values();
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

        if ($this->selectedOwnershipId !== '' && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        if ($this->selectedOwnershipId !== '') {
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

    public function selectOwnership(int $id, string $name): void
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
        $this->dispatch('clearOwnershipInput');
        $this->refreshAllData();
    }

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch(): void
    {
        if (empty(trim($this->supplierSearch))) {
            $this->selectedSupplierId = '';
            $this->selectedSupplierName = '';
            $this->supplierResults = new Collection();
            $this->showSupplierDropdown = false;
            $this->refreshAllData();
            return;
        }

        if ($this->selectedSupplierId !== '' && $this->supplierSearch === $this->selectedSupplierName) {
            $this->showSupplierDropdown = false;
            return;
        }

        if ($this->selectedSupplierId !== '') {
            $this->selectedSupplierId = '';
            $this->selectedSupplierName = '';
        }

        if (strlen(trim($this->supplierSearch)) < 2) {
            $this->supplierResults = new Collection();
            $this->showSupplierDropdown = false;
            return;
        }

        $this->supplierResults = Entity::where('valid', 1)
            ->whereIn('entity_type', ['fornitore', 'entrambi'])
            ->where(function($q) {
                $q->where('ragione_sociale', 'like', '%' . $this->supplierSearch . '%')
                ->orWhere('nome', 'like', '%' . $this->supplierSearch . '%')
                ->orWhere('cognome', 'like', '%' . $this->supplierSearch . '%')
                ->orWhere('partita_iva', 'like', '%' . $this->supplierSearch . '%');
            })
            ->limit(10)
            ->get(['id_cliente as id', 'ragione_sociale as name', 'partita_iva as piva']);

        $this->showSupplierDropdown = $this->supplierResults->isNotEmpty();
    }

    public function selectSupplier(int $id, string $name): void
    {
        $this->selectedSupplierId = (string)$id;
        $this->selectedSupplierName = $name;
        $this->supplierSearch = $name;
        $this->showSupplierDropdown = false;
        $this->refreshAllData();
    }

    public function clearSupplier(): void
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
        $this->dispatch('clearSupplierInput');
        $this->refreshAllData();
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch(): void
    {
        if (empty(trim($this->costCenterSearch))) {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            $this->refreshAllData();
            return;
        }

        if ($this->selectedCostCenterId !== '' && $this->costCenterSearch === $this->selectedCostCenterName) {
            $this->showCostCenterDropdown = false;
            return;
        }

        if ($this->selectedCostCenterId !== '') {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
        }

        if (strlen(trim($this->costCenterSearch)) < 2) {
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            return;
        }

        $this->costCenterResults = CostCenter::where('Nome', 'like', '%' . $this->costCenterSearch . '%')
            ->limit(10)
            ->get(['id', 'Nome', 'Localita']);

        $this->showCostCenterDropdown = $this->costCenterResults->isNotEmpty();
    }

    public function selectCostCenter(int $id, string $name): void
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
        return view('livewire.admin.invoices-received-statistics');
    }
}