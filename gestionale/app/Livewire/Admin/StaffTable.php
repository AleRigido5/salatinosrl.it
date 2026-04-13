<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

class StaffTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'id_personale';
    public $sortDirection = 'asc';
    
    public $showViewModal = false;
    public $viewingStaff = null;
    public $showCreateModal = false;
    public $showEditModal = false;
    
    public $formNome = '';
    public $formCognome = '';
    public $formSoprannome = '';
    public $formCodFiscale = '';
    public $formTelefono = '';
    public $formCellulare = '';
    public $formEmail = '';
    public $formIndirizzo = '';
    public $formCitta = '';
    public $formProvincia = '';
    public $formCap = '';
    public $formDataNascita = '';
    public $formLuogoNascita = '';
    public $formValid = true;
    public $editingId = null;
    
    protected $paginationTheme = 'tailwind';
    
    // IMPORTANTE: Non mantenere la pagina nell'URL
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'sortField' => ['except' => 'id_personale'],
        'sortDirection' => ['except' => 'asc'],
        // 'page' => ['except' => 1], // RIMOSSO - causa problemi
    ];
    
    public function mount()
    {
        // Resetta sempre la pagina all'inizio
        $this->resetPage();
        
        if (session()->has('staff_filters')) {
            $filters = session('staff_filters');
            $this->search = $filters['search'] ?? '';
            $this->statusFilter = $filters['statusFilter'] ?? '';
            $this->sortField = $filters['sortField'] ?? 'id_personale';
            $this->sortDirection = $filters['sortDirection'] ?? 'asc';
            session()->forget('staff_filters');
        }
    }
    
    public function saveFiltersToSession()
    {
        session(['staff_filters' => [
            'search' => $this->search,
            'statusFilter' => $this->statusFilter,
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
    
    public function getStaffProperty()
    {
        $query = Staff::query();
        
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
        
        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter === 'active');
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->paginate($this->perPage);
    }
    
    public function viewStaff($id)
    {
        try {
            $staff = Staff::find($id);
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
    
    public function editStaff($id)
    {
        $this->saveFiltersToSession();
        return redirect()->route('admin.staff.edit', $id);
    }
    
    public function toggleStatus($id)
    {
        try {
            $staff = Staff::find($id);
            if (!$staff) {
                $this->dispatch('showError', message: 'Personale non trovato');
                return;
            }
            $newStatus = !$staff->valid;
            $staff->update(['valid' => $newStatus]);
            $statusText = $newStatus ? 'attivato' : 'disattivato';
            $this->dispatch('showSuccess', message: "Personale '{$staff->NomePers} {$staff->CognomePers}' {$statusText} con successo!");
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortField = 'id_personale';
        $this->sortDirection = 'asc';
        $this->resetPage();
        session()->forget('staff_filters');
    }
    
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
    
    public function openEditModal($id)
    {
        $staff = Staff::find($id);
        if ($staff) {
            $this->editingId = $id;
            $this->formNome = $staff->NomePers;
            $this->formCognome = $staff->CognomePers;
            $this->formSoprannome = $staff->Soprannome;
            $this->formCodFiscale = $staff->CodFiscPers;
            $this->formTelefono = $staff->TelPers;
            $this->formCellulare = $staff->CellPers;
            $this->formEmail = $staff->EmailPers;
            $this->formIndirizzo = $staff->IndirPers;
            $this->formCitta = $staff->CittaPers;
            $this->formProvincia = $staff->ProvPers;
            $this->formCap = $staff->CapPers;
            $this->formDataNascita = $staff->DataNascPers;
            $this->formLuogoNascita = $staff->LuogoNasc;
            $this->formValid = $staff->valid;
            $this->showEditModal = true;
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetCreateForm();
        $this->editingId = null;
    }
    
    public function resetCreateForm()
    {
        $this->formNome = '';
        $this->formCognome = '';
        $this->formSoprannome = '';
        $this->formCodFiscale = '';
        $this->formTelefono = '';
        $this->formCellulare = '';
        $this->formEmail = '';
        $this->formIndirizzo = '';
        $this->formCitta = '';
        $this->formProvincia = '';
        $this->formCap = '';
        $this->formDataNascita = '';
        $this->formLuogoNascita = '';
        $this->formValid = true;
    }
    
    public function save()
    {
        $this->validate([
            'formNome' => 'nullable|string|max:255',
            'formCognome' => 'nullable|string|max:255',
            'formEmail' => 'nullable|email|max:255',
        ]);
        
        try {
            Staff::create([
                'NomePers' => $this->formNome,
                'CognomePers' => $this->formCognome,
                'Soprannome' => $this->formSoprannome,
                'CodFiscPers' => $this->formCodFiscale,
                'TelPers' => $this->formTelefono,
                'CellPers' => $this->formCellulare,
                'EmailPers' => $this->formEmail,
                'IndirPers' => $this->formIndirizzo,
                'CittaPers' => $this->formCitta,
                'ProvPers' => $this->formProvincia,
                'CapPers' => $this->formCap,
                'DataNascPers' => $this->formDataNascita,
                'LuogoNasc' => $this->formLuogoNascita,
                'valid' => $this->formValid
            ]);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Personale creato con successo!');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function update()
    {
        $this->validate([
            'formNome' => 'nullable|string|max:255',
            'formCognome' => 'nullable|string|max:255',
            'formEmail' => 'nullable|email|max:255',
        ]);
        
        try {
            $staff = Staff::find($this->editingId);
            if ($staff) {
                $staff->update([
                    'NomePers' => $this->formNome,
                    'CognomePers' => $this->formCognome,
                    'Soprannome' => $this->formSoprannome,
                    'CodFiscPers' => $this->formCodFiscale,
                    'TelPers' => $this->formTelefono,
                    'CellPers' => $this->formCellulare,
                    'EmailPers' => $this->formEmail,
                    'IndirPers' => $this->formIndirizzo,
                    'CittaPers' => $this->formCitta,
                    'ProvPers' => $this->formProvincia,
                    'CapPers' => $this->formCap,
                    'DataNascPers' => $this->formDataNascita,
                    'LuogoNasc' => $this->formLuogoNascita,
                    'valid' => $this->formValid
                ]);
            }
            $this->closeEditModal();
            $this->dispatch('showSuccess', message: 'Personale aggiornato con successo!');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.admin.staff-table', [
            'staff' => $this->staff,
        ]);
    }
}