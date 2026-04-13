<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'id_personale';
    public $sortDirection = 'asc';
    
    // Modal properties
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showViewModal = false;
    public $showDeleteModal = false;
    
    // Form properties
    public $formId = '';
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
    
    public $viewingStaff = null;
    public $deletingStaffId = null;
    public $deletingStaffName = '';
    
    protected $queryString = ['search', 'statusFilter', 'sortField', 'sortDirection'];
    
    protected $listeners = [
        'refreshTable' => '$refresh',
        'openCreateModal' => 'openCreateModal'
    ];
    
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
        
        // Ricerca
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
        
        // Filtro per stato
        if ($this->statusFilter !== '') {
            $query->where('valid', $this->statusFilter === 'active');
        }
        
        // Ordina
        $query->orderBy($this->sortField, $this->sortDirection);
        
        return $query->paginate($this->perPage);
    }
    
    // ==================== MODAL CREAZIONE ====================
    
    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }
    
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }
    
    public function resetForm()
    {
        $this->formId = '';
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
        if (!Auth::guard('admin')->user()->hasPermission('create_staff')) {
            session()->flash('error', 'Non hai i permessi necessari.');
            return;
        }
        
        $this->validate([
            'formNome' => 'nullable|string|max:255',
            'formCognome' => 'nullable|string|max:255',
            'formEmail' => 'nullable|email|max:255',
            'formCellulare' => 'nullable|string|max:20',
        ]);
        
        try {
            DB::table('staff')->insert([
                'NomePers' => $this->formNome ?: null,
                'CognomePers' => $this->formCognome ?: null,
                'Soprannome' => $this->formSoprannome ?: null,
                'CodFiscPers' => $this->formCodFiscale ?: null,
                'TelPers' => $this->formTelefono ?: null,
                'CellPers' => $this->formCellulare ?: null,
                'EmailPers' => $this->formEmail ?: null,
                'IndirPers' => $this->formIndirizzo ?: null,
                'CittaPers' => $this->formCitta ?: null,
                'ProvPers' => $this->formProvincia ?: null,
                'CapPers' => $this->formCap ?: null,
                'DataNascPers' => $this->formDataNascita ?: null,
                'LuogoNasc' => $this->formLuogoNascita ?: null,
                'valid' => $this->formValid ? 1 : 0,
            ]);
            
            $this->closeCreateModal();
            $this->dispatch('showSuccess', message: 'Personale aggiunto con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    // ==================== MODAL VISUALIZZAZIONE ====================
    
    public function viewStaff($id)
    {
        $this->viewingStaff = Staff::find($id);
        if ($this->viewingStaff) {
            $this->showViewModal = true;
        } else {
            $this->dispatch('showError', message: 'Personale non trovato');
        }
    }
    
    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingStaff = null;
    }
    
    // ==================== MODAL MODIFICA ====================
    
    public function editStaff($id)
    {
        $staff = Staff::find($id);
        if (!$staff) {
            $this->dispatch('showError', message: 'Personale non trovato');
            return;
        }
        
        $this->formId = $staff->id_personale;
        $this->formNome = $staff->NomePers ?? '';
        $this->formCognome = $staff->CognomePers ?? '';
        $this->formSoprannome = $staff->Soprannome ?? '';
        $this->formCodFiscale = $staff->CodFiscPers ?? '';
        $this->formTelefono = $staff->TelPers ?? '';
        $this->formCellulare = $staff->CellPers ?? '';
        $this->formEmail = $staff->EmailPers ?? '';
        $this->formIndirizzo = $staff->IndirPers ?? '';
        $this->formCitta = $staff->CittaPers ?? '';
        $this->formProvincia = $staff->ProvPers ?? '';
        $this->formCap = $staff->CapPers ?? '';
        $this->formDataNascita = $staff->DataNascPers ?? '';
        $this->formLuogoNascita = $staff->LuogoNasc ?? '';
        $this->formValid = $staff->valid == 1;
        
        $this->showEditModal = true;
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetForm();
    }
    
    public function update()
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            $this->dispatch('showError', message: 'Non hai i permessi necessari.');
            return;
        }
        
        $this->validate([
            'formNome' => 'nullable|string|max:255',
            'formCognome' => 'nullable|string|max:255',
            'formEmail' => 'nullable|email|max:255',
            'formCellulare' => 'nullable|string|max:20',
        ]);
        
        try {
            DB::table('staff')
                ->where('id_personale', $this->formId)
                ->update([
                    'NomePers' => $this->formNome ?: null,
                    'CognomePers' => $this->formCognome ?: null,
                    'Soprannome' => $this->formSoprannome ?: null,
                    'CodFiscPers' => $this->formCodFiscale ?: null,
                    'TelPers' => $this->formTelefono ?: null,
                    'CellPers' => $this->formCellulare ?: null,
                    'EmailPers' => $this->formEmail ?: null,
                    'IndirPers' => $this->formIndirizzo ?: null,
                    'CittaPers' => $this->formCitta ?: null,
                    'ProvPers' => $this->formProvincia ?: null,
                    'CapPers' => $this->formCap ?: null,
                    'DataNascPers' => $this->formDataNascita ?: null,
                    'LuogoNasc' => $this->formLuogoNascita ?: null,
                    'valid' => $this->formValid ? 1 : 0,
                ]);
            
            $this->closeEditModal();
            $this->dispatch('showSuccess', message: 'Personale aggiornato con successo!');
            $this->resetPage();
            
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
        }
    }
    
    // ==================== MODAL ELIMINAZIONE ====================
    
    public function confirmDelete($id)
    {
        $staff = Staff::find($id);
        if ($staff) {
            $this->deletingStaffId = $id;
            $this->deletingStaffName = trim($staff->NomePers . ' ' . $staff->CognomePers);
            $this->showDeleteModal = true;
        }
    }
    
    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingStaffId = null;
        $this->deletingStaffName = '';
    }
    
    public function deleteStaff()
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_staff')) {
            $this->dispatch('showError', message: 'Non hai i permessi necessari.');
            return;
        }
        
        try {
            DB::table('staff')->where('id_personale', $this->deletingStaffId)->delete();
            $this->dispatch('showSuccess', message: "Personale '{$this->deletingStaffName}' eliminato con successo!");
            $this->closeDeleteModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore: ' . $e->getMessage());
            $this->closeDeleteModal();
        }
    }
    
    // ==================== METODI STATO ====================
    
    public function toggleStatus($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            $this->dispatch('showError', message: 'Non hai i permessi necessari.');
            return;
        }
        
        try {
            $staff = Staff::find($id);
            if ($staff) {
                $newStatus = !$staff->valid;
                DB::table('staff')->where('id_personale', $id)->update(['valid' => $newStatus ? 1 : 0]);
                $status = $newStatus ? 'attivato' : 'disattivato';
                $this->dispatch('showSuccess', message: "Personale {$status} con successo!");
                $this->resetPage();
            }
        } catch (\Exception $e) {
            $this->dispatch('showError', message: 'Errore durante il cambio di stato');
        }
    }
    
    // ==================== FILTRI ====================
    
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->sortField = 'id_personale';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.admin.staff-table', [
            'staff' => $this->staff
        ]);
    }
}