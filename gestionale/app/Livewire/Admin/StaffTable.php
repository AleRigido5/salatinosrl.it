<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Staff;
use App\Models\Setting;
use App\Models\Expiration;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'active'; // Default: solo attivi
    public $gruppoFilter = '';
    public $perPage = 15;
    public $sortField = 'id_personale';
    public $sortDirection = 'asc';
    
    // Modal visualizzazione
    public $showViewModal = false;
    public $viewingStaff = null;
    
    // Modal modifica
    public $showEditModal = false;
    public $editingStaff = null;
    public $editingId = null;
    
    // Form fields per modifica
    public $editNome = '';
    public $editCognome = '';
    public $editSoprannome = '';
    public $editCodFiscale = '';
    public $editTelefono = '';
    public $editCellulare = '';
    public $editEmail = '';
    public $editIndirizzo = '';
    public $editCitta = '';
    public $editProvincia = '';
    public $editCap = '';
    public $editDataNascita = '';
    public $editLuogoNascita = '';
    public $editValid = true;
    public $editIban = '';
    public $editGruppo = '';
    
    // Modal creazione
    public $showCreateModal = false;
    
    // Form fields per creazione
    public $createNome = '';
    public $createCognome = '';
    public $createSoprannome = '';
    public $createCodFiscale = '';
    public $createTelefono = '';
    public $createCellulare = '';
    public $createEmail = '';
    public $createIndirizzo = '';
    public $createCitta = '';
    public $createProvincia = '';
    public $createCap = '';
    public $createDataNascita = '';
    public $createLuogoNascita = '';
    public $createValid = true;
    public $createIban = '';
    public $createGruppo = '';
    
    protected $paginationTheme = 'tailwind';
    
    protected $listeners = [
        'openCreateModal' => 'openCreateModal'
    ];
    
    public function mount()
    {
        if (session()->has('staff_filters')) {
            $filters = session('staff_filters');
            $this->search = $filters['search'] ?? '';
            $this->statusFilter = $filters['statusFilter'] ?? 'active';
            $this->gruppoFilter = $filters['gruppoFilter'] ?? '';
            $this->sortField = $filters['sortField'] ?? 'CognomePers';
            $this->sortDirection = $filters['sortDirection'] ?? 'asc';
            session()->forget('staff_filters');
        }
    }
    
    public function saveFiltersToSession()
    {
        session(['staff_filters' => [
            'search' => $this->search,
            'statusFilter' => $this->statusFilter,
            'gruppoFilter' => $this->gruppoFilter,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection
        ]]);
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
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingGruppoFilter()
    {
        $this->resetPage();
    }
    
    public function getStaffProperty()
    {
        $query = Staff::query();
        
        // Filtro per stato (attivo/inattivo)
        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter === 'active');
        }
        
        // Filtro per gruppo
        if (!empty($this->gruppoFilter)) {
            $query->where('id_gruppo', $this->gruppoFilter);
        }
        
        // Filtro ricerca
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('NomePers', 'like', $searchTerm)
                  ->orWhere('CognomePers', 'like', $searchTerm)
                  ->orWhere('Soprannome', 'like', $searchTerm)
                  ->orWhere('CellPers', 'like', $searchTerm)
                  ->orWhere('EmailPers', 'like', $searchTerm)
                  ->orWhere('CodFiscPers', 'like', $searchTerm);
            });
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->with([
            'createdBy', 
            'updatedBy', 
            'gruppo',
            'expirations' => function($q) {
                $q->whereIn('titolo', ['Assunzione', 'Visita medica'])
                  ->orderBy('data_fine', 'desc');
            }
        ])->paginate($this->perPage);
    }
    
    public function getStaffGroupsProperty()
    {
        return Setting::where('tabella_riferimento', 'staff')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
    }
    
    // Helper per formattare la data e calcolare lo stato con bg color
    public function formatExpirationDate($date)
    {
        if (!$date) {
            return [
                'formatted' => '-',
                'status' => 'none',
                'days_left' => null,
                'bg_class' => 'bg-gray-100 text-gray-500',
                'is_expired' => false,
                'is_expiring_soon' => false
            ];
        }
        
        $carbonDate = Carbon::parse($date);
        $now = Carbon::now();
        $daysLeft = $now->diffInDays($carbonDate, false);
        
        if ($carbonDate->isPast()) {
            return [
                'formatted' => $carbonDate->format('d/m/Y'),
                'status' => 'expired',
                'days_left' => abs($daysLeft),
                'bg_class' => 'bg-red-100 text-red-800',
                'is_expired' => true,
                'is_expiring_soon' => false
            ];
        } elseif ($daysLeft <= 30) {
            return [
                'formatted' => $carbonDate->format('d/m/Y'),
                'status' => 'warning',
                'days_left' => $daysLeft,
                'bg_class' => 'bg-yellow-100 text-yellow-800',
                'is_expired' => false,
                'is_expiring_soon' => true
            ];
        } else {
            return [
                'formatted' => $carbonDate->format('d/m/Y'),
                'status' => 'valid',
                'days_left' => $daysLeft,
                'bg_class' => 'bg-green-100 text-green-800',
                'is_expired' => false,
                'is_expiring_soon' => false
            ];
        }
    }
    
    // ==================== METODI VISUALIZZAZIONE ====================
    
    public function viewStaff($id)
    {
        try {
            $staff = Staff::with(['createdBy', 'updatedBy', 'gruppo', 'expirations' => function($q) {
                $q->whereIn('titolo', ['Assunzione', 'Visita medica'])
                  ->orderBy('data_fine', 'desc');
            }])->find($id);
            
            if (!$staff) {
                $this->dispatch('showError', message: 'Personale non trovato');
                return;
            }
            $this->viewingStaff = $staff;
            $this->showViewModal = true;
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento dei dettagli: ' . $e->getMessage());
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingStaff = null;
    }
    
    // ==================== METODI MODIFICA ====================
    
    public function editStaff($id)
    {
        try {
            $staff = Staff::find($id);
            if (!$staff) {
                $this->dispatch('showError', message: 'Personale non trovato');
                return;
            }
            
            $this->editingId = $id;
            $this->editingStaff = $staff;
            $this->editNome = $staff->NomePers;
            $this->editCognome = $staff->CognomePers;
            $this->editSoprannome = $staff->Soprannome;
            $this->editCodFiscale = $staff->CodFiscPers;
            $this->editTelefono = $staff->TelPers;
            $this->editCellulare = $staff->CellPers;
            $this->editEmail = $staff->EmailPers;
            $this->editIndirizzo = $staff->IndirPers;
            $this->editCitta = $staff->CittaPers;
            $this->editProvincia = $staff->ProvPers;
            $this->editCap = $staff->CapPers;
            $this->editDataNascita = $staff->DataNascPers;
            $this->editLuogoNascita = $staff->LuogoNasc;
            $this->editValid = (bool)$staff->valid;
            $this->editIban = $staff->IbanPers;
            $this->editGruppo = $staff->id_gruppo;
            
            $this->showEditModal = true;
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore nel caricamento dei dati: ' . $e->getMessage());
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingStaff = null;
        $this->editingId = null;
        $this->resetEditForm();
    }
    
    public function resetEditForm()
    {
        $this->editNome = '';
        $this->editCognome = '';
        $this->editSoprannome = '';
        $this->editCodFiscale = '';
        $this->editTelefono = '';
        $this->editCellulare = '';
        $this->editEmail = '';
        $this->editIndirizzo = '';
        $this->editCitta = '';
        $this->editProvincia = '';
        $this->editCap = '';
        $this->editDataNascita = '';
        $this->editLuogoNascita = '';
        $this->editValid = true;
        $this->editIban = '';
        $this->editGruppo = '';
    }
    
    public function updateStaff()
    {
        $this->validate([
            'editNome' => 'nullable|string|max:255',
            'editCognome' => 'nullable|string|max:255',
            'editEmail' => 'nullable|email|max:255',
            'editIban' => 'nullable|string|max:255',
            'editGruppo' => 'nullable|integer',
        ]);
        
        try {
            $staff = Staff::find($this->editingId);
            if ($staff) {
                $staff->update([
                    'NomePers' => $this->editNome,
                    'CognomePers' => $this->editCognome,
                    'Soprannome' => $this->editSoprannome,
                    'CodFiscPers' => $this->editCodFiscale,
                    'TelPers' => $this->editTelefono,
                    'CellPers' => $this->editCellulare,
                    'EmailPers' => $this->editEmail,
                    'IndirPers' => $this->editIndirizzo,
                    'CittaPers' => $this->editCitta,
                    'ProvPers' => $this->editProvincia,
                    'CapPers' => $this->editCap,
                    'DataNascPers' => $this->editDataNascita,
                    'LuogoNasc' => $this->editLuogoNascita,
                    'IbanPers' => $this->editIban,
                    'id_gruppo' => $this->editGruppo,
                    'valid' => $this->editValid,
                    'updated_by' => auth()->guard('admin')->id(),
                    'updated_at' => now()
                ]);
                
                $this->closeEditModal();
                $this->dispatch('showSuccess', message: 'Personale aggiornato con successo!');
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
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
        $this->createNome = '';
        $this->createCognome = '';
        $this->createSoprannome = '';
        $this->createCodFiscale = '';
        $this->createTelefono = '';
        $this->createCellulare = '';
        $this->createEmail = '';
        $this->createIndirizzo = '';
        $this->createCitta = '';
        $this->createProvincia = '';
        $this->createCap = '';
        $this->createDataNascita = '';
        $this->createLuogoNascita = '';
        $this->createValid = true;
        $this->createIban = '';
        $this->createGruppo = '';
    }
    
    public function saveStaff()
    {
        if (!empty($this->createCodFiscale)) {
            $existingStaff = Staff::where('CodFiscPers', $this->createCodFiscale)->first();
            if ($existingStaff) {
                $this->dispatch('showError', message: "Codice Fiscale {$this->createCodFiscale} già presente in archivio per: " . $existingStaff->full_name);
                return;
            }
        }
        
        $this->validate([
            'createNome' => 'nullable|string|max:255',
            'createCognome' => 'nullable|string|max:255',
            'createEmail' => 'nullable|email|max:255',
        ]);
        
        try {
            $adminId = auth()->guard('admin')->id();
            
            $staff = Staff::create([
                'NomePers' => $this->createNome,
                'CognomePers' => $this->createCognome,
                'Soprannome' => $this->createSoprannome,
                'CodFiscPers' => $this->createCodFiscale,
                'TelPers' => $this->createTelefono,
                'CellPers' => $this->createCellulare,
                'EmailPers' => $this->createEmail,
                'IndirPers' => $this->createIndirizzo,
                'CittaPers' => $this->createCitta,
                'ProvPers' => $this->createProvincia,
                'CapPers' => $this->createCap,
                'DataNascPers' => $this->createDataNascita,
                'LuogoNasc' => $this->createLuogoNascita,
                'IbanPers' => $this->createIban,
                'id_gruppo' => $this->createGruppo,
                'valid' => $this->createValid,
                'created_by' => $adminId,
                'updated_by' => $adminId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Personale aggiunto con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il salvataggio: ' . $e->getMessage());
        }
    }
    
    // ==================== METODI STATO ====================
    
    public function toggleStatus($id)
    {
        try {
            $staff = Staff::find($id);
            if (!$staff) {
                $this->dispatch('showError', message: 'Personale non trovato');
                return;
            }
            $newStatus = !$staff->valid;
            $staff->update([
                'valid' => $newStatus,
                'updated_by' => auth()->guard('admin')->id(),
                'updated_at' => now()
            ]);
            $statusText = $newStatus ? 'attivato' : 'disattivato';
            $this->dispatch('showSuccess', message: "Personale '{$staff->NomePers} {$staff->CognomePers}' {$statusText} con successo!");
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }
    
    // ==================== METODO PER SCADENZE ====================
    
    public function goToExpiration($staffId)
    {
        return redirect()->route('admin.expiration-staff.index', ['staffId' => $staffId]);
    }
    
    // ==================== METODI FILTRI ====================
    
    public function resetFilters()
    {
        $this->reset(['search', 'gruppoFilter']);
        $this->statusFilter = 'active';
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.staff-table', [
            'staff' => $this->staff,
            'staffGroups' => $this->staffGroups,
        ]);
    }
}