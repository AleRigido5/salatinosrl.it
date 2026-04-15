<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Entity;
use App\Models\Address;
use App\Models\Contact;
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
    
    protected $queryString = ['search', 'typeFilter', 'statusFilter'];
    
    protected $listeners = [
        'refreshTable' => 'refreshTable',
        'openCreateModal' => 'openCreateModal',
        'closeViewModal' => 'closeViewModal',
        'closeCreateModal' => 'closeCreateModal',
        'redirectToEdit' => 'redirectToEdit'
    ];
    
    public function refreshTable()
    {
        $this->resetPage();
        $this->dispatch('tableRefreshed');
    }
    
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
        
        // Filtro di ricerca
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('pec', 'like', $searchTerm)
                  ->orWhere('partita_iva', 'like', $searchTerm)
                  ->orWhere('codice_fiscale', 'like', $searchTerm)
                  ->orWhere('persona_riferimento', 'like', $searchTerm)
                  // Ricerca su address
                  ->orWhereExists(function($subq) use ($searchTerm) {
                      $subq->select(DB::raw(1))
                          ->from('address')
                          ->whereColumn('address.clienti_id_cliente', 'entities.id_cliente')
                          ->where(function($add) use ($searchTerm) {
                              $add->where('address.indirizzo', 'like', $searchTerm)
                                  ->orWhere('address.citta', 'like', $searchTerm)
                                  ->orWhere('address.provincia', 'like', $searchTerm)
                                  ->orWhere('address.cap', 'like', $searchTerm);
                          });
                  })
                  // Ricerca su contacts
                  ->orWhereExists(function($subq) use ($searchTerm) {
                      $subq->select(DB::raw(1))
                          ->from('contacts')
                          ->whereColumn('contacts.id_entities', 'entities.id_cliente')
                          ->where('contacts.valore', 'like', $searchTerm);
                  });
            });
        }
        
        // Filtro per tipo entità
        if ($this->typeFilter) {
            $query->where('entity_type', $this->typeFilter);
        }
        
        // Filtro per stato
        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter === 'active');
        }
        
        // Ordina
        $query->orderBy($this->sortField, $this->sortDirection);
        
        // Carica le relazioni e paginate
        return $query->with([
            'contacts' => function($q) {
                $q->orderBy('id_settings')->with('setting');
            },
            'addresses' // Rimuoviamo la relazione addressType che non esiste
        ])->paginate($this->perPage);
    }
    
    // ==================== METODI VISUALIZZAZIONE ====================
    
    public function viewEntity($id)
    {
        try {
            $entity = Entity::with([
                'contacts' => function($q) {
                    $q->orderBy('id_settings')->with('setting');
                },
                'addresses' // Rimuoviamo la relazione addressType che non esiste
            ])->find($id);
            
            if (!$entity) {
                $this->dispatch('showError', message: 'Cliente/Fornitore non trovato');
                return;
            }
            
            $this->viewingEntity = $entity;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingEntity = null;
    }
    
    // ==================== METODO MODIFICA ====================
    
    public function openEditPage($id)
    {
        // Reindirizza alla pagina di modifica
        return redirect()->route('admin.entities.edit', $id);
    }
    
    public function redirectToEdit($id)
    {
        return redirect()->route('admin.entities.edit', $id);
    }
    
    // ==================== METODI ELIMINAZIONE ====================
    
    public function confirmDelete($id)
    {
        $entity = Entity::find($id);
        
        if (!$entity) {
            $this->dispatch('showError', message: 'Cliente/Fornitore non trovato');
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
                $this->dispatch('showError', message: 'Nessun elemento selezionato');
                return;
            }
            
            $entityName = $this->entityToDelete->full_name;
            $this->entityToDelete->delete();
            
            $this->dispatch('showSuccess', message: "Cliente/Fornitore '{$entityName}' eliminato con successo!");
            
            $this->showDeleteModal = false;
            $this->entityToDelete = null;
            $this->entityNameToDelete = '';
            
            $this->refreshTable();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'eliminazione: ' . $e->getMessage());
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
                $this->dispatch('showSuccess', message: "Cliente/Fornitore {$status} con successo!");
                $this->refreshTable();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato');
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
            'formPartitaIva' => 'nullable|string|max:20',
        ]);
        
        try {
            $entity = Entity::create([
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
            
            if ($entity) {
                $this->closeCreateModal();
                $this->dispatch('showSuccess', message: 'Cliente/Fornitore creato con successo!');
                $this->refreshTable();
            } else {
                throw new \Exception('Errore durante la creazione');
            }
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
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