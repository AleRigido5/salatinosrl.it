<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Administrator;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;

class AdministratorTable extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $trashedFilter = ''; // Filtro per soft delete
    public $perPage = 15;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $confirmingDelete = false;
    public $confirmingForceDelete = false;
    public $confirmingRestore = false;
    public $selectedAdminId = null;
    public $selectedAdminName = '';
    
    protected $queryString = ['search', 'roleFilter', 'statusFilter', 'trashedFilter', 'sortField', 'sortDirection'];
    
    protected $listeners = ['administratorDeleted' => 'refreshTable', 'refreshTable' => '$refresh'];
    
    public function refreshTable()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingRoleFilter()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingTrashedFilter()
    {
        $this->resetPage();
    }
    
    public function updatingPerPage()
    {
        $this->resetPage();
    }
    
    public function getRolesProperty()
    {
        return Role::where('is_active', true)->get();
    }
    
    public function getAdministratorsProperty()
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        $query = Administrator::query()
            ->with('role')
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, fn($q) => $q->where('role_id', $this->roleFilter))
            ->when($this->statusFilter !== '', function($q) {
                $q->where('is_active', $this->statusFilter === 'active');
            });
        
        // Filtro per soft delete
        if ($this->trashedFilter === 'only_trashed') {
            $query->onlyTrashed();
        } elseif ($this->trashedFilter === 'with_trashed') {
            $query->withTrashed();
        }
        
        // Filtro per livello (solo per non super admin)
        if (!$currentAdmin->isSuperAdmin()) {
            $query->whereHas('role', function($roleQuery) use ($currentAdmin) {
                $roleQuery->where('level', '>', $currentAdmin->role->level);
            });
        }
        
        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function confirmDelete($id)
    {
        $admin = Administrator::find($id);
        if ($admin) {
            $this->selectedAdminId = $id;
            $this->selectedAdminName = $admin->name;
            $this->confirmingDelete = true;
        }
    }
    
    public function confirmForceDelete($id)
    {
        $admin = Administrator::withTrashed()->find($id);
        if ($admin) {
            $this->selectedAdminId = $id;
            $this->selectedAdminName = $admin->name;
            $this->confirmingForceDelete = true;
        }
    }
    
    public function confirmRestore($id)
    {
        $admin = Administrator::withTrashed()->find($id);
        if ($admin) {
            $this->selectedAdminId = $id;
            $this->selectedAdminName = $admin->name;
            $this->confirmingRestore = true;
        }
    }
    
    public function deleteAdministrator()
    {
        $admin = Administrator::find($this->selectedAdminId);
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$admin) {
            session()->flash('error', 'Amministratore non trovato');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare te stesso
        if ($admin->id === $currentAdmin->id) {
            session()->flash('error', 'Non puoi eliminare il tuo account!');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            session()->flash('error', 'Non puoi eliminare un Super Amministratore!');
            $this->cancelDelete();
            return;
        }
        
        $admin->delete();
        session()->flash('success', "Amministratore {$admin->name} è stato spostato nel cestino.");
        $this->dispatch('administratorDeleted');
        $this->cancelDelete();
    }
    
    public function forceDeleteAdministrator()
    {
        $admin = Administrator::withTrashed()->find($this->selectedAdminId);
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$admin) {
            session()->flash('error', 'Amministratore non trovato');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare te stesso permanentemente
        if ($admin->id === $currentAdmin->id) {
            session()->flash('error', 'Non puoi eliminare permanentemente il tuo account!');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            session()->flash('error', 'Non puoi eliminare permanentemente un Super Amministratore!');
            $this->cancelDelete();
            return;
        }
        
        $adminName = $admin->name;
        $admin->forceDelete();
        session()->flash('success', "Amministratore {$adminName} è stato eliminato permanentemente.");
        $this->dispatch('administratorDeleted');
        $this->cancelDelete();
    }
    
    public function restoreAdministrator()
    {
        $admin = Administrator::withTrashed()->find($this->selectedAdminId);
        
        if (!$admin) {
            session()->flash('error', 'Amministratore non trovato');
            $this->cancelDelete();
            return;
        }
        
        $admin->restore();
        session()->flash('success', "Amministratore {$admin->name} è stato ripristinato con successo.");
        $this->dispatch('administratorDeleted');
        $this->cancelDelete();
    }
    
    public function cancelDelete()
    {
        $this->confirmingDelete = false;
        $this->confirmingForceDelete = false;
        $this->confirmingRestore = false;
        $this->selectedAdminId = null;
        $this->selectedAdminName = '';
    }

    public function getTrashCountProperty()
    {
        return Administrator::onlyTrashed()->count();
    }
    
    public function toggleStatus($id)
    {
        $admin = Administrator::find($id);
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$admin) {
            session()->flash('error', 'Amministratore non trovato');
            return;
        }
        
        // Non puoi disattivare te stesso
        if ($admin->id === $currentAdmin->id) {
            session()->flash('error', 'Non puoi modificare il tuo stato!');
            return;
        }
        
        // Non puoi disattivare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            session()->flash('error', 'Non puoi modificare lo stato di un Super Amministratore!');
            return;
        }
        
        $admin->update(['is_active' => !$admin->is_active]);
        $status = $admin->is_active ? 'attivato' : 'disattivato';
        session()->flash('success', "Amministratore {$status} con successo!");
        $this->dispatch('refreshTable');
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->trashedFilter = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.admin.administrator-table', [
            'administrators' => $this->administrators,
            'roles' => $this->roles
        ]);
    }
}