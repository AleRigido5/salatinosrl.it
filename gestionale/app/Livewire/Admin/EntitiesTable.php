<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Entity;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\Address;
use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class EntitiesTable extends Component
{
    use WithPagination;

    // Filtri attivi (quelli applicati)
    public $activeSearch = '';
    public $activeTypeFilter = '';
    public $activeStatusFilter = '';
    public $activeRatingFilter = '';
    
    // Filtri temporanei (quelli nel form)
    public $tempSearch = '';
    public $tempTypeFilter = '';
    public $tempStatusFilter = '';
    public $tempRatingFilter = '';

    // Autocomplete ricerca
    public Collection $searchResults;
    public $showSearchDropdown = false;
    
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
    public $formCodiceFiscale = '';
    public $formRiferimento = '';
    
    // Modal eliminazione
    public $showDeleteModal = false;
    public $entityToDelete = null;
    public $entityNameToDelete = '';
    
    // Modal visualizzazione dettagli
    public $showViewModal = false;
    public $viewingEntity = null;
    
    // MODAL CESTINO
    public $showTrashModal = false;
    public $trashSearch = '';
    public $trashTypeFilter = '';
    public $trashSortField = 'deleted_at';
    public $trashSortDirection = 'desc';
    
    // Contatore cestino
    public $trashCount = 0;
    
    protected $queryString = ['activeSearch', 'activeTypeFilter', 'activeStatusFilter', 'activeRatingFilter'];
    
    protected $listeners = [
        'refreshTable' => 'refreshTable',
        'openCreateModal' => 'openCreateModal',
        'closeViewModal' => 'closeViewModal',
        'closeCreateModal' => 'closeCreateModal',
        'redirectToEdit' => 'redirectToEdit',
        'openTrashModal' => 'openTrashModal',
        'closeTrashModal' => 'closeTrashModal',
        'updateTrashCount' => 'updateTrashCount'
    ];
    
    public function mount()
    {
        $this->searchResults = new Collection();
        $this->updateTrashCount();
        $this->syncTempFilters();
    }
    
    public function syncTempFilters()
    {
        $this->tempSearch = $this->activeSearch;
        $this->tempTypeFilter = $this->activeTypeFilter;
        $this->tempStatusFilter = $this->activeStatusFilter;
        $this->tempRatingFilter = $this->activeRatingFilter;
    }
    
    public function refreshTable()
    {
        $this->resetPage();
        $this->dispatch('tableRefreshed');
        $this->updateTrashCount();
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
    
    public function getEntityTypesProperty()
    {
        return [
            'cliente' => 'Cliente',
            'fornitore' => 'Fornitore',
            'entrambi' => 'Entrambi'
        ];
    }
    
    /**
     * Funzione per normalizzare la Partita IVA (rimuove il prefisso del paese)
     */
    private function normalizePartitaIva($partitaIva)
    {
        if (empty($partitaIva)) {
            return $partitaIva;
        }
        
        // Rimuove prefissi paese (IT, DE, FR, ES, GB, ecc.) di 2 lettere
        // Esempio: "IT023750907498" -> "023750907498"
        //          "DE123456789" -> "123456789"
        return preg_replace('/^[A-Z]{2}/', '', $partitaIva);
    }
    
    /**
     * Verifica se una Partita IVA esiste già (ignorando il prefisso del paese)
     */
    private function checkDuplicatePartitaIva($partitaIva, $excludeId = null)
    {
        if (empty($partitaIva)) {
            return null;
        }
        
        $normalizedNew = $this->normalizePartitaIva($partitaIva);
        
        $query = Entity::where(function($q) use ($normalizedNew) {
            // Controllo 1: Partita IVA esattamente uguale alla normalizzata
            $q->where('partita_iva', $normalizedNew)
              // Controllo 2: Partita IVA che finisce con la normalizzata
              ->orWhere('partita_iva', 'LIKE', '%' . $normalizedNew)
              // Controllo 3: Partita IVA senza le prime 2 lettere
              ->orWhereRaw("REPLACE(partita_iva, SUBSTRING(partita_iva, 1, 2), '') = ?", [$normalizedNew]);
        });
        
        if ($excludeId) {
            $query->where('id_cliente', '!=', $excludeId);
        }
        
        return $query->first();
    }

    // ==================== AUTOCOMPLETE RICERCA ====================

    public function updatedTempSearch()
    {
        if (strlen(trim($this->tempSearch)) < 2) {
            $this->searchResults = new Collection();
            $this->showSearchDropdown = false;
            return;
        }

        $searchTerm = '%' . $this->tempSearch . '%';

        $this->searchResults = Entity::where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('partita_iva', 'like', $searchTerm)
                  ->orWhere('codice_fiscale', 'like', $searchTerm)
                  ->orWhere('persona_riferimento', 'like', $searchTerm);
            })
            ->orderBy('ragione_sociale')
            ->limit(8)
            ->get(['id_cliente', 'ragione_sociale', 'nome', 'cognome', 'partita_iva', 'entity_type']);

        $this->showSearchDropdown = $this->searchResults->isNotEmpty();
    }

    public function selectSearchResult($id, $name)
    {
        $this->tempSearch = $name;
        $this->showSearchDropdown = false;
        $this->applyFilters();
    }

    public function clearSearch()
    {
        $this->tempSearch = '';
        $this->searchResults = new Collection();
        $this->showSearchDropdown = false;
    }
    
    public function getEntitiesProperty()
    {
        $query = Entity::query();
        
        // Filtro di ricerca - usa activeSearch
        if ($this->activeSearch) {
            $searchTerm = '%' . $this->activeSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('pec', 'like', $searchTerm)
                  ->orWhere('partita_iva', 'like', $searchTerm)
                  ->orWhere('codice_fiscale', 'like', $searchTerm)
                  ->orWhere('persona_riferimento', 'like', $searchTerm)
                  // Subquery per indirizzi - usa il nome corretto della colonna
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
                  // Subquery per contatti - usa 'contacts' e non 'contatti'
                  ->orWhereExists(function($subq) use ($searchTerm) {
                      $subq->select(DB::raw(1))
                          ->from('contacts')
                          ->whereColumn('contacts.id_entities', 'entities.id_cliente')
                          ->where('contacts.valore', 'like', $searchTerm);
                  });
            });
        }
        
        // Filtro per tipo entità - usa activeTypeFilter
        if ($this->activeTypeFilter) {
            $query->where('entity_type', $this->activeTypeFilter);
        }
        
        // Filtro per stato - usa activeStatusFilter
        if ($this->activeStatusFilter !== '') {
            $query->where('valid', $this->activeStatusFilter === 'active');
        }
        
        // Filtro per valutazione (stelle) - usa activeRatingFilter
        if ($this->activeRatingFilter !== '') {
            $query->where('rating', (int) $this->activeRatingFilter);
        }
        
        // Ordina
        $query->orderBy($this->sortField, $this->sortDirection);
        
        // Carica le relazioni e paginate
        return $query->with([
            'contacts' => function($q) {
                $q->orderBy('id_settings')->with('setting');
            },
            'addresses',
            'createdBy',
            'updatedBy'
        ])->paginate($this->perPage);
    }
    
    // ==================== METODI PER I FILTRI ====================
    
    public function applyFilters()
    {
        $this->activeSearch = $this->tempSearch;
        $this->activeTypeFilter = $this->tempTypeFilter;
        $this->activeStatusFilter = $this->tempStatusFilter;
        $this->activeRatingFilter = $this->tempRatingFilter;
        $this->showSearchDropdown = false;
        $this->resetPage();
        $this->dispatch('filters-applied');
    }
    
    public function removeFilter($filter)
    {
        switch($filter) {
            case 'search':
                $this->activeSearch = '';
                $this->tempSearch = '';
                break;
            case 'type':
                $this->activeTypeFilter = '';
                $this->tempTypeFilter = '';
                break;
            case 'status':
                $this->activeStatusFilter = '';
                $this->tempStatusFilter = '';
                break;
            case 'rating':
                $this->activeRatingFilter = '';
                $this->tempRatingFilter = '';
                break;
        }
        $this->resetPage();
        $this->dispatch('filter-removed');
    }
    
    public function resetFilters()
    {
        $this->activeSearch = '';
        $this->activeTypeFilter = '';
        $this->activeStatusFilter = '';
        $this->activeRatingFilter = '';
        $this->tempSearch = '';
        $this->tempTypeFilter = '';
        $this->tempStatusFilter = '';
        $this->tempRatingFilter = '';
        $this->searchResults = new Collection();
        $this->showSearchDropdown = false;
        $this->resetPage();
        $this->dispatch('filters-reset');
    }
    
    // ==================== METODI PER IL CESTINO ====================
    
    public function updateTrashCount()
    {
        $this->trashCount = Entity::onlyTrashed()->count();
        $this->dispatch('trashCountUpdated', count: $this->trashCount);
    }
    
    public function openTrashModal()
    {
        $this->resetTrashFilters();
        $this->showTrashModal = true;
        $this->updateTrashCount();
    }
    
    public function closeTrashModal()
    {
        $this->showTrashModal = false;
        $this->resetTrashFilters();
        $this->updateTrashCount();
    }
    
    public function resetTrashFilters()
    {
        $this->trashSearch = '';
        $this->trashTypeFilter = '';
        $this->trashSortField = 'deleted_at';
        $this->trashSortDirection = 'desc';
    }
    
    public function trashSortBy($field)
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
        }
    }
    
    public function getTrashedEntitiesProperty()
    {
        $query = Entity::onlyTrashed();
        
        // Filtro di ricerca
        if ($this->trashSearch) {
            $searchTerm = '%' . $this->trashSearch . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('ragione_sociale', 'like', $searchTerm)
                  ->orWhere('nome', 'like', $searchTerm)
                  ->orWhere('cognome', 'like', $searchTerm)
                  ->orWhere('email', 'like', $searchTerm)
                  ->orWhere('partita_iva', 'like', $searchTerm)
                  ->orWhere('codice_fiscale', 'like', $searchTerm)
                  ->orWhere('persona_riferimento', 'like', $searchTerm);
            });
        }
        
        // Filtro per tipo entità
        if ($this->trashTypeFilter) {
            $query->where('entity_type', $this->trashTypeFilter);
        }
        
        // Ordina
        $query->orderBy($this->trashSortField, $this->trashSortDirection);
        
        return $query->with([
            'contacts',
            'addresses',
            'createdBy',
            'updatedBy'
        ])->paginate(10);
    }
    
    public function restoreFromTrash($id)
    {
        try {
            $entity = Entity::onlyTrashed()->find($id);
            if ($entity) {
                $entityName = $entity->full_name;
                $entity->restore();
                $this->dispatch('showSuccess', message: "Cliente/Fornitore '{$entityName}' è stato ripristinato!");
                $this->refreshTable();
                $this->updateTrashCount();
                $this->dispatch('refreshTrashTable');
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il ripristino: ' . $e->getMessage());
        }
    }
    
    public function forceDeleteFromTrash($id)
    {
        try {
            $entity = Entity::onlyTrashed()->find($id);
            if ($entity) {
                $entityName = $entity->full_name;
                $entity->forceDelete();
                $this->dispatch('showSuccess', message: "Cliente/Fornitore '{$entityName}' è stato eliminato definitivamente!");
                $this->refreshTable();
                $this->updateTrashCount();
                $this->dispatch('refreshTrashTable');
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'eliminazione definitiva: ' . $e->getMessage());
        }
    }
    
    // ==================== METODI VISUALIZZAZIONE ====================
    
    public function viewEntity($id)
    {
        try {
            $entity = Entity::with([
                'contacts' => function($q) {
                    $q->orderBy('id_settings')->with('setting');
                },
                'addresses',
                'createdBy',
                'updatedBy'
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
            
            $this->dispatch('showSuccess', message: "Cliente/Fornitore '{$entityName}' è stato spostato nel cestino!");
            
            $this->showDeleteModal = false;
            $this->entityToDelete = null;
            $this->entityNameToDelete = '';
            
            $this->refreshTable();
            $this->updateTrashCount();
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
                $entity->update([
                    'valid' => !$entity->valid,
                    'updated_by' => auth()->guard('admin')->id(),
                    'updated_at' => now()
                ]);
                $status = $entity->valid ? 'attivato' : 'disattivato';
                $this->dispatch('showSuccess', message: "Cliente/Fornitore {$status} con successo!");
                $this->refreshTable();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
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
        $this->formCodiceFiscale = '';
        $this->formRiferimento = '';
    }
    
    public function save()
    {
        $this->validate([
            'formTipologia' => 'required|in:cliente,fornitore,entrambi',
            'formRagioneSociale' => 'required|string|max:255',
            'formPartitaIva' => 'required|string|max:30',
            'formEmail' => 'nullable|email',
            'formCodiceFiscale' => 'nullable|string|max:20',
        ], [
            'formTipologia.required' => 'La tipologia è obbligatoria',
            'formTipologia.in' => 'Seleziona un tipo valido',
            'formRagioneSociale.required' => 'La ragione sociale è obbligatoria',
            'formRagioneSociale.max' => 'La ragione sociale non può superare i 255 caratteri',
            'formPartitaIva.required' => 'La partita IVA è obbligatoria',
            'formPartitaIva.max' => 'La partita IVA non può superare i 30 caratteri',
            'formEmail.email' => 'Inserisci un indirizzo email valido',
        ]);

        try {
            // Normalizza la Partita IVA (rimuove prefisso paese)
            $normalizedPartitaIva = $this->normalizePartitaIva($this->formPartitaIva);
            
            // CONTROLLO DUPLICATI PARTITA IVA (ignorando il prefisso del paese)
            $existingEntity = $this->checkDuplicatePartitaIva($this->formPartitaIva);
            if ($existingEntity) {
                $this->dispatch('showError', message: "Partita IVA '{$this->formPartitaIva}' già presente in archivio per: " . $existingEntity->full_name);
                return;
            }
            
            // Controllo anche codice fiscale se presente
            if (!empty($this->formCodiceFiscale)) {
                $existingByCF = Entity::where('codice_fiscale', $this->formCodiceFiscale)->first();
                if ($existingByCF) {
                    $this->dispatch('showError', message: "Codice Fiscale '{$this->formCodiceFiscale}' già presente in archivio per: " . $existingByCF->full_name);
                    return;
                }
            }

            $adminId = auth()->guard('admin')->id();
            
            $entity = Entity::create([
                'entity_type' => $this->formTipologia,
                'ragione_sociale' => $this->formRagioneSociale,
                'nome' => $this->formNome ?: null,
                'cognome' => $this->formCognome ?: null,
                'persona_riferimento' => $this->formRiferimento ?: null,
                'email' => $this->formEmail ?: null,
                'partita_iva' => $normalizedPartitaIva, // Salva la versione normalizzata
                'codice_fiscale' => $this->formCodiceFiscale ?: null,
                'valid' => 1,
                'data_inserimento' => now(),
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => $adminId,
                'updated_by' => $adminId
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
            'entityTypes' => $this->entityTypes,
            'trashedEntities' => $this->trashedEntities,
            'trashCount' => $this->trashCount
        ]);
    }
}