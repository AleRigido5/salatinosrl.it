<?php
// app/Livewire/Admin/ExpirationAllTable.php

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
use Illuminate\Support\Facades\DB;

class ExpirationAllTable extends Component
{
    use WithPagination;

    public $search = '';
    public $tipologiaFilter = '';
    public $statusFilter = '';
    public $typeFilter = ''; // staff, vehicle, all
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
    public $createEntityType = ''; // staff, vehicle
    public $createEntityId = '';
    
    // Autocomplete per entità (clienti/fornitori)
    public $createEntitySearch = '';
    public $createEntityResults = [];
    public $createEntityIdField = '';
    public $createEntityNome = '';
    
    // Autocomplete per staff
    public $createStaffSearch = '';
    public $createStaffResults = [];
    public $createStaffId = '';
    public $createStaffName = '';
    
    // Autocomplete per veicoli
    public $createVehicleSearch = '';
    public $createVehicleResults = [];
    public $createVehicleIds = [];
    public $createVehicleNames = [];
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipologiaFilter', 'statusFilter', 'typeFilter'];
    
    public function mount()
    {
        $this->createDataInizio = date('Y-m-d');
        $this->createDataFine = date('Y-m-d', strtotime('+1 year'));
    }
    
    // ==================== AUTOCOMPLETE ====================
    
    public function updatedCreateStaffSearch()
    {
        if (strlen($this->createStaffSearch) >= 2) {
            $this->createStaffResults = Staff::where(function($q) {
                    $q->where('NomePers', 'like', '%' . $this->createStaffSearch . '%')
                      ->orWhere('CognomePers', 'like', '%' . $this->createStaffSearch . '%')
                      ->orWhere('CodFiscPers', 'like', '%' . $this->createStaffSearch . '%');
                })
                ->where('valid', 1)
                ->orderBy('CognomePers')
                ->limit(10)
                ->get();
        } else {
            $this->createStaffResults = [];
        }
    }
    
    public function selectStaff($id, $nome)
    {
        $this->createStaffId = $id;
        $this->createStaffName = $nome;
        $this->createStaffSearch = $nome;
        $this->createStaffResults = [];
        $this->createEntityType = 'staff';
    }
    
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
        $this->createEntityType = 'vehicle';
    }
    
    public function removeVehicle($index)
    {
        unset($this->createVehicleIds[$index]);
        unset($this->createVehicleNames[$index]);
        $this->createVehicleIds = array_values($this->createVehicleIds);
        $this->createVehicleNames = array_values($this->createVehicleNames);
    }
    
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
    
    public function selectEntityField($id, $nome)
    {
        $this->createEntityIdField = $id;
        $this->createEntityNome = $nome;
        $this->createEntitySearch = $nome;
        $this->createEntityResults = [];
    }
    
    public function clearEntityField()
    {
        $this->createEntityIdField = '';
        $this->createEntityNome = '';
        $this->createEntitySearch = '';
        $this->createEntityResults = [];
    }
    
    public function clearStaff()
    {
        $this->createStaffId = '';
        $this->createStaffName = '';
        $this->createStaffSearch = '';
        $this->createStaffResults = [];
        $this->createEntityType = '';
    }
    
    public function clearVehicles()
    {
        $this->createVehicleIds = [];
        $this->createVehicleNames = [];
        $this->createVehicleSearch = '';
        $this->createVehicleResults = [];
        $this->createEntityType = '';
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
        $this->createEntityType = '';
        $this->createEntityId = '';
        $this->createStaffId = '';
        $this->createStaffName = '';
        $this->createStaffSearch = '';
        $this->createStaffResults = [];
        $this->createVehicleIds = [];
        $this->createVehicleNames = [];
        $this->createVehicleSearch = '';
        $this->createVehicleResults = [];
        $this->createEntityIdField = '';
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
            'createEntityType' => 'required|in:staff,vehicle',
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
            
            if ($this->createEntityIdField) {
                $data['id_entities'] = $this->createEntityIdField;
            }
            
            // Gestione per STAFF
            if ($this->createEntityType === 'staff' && $this->createStaffId) {
                $data['id_references'] = $this->createStaffId;
                $data['table_references'] = Expiration::TABLE_STAFF;
                Expiration::create($data);
            }
            // Gestione per VEHICLES (many-to-many)
            elseif ($this->createEntityType === 'vehicle' && count($this->createVehicleIds) > 0) {
                $data['table_references'] = Expiration::TABLE_VEHICLE;
                $expiration = Expiration::create($data);
                $expiration->vehicles()->attach($this->createVehicleIds);
            }
            else {
                $this->dispatch('showError', message: 'Seleziona almeno uno staff o un veicolo');
                return;
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
        
        // Filtro per tipo di entità
        if ($this->typeFilter === 'staff') {
            $query->where('table_references', Expiration::TABLE_STAFF);
        } elseif ($this->typeFilter === 'vehicle') {
            $query->where('table_references', Expiration::TABLE_VEHICLE);
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
                'staff',
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
        $this->typeFilter = '';
        $this->sortField = 'data_fine';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.expiration-all-table', [
            'expirations' => $this->expirations,
            'tipologie' => $this->tipologie,
            'ownerships' => $this->ownerships,
        ]);
    }
}