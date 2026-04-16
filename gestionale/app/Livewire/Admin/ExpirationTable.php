<?php
// app/Livewire/Admin/ExpirationTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expiration;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Entity;
use App\Models\Ownership;
use Illuminate\Support\Facades\Auth;

class ExpirationTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipologiaFilter = '';
    public $statusFilter = '';
    public $staffId = null;
    public $staffName = null;
    public $perPage = 15;
    public $sortField = 'data_fine';
    public $sortDirection = 'asc';
    
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
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipologiaFilter', 'statusFilter', 'staffId'];
    
    public function mount($staffId = null, $staffName = null)
    {
        $this->staffId = $staffId;
        $this->staffName = $staffName;
        $this->createDataInizio = date('Y-m-d');
        // Imposta data scadenza di default tra 1 anno
        $this->createDataFine = date('Y-m-d', strtotime('+1 year'));
        
        if ($this->staffId) {
            $staff = Staff::find($this->staffId);
            if ($staff) {
                $this->staffName = $staff->full_name;
            }
        }
    }
    
    // ==================== AUTOCOMPLETE ENTITÀ ====================
    
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
        
        // Reset dei campi di associazione
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
            ];
            
            // 1. Se c'è un ownership selezionato
            if ($this->createOwnershipId) {
                $data['id_ownership'] = $this->createOwnershipId;
            }
            
            // 2. Se c'è un cliente/fornitore selezionato, compila id_entities
            if ($this->createEntityId) {
                $data['id_entities'] = $this->createEntityId;
            }
            
            // 3. Se siamo in modalità staff, compila id_references e table_references
            if ($this->staffId) {
                $data['id_references'] = $this->staffId;
                $data['table_references'] = Expiration::TABLE_STAFF;
            }
            
            Expiration::create($data);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Scadenza creata con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }
    
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
            } elseif ($this->statusFilter === 'inactive') {
                $query->whereNotNull('deleted_at');
            } elseif ($this->statusFilter === 'expired') {
                $query->where('data_fine', '<', now())->whereNull('deleted_at');
            } elseif ($this->statusFilter === 'expiring') {
                $query->where('data_fine', '<=', now()->addDays(30))
                      ->where('data_fine', '>=', now())
                      ->whereNull('deleted_at');
            }
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->with(['setting', 'entityLegacy', 'ownershipLegacy', 'createdBy', 'updatedBy'])->paginate($this->perPage);
    }
    
    public function viewExpiration($id)
    {
        try {
            $expiration = Expiration::with(['setting', 'entityLegacy', 'ownershipLegacy', 'createdBy', 'updatedBy'])->find($id);
            
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
    
    public function toggleStatus($id)
    {
        try {
            $expiration = Expiration::withTrashed()->find($id);
            
            if (!$expiration) {
                $this->dispatch('showError', message: 'Scadenza non trovata');
                return;
            }
            
            if ($expiration->trashed()) {
                $expiration->restore();
                $statusText = 'riattivata';
            } else {
                $expiration->delete();
                $statusText = 'disattivata';
            }
            
            $this->dispatch('showSuccess', message: "Scadenza '{$expiration->titolo}' {$statusText} con successo!");
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
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
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    public function backToStaff()
    {
        return redirect()->route('admin.staff.index');
    }

    public function render()
    {
        return view('livewire.admin.expiration-table', [
            'expirations' => $this->expirations,
            'tipologie' => $this->tipologie,
            'ownerships' => $this->ownerships,
        ]);
    }
}