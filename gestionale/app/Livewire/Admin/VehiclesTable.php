<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Vehicles;
use App\Models\Ownership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VehiclesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipoFilter = '';
    public $statoFilter = '';
    public $ownershipFilter = '';
    public $perPage = 15;
    public $sortField = 'id';
    public $sortDirection = 'asc';
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingVehicle = null;
    
    // Modal creazione
    public $showCreateModal = false;
    public $createTarga = '';
    public $createMarca = '';
    public $createModello = '';
    public $createTipologia = '';
    public $createImmatricolazione = '';
    public $createValid = 1;
    public $createIdOwnership = '';
    public $createNote = '';
    
    // Modal modifica
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
    protected $queryString = ['search', 'tipoFilter', 'statoFilter', 'ownershipFilter', 'sortField', 'sortDirection'];
    
    protected $listeners = [
        'filters-reset' => 'resetFilters'
    ];
    
    // ==================== TIPI E STATI ====================
    
    public function getTipiListProperty()
    {
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
        $ownerships = Ownership::all();
        $list = [];
        
        foreach ($ownerships as $ownership) {
            if (!empty($ownership->RagAbbrev)) {
                $nome = $ownership->RagAbbrev;
            } 
            elseif (!empty($ownership->Rag_Soc_intest)) {
                $nome = $ownership->Rag_Soc_intest;
            }
            elseif (!empty($ownership->RagSocialePr)) {
                $nome = $ownership->RagSocialePr;
            }
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
        Log::info('VehiclesTable: getVehiclesProperty chiamato', [
            'search' => $this->search,
            'tipoFilter' => $this->tipoFilter,
            'statoFilter' => $this->statoFilter,
            'ownershipFilter' => $this->ownershipFilter,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection
        ]);
        
        $query = Vehicles::with('ownership', 'expirations');
        
        // Filtri di ricerca
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
        
        if ($this->ownershipFilter) {
            $query->where('id_ownership', $this->ownershipFilter);
        }
        
        // Ordinamento esplicito per campo
        switch ($this->sortField) {
            case 'id':
                $query->orderBy('id', $this->sortDirection);
                break;
            case 'id_ownership':
                $query->orderBy('id_ownership', $this->sortDirection);
                break;
            case 'tipologia':
                $query->orderBy('tipologia', $this->sortDirection);
                break;
            case 'marca':
                $query->orderBy('marca', $this->sortDirection)
                      ->orderBy('modello', $this->sortDirection);
                break;
            case 'targa':
                $query->orderBy('targa', $this->sortDirection);
                break;
            case 'immatricolazione':
                $query->orderBy('immatricolazione', $this->sortDirection);
                break;
            case 'valid':
                $query->orderBy('valid', $this->sortDirection);
                break;
            default:
                $query->orderBy('id', 'asc');
                break;
        }
        
        return $query->paginate($this->perPage);
    }
    
    // ==================== SORTING ====================
    
    public function sortBy($field)
    {
        Log::info('VehiclesTable: sortBy chiamato', ['field' => $field]);
        
        // Lista dei campi validi per l'ordinamento
        $validSortFields = ['id', 'id_ownership', 'tipologia', 'marca', 'targa', 'immatricolazione', 'valid'];
        
        if (!in_array($field, $validSortFields)) {
            Log::warning('VehiclesTable: sortBy - campo non valido', ['field' => $field]);
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
        Log::info('VehiclesTable: resetFilters chiamato');
        
        $this->search = '';
        $this->tipoFilter = '';
        $this->statoFilter = '';
        $this->ownershipFilter = '';
        $this->sortField = 'id';
        $this->sortDirection = 'asc';
        $this->resetPage();
        $this->dispatch('filters-reset');
        $this->dispatch('table-refreshed');
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
    
    public function updatingOwnershipFilter()
    {
        $this->resetPage();
    }
    
    // ==================== VISUALIZZAZIONE ====================
    
    public function viewVehicle($id)
    {
        Log::info('VehiclesTable: viewVehicle chiamato', ['id' => $id]);
        
        try {
            $vehicle = Vehicles::with(['createdBy', 'updatedBy', 'ownership'])->find($id);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $this->viewingVehicle = $vehicle;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: viewVehicle errore', ['error' => $e->getMessage()]);
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
        Log::info('VehiclesTable: openCreateModal chiamato');
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
        Log::info('VehiclesTable: saveVehicle chiamato', ['targa' => $this->createTarga]);
        
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
            
            Log::info('VehiclesTable: saveVehicle successo', ['id' => $vehicle->id]);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' creato con successo!");
            $this->resetPage();
            $this->dispatch('table-refreshed');
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: saveVehicle errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }
    
    // ==================== MODIFICA ====================
    
    public function openEditModal($id)
    {
        Log::info('VehiclesTable: openEditModal chiamato', ['id' => $id]);
        
        try {
            $vehicle = Vehicles::with('ownership')->find($id);
            
            if (!$vehicle) {
                Log::error('VehiclesTable: openEditModal - mezzo non trovato', ['id' => $id]);
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            Log::info('VehiclesTable: openEditModal - vehicle trovato', [
                'id' => $vehicle->id,
                'targa' => $vehicle->targa,
                'tipologia' => $vehicle->tipologia,
                'valid' => $vehicle->valid,
                'id_ownership' => $vehicle->id_ownership
            ]);
            
            $this->editId = $vehicle->id;
            $this->editTarga = $vehicle->targa;
            $this->editMarca = $vehicle->marca;
            $this->editModello = $vehicle->modello;
            $this->editTipologia = $vehicle->tipologia;
            $this->editImmatricolazione = $vehicle->immatricolazione ? date('Y-m-d', strtotime($vehicle->immatricolazione)) : '';
            $this->editValid = $vehicle->valid;
            $this->editIdOwnership = $vehicle->id_ownership;
            $this->editNote = $vehicle->note;
            
            Log::info('VehiclesTable: openEditModal successo - dati caricati', [
                'editId' => $this->editId,
                'editTarga' => $this->editTarga,
                'editTipologia' => $this->editTipologia,
                'editIdOwnership' => $this->editIdOwnership
            ]);
            
            $this->showEditModal = true;
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: openEditModal errore', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeEditModal()
    {
        Log::info('VehiclesTable: closeEditModal chiamato');
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
        Log::info('VehiclesTable: updateVehicle chiamato', [
            'editId' => $this->editId,
            'editTarga' => $this->editTarga,
            'editTipologia' => $this->editTipologia,
            'editIdOwnership' => $this->editIdOwnership,
            'editValid' => $this->editValid
        ]);
        
        // Validazione con messaggi di errore dettagliati
        try {
            $validator = validator([
                'editTarga' => $this->editTarga,
                'editMarca' => $this->editMarca,
                'editModello' => $this->editModello,
                'editTipologia' => $this->editTipologia,
                'editImmatricolazione' => $this->editImmatricolazione,
                'editIdOwnership' => $this->editIdOwnership,
            ], [
                'editTarga' => 'required|string|max:20|unique:vehicles,targa,' . $this->editId,
                'editMarca' => 'nullable|string|max:255',
                'editModello' => 'nullable|string|max:255',
                'editTipologia' => 'required|string|max:50',
                'editImmatricolazione' => 'nullable|date',
                'editIdOwnership' => 'required|exists:ownership,id_proprieta',
            ]);
            
            if ($validator->fails()) {
                Log::error('VehiclesTable: updateVehicle - validazione fallita', [
                    'errors' => $validator->errors()->toArray()
                ]);
                $this->dispatch('showError', message: 'Errore di validazione: ' . json_encode($validator->errors()->toArray()));
                return;
            }
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: updateVehicle - eccezione validazione', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore di validazione: ' . $e->getMessage());
            return;
        }
        
        try {
            $vehicle = Vehicles::find($this->editId);
            
            if (!$vehicle) {
                Log::error('VehiclesTable: updateVehicle - mezzo non trovato', ['editId' => $this->editId]);
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            Log::info('VehiclesTable: updateVehicle - vehicle trovato prima dell\'update', [
                'id' => $vehicle->id,
                'old_targa' => $vehicle->targa,
                'new_targa' => strtoupper($this->editTarga),
                'old_valid' => $vehicle->valid,
                'new_valid' => $this->editValid
            ]);
            
            $updateData = [
                'targa' => strtoupper($this->editTarga),
                'marca' => $this->editMarca,
                'modello' => $this->editModello,
                'tipologia' => $this->editTipologia,
                'immatricolazione' => $this->editImmatricolazione ?: null,
                'valid' => $this->editValid,
                'id_ownership' => $this->editIdOwnership,
                'note' => $this->editNote,
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ];
            
            Log::info('VehiclesTable: updateVehicle - dati da aggiornare', $updateData);
            
            $result = $vehicle->update($updateData);
            
            Log::info('VehiclesTable: updateVehicle - risultato update', [
                'result' => $result,
                'affected_rows' => $vehicle->wasChanged() ? 'si' : 'no',
                'changes' => $vehicle->getChanges()
            ]);
            
            // Verifica se l'update ha avuto successo
            $updatedVehicle = Vehicles::find($this->editId);
            
            Log::info('VehiclesTable: updateVehicle - verifica post update', [
                'targa_nel_db' => $updatedVehicle->targa,
                'targa_inserita' => strtoupper($this->editTarga)
            ]);
            
            $this->closeEditModal();
            $this->dispatch('showSuccess', message: "Mezzo '{$updatedVehicle->targa}' aggiornato con successo!");
            $this->resetPage();
            $this->dispatch('table-refreshed');
            
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('VehiclesTable: updateVehicle - errore database', [
                'error' => $e->getMessage(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? []
            ]);
            $this->dispatch('showError', message: 'Errore database: ' . $e->getMessage());
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: updateVehicle - errore generico', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }
    
    // ==================== ELIMINAZIONE ====================
    
    public function confirmDelete($id)
    {
        Log::info('VehiclesTable: confirmDelete chiamato', ['id' => $id]);
        
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
        Log::info('VehiclesTable: deleteVehicle chiamato', ['deleteId' => $this->deleteId]);
        
        try {
            $vehicle = Vehicles::find($this->deleteId);
            
            if (!$vehicle) {
                $this->dispatch('showError', message: 'Mezzo non trovato');
                return;
            }
            
            $vehicleName = $vehicle->full_name ?? $vehicle->targa;
            $vehicle->delete();
            
            Log::info('VehiclesTable: deleteVehicle successo', ['deleted' => $vehicleName]);
            
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicleName}' eliminato con successo!");
            
            $this->showDeleteModal = false;
            $this->deleteId = null;
            $this->deleteName = '';
            
            $this->resetPage();
            $this->dispatch('table-refreshed');
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: deleteVehicle errore', ['error' => $e->getMessage()]);
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
        Log::info('VehiclesTable: toggleStatus chiamato', ['id' => $id]);
        
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
                'updated_at' => now()
            ]);
            
            $statusText = $newStatus == 1 ? 'attivato' : 'disattivato';
            Log::info('VehiclesTable: toggleStatus successo', ['id' => $id, 'newStatus' => $newStatus]);
            
            $this->dispatch('showSuccess', message: "Mezzo '{$vehicle->targa}' {$statusText} con successo!");
            $this->resetPage();
            $this->dispatch('table-refreshed');
            
        } catch (\Exception $e) {
            Log::error('VehiclesTable: toggleStatus errore', ['error' => $e->getMessage()]);
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }
    
    // ==================== METODO PER SCADENZE ====================
    
    public function goToExpiration($vehicleId)
    {
        Log::info('VehiclesTable: goToExpiration chiamato', ['vehicleId' => $vehicleId]);
        return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId]);
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