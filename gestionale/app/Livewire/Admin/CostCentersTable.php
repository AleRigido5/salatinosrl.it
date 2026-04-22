<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CostCenter;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Support\Facades\Auth;

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
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingCostCenter = null;
    
    protected $queryString = ['search', 'referenceFilter', 'statusFilter', 'typeFilter', 'sortField', 'sortDirection'];
    protected $paginationTheme = 'tailwind';
    
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
        // Rimuoviamo il filtro 'valid' perché potrebbe non esistere
        $ownerships = Ownership::all(); // Rimuovi ->where('valid', 1)
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
        
        // Carica le relazioni manualmente
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
    
    // ==================== VISUALIZZAZIONE ====================
    
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
            'costCenters' => $this->costCenters,
            'referenceTypes' => $this->referenceTypes,
            'statuses' => $this->statuses,
            'referenceList' => $this->referenceList,
        ]);
    }
}