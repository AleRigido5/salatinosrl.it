<?php
// app/Livewire/Admin/StaffExpirationTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expiration;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Entity;
use App\Models\Ownership;
use Illuminate\Support\Facades\Auth;

class StaffExpirationTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipologiaFilter = '';
    public $statusFilter = '';
    public $staffId = null;
    public $staffName = null;
    public $perPage = 15;
    public $sortField = 'data_fine';
    public $sortDirection = 'desc';
    
    public $showViewModal = false;
    public $viewingExpiration = null;
    
    public $showCreateModal = false;
    public $createTitolo = '';
    public $createTipologiaId = '';
    public $createDataInizio = '';
    public $createDataFine = '';
    public $createNote = '';
    public $createQualifica = '';
    public $createOwnershipId = '';
    
    // Autocomplete per entità (clienti/fornitori)
    public $createEntitySearch = '';
    public $createEntityResults = [];
    public $createEntityId = '';
    public $createEntityNome = '';
    
    // MODAL MODIFICA
    public $showEditModal = false;
    public $editingExpiration = null;
    public $editId = null;
    
    // Form fields per modifica
    public $editTitolo = '';
    public $editTipologiaId = '';
    public $editDataInizio = '';
    public $editDataFine = '';
    public $editNote = '';
    public $editQualifica = '';
    public $editOwnershipId = '';
    public $editEntityId = '';
    public $editEntityNome = '';
    public $editEntitySearch = '';
    public $editEntityResults = [];
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipologiaFilter', 'statusFilter', 'staffId'];
    
    public function mount($staffId = null, $staffName = null)
    {
        $this->staffId = $staffId;
        $this->staffName = $staffName;
        $this->createDataInizio = date('Y-m-d');
        $this->createDataFine = date('Y-m-d', strtotime('+1 year'));
        
        if ($this->staffId && !$this->staffName) {
            $staff = Staff::find($this->staffId);
            if ($staff) {
                $this->staffName = $staff->full_name;
            }
        }
    }
    
    // ==================== AUTOCOMPLETE CREAZIONE ====================
    
    public function updatedCreateEntitySearch()
    {
        if (strlen($this->createEntitySearch) >= 2) {
            $this->createEntityResults = Entity::where(function($q) {
                    $q->where('entity_type', 'fornitore')
                      ->orWhere('entity_type', 'entrambi');
                })
                ->where(function($q) {
                    $q->where('ragione_sociale', 'like', '%' . $this->createEntitySearch . '%')
                      ->orWhere('nome', 'like', '%' . $this->createEntitySearch . '%')
                      ->orWhere('cognome', 'like', '%' . $this->createEntitySearch . '%');
                })
                ->where('valid', 1)
                ->orderBy('ragione_sociale')
                ->limit(10)
                ->get();
        } else {
            $this->createEntityResults = [];
        }
    }
    
    public function selectEntity($id, $nome)
    {
        $this->createEntityId = $id;
        $this->createEntityNome = $nome;
        $this->createEntitySearch = $nome;
        $this->createEntityResults = [];
    }
    
    public function clearEntity()
    {
        $this->createEntityId = '';
        $this->createEntityNome = '';
        $this->createEntitySearch = '';
        $this->createEntityResults = [];
    }
    
    // ==================== AUTOCOMPLETE MODIFICA ====================
    
    public function updatedEditEntitySearch()
    {
        if (strlen($this->editEntitySearch) >= 2) {
            $this->editEntityResults = Entity::where(function($q) {
                    $q->where('entity_type', 'fornitore')
                      ->orWhere('entity_type', 'entrambi');
                })
                ->where(function($q) {
                    $q->where('ragione_sociale', 'like', '%' . $this->editEntitySearch . '%')
                      ->orWhere('nome', 'like', '%' . $this->editEntitySearch . '%')
                      ->orWhere('cognome', 'like', '%' . $this->editEntitySearch . '%');
                })
                ->where('valid', 1)
                ->orderBy('ragione_sociale')
                ->limit(10)
                ->get();
        } else {
            $this->editEntityResults = [];
        }
    }
    
    public function selectEditEntity($id, $nome)
    {
        $this->editEntityId = $id;
        $this->editEntityNome = $nome;
        $this->editEntitySearch = $nome;
        $this->editEntityResults = [];
    }
    
    public function clearEditEntity()
    {
        $this->editEntityId = '';
        $this->editEntityNome = '';
        $this->editEntitySearch = '';
        $this->editEntityResults = [];
    }
    
    // ==================== METODI CREAZIONE ====================
    
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
        $this->createTitolo = '';
        $this->createTipologiaId = '';
        $this->createDataInizio = date('Y-m-d');
        $this->createDataFine = date('Y-m-d', strtotime('+1 year'));
        $this->createNote = '';
        $this->createQualifica = '';
        $this->createOwnershipId = '';
        $this->createEntityId = '';
        $this->createEntityNome = '';
        $this->createEntitySearch = '';
        $this->createEntityResults = [];
    }

    public function saveExpiration()
    {
        $this->validate([
            'createTitolo' => 'required|string|max:255',
            'createTipologiaId' => 'required|exists:settings,id',
            'createDataInizio' => 'required|date',
            'createDataFine' => 'nullable|date|after_or_equal:createDataInizio',
        ]);
        
        try {
            $adminId = Auth::guard('admin')->id();
            
            $data = [
                'titolo' => $this->createTitolo,
                'id_settings' => $this->createTipologiaId,
                'data_inizio' => $this->createDataInizio,
                'data_fine' => $this->createDataFine,
                'subtitolo' => $this->createQualifica,
                'note' => $this->createNote,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'id_references' => $this->staffId,
                'table_references' => Expiration::TABLE_STAFF,
            ];
            
            if ($this->createOwnershipId) {
                $data['id_ownership'] = $this->createOwnershipId;
            }
            
            if ($this->createEntityId) {
                $data['id_entities'] = $this->createEntityId;
            }
            
            Expiration::create($data);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Scadenza creata con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }
    
    // ==================== METODI MODIFICA ====================
    
    public function openEditModal($id)
    {
        try {
            $expiration = Expiration::with(['setting', 'entityLegacy'])->find($id);
            
            if (!$expiration) {
                $this->dispatch('showError', message: 'Scadenza non trovata');
                return;
            }
            
            $this->editId = $id;
            $this->editingExpiration = $expiration;
            $this->editTitolo = $expiration->titolo;
            $this->editTipologiaId = $expiration->id_settings;
            $this->editDataInizio = $expiration->data_inizio ? $expiration->data_inizio->format('Y-m-d') : '';
            $this->editDataFine = $expiration->data_fine ? $expiration->data_fine->format('Y-m-d') : '';
            $this->editNote = $expiration->note;
            $this->editQualifica = $expiration->subtitolo;
            $this->editOwnershipId = $expiration->id_ownership;
            
            // Carica entità associata
            if ($expiration->id_entities) {
                $entity = Entity::find($expiration->id_entities);
                if ($entity) {
                    $this->editEntityId = $entity->id_cliente;
                    $this->editEntityNome = $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
                    $this->editEntitySearch = $this->editEntityNome;
                }
            }
            
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
        $this->editingExpiration = null;
        $this->editTitolo = '';
        $this->editTipologiaId = '';
        $this->editDataInizio = '';
        $this->editDataFine = '';
        $this->editNote = '';
        $this->editQualifica = '';
        $this->editOwnershipId = '';
        $this->editEntityId = '';
        $this->editEntityNome = '';
        $this->editEntitySearch = '';
        $this->editEntityResults = [];
    }
    
    public function updateExpiration()
    {
        $this->validate([
            'editTitolo' => 'required|string|max:255',
            'editTipologiaId' => 'required|exists:settings,id',
            'editDataInizio' => 'required|date',
            'editDataFine' => 'nullable|date|after_or_equal:editDataInizio',
        ]);
        
        try {
            $expiration = Expiration::find($this->editId);
            
            if (!$expiration) {
                $this->dispatch('showError', message: 'Scadenza non trovata');
                return;
            }
            
            $data = [
                'titolo' => $this->editTitolo,
                'id_settings' => $this->editTipologiaId,
                'data_inizio' => $this->editDataInizio,
                'data_fine' => $this->editDataFine,
                'subtitolo' => $this->editQualifica,
                'note' => $this->editNote,
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ];
            
            if ($this->editOwnershipId) {
                $data['id_ownership'] = $this->editOwnershipId;
            }
            
            if ($this->editEntityId) {
                $data['id_entities'] = $this->editEntityId;
            }
            
            $expiration->update($data);
            
            $this->closeEditModal();
            $this->dispatch('showSuccess', message: 'Scadenza aggiornata con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }
    
    // ==================== METODI UTILITY ====================
    
    public function getTipologieProperty()
    {
        return Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    public function getOwnershipsProperty()
    {
        return Ownership::orderBy('RagSocialePr')->get();
    }
    
    public function getExpirationsProperty()
    {
        $query = Expiration::query();
        
        // Filtro per STAFF
        if ($this->staffId) {
            $query->where(function($q) {
                $q->where('table_references', Expiration::TABLE_STAFF)
                  ->where('id_references', $this->staffId)
                  ->orWhere(function($q2) {
                      $q2->whereNull('table_references')
                         ->where('id_entities', $this->staffId);
                  });
            });
        }
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('titolo', 'like', $searchTerm)
                ->orWhere('subtitolo', 'like', $searchTerm)
                ->orWhere('note', 'like', $searchTerm);
            });
        }
        
        if ($this->tipologiaFilter) {
            $query->where('id_settings', $this->tipologiaFilter);
        }
        
        if ($this->statusFilter !== '') {
            if ($this->statusFilter === 'active') {
                $query->whereNull('deleted_at');
            } elseif ($this->statusFilter === 'expired') {
                $query->where('data_fine', '<', now())->whereNull('deleted_at');
            } elseif ($this->statusFilter === 'expiring') {
                $query->where('data_fine', '<=', now()->addDays(30))
                    ->where('data_fine', '>=', now())
                    ->whereNull('deleted_at');
            }
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->with([
            'setting', 
            'entityLegacy', 
            'ownershipLegacy',
            'createdBy', 
            'updatedBy'
        ])->paginate($this->perPage);
    }
    
    public function viewExpiration($id)
    {
        try {
            $expiration = Expiration::with([
                'setting', 
                'entityLegacy', 
                'ownershipLegacy',
                'createdBy', 
                'updatedBy'
            ])->find($id);            
            if (!$expiration) {
                $this->dispatch('showError', message: 'Scadenza non trovata');
                return;
            }
            
            $this->viewingExpiration = $expiration;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingExpiration = null;
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
    
    public function resetFilters()
    {
        $this->search = '';
        $this->tipologiaFilter = '';
        $this->statusFilter = '';
        $this->sortField = 'data_fine';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }
    
    public function backToStaff()
    {
        return redirect()->route('admin.staff.index');
    }

    public function render()
    {
        return view('livewire.admin.staff.staff-expiration-table', [
            'expirations' => $this->expirations,
            'tipologie' => $this->tipologie,
            'ownerships' => $this->ownerships,
            'staffName' => $this->staffName,
            'staffId' => $this->staffId
        ]);
    }
}