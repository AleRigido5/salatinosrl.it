<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CostCenter;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\Activity;
use App\Models\InvoiceRow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CostCentersTable extends Component
{
    use WithPagination;

    public $search = '';
    public $referenceFilter = '';
    public $statusFilter = '';
    public $typeFilter = '';
    public $perPage = 15;
    public $sortField = 'id';
    public $sortDirection = 'asc';
    
    // Modal visualizzazione semplice
    public $showViewModal = false;
    public $viewingCostCenter = null;

    // ==================== MODAL DETTAGLIO CENTRO DI COSTO ====================
    public $showDetailModal = false;
    public $detailCostCenter = null;
    public $detailDateFrom = '';
    public $detailDateTo = '';
    
    protected $queryString = ['search', 'referenceFilter', 'statusFilter', 'typeFilter', 'sortField', 'sortDirection'];
    protected $paginationTheme = 'tailwind';
    
    protected $listeners = [
        'dateRangeUpdated' => 'updateDetailDateRange',
    ];
    
    // ==================== PROPRIETÀ ====================
    
    public function getReferenceTypesProperty()
    {
        return [
            '' => 'Tutti i tipi',
            'ownership' => 'Proprietà',
            'entities' => 'Clienti/Fornitori'
        ];
    }
    
    public function getStatusesProperty()
    {
        return [
            '' => 'Tutti gli stati',
            '1' => 'Attivi',
            '0' => 'Disattivi'
        ];
    }
    
    public function getReferenceListProperty()
    {
        $ownerships = Ownership::all();
        $entities = Entity::where('valid', 1)->get();
        
        $list = [];
        
        foreach ($ownerships as $ownership) {
            $name = $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? 'Proprietà ' . $ownership->id_proprieta;
            $list['ownership_' . $ownership->id_proprieta] = '[PROP] ' . $name;
        }
        
        foreach ($entities as $entity) {
            $name = $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome);
            $list['entities_' . $entity->id] = '[CLI/FOR] ' . $name;
        }
        
        return $list;
    }
    
    // ==================== QUERY ====================
    
    public function getCostCentersProperty()
    {
        $orderByMap = [
            'id' => 'id',
            'nome' => 'Nome',
            'contrada' => 'Contrada',
            'localita' => 'Localita',
            'coltura' => 'Coltura',
            'superficie' => 'Superficie',
            'costo_h' => 'CostoH',
            'num_h' => 'NumH',
            'competenza' => 'Competenza',
            'valid' => 'valid'
        ];
        
        $orderByField = $orderByMap[$this->sortField] ?? 'id';
        
        $query = CostCenter::query();
        
        if ($this->search) {
            $query->search($this->search);
        }
        
        if ($this->typeFilter) {
            $query->where('table_references', $this->typeFilter);
        }
        
        if ($this->referenceFilter) {
            $parts = explode('_', $this->referenceFilter, 2);
            if (count($parts) == 2) {
                $query->where('table_references', $parts[0])
                    ->where('id_references', $parts[1]);
            }
        }
        
        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter);
        }
        
        $query->orderBy($orderByField, $this->sortDirection);
        
        $results = $query->paginate($this->perPage);
        
        foreach ($results as $center) {
            if ($center->table_references === 'ownership') {
                $center->load('ownership');
            } elseif ($center->table_references === 'entities') {
                $center->load('entity');
            }
        }
        
        return $results;
    }
    
    // ==================== SORTING ====================
    
    public function sortBy($field)
    {
        $fieldMap = [
            'id' => 'id',
            'nome' => 'Nome',
            'contrada' => 'Contrada',
            'localita' => 'Localita',
            'coltura' => 'Coltura',
            'superficie' => 'Superficie',
            'costo_h' => 'CostoH',
            'num_h' => 'NumH',
            'competenza' => 'Competenza',
            'valid' => 'valid'
        ];
        
        $dbField = $fieldMap[$field] ?? $field;
        
        $validSortFields = ['id', 'Nome', 'Contrada', 'Localita', 'Coltura', 'Superficie', 'CostoH', 'NumH', 'Competenza', 'valid'];
        
        if (!in_array($dbField, $validSortFields)) {
            return;
        }
        
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }
    
    // ==================== FILTRI ====================
    
    public function resetFilters()
    {
        $this->search = '';
        $this->referenceFilter = '';
        $this->statusFilter = '';
        $this->typeFilter = '';
        $this->sortField = 'id';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingReferenceFilter()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingTypeFilter()
    {
        $this->resetPage();
    }
    
    // ==================== VISUALIZZAZIONE SEMPLICE ====================
    
    public function viewCostCenter($id)
    {
        try {
            $costCenter = CostCenter::with(['ownership', 'entity', 'createdBy', 'updatedBy'])->find($id);
            
            if (!$costCenter) {
                $this->dispatch('showError', message: 'Centro di Costo non trovato');
                return;
            }
            
            $this->viewingCostCenter = $costCenter;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingCostCenter = null;
    }

    // ==================== MODAL DETTAGLIO CON FATTURE + ATTIVITÀ ====================

    public function openDetailModal(int $id): void
    {
        try {
            $costCenter = CostCenter::with(['ownership', 'entity'])->find($id);

            if (!$costCenter) {
                $this->dispatch('showError', message: 'Centro di Costo non trovato');
                return;
            }

            $this->detailCostCenter = $costCenter;

            // Default: mese corrente
            $this->detailDateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->detailDateTo   = Carbon::now()->endOfMonth()->format('Y-m-d');

            $this->showDetailModal = true;

        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailCostCenter = null;
        $this->dispatch('resetDates');
    }

    public function updateDetailDateRange(array $data): void
    {
        $this->detailDateFrom = $data['date_from'];
        $this->detailDateTo   = $data['date_to'];
    }

    /**
     * Fatture (righe fattura) associate al centro di costo nel periodo selezionato.
     * Raggruppa per documento per mostrare una riga per fattura.
     */
    public function getDetailInvoicesProperty()
    {
        if (!$this->detailCostCenter) {
            return collect();
        }

        $query = InvoiceRow::with(['invoiceReceived.entity', 'invoiceReceived.ownership', 'service'])
            ->where('id_cost_center', $this->detailCostCenter->id)
            ->whereNull('invoice_row.deleted_at');

        // Filtro data sulla fattura collegata
        if ($this->detailDateFrom || $this->detailDateTo) {
            $query->where(function ($q) {
                // Fatture ricevute
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_received')
                        ->whereColumn('invoices_received.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_received')
                        ->when($this->detailDateFrom, fn($s) => $s->whereDate('invoices_received.invoice_date', '>=', $this->detailDateFrom))
                        ->when($this->detailDateTo,   fn($s) => $s->whereDate('invoices_received.invoice_date', '<=', $this->detailDateTo));
                })
                // Fatture emesse
                ->orWhereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('invoices_sent')
                        ->whereColumn('invoices_sent.id', 'invoice_row.document_id')
                        ->where('invoice_row.document_type', 'invoice_sent')
                        ->when($this->detailDateFrom, fn($s) => $s->whereDate('invoices_sent.invoice_date', '>=', $this->detailDateFrom))
                        ->when($this->detailDateTo,   fn($s) => $s->whereDate('invoices_sent.invoice_date', '<=', $this->detailDateTo));
                });
            });
        }

        return $query->orderBy('invoice_row.id', 'desc')->get();
    }

    /**
     * Attività giornaliere con personale per il centro di costo nel periodo.
     * Raggruppa per data → lista staff con ore.
     */
    public function getDetailActivitiesProperty()
    {
        if (!$this->detailCostCenter) {
            return collect();
        }

        $query = Activity::with([
                'service:id,Titolo',
                'staffDetails.staff:id_personale,NomePers,CognomePers,Soprannome',
            ])
            ->where('id_cost_centers', $this->detailCostCenter->id)
            ->when($this->detailDateFrom, fn($q) => $q->whereDate('data_activities', '>=', $this->detailDateFrom))
            ->when($this->detailDateTo,   fn($q) => $q->whereDate('data_activities', '<=', $this->detailDateTo))
            ->orderBy('data_activities', 'asc');

        return $query->get();
    }

    /**
     * Totali riepilogativi per il modal dettaglio.
     */
    public function getDetailTotalsProperty(): array
    {
        if (!$this->detailCostCenter) {
            return ['invoice_total' => 0, 'total_ha' => 0, 'total_ore' => 0];
        }

        $invoiceTotal = $this->detailInvoices->sum('total');

        $activities = $this->detailActivities;
        $totalHa  = $activities->sum('ha');
        $totalOre = $activities->sum(function ($act) {
            return $act->staffDetails->sum('n_ore');
        });

        return [
            'invoice_total' => $invoiceTotal,
            'total_ha'      => $totalHa,
            'total_ore'     => $totalOre,
        ];
    }
    
    // ==================== CAMBIO STATO ====================
    
    public function toggleStatus($id)
    {
        try {
            $costCenter = CostCenter::find($id);
            
            if (!$costCenter) {
                $this->dispatch('showError', message: 'Centro di Costo non trovato');
                return;
            }
            
            $newStatus = $costCenter->valid == 1 ? 0 : 1;
            $costCenter->update([
                'valid' => $newStatus,
                'updated_by' => Auth::guard('admin')->id()
            ]);
            
            $statusText = $newStatus == 1 ? 'attivato' : 'disattivato';
            $this->dispatch('showSuccess', message: "Centro di Costo '{$costCenter->nome}' {$statusText} con successo!");
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }
    
    // ==================== ELIMINAZIONE ====================
    
    public function confirmDelete($id)
    {
        $costCenter = CostCenter::find($id);
        
        if (!$costCenter) {
            $this->dispatch('showError', message: 'Centro di Costo non trovato');
            return;
        }
        
        if (!Auth::guard('admin')->user()->hasPermission('delete_cost_centers')) {
            $this->dispatch('showError', message: 'Non hai i permessi per eliminare');
            return;
        }
        
        $this->dispatch('confirmDelete', id: $id, name: $costCenter->nome);
    }
    
    public function deleteCostCenter($id)
    {
        try {
            $costCenter = CostCenter::find($id);
            
            if (!$costCenter) {
                $this->dispatch('showError', message: 'Centro di Costo non trovato');
                return;
            }
            
            $name = $costCenter->nome;
            $costCenter->delete();
            
            $this->dispatch('showSuccess', message: "Centro di Costo '{$name}' eliminato con successo!");
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    // ==================== RENDER ====================
    
    public function render()
    {
        return view('livewire.admin.cost-centers-table', [
            'costCenters'   => $this->costCenters,
            'referenceTypes' => $this->referenceTypes,
            'statuses'       => $this->statuses,
            'referenceList'  => $this->referenceList,
        ]);
    }
}