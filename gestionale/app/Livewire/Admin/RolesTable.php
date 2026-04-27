<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class RolesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 15;
    public $sortField = 'level';
    public $sortDirection = 'asc';
    
    // Cestino
    public $showTrashModal = false;
    public $trashSearch = '';
    public $trashSortField = 'deleted_at';
    public $trashSortDirection = 'desc';
    
    // Modale conferma eliminazione
    public $showDeleteModal = false;
    public $roleToDelete = null;
    public $roleNameToDelete = '';
    
    protected $queryString = ['search', 'sortField', 'sortDirection'];
    
    protected $listeners = ['roleDeleted' => 'refreshTable'];
    
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
        $this->resetPage();
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function getRolesProperty()
    {
        return Role::query()
            ->withCount('administrators')
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('slug', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function getTrashedRolesProperty()
    {
        return Role::onlyTrashed()
            ->with('deletedBy')
            ->when($this->trashSearch, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->trashSearch . '%')
                      ->orWhere('slug', 'like', '%' . $this->trashSearch . '%');
                });
            })
            ->orderBy($this->trashSortField, $this->trashSortDirection)
            ->paginate(10);
    }
    
    public function resetFilters()
    {
        $this->search = '';
        $this->sortField = 'level';
        $this->sortDirection = 'asc';
        $this->resetPage();
    }
    
    public function toggleStatus($id)
    {
        $role = Role::find($id);
        if ($role && $role->slug != 'super_admin') {
            $role->update([
                'is_active' => !$role->is_active,
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);
            $this->dispatch('showSuccess', message: 'Stato del ruolo aggiornato con successo!');
        }
    }
    
    public function confirmDelete($id)
    {
        $role = Role::find($id);
        
        if (!$role) {
            $this->dispatch('showError', message: 'Ruolo non trovato');
            return;
        }
        
        if (in_array($role->slug, ['super_admin', 'admin', 'editor', 'viewer'])) {
            $this->dispatch('showError', message: 'Non puoi eliminare i ruoli di sistema.');
            return;
        }
        
        if ($role->administrators()->count() > 0) {
            $this->dispatch('showError', message: 'Non puoi eliminare un ruolo che ha amministratori associati.');
            return;
        }
        
        $this->roleToDelete = $id;
        $this->roleNameToDelete = $role->name;
        $this->showDeleteModal = true;
    }
    
    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->roleToDelete = null;
        $this->roleNameToDelete = '';
    }
    
    public function deleteRole()
    {
        $role = Role::find($this->roleToDelete);
        if ($role) {
            $role->delete();
            $this->dispatch('showSuccess', message: 'Ruolo spostato nel cestino con successo!');
        }
        $this->cancelDelete();
        $this->resetPage();
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
    
    public function trashSortBy($field)
    {
        if ($this->trashSortField === $field) {
            $this->trashSortDirection = $this->trashSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->trashSortField = $field;
            $this->trashSortDirection = 'asc';
        }
    }
    
    public function restoreFromTrash($id)
    {
        $role = Role::onlyTrashed()->find($id);
        if ($role) {
            $role->restore();
            $role->update([
                'updated_by' => Auth::guard('admin')->id(),
                'updated_at' => now()
            ]);
            $this->dispatch('showSuccess', message: 'Ruolo ripristinato con successo!');
            $this->refreshTable();
        }
    }
    
    public function forceDeleteFromTrash($id)
    {
        $role = Role::onlyTrashed()->find($id);
        if ($role) {
            $role->forceDelete();
            $this->dispatch('showSuccess', message: 'Ruolo eliminato definitivamente!');
            $this->refreshTable();
        }
    }
    
    public function render()
    {
        return view('livewire.admin.roles-table', [
            'roles' => $this->roles,
            'trashedRoles' => $this->trashedRoles,
        ]);
    }
}