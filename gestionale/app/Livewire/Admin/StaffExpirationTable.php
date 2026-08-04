<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expiration;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Entity;
use App\Models\Ownership;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffExpirationTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipologiaFilter = '';
    public $statusFilter = '';
    public $ownershipFilter = '';
    public $staffCategoryFilter = '';
    public $staffId = null;
    public $staffName = null;
    public $perPage = 100;
    public $sortField = 'data_fine';
    public $sortDirection = 'desc';
    
    public $dateFrom = '';
    public $dateTo = '';
    
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
    
    // Autocomplete per staff - CREAZIONE
    public $createStaffSearch = '';
    public $createStaffResults = [];
    public $createStaffId = '';
    public $createStaffNome = '';
    
    // Autocomplete per entità - CREAZIONE
    public $createEntitySearch = '';
    public $createEntityResults = [];
    public $createEntityId = '';
    public $createEntityNome = '';
    
    // MODAL MODIFICA
    public $showEditModal = false;
    public $editingExpiration = null;
    public $editId = null;
    
    public $editTitolo = '';
    public $editTipologiaId = '';
    public $editDataInizio = '';
    public $editDataFine = '';
    public $editNote = '';
    public $editQualifica = '';
    public $editOwnershipId = '';
    
    // Autocomplete per staff - MODIFICA
    public $editStaffSearch = '';
    public $editStaffResults = [];
    public $editStaffId = '';
    public $editStaffNome = '';
    
    // Autocomplete per entità - MODIFICA
    public $editEntityId = '';
    public $editEntityNome = '';
    public $editEntitySearch = '';
    public $editEntityResults = [];
    
    // Messaggi di errore/successo
    public $errorMessage = '';
    public $successMessage = '';
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipologiaFilter', 'statusFilter', 'ownershipFilter', 'staffCategoryFilter', 'staffId', 'dateFrom', 'dateTo'];
    
    protected $listeners = [
        'dateRangeUpdated' => 'updateDateRange',
        'resetDates' => 'resetDateRange'
    ];
    
    public function mount($staffId = null, $staffName = null)
    {
        $this->staffId = $staffId;
        $this->staffName = $staffName;
        $this->createDataInizio = date('Y-m-d');
        $this->createDataFine = date('Y-m-d', strtotime('+1 year'));
        
        // Pre-popola lo staff se passato
        if ($this->staffId) {
            $staff = Staff::find($this->staffId);
            if ($staff) {
                $this->staffName = $staff->full_name;
                $this->createStaffId = $staff->id_personale;
                $this->createStaffNome = $staff->full_name;
                $this->createStaffSearch = $staff->full_name;
            }
        }
        
        if (empty($this->dateFrom) && empty($this->dateTo) && empty($this->statusFilter)) {
            $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        }
    }
    
    // ==================== LISTENER PER DATE RANGE FILTER ====================
    
    public function updateDateRange($data)
    {
        $this->dateFrom = $data['date_from'] ?? '';
        $this->dateTo = $data['date_to'] ?? '';
        $this->resetPage();
    }
    
    public function resetDateRange()
    {
        if (empty($this->statusFilter)) {
            $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $this->dateFrom = '';
            $this->dateTo = '';
        }
        $this->resetPage();
    }
    
    // ==================== METODI PER STATUS FILTER ====================
    
    public function updatedStatusFilter()
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
        $this->dispatch('resetDates');
    }

    public function updatedOwnershipFilter()
    {
        $this->resetPage();
    }

    public function updatedStaffCategoryFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    // ==================== AUTOCOMPLETE STAFF - CREAZIONE ====================
    
    public function updatedCreateStaffSearch()
    {
        $search = trim($this->createStaffSearch);
        
        if (strlen($search) >= 2) {
            $searchTerm = '%' . $search . '%';
            $this->createStaffResults = Staff::where(function($q) use ($searchTerm) {
                    $q->where('NomePers', 'like', $searchTerm)
                      ->orWhere('CognomePers', 'like', $searchTerm)
                      ->orWhere('EmailPers', 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(NomePers, ' ', CognomePers)"), 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(CognomePers, ' ', NomePers)"), 'like', $searchTerm);
                })
                ->where('valid', 1)
                ->orderBy('CognomePers')
                ->orderBy('NomePers')
                ->limit(10)
                ->get();
        } else {
            $this->createStaffResults = [];
        }
    }
    
    public function selectStaff($id, $nome)
    {
        $this->createStaffId = $id;
        $this->createStaffNome = $nome;
        $this->createStaffSearch = $nome;
        $this->createStaffResults = [];
    }
    
    public function clearStaff()
    {
        $this->createStaffId = '';
        $this->createStaffNome = '';
        $this->createStaffSearch = '';
        $this->createStaffResults = [];
    }
    
    // ==================== AUTOCOMPLETE STAFF - MODIFICA ====================
    
    public function updatedEditStaffSearch()
    {
        $search = trim($this->editStaffSearch);
        
        if (strlen($search) >= 2) {
            $searchTerm = '%' . $search . '%';
            $this->editStaffResults = Staff::where(function($q) use ($searchTerm) {
                    $q->where('NomePers', 'like', $searchTerm)
                      ->orWhere('CognomePers', 'like', $searchTerm)
                      ->orWhere('EmailPers', 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(NomePers, ' ', CognomePers)"), 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(CognomePers, ' ', NomePers)"), 'like', $searchTerm);
                })
                ->where('valid', 1)
                ->orderBy('CognomePers')
                ->orderBy('NomePers')
                ->limit(10)
                ->get();
        } else {
            $this->editStaffResults = [];
        }
    }
    
    public function selectEditStaff($id, $nome)
    {
        $this->editStaffId = $id;
        $this->editStaffNome = $nome;
        $this->editStaffSearch = $nome;
        $this->editStaffResults = [];
    }
    
    public function clearEditStaff()
    {
        $this->editStaffId = '';
        $this->editStaffNome = '';
        $this->editStaffSearch = '';
        $this->editStaffResults = [];
    }
    
    // ==================== AUTOCOMPLETE ENTITY - CREAZIONE ====================
    
    public function updatedCreateEntitySearch()
    {
        $search = trim($this->createEntitySearch);
        
        if (strlen($search) >= 2) {
            $searchTerm = '%' . $search . '%';
            $this->createEntityResults = Entity::where(function($q) use ($searchTerm) {
                    $q->where('ragione_sociale', 'like', $searchTerm)
                      ->orWhere('nome', 'like', $searchTerm)
                      ->orWhere('cognome', 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(nome, ' ', cognome)"), 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(cognome, ' ', nome)"), 'like', $searchTerm);
                })
                ->where('valid', 1)
                ->where(function($q) {
                    $q->where('entity_type', 'fornitore')
                      ->orWhere('entity_type', 'entrambi');
                })
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
    
    // ==================== AUTOCOMPLETE ENTITY - MODIFICA ====================
    
    public function updatedEditEntitySearch()
    {
        $search = trim($this->editEntitySearch);
        
        if (strlen($search) >= 2) {
            $searchTerm = '%' . $search . '%';
            $this->editEntityResults = Entity::where(function($q) use ($searchTerm) {
                    $q->where('ragione_sociale', 'like', $searchTerm)
                      ->orWhere('nome', 'like', $searchTerm)
                      ->orWhere('cognome', 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(nome, ' ', cognome)"), 'like', $searchTerm)
                      ->orWhere(DB::raw("CONCAT(cognome, ' ', nome)"), 'like', $searchTerm);
                })
                ->where('valid', 1)
                ->where(function($q) {
                    $q->where('entity_type', 'fornitore')
                      ->orWhere('entity_type', 'entrambi');
                })
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
        
        if ($this->staffId) {
            $staff = Staff::find($this->staffId);
            if ($staff) {
                $this->createStaffId = $staff->id_personale;
                $this->createStaffNome = $staff->full_name;
                $this->createStaffSearch = $staff->full_name;
            }
        }
        
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
        $this->createStaffId = '';
        $this->createStaffNome = '';
        $this->createStaffSearch = '';
        $this->createStaffResults = [];
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    public function saveExpiration()
    {
        $this->validate([
            'createTitolo' => 'required|string|max:255',
            'createTipologiaId' => 'required|exists:settings,id',
            'createDataInizio' => 'required|date',
            'createDataFine' => 'nullable|date|after_or_equal:createDataInizio',
            'createStaffId' => 'required|exists:staff,id_personale',
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
                'id_references' => $this->createStaffId,
                'table_references' => Expiration::TABLE_STAFF,
            ];
            
            if ($this->createOwnershipId) {
                $data['id_ownership'] = $this->createOwnershipId;
            }
            
            if ($this->createEntityId) {
                $data['id_entities'] = $this->createEntityId;
            }
            
            $expiration = Expiration::create($data);
            
            if ($expiration) {
                $this->closeCreateModal();
                session()->flash('success', 'Scadenza creata con successo!');
                $this->resetPage();
            } else {
                throw new \Exception('Errore durante la creazione della scadenza');
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }
    
    // ==================== METODI MODIFICA ====================
    
    public function openEditModal($id)
    {
        try {
            $expiration = Expiration::with(['setting', 'entityLegacy', 'staff'])->find($id);
            
            if (!$expiration) {
                session()->flash('error', 'Scadenza non trovata');
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
            
            // Carica staff associato
            if ($expiration->id_references && $expiration->table_references == Expiration::TABLE_STAFF) {
                $staff = Staff::find($expiration->id_references);
                if ($staff) {
                    $this->editStaffId = $staff->id_personale;
                    $this->editStaffNome = $staff->full_name;
                    $this->editStaffSearch = $staff->full_name;
                }
            }
            
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
            session()->flash('error', 'Errore nel caricamento: ' . $e->getMessage());
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
        $this->editStaffId = '';
        $this->editStaffNome = '';
        $this->editStaffSearch = '';
        $this->editStaffResults = [];
        $this->editEntityId = '';
        $this->editEntityNome = '';
        $this->editEntitySearch = '';
        $this->editEntityResults = [];
        $this->errorMessage = '';
        $this->successMessage = '';
    }
    
    public function updateExpiration()
    {
        $this->validate([
            'editTitolo' => 'required|string|max:255',
            'editTipologiaId' => 'required|exists:settings,id',
            'editDataInizio' => 'required|date',
            'editDataFine' => 'nullable|date|after_or_equal:editDataInizio',
            'editStaffId' => 'required|exists:staff,id_personale',
        ]);
        
        try {
            $expiration = Expiration::find($this->editId);
            
            if (!$expiration) {
                session()->flash('error', 'Scadenza non trovata');
                return;
            }
            
            $data = [
                'titolo' => $this->editTitolo,
                'id_settings' => $this->editTipologiaId,
                'data_inizio' => $this->editDataInizio,
                'data_fine' => $this->editDataFine,
                'subtitolo' => $this->editQualifica,
                'note' => $this->editNote,
                'id_references' => $this->editStaffId,
                'table_references' => Expiration::TABLE_STAFF,
                'updated_by' => Auth::guard('admin')->id(),
            ];
            
            if ($this->editOwnershipId) {
                $data['id_ownership'] = $this->editOwnershipId;
            }
            
            if ($this->editEntityId) {
                $data['id_entities'] = $this->editEntityId;
            }
            
            $expiration->update($data);
            
            $this->closeEditModal();
            session()->flash('success', 'Scadenza aggiornata con successo!');
            $this->resetPage();
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            session()->flash('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
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

    /**
     * Categorie di personale (Agricoltura, Ristorazione, ecc.), dalla
     * categoria Impostazioni "Gruppi Operai" (tabella_riferimento = staff),
     * collegata allo staff tramite Staff.id_gruppo -> Setting.id
     */
    public function getStaffCategoriesProperty()
    {
        return Setting::where('tabella_riferimento', 'staff')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    public function getExpirationsProperty()
    {
        $query = Expiration::query()
            ->where('table_references', Expiration::TABLE_STAFF);

        if ($this->staffId) {
            $query->where('id_references', $this->staffId);
        }

        if (empty($this->statusFilter)) {
            if (!empty($this->dateFrom) && !empty($this->dateTo)) {
                $query->whereBetween('data_fine', [$this->dateFrom, $this->dateTo]);
            } elseif (!empty($this->dateFrom)) {
                $query->whereDate('data_fine', '>=', $this->dateFrom);
            } elseif (!empty($this->dateTo)) {
                $query->whereDate('data_fine', '<=', $this->dateTo);
            }
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

        // Filtro per Proprietà (id_ownership sulla scadenza)
        if ($this->ownershipFilter) {
            $query->where('id_ownership', $this->ownershipFilter);
        }

        // Filtro per Categoria di personale, tramite il dipendente collegato
        if ($this->staffCategoryFilter) {
            $query->whereHas('staff', function($q) {
                $q->where('id_gruppo', $this->staffCategoryFilter);
            });
        }

        if ($this->statusFilter !== '') {
            if ($this->statusFilter === 'active') {
                $query->whereNull('deleted_at')
                      ->where(function($q) {
                          $q->whereNull('data_fine')
                            ->orWhere('data_fine', '>=', now());
                      });
            } elseif ($this->statusFilter === 'expired') {
                $query->where('data_fine', '<', now())
                      ->whereNull('deleted_at');
            } elseif ($this->statusFilter === 'expiring') {
                $query->whereBetween('data_fine', [now(), now()->addDays(30)])
                      ->whereNull('deleted_at');
            }
        }

        $query->orderBy($this->sortField, $this->sortDirection);

        $query->with([
            'setting',
            'entityLegacy',
            'ownershipLegacy',
            'staff',
            'createdBy',
            'updatedBy'
        ]);

        if ((int) $this->perPage >= 10000) {
            // "Tutti": evitiamo una vera paginazione con un numero enorme di risultati per pagina
            return $query->paginate($query->count() ?: 1);
        }

        return $query->paginate($this->perPage);
    }
    
    public function viewExpiration($id)
    {
        try {
            $expiration = Expiration::with([
                'setting', 
                'entityLegacy', 
                'ownershipLegacy',
                'staff',
                'createdBy', 
                'updatedBy'
            ])->find($id);            
            if (!$expiration) {
                session()->flash('error', 'Scadenza non trovata');
                return;
            }
            
            $this->viewingExpiration = $expiration;
            $this->showViewModal = true;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Errore nel caricamento: ' . $e->getMessage());
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
        $this->ownershipFilter = '';
        $this->staffCategoryFilter = '';
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->sortField = 'data_fine';
        $this->sortDirection = 'desc';
        $this->resetPage();
        $this->dispatch('resetDates');
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
            'staffCategories' => $this->staffCategories,
            'staffName' => $this->staffName,
            'staffId' => $this->staffId,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo
        ]);
    }
}