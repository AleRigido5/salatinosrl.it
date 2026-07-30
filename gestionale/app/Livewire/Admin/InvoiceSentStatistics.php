<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\InvoiceSent;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;

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

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->customerResults = new Collection();
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

    // ==================== STATISTICHE ====================
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
    }

    protected function calculateStatistics(): Collection
    {
        $query = InvoiceSent::query()
            ->whereIn('status', ['approved', 'issued'])
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
            foreach ($invoice->rows as $row) {
                $categoryName = 'Non categorizzato';

                if ($row->service && $row->service->category) {
                    $categoryName = $row->service->category->valore ?? 'Non categorizzato';
                }

                $existing = $stats->firstWhere('service_category', $categoryName);

                if ($existing) {
                    $existing->total += $row->total;
                    $existing->count += 1;
                } else {
                    $stats->push((object) [
                        'service_category' => $categoryName,
                        'total' => $row->total,
                        'count' => 1,
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
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
        $this->dispatch('clearOwnershipInput');
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
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
            $this->statistics = $this->calculateStatistics();
            $this->periodDisplay = $this->getPeriodDisplay();
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
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = '';
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
        $this->dispatch('clearCustomerInput');
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
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
            $this->statistics = $this->calculateStatistics();
            $this->periodDisplay = $this->getPeriodDisplay();
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
        return view('livewire.admin.invoice-sent-statistics');
    }
}