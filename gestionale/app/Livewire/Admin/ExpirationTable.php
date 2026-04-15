<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Expiration;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Entity;
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
    
    public $createFornitoreId = '';
    public $createFornitoreSearch = '';
    public $createFornitoreResults = [];
    public $createFornitoreNome = '';
    
    protected $paginationTheme = 'tailwind';
    protected $queryString = ['search', 'tipologiaFilter', 'statusFilter', 'staffId'];
    
    public function mount($staffId = null, $staffName = null)
    {
        $this->staffId = $staffId;
        $this->staffName = $staffName;
        $this->createDataInizio = date('Y-m-d');
        
        if ($this->staffId) {
            $staff = Staff::find($this->staffId);
            if ($staff) {
                $this->staffName = $staff->full_name;
            }
        }
    }
    
    // ==================== AUTOCOMPLETE FORNITORE ====================
    
    public function updatedCreateFornitoreSearch()
    {
        if (strlen($this->createFornitoreSearch) >= 2) {
            $this->createFornitoreResults = Entity::where(function($q) {
                    $q->where('entity_type', 'fornitore')
                      ->orWhere('entity_type', 'entrambi');
                })
                ->where(function($q) {
                    $q->where('ragione_sociale', 'like', '%' . $this->createFornitoreSearch . '%')
                      ->orWhere('nome', 'like', '%' . $this->createFornitoreSearch . '%')
                      ->orWhere('cognome', 'like', '%' . $this->createFornitoreSearch . '%');
                })
                ->where('valid', 1)
                ->orderBy('ragione_sociale')
                ->limit(10)
                ->get();
        } else {
            $this->createFornitoreResults = [];
        }
    }
    
    public function selectFornitore($id, $nome)
    {
        $this->createFornitoreId = $id;
        $this->createFornitoreNome = $nome;
        $this->createFornitoreSearch = $nome;
        $this->createFornitoreResults = [];
    }
    
    public function clearFornitore()
    {
        $this->createFornitoreId = '';
        $this->createFornitoreNome = '';
        $this->createFornitoreSearch = '';
        $this->createFornitoreResults = [];
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
        $this->createDataFine = '';
        $this->createNote = '';
        $this->createQualifica = '';
        $this->createFornitoreId = '';
        $this->createFornitoreNome = '';
        $this->createFornitoreSearch = '';
        $this->createFornitoreResults = [];
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
            
            // Determina l'ID dell'entità:
            // - Se c'è un fornitore selezionato, usa quello
            // - Altrimenti usa l'ID dello staff
            $idEntities = $this->createFornitoreId ?: $this->staffId;
            
            $expiration = Expiration::create([
                'titolo' => $this->createTitolo,
                'id_settings' => $this->createTipologiaId,
                'data_inizio' => $this->createDataInizio,
                'data_fine' => $this->createDataFine,
                'id_entities' => $idEntities,
                'subtitolo' => $this->createQualifica,
                'note' => $this->createNote,
                'created_by' => $adminId,
                'updated_by' => $adminId
            ]);
            
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
    
    public function getExpirationsProperty()
    {
        $query = Expiration::query();
        
        if ($this->staffId) {
            $query->where('id_entities', $this->staffId);
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
        
        return $query->with(['setting', 'staff', 'entity', 'createdBy', 'updatedBy'])->paginate($this->perPage);
    }
    
    public function viewExpiration($id)
    {
        try {
            $expiration = Expiration::with(['setting', 'staff', 'entity', 'createdBy', 'updatedBy'])->find($id);
            
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
        ]);
    }
}