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
    public $perPage = 15;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Cestino - contatore
    public $trashCount = 0;
    
    // Modali
    public $confirmingDelete = false;
    public $confirmingForceDelete = false;
    public $confirmingRestore = false;
    public $selectedAdminId = null;
    public $selectedAdminName = '';
    
    // Cestino
    public $showTrashModal = false;
    public $trashSearch = '';
    public $trashSortField = 'deleted_at';
    public $trashSortDirection = 'desc';
    
    protected $queryString = ['search', 'roleFilter', 'statusFilter', 'sortField', 'sortDirection'];
    
    protected $listeners = ['administratorDeleted' => 'refreshTable', 'refreshTable' => '$refresh'];
    
    public function mount()
    {
        $this->trashCount = Administrator::onlyTrashed()->count();
    }
    
    public function refreshTable()
    {
        $this->resetPage();
        $this->trashCount = Administrator::onlyTrashed()->count();
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
    
    public function trashSortBy($field)
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
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
    
    public function getRolesProperty()
    {
        return Role::where('is_active', true)->get();
    }
    
    public function getAdministratorsProperty()
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        $query = Administrator::query()
            ->with('role')
            ->whereNull('deleted_at')
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
        
        // Filtro per livello (solo per non super admin)
        if (!$currentAdmin->isSuperAdmin()) {
            $query->whereHas('role', function($roleQuery) use ($currentAdmin) {
                $roleQuery->where('level', '>', $currentAdmin->role->level);
            });
        }
        
        return $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function getTrashedAdministratorsProperty()
    {
        return Administrator::onlyTrashed()
            ->with('role')
            ->when($this->trashSearch, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->trashSearch . '%')
                      ->orWhere('email', 'like', '%' . $this->trashSearch . '%');
                });
            })
            ->orderBy($this->trashSortField, $this->trashSortDirection)
            ->paginate(10);
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
        $admin = Administrator::onlyTrashed()->find($id);
        if ($admin) {
            $this->selectedAdminId = $id;
            $this->selectedAdminName = $admin->name;
            $this->confirmingForceDelete = true;
        }
    }
    
    public function confirmRestore($id)
    {
        $admin = Administrator::onlyTrashed()->find($id);
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
            $this->dispatch('showError', message: 'Amministratore non trovato');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare te stesso
        if ($admin->id === $currentAdmin->id) {
            $this->dispatch('showError', message: 'Non puoi eliminare il tuo account!');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            $this->dispatch('showError', message: 'Non puoi eliminare un Super Amministratore!');
            $this->cancelDelete();
            return;
        }
        
        $adminName = $admin->name;
        $admin->delete();
        $this->dispatch('showSuccess', message: "Amministratore {$adminName} spostato nel cestino.");
        $this->cancelDelete();
        $this->refreshTable();
        $this->trashCount = Administrator::onlyTrashed()->count();
    }
    
    public function restoreAdministrator()
    {
        $admin = Administrator::onlyTrashed()->find($this->selectedAdminId);
        
        if (!$admin) {
            $this->dispatch('showError', message: 'Amministratore non trovato nel cestino');
            $this->cancelDelete();
            return;
        }
        
        $adminName = $admin->name;
        $admin->restore();
        
        $this->dispatch('showSuccess', message: "Amministratore {$adminName} ripristinato con successo.");
        $this->cancelDelete();
        $this->refreshTable();
        $this->closeTrashModal();
        $this->trashCount = Administrator::onlyTrashed()->count();
    }
    
    public function forceDeleteAdministrator()
    {
        $admin = Administrator::onlyTrashed()->find($this->selectedAdminId);
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$admin) {
            $this->dispatch('showError', message: 'Amministratore non trovato nel cestino');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare te stesso permanentemente
        if ($admin->id === $currentAdmin->id) {
            $this->dispatch('showError', message: 'Non puoi eliminare permanentemente il tuo account!');
            $this->cancelDelete();
            return;
        }
        
        // Non puoi eliminare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            $this->dispatch('showError', message: 'Non puoi eliminare permanentemente un Super Amministratore!');
            $this->cancelDelete();
            return;
        }
        
        $adminName = $admin->name;
        $admin->forceDelete();
        
        $this->dispatch('showSuccess', message: "Amministratore {$adminName} eliminato definitivamente.");
        $this->cancelDelete();
        $this->refreshTable();
        $this->closeTrashModal();
        $this->trashCount = Administrator::onlyTrashed()->count();
    }
    
    public function cancelDelete()
    {
        $this->confirmingDelete = false;
        $this->confirmingForceDelete = false;
        $this->confirmingRestore = false;
        $this->selectedAdminId = null;
        $this->selectedAdminName = '';
    }
    
    // Gestione Cestino
    public function openTrashModal()
    {
        $this->showTrashModal = true;
        $this->trashSearch = '';
        $this->trashSortField = 'deleted_at';
        $this->trashSortDirection = 'desc';
    }
    
    public function closeTrashModal()
    {
        $this->showTrashModal = false;
    }
    
    public function resetTrashFilters()
    {
        $this->trashSearch = '';
        $this->trashSortField = 'deleted_at';
        $this->trashSortDirection = 'desc';
    }
    
    public function toggleStatus($id)
    {
        $admin = Administrator::find($id);
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$admin) {
            $this->dispatch('showError', message: 'Amministratore non trovato');
            return;
        }
        
        // Non puoi disattivare te stesso
        if ($admin->id === $currentAdmin->id) {
            $this->dispatch('showError', message: 'Non puoi modificare il tuo stato!');
            return;
        }
        
        // Non puoi disattivare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            $this->dispatch('showError', message: 'Non puoi modificare lo stato di un Super Amministratore!');
            return;
        }
        
        $admin->update(['is_active' => !$admin->is_active]);
        $status = $admin->is_active ? 'attivato' : 'disattivato';
        $this->dispatch('showSuccess', message: "Amministratore {$status} con successo!");
        $this->refreshTable();
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.admin.administrator-table', [
            'administrators' => $this->administrators,
            'roles' => $this->roles,
            'trashedAdministrators' => $this->trashedAdministrators,
            'trashCount' => $this->trashCount,
        ]);
    }
}