<?php
// app/Livewire/Admin/ExpirationTable.php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expiration;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Vehicles;
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
    public $entityId = null;
    public $entityType = null;
    public $entityName = null;
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
    
    // Autocomplete per veicoli
    public $createVehicleSearch = '';
    public $createVehicleResults = [];
    public $createVehicleIds = [];
    public $createVehicleNames = [];
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipologiaFilter', 'statusFilter', 'staffId', 'entityId', 'entityType'];
    
    public function mount($staffId = null, $staffName = null, $entityId = null, $entityType = null, $entityName = null)
    {
        $this->staffId = $staffId;
        $this->staffName = $staffName;
        $this->entityId = $entityId;
        $this->entityType = $entityType;
        $this->entityName = $entityName;
        $this->createDataInizio = date('Y-m-d');
        $this->createDataFine = date('Y-m-d', strtotime('+1 year'));
        
        // Se c'è staffId ma non staffName, cerca il nome
        if ($this->staffId && !$this->staffName) {
            $staff = Staff::find($this->staffId);
            if ($staff) {
                $this->staffName = $staff->full_name;
                $this->entityName = $this->staffName;
                $this->entityType = 'staff';
                $this->entityId = $this->staffId;
            }
        }
        
        // Se c'è entityId ma non entityName, cerca il nome
        if ($this->entityId && !$this->entityName) {
            if ($this->entityType === 'staff') {
                $staff = Staff::find($this->entityId);
                $this->entityName = $staff ? $staff->full_name : null;
                $this->staffName = $this->entityName;
                $this->staffId = $this->entityId;
            } elseif ($this->entityType === 'vehicle') {
                $vehicle = Vehicles::find($this->entityId);
                $this->entityName = $vehicle ? ($vehicle->full_name ?? $vehicle->targa) : null;
            }
        }
        
        // Se c'è entityName ma non staffName, sincronizza
        if ($this->entityName && !$this->staffName && $this->entityType === 'staff') {
            $this->staffName = $this->entityName;
            $this->staffId = $this->entityId;
        }
    }
    
    // ==================== AUTOCOMPLETE VEICOLI ====================
    
    public function updatedCreateVehicleSearch()
    {
        if (strlen($this->createVehicleSearch) >= 2) {
            $this->createVehicleResults = Vehicles::where(function($q) {
                    $q->where('targa', 'like', '%' . $this->createVehicleSearch . '%')
                      ->orWhere('marca', 'like', '%' . $this->createVehicleSearch . '%')
                      ->orWhere('modello', 'like', '%' . $this->createVehicleSearch . '%');
                })
                ->where('valid', 1)
                ->orderBy('targa')
                ->limit(10)
                ->get();
        } else {
            $this->createVehicleResults = [];
        }
    }
    
    public function selectVehicle($id, $nome)
    {
        if (!in_array($id, $this->createVehicleIds)) {
            $this->createVehicleIds[] = $id;
            $this->createVehicleNames[] = $nome;
        }
        $this->createVehicleSearch = '';
        $this->createVehicleResults = [];
    }
    
    public function removeVehicle($index)
    {
        unset($this->createVehicleIds[$index]);
        unset($this->createVehicleNames[$index]);
        $this->createVehicleIds = array_values($this->createVehicleIds);
        $this->createVehicleNames = array_values($this->createVehicleNames);
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
        $this->createEntityId = '';
        $this->createEntityNome = '';
        $this->createEntitySearch = '';
        $this->createEntityResults = [];
        $this->createVehicleIds = [];
        $this->createVehicleNames = [];
        $this->createVehicleSearch = '';
        $this->createVehicleResults = [];
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
            
            if ($this->createOwnershipId) {
                $data['id_ownership'] = $this->createOwnershipId;
            }
            
            if ($this->createEntityId) {
                $data['id_entities'] = $this->createEntityId;
            }
            
            // Gestione per STAFF
            if ($this->staffId || ($this->entityType === 'staff' && $this->entityId)) {
                $staffIdToUse = $this->staffId ?? $this->entityId;
                $data['id_references'] = $staffIdToUse;
                $data['table_references'] = Expiration::TABLE_STAFF;
                $expiration = Expiration::create($data);
                $this->closeCreateModal();
                $this->dispatch('showSuccess', message: 'Scadenza creata con successo!');
                $this->resetPage();
                return;
            }
            
            // Gestione per VEHICLE (singolo dalla tabella mezzi)
            if ($this->entityType === 'vehicle' && $this->entityId) {
                $data['table_references'] = Expiration::TABLE_VEHICLE;
                $expiration = Expiration::create($data);
                $expiration->vehicles()->attach($this->entityId);
                $this->closeCreateModal();
                $this->dispatch('showSuccess', message: 'Scadenza creata con successo!');
                $this->resetPage();
                return;
            }
            
            // Gestione per VEICOLI MULTIPLI (creazione da zero)
            if (count($this->createVehicleIds) > 0) {
                $data['table_references'] = Expiration::TABLE_VEHICLE;
                $expiration = Expiration::create($data);
                $expiration->vehicles()->attach($this->createVehicleIds);
            } else {
                Expiration::create($data);
            }
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Scadenza creata con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
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
        
        // Filtro per VEHICLE (tramite entityId/entityType)
        if ($this->entityType === 'vehicle' && $this->entityId) {
            $query->where(function($q) {
                $q->where('table_references', Expiration::TABLE_VEHICLE)
                  ->whereHas('vehicles', function($q2) {
                      $q2->where('vehicles.id', $this->entityId);
                  })
                  ->orWhere(function($q3) {
                      $q3->whereNull('table_references')
                         ->where('id_entities', $this->entityId);
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
        
        return $query->with([
            'setting', 
            'entityLegacy', 
            'ownershipLegacy', 
            'vehicles',
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
                'vehicles',
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
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
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
    
    public function backToParent()
    {
        if ($this->staffId) {
            return redirect()->route('admin.staff.index');
        }
        if ($this->entityType === 'vehicle') {
            return redirect()->route('admin.vehicles.index');
        }
        return redirect()->route('admin.expiration.index');
    }

    public function render()
    {
        return view('livewire.admin.expiration-table', [
            'expirations' => $this->expirations,
            'tipologie' => $this->tipologie,
            'ownerships' => $this->ownerships,
            'staffName' => $this->staffName,
            'entityName' => $this->entityName,
            'entityType' => $this->entityType,
            'entityId' => $this->entityId
        ]);
    }
}