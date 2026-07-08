<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoiceSent;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class InvoiceSentTable extends Component
{
    use WithPagination;

    // Filtri ricerca
    public string $search = '';
    public string $status = '';
    public string $type_invoice = '';
    
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
    
    // Ordinamento
    public string $sortField = 'data_invoice';
    public string $sortDirection = 'desc';
    public int $perPage = 100;
    
    // Modal
    public $selectedInvoice = null;
    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public $invoiceToDelete = null;
    public string $invoiceNameToDelete = '';
    public bool $showTrashModal = false;
    public string $trashSearch = '';
    public string $trashSortField = 'deleted_at';
    public string $trashSortDirection = 'desc';
    public int $trashCount = 0;

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
        
        // Ripristina filtri dalla sessione (solo al ritorno da edit)
        $savedFilters = session()->pull('invoices_sent_filters');
        
        if (!empty($savedFilters)) {
            $this->dateFrom = $savedFilters['date_from'] ?? '';
            $this->dateTo = $savedFilters['date_to'] ?? '';
            $this->ownershipSearch = $savedFilters['ownership_search'] ?? '';
            $this->selectedOwnershipId = $savedFilters['selected_ownership_id'] ?? '';
            $this->selectedOwnershipName = $savedFilters['selected_ownership_name'] ?? '';
            $this->customerSearch = $savedFilters['customer_search'] ?? '';
            $this->selectedCustomerId = $savedFilters['selected_customer_id'] ?? '';
            $this->selectedCustomerName = $savedFilters['selected_customer_name'] ?? '';
            $this->costCenterSearch = $savedFilters['cost_center_search'] ?? '';
            $this->selectedCostCenterId = $savedFilters['selected_cost_center_id'] ?? '';
            $this->selectedCostCenterName = $savedFilters['selected_cost_center_name'] ?? '';
            $this->status = $savedFilters['status'] ?? '';
            $this->type_invoice = $savedFilters['type_invoice'] ?? '';
            $this->search = $savedFilters['search'] ?? '';
            $this->sortField = $savedFilters['sort_field'] ?? 'data_invoice';
            $this->sortDirection = $savedFilters['sort_direction'] ?? 'desc';
            $this->perPage = $savedFilters['per_page'] ?? 100;
        } else {
            $this->dateFrom = date('Y-m-01');
            $this->dateTo = date('Y-m-d');
        }
        
        $this->updateTrashCount();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        if (empty(trim($this->ownershipSearch))) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->ownershipResults = new Collection();
            $this->showOwnershipDropdown = false;
            $this->resetPage();
            return;
        }

        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        if (!empty($this->selectedOwnershipId) && $this->ownershipSearch !== $this->selectedOwnershipName) {
            $this->selectedOwnershipId = '';
            $this->selectedOwnershipName = '';
            $this->resetPage();
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
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->showOwnershipDropdown = false;
        $this->resetPage();
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
            $this->resetPage();
            return;
        }

        if (!empty($this->selectedCustomerId) && $this->customerSearch !== $this->selectedCustomerName) {
            $this->selectedCustomerId = '';
            $this->selectedCustomerName = '';
            $this->resetPage();
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
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearCustomer(): void
    {
        $this->selectedCustomerId = '';
        $this->selectedCustomerName = '';
        $this->customerSearch = '';
        $this->resetPage();
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
            $this->resetPage();
            return;
        }

        if (!empty($this->selectedCostCenterId) && $this->costCenterSearch !== $this->selectedCostCenterName) {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
            $this->resetPage();
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
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearCostCenter(): void
    {
        $this->selectedCostCenterId = '';
        $this->selectedCostCenterName = '';
        $this->costCenterSearch = '';
        $this->resetPage();
        $this->dispatch('clearCostCenterInput');
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
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    protected function calculateStatistics(): Collection
    {
        $query = InvoiceSent::query()
            ->whereIn('status', ['approved', 'issued'])
            ->with(['rows.service.category']) // Carica la relazione
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
                // CORREZIONE: usa 'valore' invece di 'Nome'
                $categoryName = 'Non categorizzato';
                
                if ($row->service && $row->service->category) {
                    // Il campo nella tabella settings si chiama 'valore'
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
        $from = $this->dateFrom ? date('d/m/Y', strtotime($this->dateFrom)) : '';
        $to = $this->dateTo ? date('d/m/Y', strtotime($this->dateTo)) : '';
        
        if ($from && $to) {
            return "Dal {$from} al {$to}";
        }
        
        $now = now();
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

    // ==================== AGGIORNAMENTO STATO ====================
    public function updateInvoiceStatus(int $id, string $newStatus): void
    {
        try {
            $invoice = InvoiceSent::find($id);
            if (!$invoice) {
                $this->dispatch('showError', message: 'Fattura non trovata');
                return;
            }

            DB::beginTransaction();
            $invoice->status = $newStatus;
            $invoice->save();

            if ($newStatus === 'issued') {
                $invoice->payments()->update(['status' => 'issued']);
            }

            DB::commit();

            $statusLabel = $newStatus === 'issued' ? 'Emessa' : 'Approvata';
            $this->dispatch('showSuccess', message: "Stato fattura aggiornato a '{$statusLabel}'");
            
            $this->statistics = $this->calculateStatistics();
            $this->periodDisplay = $this->getPeriodDisplay();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== FILTRI E QUERY ====================
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        session()->forget('invoices_sent_filters');
        
        $this->search = '';
        $this->status = '';
        $this->type_invoice = '';
        $this->clearOwnership();
        $this->clearCustomer();
        $this->clearCostCenter();
        
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-d');
        
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
        
        $this->dispatch('resetDates');
        $this->dispatch('resetAllFilters');
    }

    public function clearStatus(): void
    {
        $this->status = '';
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearTypeInvoice(): void
    {
        $this->type_invoice = '';
        $this->resetPage();
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearDates(): void
    {
        $this->dateFrom = date('Y-m-01');
        $this->dateTo = date('Y-m-d');
        $this->resetPage();
        $this->dispatch('resetDates');
        $this->statistics = $this->calculateStatistics();
        $this->periodDisplay = $this->getPeriodDisplay();
    }

    public function getInvoicesProperty()
    {
        $query = InvoiceSent::query()
            ->with(['ownership', 'entity', 'rows.costCenter', 'rows.service'])
            ->when($this->search, fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedCustomerId, fn($q) => $q->where('id_entities', $this->selectedCustomerId))
            ->when($this->selectedCostCenterId, function($q) {
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('data_invoice', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('data_invoice', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        if (empty($this->dateFrom) && empty($this->dateTo)) {
            $query->whereDate('data_invoice', '>=', now()->subMonths(6));
        }

        if ($this->perPage == 10000) {
            $results = $query->get();
            $page = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $perPage = $results->count();
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $results->forPage($page, $perPage),
                $results->count(),
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );
        }
        
        return $query->paginate($this->perPage);
    }

    // ==================== NAVIGAZIONE CON FILTRI ====================
    public function editInvoice(int $id): mixed
    {
        session(['invoices_sent_filters' => [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'ownership_search' => $this->ownershipSearch,
            'selected_ownership_id' => $this->selectedOwnershipId,
            'selected_ownership_name' => $this->selectedOwnershipName,
            'customer_search' => $this->customerSearch,
            'selected_customer_id' => $this->selectedCustomerId,
            'selected_customer_name' => $this->selectedCustomerName,
            'cost_center_search' => $this->costCenterSearch,
            'selected_cost_center_id' => $this->selectedCostCenterId,
            'selected_cost_center_name' => $this->selectedCostCenterName,
            'status' => $this->status,
            'type_invoice' => $this->type_invoice,
            'search' => $this->search,
            'sort_field' => $this->sortField,
            'sort_direction' => $this->sortDirection,
            'per_page' => $this->perPage,
        ]]);

        return redirect()->route('admin.invoices-sent.edit', $id);
    }

    // ==================== MODAL DETTAGLI ====================
    public function showDetails(int $id): void
    {
        $this->selectedInvoice = InvoiceSent::with([
            'ownership', 'entity', 'rows.costCenter', 'rows.vehicle', 'rows.service',
            'payments', 'creator', 'updater', 'vatSummaries', 'invoiceSeries'
        ])->find($id);

        if ($this->selectedInvoice) {
            $this->selectedInvoice->attachments_list = $this->selectedInvoice->getAllAttachmentsAttribute();
        }

        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedInvoice = null;
    }

    // ==================== ESPORTAZIONI ====================
    public function getExportPdfUrl()
    {
        $params = [];
        
        if ($this->dateFrom) $params['date_from'] = $this->dateFrom;
        if ($this->dateTo) $params['date_to'] = $this->dateTo;
        if ($this->selectedOwnershipId) $params['ownership_id'] = $this->selectedOwnershipId;
        if ($this->selectedCustomerId) $params['customer_id'] = $this->selectedCustomerId;
        if ($this->selectedCostCenterId) $params['cost_center_id'] = $this->selectedCostCenterId;
        if ($this->status) $params['status'] = $this->status;
        if ($this->type_invoice) $params['type_invoice'] = $this->type_invoice;
        if ($this->search) $params['search'] = $this->search;
        
        return route('admin.invoices-sent.export-pdf', $params);
    }

    public function getExportExcelUrl()
    {
        $params = [];
        
        if ($this->dateFrom) $params['date_from'] = $this->dateFrom;
        if ($this->dateTo) $params['date_to'] = $this->dateTo;
        if ($this->selectedOwnershipId) $params['ownership_id'] = $this->selectedOwnershipId;
        if ($this->selectedCustomerId) $params['customer_id'] = $this->selectedCustomerId;
        if ($this->selectedCostCenterId) $params['cost_center_id'] = $this->selectedCostCenterId;
        if ($this->status) $params['status'] = $this->status;
        if ($this->type_invoice) $params['type_invoice'] = $this->type_invoice;
        if ($this->search) $params['search'] = $this->search;
        
        return route('admin.invoices-sent.export-excel', $params);
    }

    // ==================== ELIMINAZIONE ====================
    public function confirmDelete(int $id): void
    {
        $invoice = InvoiceSent::find($id);
        if (!$invoice) {
            $this->dispatch('showError', message: 'Fattura non trovata');
            return;
        }
        $this->invoiceToDelete = $invoice;
        $this->invoiceNameToDelete = 'Fattura n. ' . $invoice->n_invoice . ' del ' . $invoice->data_invoice->format('d/m/Y');
        $this->showDeleteModal = true;
    }

    public function deleteInvoice(): void
    {
        try {
            if (!$this->invoiceToDelete) {
                $this->dispatch('showError', message: 'Nessuna fattura selezionata');
                return;
            }
            $name = $this->invoiceNameToDelete;
            $this->invoiceToDelete->delete();
            $this->showDeleteModal = false;
            $this->invoiceToDelete = null;
            $this->updateTrashCount();
            $this->dispatch('showSuccess', message: "'{$name}' spostata nel cestino.");
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->invoiceToDelete = null;
    }

    // ==================== CESTINO ====================
    public function updateTrashCount(): void
    {
        $this->trashCount = InvoiceSent::onlyTrashed()->count();
    }

    public function openTrashModal(): void
    {
        $this->trashSearch = '';
        $this->showTrashModal = true;
        $this->updateTrashCount();
    }

    public function closeTrashModal(): void
    {
        $this->showTrashModal = false;
        $this->updateTrashCount();
    }

    public function trashSortBy(string $field): void
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
        }
    }

    public function getTrashedInvoicesProperty()
    {
        $query = InvoiceSent::onlyTrashed()->with(['ownership', 'entity']);
        if ($this->trashSearch) {
            $searchTerm = '%' . $this->trashSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('n_invoice', 'like', $searchTerm)
                  ->orWhereHas('entity', fn($eq) => $eq->where('ragione_sociale', 'like', $searchTerm));
            });
        }
        return $query->orderBy($this->trashSortField, $this->trashSortDirection)->paginate(10);
    }

    public function restoreFromTrash(int $id): void
    {
        try {
            $invoice = InvoiceSent::onlyTrashed()->find($id);
            if ($invoice) {
                $name = 'Fattura n. ' . $invoice->n_invoice;
                $invoice->restore();
                $this->dispatch('showSuccess', message: "'{$name}' ripristinata con successo!");
                $this->updateTrashCount();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function forceDeleteFromTrash(int $id): void
    {
        try {
            $invoice = InvoiceSent::onlyTrashed()->find($id);
            if ($invoice) {
                $name = 'Fattura n. ' . $invoice->n_invoice;
                $invoice->rows()->forceDelete();
                $invoice->forceDelete();
                $this->dispatch('showSuccess', message: "'{$name}' eliminata definitivamente.");
                $this->updateTrashCount();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    // ==================== CONFIGURAZIONI ====================
    public function getStatusesProperty(): array
    {
        $configStatuses = config('gestionale.invoice_status', []);
        
        if (!isset($configStatuses['approved'])) {
            $configStatuses['approved'] = [
                'label' => 'Approvata',
                'badge_class' => 'bg-lime-100 text-lime-800'
            ];
        }
        
        if (isset($configStatuses['issued'])) {
            $configStatuses['issued']['label'] = 'Emessa';
            $configStatuses['issued']['badge_class'] = 'bg-yellow-100 text-yellow-800';
        }
        
        return $configStatuses;
    }

    public function getTypeDocumentsProperty(): array
    {
        return config('gestionale.tipo_documento', []);
    }

    public function render()
    {
        return view('livewire.admin.invoice-sent-table', [
            'invoices' => $this->invoices,
            'statuses' => $this->statuses,
            'typeDocuments' => $this->typeDocuments,
            'trashedInvoices' => $this->trashedInvoices,
            'trashCount' => $this->trashCount,
        ]);
    }
}