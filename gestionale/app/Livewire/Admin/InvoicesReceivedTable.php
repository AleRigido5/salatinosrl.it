<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoicesReceivedTable extends Component
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
    
    // Ordinamento
    public string $sortField = 'data_invoice';
    public string $sortDirection = 'desc';
    public int $perPage = 100000; // Default: 100 per pagina
    
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

    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
    ];

    public function mount(): void
    {
        $this->ownershipResults = new Collection();
        $this->supplierResults = new Collection();
        $this->costCenterResults = new Collection();

        // Ripristina filtri dalla sessione (solo al ritorno da edit)
        $savedFilters = session()->pull('invoices_received_filters');

        if (!empty($savedFilters)) {
            $this->dateFrom = $savedFilters['date_from'] ?? '';
            $this->dateTo = $savedFilters['date_to'] ?? '';
            $this->ownershipSearch = $savedFilters['ownership_search'] ?? '';
            $this->selectedOwnershipId = $savedFilters['selected_ownership_id'] ?? '';
            $this->selectedOwnershipName = $savedFilters['selected_ownership_name'] ?? '';
            $this->supplierSearch = $savedFilters['supplier_search'] ?? '';
            $this->selectedSupplierId = $savedFilters['selected_supplier_id'] ?? '';
            $this->selectedSupplierName = $savedFilters['selected_supplier_name'] ?? '';
            $this->costCenterSearch = $savedFilters['cost_center_search'] ?? '';
            $this->selectedCostCenterId = $savedFilters['selected_cost_center_id'] ?? '';
            $this->selectedCostCenterName = $savedFilters['selected_cost_center_name'] ?? '';
            $this->status = $savedFilters['status'] ?? '';
            $this->type_invoice = $savedFilters['type_invoice'] ?? '';
            $this->search = $savedFilters['search'] ?? '';
            $this->sortField = $savedFilters['sort_field'] ?? 'data_invoice';
            $this->sortDirection = $savedFilters['sort_direction'] ?? 'desc';
            $this->perPage = $savedFilters['per_page'] ?? 100000;
        }

        $this->updateTrashCount();
    }

    public function updateDateRange(array $data): void
    {
        $this->dateFrom = $data['date_from'];
        $this->dateTo = $data['date_to'];
        $this->resetPage();
    }

    // ==================== AUTOCOMPLETE PROPRIETÀ ====================
    public function updatedOwnershipSearch(): void
    {
        if ($this->selectedOwnershipId !== '' && $this->ownershipSearch === $this->selectedOwnershipName) {
            $this->showOwnershipDropdown = false;
            return;
        }

        if ($this->selectedOwnershipId !== '') {
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

    public function selectOwnership(int $id, string $name): void
    {
        $this->selectedOwnershipId = (string)$id;
        $this->selectedOwnershipName = $name;
        $this->ownershipSearch = $name;
        $this->showOwnershipDropdown = false;
        $this->resetPage();
    }

    public function clearOwnership(): void
    {
        $this->selectedOwnershipId = '';
        $this->selectedOwnershipName = '';
        $this->ownershipSearch = '';
        $this->resetPage();
        $this->dispatch('clearOwnershipInput');
    }

    // ==================== AUTOCOMPLETE FORNITORE ====================
    public function updatedSupplierSearch(): void
    {
        if ($this->selectedSupplierId !== '' && $this->supplierSearch === $this->selectedSupplierName) {
            $this->showSupplierDropdown = false;
            return;
        }

        if ($this->selectedSupplierId !== '') {
            $this->selectedSupplierId = '';
            $this->selectedSupplierName = '';
            $this->resetPage();
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
        $this->resetPage();
    }

    public function clearSupplier(): void
    {
        $this->selectedSupplierId = '';
        $this->selectedSupplierName = '';
        $this->supplierSearch = '';
        $this->resetPage();
        $this->dispatch('clearSupplierInput');
    }

    // ==================== AUTOCOMPLETE CENTRO DI COSTO ====================
    public function updatedCostCenterSearch(): void
    {
        if ($this->selectedCostCenterId !== '' && $this->costCenterSearch === $this->selectedCostCenterName) {
            $this->showCostCenterDropdown = false;
            return;
        }

        if ($this->selectedCostCenterId !== '') {
            $this->selectedCostCenterId = '';
            $this->selectedCostCenterName = '';
            $this->resetPage();
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
        $this->resetPage();
    }

    public function clearCostCenter(): void
    {
        $this->selectedCostCenterId = '';
        $this->selectedCostCenterName = '';
        $this->costCenterSearch = '';
        $this->resetPage();
        $this->dispatch('clearCostCenterInput');
    }

    // ==================== AGGIORNAMENTO STATO FATTURA ====================
    public function updateInvoiceStatus(int $id, string $newStatus): void
    {
        try {
            $invoice = InvoiceReceived::find($id);
            if (!$invoice) {
                $this->dispatch('showError', message: 'Fattura non trovata');
                return;
            }
            
            // Verifica che lo stato sia valido (issued o viewed)
            if (!in_array($newStatus, ['issued', 'viewed'])) {
                $this->dispatch('showError', message: 'Stato non valido');
                return;
            }
            
            DB::beginTransaction();
            
            // Aggiorna lo stato della fattura principale
            $invoice->status = $newStatus;
            $invoice->save();
            
            // Aggiorna anche lo stato dei pagamenti associati
            // Se la fattura è "Visionata", i pagamenti diventano "in_attesa" o rimangono invariati
            // Se la fattura è "Emessa", i pagamenti tornano "in_attesa" (non ancora pagati)
            if ($newStatus === 'viewed') {
                // Fattura visionata -> i pagamenti sono in attesa di pagamento
                $invoice->payments()->update(['status' => 'In attesa']);
            } elseif ($newStatus === 'issued') {
                // Fattura emessa -> i pagamenti sono ancora in attesa (non pagati)
                // Se vuoi resettare i pagamenti a "pending" quando torni a "emessa"
                $invoice->payments()->update(['status' => 'issued']);
            }
            
            DB::commit();
            
            // Aggiorna anche l'istanza selezionata nel modal
            if ($this->selectedInvoice && $this->selectedInvoice->id === $id) {
                $this->selectedInvoice->status = $newStatus;
                // Ricarica i pagamenti aggiornati
                $this->selectedInvoice->load('payments');
            }
            
            $statusLabel = $newStatus === 'issued' ? 'Emessa' : 'Visionata';
            $this->dispatch('showSuccess', message: "Stato fattura aggiornato a '{$statusLabel}' e pagamenti sincronizzati");
            
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
        session()->forget('invoices_received_filters'); // ← aggiungi questa riga

        $this->search = '';
        $this->status = '';
        $this->type_invoice = '';
        $this->clearOwnership();
        $this->clearSupplier();
        $this->clearCostCenter();
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDates');
        $this->dispatch('resetDateRangeFilterWithoutApply');
        $this->dispatch('resetAllFilters');
    }

    public function clearStatus(): void
    {
        $this->status = '';
        $this->resetPage();
    }

    public function clearTypeInvoice(): void
    {
        $this->type_invoice = '';
        $this->resetPage();
    }

    public function clearSearch(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function clearDates(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDateRangeFilterWithoutApply');
    }

    public function getInvoicesProperty()
    {
        $query = InvoiceReceived::query()
            ->with(['ownership', 'entity', 'rows.costCenter'])
            ->when($this->search, fn($q) => $q->where('n_invoice', 'like', '%' . $this->search . '%'))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->type_invoice, fn($q) => $q->where('type_invoice', $this->type_invoice))
            ->when($this->selectedOwnershipId, fn($q) => $q->where('id_ownership', $this->selectedOwnershipId))
            ->when($this->selectedSupplierId, fn($q) => $q->where('id_entities', $this->selectedSupplierId))
            ->when($this->selectedCostCenterId, function($q) {
                // Filtra per centro di costo attraverso le righe fattura
                $q->whereHas('rows', fn($q2) => $q2->where('id_cost_center', $this->selectedCostCenterId));
            })
            ->when($this->dateFrom, fn($q) => $q->whereDate('data_invoice', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('data_invoice', '<=', $this->dateTo))
            ->orderBy($this->sortField, $this->sortDirection);

        // Gestione della paginazione "Tutti"
        if ($this->perPage == 10000) {
            $results = $query->get();
            // Crea un paginatore manuale per mantenere la compatibilità con la view
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
        session(['invoices_received_filters' => [
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'ownership_search' => $this->ownershipSearch,
            'selected_ownership_id' => $this->selectedOwnershipId,
            'selected_ownership_name' => $this->selectedOwnershipName,
            'supplier_search' => $this->supplierSearch,
            'selected_supplier_id' => $this->selectedSupplierId,
            'selected_supplier_name' => $this->selectedSupplierName,
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

        return redirect()->route('admin.invoices-received.edit', $id);
    }

    // ==================== MODAL DETTAGLI ====================
    
    public function showDetails(int $id): void
    {
        $this->selectedInvoice = InvoiceReceived::with([
            'ownership', 
            'entity', 
            'rows.costCenter', 
            'rows.vehicle',
            'payments', 
            'creator', 
            'updater', 
            'vatSummaries'
        ])->find($id);

        // Aggiungi questo per caricare gli allegati
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

    /**
     * Genera l'URL per l'esportazione PDF con tutti i filtri attivi
     */
    public function getExportPdfUrl()
    {
        $params = [];
        
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->selectedOwnershipId) {
            $params['ownership_id'] = $this->selectedOwnershipId;
        }
        if ($this->selectedSupplierId) {
            $params['supplier_id'] = $this->selectedSupplierId;
        }
        if ($this->selectedCostCenterId) {
            $params['cost_center_id'] = $this->selectedCostCenterId;
        }
        if ($this->status) {
            $params['status'] = $this->status;
        }
        if ($this->type_invoice) {
            $params['type_invoice'] = $this->type_invoice;
        }
        if ($this->search) {
            $params['search'] = $this->search;
        }
        
        return route('admin.invoices-received.export-pdf', $params);
    }

    /**
     * Genera l'URL per l'esportazione Excel con tutti i filtri attivi
     */
    public function getExportExcelUrl()
    {
        $params = [];
        
        if ($this->dateFrom) {
            $params['date_from'] = $this->dateFrom;
        }
        if ($this->dateTo) {
            $params['date_to'] = $this->dateTo;
        }
        if ($this->selectedOwnershipId) {
            $params['ownership_id'] = $this->selectedOwnershipId;
        }
        if ($this->selectedSupplierId) {
            $params['supplier_id'] = $this->selectedSupplierId;
        }
        if ($this->selectedCostCenterId) {
            $params['cost_center_id'] = $this->selectedCostCenterId;
        }
        if ($this->status) {
            $params['status'] = $this->status;
        }
        if ($this->type_invoice) {
            $params['type_invoice'] = $this->type_invoice;
        }
        if ($this->search) {
            $params['search'] = $this->search;
        }
        
        return route('admin.invoices-received.export-excel', $params);
    }

    // ==================== ALLEGATI ====================
    
    /**
     * Scarica l'allegato della fattura
     */
    // public function downloadAttachment($invoiceId)
    // {
    //     try {
    //         $invoice = InvoiceReceived::find($invoiceId);
    //         if (!$invoice || !$invoice->attachment) {
    //             $this->dispatch('showError', message: 'Nessun allegato trovato');
    //             return;
    //         }

    //         // Se l'attachment è un percorso relativo
    //         $attachmentPath = $invoice->attachment;
            
    //         // Verifica se il file esiste nello storage
    //         if (Storage::disk('public')->exists($attachmentPath)) {
    //             // Restituisci il file per il download
    //             return response()->download(storage_path('app/public/' . $attachmentPath));
    //         }
            
    //         // Se il percorso è un URL completo, apri in una nuova finestra
    //         if (filter_var($attachmentPath, FILTER_VALIDATE_URL)) {
    //             $this->dispatch('openWindow', url: $attachmentPath);
    //             return;
    //         }
            
    //         $this->dispatch('showError', message: 'File non trovato sul server');
            
    //     } catch (\Exception $e) {
    //         Log::error('Errore download allegato: ' . $e->getMessage());
    //         $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
    //     }
    // }

    /**
     * Verifica se l'allegato esiste
     */
    // public function attachmentExists($invoiceId)
    // {
    //     $invoice = InvoiceReceived::find($invoiceId);
    //     if (!$invoice || !$invoice->attachment) {
    //         return false;
    //     }
        
    //     $attachmentPath = $invoice->attachment;
        
    //     if (Storage::disk('public')->exists($attachmentPath)) {
    //         return true;
    //     }
        
    //     if (filter_var($attachmentPath, FILTER_VALIDATE_URL)) {
    //         return true;
    //     }
        
    //     return false;
    // }

    // ==================== ELIMINAZIONE ====================
    
    public function confirmDelete(int $id): void
    {
        $invoice = InvoiceReceived::find($id);
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
            $this->invoiceNameToDelete = '';
            $this->updateTrashCount();
            $this->dispatch('showSuccess', message: "'{$name}' spostata nel cestino.");
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
            $this->showDeleteModal = false;
        }
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->invoiceToDelete = null;
        $this->invoiceNameToDelete = '';
    }

    // ==================== CESTINO ====================
    
    public function updateTrashCount(): void
    {
        $this->trashCount = InvoiceReceived::onlyTrashed()->count();
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
        $this->trashSearch = '';
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
        $query = InvoiceReceived::onlyTrashed()->with(['ownership', 'entity']);
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
            $invoice = InvoiceReceived::onlyTrashed()->find($id);
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
            $invoice = InvoiceReceived::onlyTrashed()->find($id);
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

    /**
     * Svuota completamente il cestino (elimina definitivamente tutte le fatture)
     */
    public function emptyTrash(): void
    {
        try {
            $trashedInvoices = InvoiceReceived::onlyTrashed()->get();
            $count = $trashedInvoices->count();
            
            if ($count === 0) {
                $this->dispatch('showInfo', message: 'Il cestino è già vuoto.');
                return;
            }
            
            DB::beginTransaction();
            
            foreach ($trashedInvoices as $invoice) {
                // Elimina le righe associate
                $invoice->rows()->forceDelete();
                
                // Elimina i pagamenti associati
                $invoice->payments()->delete();
                
                // Elimina i riepiloghi IVA associati
                $invoice->vatSummaries()->delete();
                
                // Elimina la fattura definitivamente
                $invoice->forceDelete();
            }
            
            DB::commit();
            
            $this->dispatch('showSuccess', message: "Cestino svuotato! {$count} fattura/e eliminate definitivamente.");
            $this->updateTrashCount();
            
            // Ricarica la lista delle fatture nel cestino (resetta la ricerca)
            $this->trashSearch = '';
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore svuotamento cestino: ' . $e->getMessage());
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function getStatusesProperty(): array
    {
        return (array) config('gestionale.invoice_status', []);
    }

    public function getTypeDocumentsProperty(): array
    {
        return config('gestionale.tipo_documento', []);
    }

    public function render()
    {
        return view('livewire.admin.invoices-received-table', [
            'invoices' => $this->invoices,
            'statuses' => $this->statuses,
            'typeDocuments' => $this->typeDocuments,
            'trashedInvoices' => $this->trashedInvoices,
            'trashCount' => $this->trashCount,
        ]);
    }
}