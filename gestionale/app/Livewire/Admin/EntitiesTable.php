<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Entity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntitiesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $typeFilter = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'ragione_sociale';
    public $sortDirection = 'asc';
    
    // Modal inserimento
    public $showCreateModal = false;
    public $formNome = '';
    public $formCognome = '';
    public $formEmail = '';
    public $formTipologia = '';
    public $formRagioneSociale = '';
    public $formPartitaIva = '';
    public $formRiferimento = '';
    
    // Modal eliminazione
    public $showDeleteModal = false;
    public $entityToDelete = null;
    public $entityNameToDelete = '';
    
    // Modal visualizzazione dettagli
    public $showViewModal = false;
    public $viewingEntity = null;
    
    // Modal modifica
    public $showEditModal = false;
    public $editingEntity = null;
    public $editId = null;
    public $editNome = '';
    public $editCognome = '';
    public $editEmail = '';
    public $editTipologia = '';
    public $editRagioneSociale = '';
    public $editPartitaIva = '';
    public $editRiferimento = '';
    public $editCodiceFiscale = '';
    public $editPec = '';
    public $editCodiceSdi = '';
    public $editValid = true;
    
    protected $queryString = ['search', 'typeFilter', 'statusFilter', 'sortField', 'sortDirection'];
    
    protected $listeners = [
        'refreshTable' => '$refresh',
        'openCreateModal' => 'openCreateModal'
    ];
    
    public function refreshTable()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingTypeFilter()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingPerPage()
    {
        $this->resetPage();
    }
    
    public function getEntityTypesProperty()
    {
        return [
            'cliente' => 'Cliente',
            'fornitore' => 'Fornitore',
            'entrambi' => 'Entrambi'
        ];
    }
    
    public function getEntitiesProperty()
    {
        $query = Entity::query();
        
        $query->with(['contacts' => function($q) {
            $q->orderBy('id_settings');
        }]);
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm);
            });
        }
        
        if ($this->typeFilter) {
            $query->where('entity_type', $this->typeFilter);
        }
        
        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter === 'active');
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->paginate($this->perPage);
    }
    
    // ==================== METODI VISUALIZZAZIONE ====================
    
    public function viewEntity($id)
    {
        try {
            $entity = Entity::with(['contacts' => function($q) {
                $q->with('setting');
            }])->find($id);
            
            if (!$entity) {
                session()->flash('error', 'Cliente/Fornitore non trovato');
                return;
            }
            
            $this->viewingEntity = $entity;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore nel caricamento dei dettagli: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingEntity = null;
    }
    
    // ==================== METODI MODIFICA ====================
    
    public function openEditModal($id)
    {
        try {
            $entity = Entity::find($id);
            
            if (!$entity) {
                session()->flash('error', 'Cliente/Fornitore non trovato');
                return;
            }
            
            $this->editingEntity = $entity;
            $this->editId = $entity->id_cliente;
            $this->editNome = $entity->nome;
            $this->editCognome = $entity->cognome;
            $this->editEmail = $entity->email;
            $this->editTipologia = $entity->entity_type;
            $this->editRagioneSociale = $entity->ragione_sociale;
            $this->editPartitaIva = $entity->partita_iva;
            $this->editRiferimento = $entity->persona_riferimento;
            $this->editCodiceFiscale = $entity->codice_fiscale;
            $this->editPec = $entity->pec;
            $this->editCodiceSdi = $entity->codice_sdi;
            $this->editValid = $entity->valid;
            
            $this->showEditModal = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore nel caricamento dei dati: ' . $e->getMessage());
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingEntity = null;
        $this->reset([
            'editId', 'editNome', 'editCognome', 'editEmail', 'editTipologia',
            'editRagioneSociale', 'editPartitaIva', 'editRiferimento',
            'editCodiceFiscale', 'editPec', 'editCodiceSdi', 'editValid'
        ]);
    }
    
    public function updateEntity()
    {
        $this->validate([
            'editTipologia' => 'required|in:cliente,fornitore,entrambi',
            'editEmail' => 'nullable|email',
            'editPec' => 'nullable|email',
        ]);
        
        try {
            $entity = Entity::find($this->editId);
            
            if (!$entity) {
                throw new \Exception('Entità non trovata');
            }
            
            $entity->update([
                'entity_type' => $this->editTipologia,
                'ragione_sociale' => $this->editRagioneSociale ?: null,
                'nome' => $this->editNome ?: null,
                'cognome' => $this->editCognome ?: null,
                'persona_riferimento' => $this->editRiferimento ?: null,
                'email' => $this->editEmail ?: null,
                'partita_iva' => $this->editPartitaIva ?: null,
                'codice_fiscale' => $this->editCodiceFiscale ?: null,
                'pec' => $this->editPec ?: null,
                'codice_sdi' => $this->editCodiceSdi ?: null,
                'valid' => $this->editValid
            ]);
            
            $this->closeEditModal();
            session()->flash('success', 'Cliente/Fornitore aggiornato con successo!');
            $this->refreshTable();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }
    
    // ==================== METODI ELIMINAZIONE ====================
    
    public function confirmDelete($id)
    {
        $entity = Entity::find($id);
        
        if (!$entity) {
            session()->flash('error', 'Cliente/Fornitore non trovato');
            return;
        }
        
        $this->entityToDelete = $entity;
        $this->entityNameToDelete = $entity->full_name;
        $this->showDeleteModal = true;
    }
    
    public function deleteEntity()
    {
        try {
            if (!$this->entityToDelete) {
                session()->flash('error', 'Nessun elemento selezionato per l\'eliminazione');
                return;
            }
            
            $entityName = $this->entityToDelete->full_name;
            $this->entityToDelete->delete();
            
            session()->flash('success', "Cliente/Fornitore '{$entityName}' eliminato con successo!");
            
            $this->showDeleteModal = false;
            $this->entityToDelete = null;
            $this->entityNameToDelete = '';
            
            $this->refreshTable();
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
            $this->showDeleteModal = false;
        }
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->entityToDelete = null;
        $this->entityNameToDelete = '';
    }
    
    // ==================== METODI STATO ====================
    
    public function toggleStatus($id)
    {
        try {
            $entity = Entity::find($id);
            if ($entity) {
                $entity->update(['valid' => !$entity->valid]);
                $status = $entity->valid ? 'attivato' : 'disattivato';
                session()->flash('success', "Cliente/Fornitore {$status} con successo!");
                $this->refreshTable();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante il cambio di stato');
        }
    }
    
    // ==================== METODI FILTRI ====================
    
    public function resetFilters()
    {
        $this->search = '';
        $this->typeFilter = '';
        $this->statusFilter = '';
        $this->sortField = 'ragione_sociale';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    // ==================== METODI INSERIMENTO ====================
    
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
        $this->formNome = '';
        $this->formCognome = '';
        $this->formEmail = '';
        $this->formTipologia = '';
        $this->formRagioneSociale = '';
        $this->formPartitaIva = '';
        $this->formRiferimento = '';
    }
    
    public function save()
    {
        $this->validate([
            'formTipologia' => 'required|in:cliente,fornitore,entrambi',
            'formEmail' => 'nullable|email',
        ]);
        
        try {
            $id = DB::table('entities')->insertGetId([
                'entity_type' => $this->formTipologia,
                'ragione_sociale' => $this->formRagioneSociale ?: null,
                'nome' => $this->formNome ?: null,
                'cognome' => $this->formCognome ?: null,
                'persona_riferimento' => $this->formRiferimento ?: null,
                'email' => $this->formEmail ?: null,
                'partita_iva' => $this->formPartitaIva ?: null,
                'valid' => 1,
                'data_inserimento' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            if ($id) {
                $this->closeCreateModal();
                session()->flash('success', 'Cliente/Fornitore creato con successo!');
                $this->refreshTable();
            } else {
                throw new \Exception('Nessun ID restituito');
            }
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.entities-table', [
            'entities' => $this->entities,
            'entityTypes' => $this->entityTypes
        ]);
    }
}