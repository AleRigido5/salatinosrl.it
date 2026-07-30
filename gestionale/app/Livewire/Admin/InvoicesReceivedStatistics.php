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

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->supplierResults = new Collection();
        $this->costCenterResults = new Collection();

        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function refreshStats(): void
    {
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function updatedStatPeriod(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
        $this->dispatch('resetDates');
        $this->dispatch('resetDateRangeFilterWithoutApply');
    }

    public function updatedExcludeCreditNotes(): void
    {
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    protected function calculateStatistics(): Collection
    {
        $query = InvoiceReceived::query()
            ->whereIn('status', ['approved', 'issued', 'viewed'])
            ->with(['rows.costCenter'])
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedSupplierId, fn($q) => $q->where('id_entities', $this->selectedSupplierId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->when($this->excludeCreditNotes, function($q) {
                $q->where('type_invoice', '!=', 'TD04');
            });

        $this->applyDateFilter($query);

        $invoices = $query->get();

        $stats = collect();

        foreach ($invoices as $invoice) {
            // TD04 = Nota di Credito → segno negativo
            $isCreditNote = $invoice->type_invoice === 'TD04';
            $sign = $isCreditNote ? -1 : 1;

            foreach ($invoice->rows as $row) {
                $costCenterName = 'Non assegnato';

                if ($row->costCenter) {
                    $costCenterName = $row->costCenter->Nome ?? 'Non assegnato';
                }

                $existing = $stats->firstWhere('cost_center', $costCenterName);

                $rowTotal = $row->total * $sign;

                if ($existing) {
                    $existing->total += $rowTotal;
                    $existing->count += 1;
                    if ($isCreditNote) {
                        $existing->credit_count += 1;
                    } else {
                        $existing->debit_count += 1;
                    }
                } else {
                    $stats->push((object) [
                        'cost_center' => $costCenterName,
                        'total' => $rowTotal,
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
            $this->statistics = $this->calculateStatistics();
            $this->periodDisplay = $this->getPeriodDisplay();
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
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->dispatch('clearOwnershipInput');
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch(): void
    {
        if (empty(trim($this->supplierSearch))) {
            $this->selectedSupplierId = '';
            $this->selectedSupplierName = '';
            $this->supplierResults = new Collection();
            $this->showSupplierDropdown = false;
            $this->statistics = $this->calculateStatistics();
            $this->periodDisplay = $this->getPeriodDisplay();
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
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearSupplier(): void
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
        $this->dispatch('clearSupplierInput');
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch(): void
    {
        if (empty(trim($this->costCenterSearch))) {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
            $this->costCenterResults = new Collection();
            $this->showCostCenterDropdown = false;
            $this->statistics = $this->calculateStatistics();
            $this->periodDisplay = $this->getPeriodDisplay();
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
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearCostCenter(): void
    {
        $this->selectedCostCenterId = '';
        $this->selectedCostCenterName = '';
        $this->costCenterSearch = '';
        $this->dispatch('clearCostCenterInput');
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function render()
    {
        return view('livewire.admin.invoices-received-statistics');
    }
}