<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vehicles;
use App\Models\Ownership;
use Illuminate\Support\Facades\Auth;

class VehiclesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipoFilter = '';
    public $statoFilter = '';
    public $perPage = 15;
    public $sortField = 'targa';
    public $sortDirection = 'asc';
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingVehicle = null;
    
    // Modal creazione - CAMPI CORRETTI PER IL DB
    public $showCreateModal = false;
    public $createTarga = '';
    public $createMarca = '';
    public $createModello = '';
    public $createTipologia = '';
    public $createImmatricolazione = '';
    public $createValid = 1;
    public $createIdOwnership = '';
    public $createNote = '';
    
    // Modal modifica - CAMPI CORRETTI PER IL DB
    public $showEditModal = false;
    public $editId = null;
    public $editTarga = '';
    public $editMarca = '';
    public $editModello = '';
    public $editTipologia = '';
    public $editImmatricolazione = '';
    public $editValid = 1;
    public $editIdOwnership = '';
    public $editNote = '';
    
    // Modal eliminazione
    public $showDeleteModal = false;
    public $deleteId = null;
    public $deleteName = '';
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipoFilter', 'statoFilter'];
    
    // ==================== TIPI E STATI ====================
    
    public function getTipiListProperty()
    {
        // Estrai i tipi unici dal database
        $tipi = Vehicles::select('tipologia')
            ->whereNotNull('tipologia')
            ->distinct()
            ->pluck('tipologia', 'tipologia')
            ->toArray();
        
        if (empty($tipi)) {
            return [
                'Autocarro' => 'Autocarro',
                'Autovettura' => 'Autovettura',
                'Trattore Stradale' => 'Trattore Stradale',
                'Trattrice Agricola' => 'Trattrice Agricola',
                'Macchina Agricola' => 'Macchina Agricola',
                'Rimorchio' => 'Rimorchio',
                'Attrezzature' => 'Attrezzature',
                'Vendemmiatrice' => 'Vendemmiatrice',
                'Escavatore' => 'Escavatore',
                'Furgone' => 'Furgone',
                'motociclo' => 'motociclo'
            ];
        }
        
        return $tipi;
    }
    
    public function getProprietaListProperty()
    {
        // IMPORTANTE: Recupera tutte le proprietà dalla tabella ownership
        $ownerships = Ownership::all();
        $list = [];
        
        foreach ($ownerships as $ownership) {
            // Usa RagAbbrev come nome visualizzato (es. "Salatino s.r.l.")
            if (!empty($ownership->RagAbbrev)) {
                $nome = $ownership->RagAbbrev;
            } 
            // Altrimenti usa Rag_Soc_intest (es. "SALATINO s.r.l.")
            elseif (!empty($ownership->Rag_Soc_intest)) {
                $nome = $ownership->Rag_Soc_intest;
            }
            // Altrimenti usa RagSocialePr
            elseif (!empty($ownership->RagSocialePr)) {
                $nome = $ownership->RagSocialePr;
            }
            // Altrimenti usa un nome generico
            else {
                $nome = 'Proprietà ' . $ownership->id_proprieta;
            }
            
            $list[$ownership->id_proprieta] = $nome;
        }
        
        return $list;
    }
    
    public function getStatiListProperty()
    {
        return [
            '1' => 'Attivi',
            '0' => 'Disattivi'
        ];
    }
    
    // ==================== QUERY ====================
    
    public function getVehiclesProperty()
    {
        $query = Vehicles::with('ownership');
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('targa', 'like', '%'.$this->search.'%')
                  ->orWhere('marca', 'like', '%'.$this->search.'%')
                  ->orWhere('modello', 'like', '%'.$this->search.'%');
            });
        }
        
        if ($this->tipoFilter) {
            $query->where('tipologia', $this->tipoFilter);
        }
        
        if ($this->statoFilter !== '') {
            $query->where('valid', $this->statoFilter);
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->paginate($this->perPage);
    }
    
    // ==================== SORTING ====================
    
    public function sortBy($field)
    {
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
        $this->tipoFilter = '';
        $this->statoFilter = '';
        $this->sortField = 'targa';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingTipoFilter()
    {
        $this->resetPage();
    }
    
    public function updatingStatoFilter()
    {
        $this->resetPage();
    }
    
    // ==================== SALVA FILTRI IN SESSIONE ====================
    
    public function saveFiltersToSession()
    {
        session(['vehicles_filters' => [
            'search' => $this->search,
            'tipoFilter' => $this->tipoFilter,
            'statoFilter' => $this->statoFilter,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection
        ]]);
    }
    
    // ==================== VISUALIZZAZIONE ====================
    
    public function viewVehicle($id)
    {
        try {
            $vehicle = Vehicles::with(['createdBy', 'updatedBy', 'ownership'])->find($id);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $this->viewingVehicle = $vehicle;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingVehicle = null;
    }
    
    // ==================== CREAZIONE ====================
    
    public function openCreateModal()
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }
    
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetCreateForm();
    }
    
    public function resetCreateForm()
    {
        $this->createTarga = '';
        $this->createMarca = '';
        $this->createModello = '';
        $this->createTipologia = '';
        $this->createImmatricolazione = '';
        $this->createValid = 1;
        $this->createIdOwnership = '';
        $this->createNote = '';
    }
    
    public function saveVehicle()
    {
        $this->validate([
            'createTarga' => 'required|string|max:20|unique:vehicles,targa',
            'createMarca' => 'nullable|string|max:255',
            'createModello' => 'nullable|string|max:255',
            'createTipologia' => 'required|string|max:50',
            'createImmatricolazione' => 'nullable|date',
            'createIdOwnership' => 'required|exists:ownership,id_proprieta',
        ]);
        
        try {
            $adminId = Auth::guard('admin')->id();
            
            $vehicle = Vehicles::create([
                'targa' => strtoupper($this->createTarga),
                'marca' => $this->createMarca,
                'modello' => $this->createModello,
                'tipologia' => $this->createTipologia,
                'immatricolazione' => $this->createImmatricolazione ?: null,
                'valid' => $this->createValid,
                'id_ownership' => $this->createIdOwnership,
                'note' => $this->createNote,
                'created_by' => $adminId,
                'updated_by' => $adminId
            ]);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' creato con successo!");
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }
    
    // ==================== MODIFICA ====================
    
    public function openEditModal($id)
    {
        try {
            $vehicle = Vehicles::with('ownership')->find($id);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $this->editId = $vehicle->id;
            $this->editTarga = $vehicle->targa;
            $this->editMarca = $vehicle->marca;
            $this->editModello = $vehicle->modello;
            $this->editTipologia = $vehicle->tipologia;
            $this->editImmatricolazione = $vehicle->immatricolazione ? date('Y-m-d', strtotime($vehicle->immatricolazione)) : '';
            $this->editValid = $vehicle->valid;
            $this->editIdOwnership = $vehicle->id_ownership;
            $this->editNote = $vehicle->note;
            
            $this->showEditModal = true;
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetEditForm();
    }
    
    public function resetEditForm()
    {
        $this->editId = null;
        $this->editTarga = '';
        $this->editMarca = '';
        $this->editModello = '';
        $this->editTipologia = '';
        $this->editImmatricolazione = '';
        $this->editValid = 1;
        $this->editIdOwnership = '';
        $this->editNote = '';
    }
    
    public function updateVehicle()
    {
        $this->validate([
            'editTarga' => 'required|string|max:20|unique:vehicles,targa,' . $this->editId,
            'editMarca' => 'nullable|string|max:255',
            'editModello' => 'nullable|string|max:255',
            'editTipologia' => 'required|string|max:50',
            'editImmatricolazione' => 'nullable|date',
            'editIdOwnership' => 'required|exists:ownership,id_proprieta',
        ]);
        
        try {
            $vehicle = Vehicles::find($this->editId);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $vehicle->update([
                'targa' => strtoupper($this->editTarga),
                'marca' => $this->editMarca,
                'modello' => $this->editModello,
                'tipologia' => $this->editTipologia,
                'immatricolazione' => $this->editImmatricolazione ?: null,
                'valid' => $this->editValid,
                'id_ownership' => $this->editIdOwnership,
                'note' => $this->editNote,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            $this->closeEditModal();
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' aggiornato con successo!");
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }
    
    // ==================== ELIMINAZIONE ====================
    
    public function confirmDelete($id)
    {
        $vehicle = Vehicles::find($id);
        
        if (!$vehicle) {
            $this->dispatch('showError', message: 'Mezzo non trovato');
            return;
        }
        
        $this->deleteId = $vehicle->id;
        $this->deleteName = $vehicle->full_name ?? $vehicle->targa;
        $this->showDeleteModal = true;
    }
    
    public function deleteVehicle()
    {
        try {
            $vehicle = Vehicles::find($this->deleteId);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $vehicleName = $vehicle->full_name ?? $vehicle->targa;
            $vehicle->delete();
            
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicleName}' eliminato con successo!");
            
            $this->showDeleteModal = false;
            $this->deleteId = null;
            $this->deleteName = '';
            
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'eliminazione: ' . $e->getMessage());
            $this->showDeleteModal = false;
        }
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->deleteId = null;
        $this->deleteName = '';
    }
    
    // ==================== CAMBIO STATO ====================
    
    public function toggleStatus($id)
    {
        try {
            $vehicle = Vehicles::find($id);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $newStatus = $vehicle->valid == 1 ? 0 : 1;
            $vehicle->update([
                'valid' => $newStatus,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            $statusText = $newStatus == 1 ? 'attivato' : 'disattivato';
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' {$statusText} con successo!");
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }


    }
    
    // ==================== METODO PER SCADENZE ====================
    
    public function goToExpiration($vehicleId)
    {
        $this->saveFiltersToSession();
        return redirect()->route('admin.expiration.index', [
            'entityId' => $vehicleId,
            'entityType' => 'vehicle',
            'entityName' => $this->getVehicleName($vehicleId)
        ]);
    }
    
    private function getVehicleName($vehicleId)
    {
        $vehicle = Vehicles::find($vehicleId);
        return $vehicle ? ($vehicle->full_name ?? $vehicle->targa) : null;
    }
    
    // ==================== RENDER ====================
    
    public function render()
    {
        return view('livewire.admin.vehicles-table', [
            'vehicles' => $this->vehicles,
            'tipiList' => $this->tipiList,
            'statiList' => $this->statiList,
            'proprietaList' => $this->proprietaList,
        ]);
    }
}