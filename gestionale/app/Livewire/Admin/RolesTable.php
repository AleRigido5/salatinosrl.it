<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;

class RolesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $perPage = 15;
    public $sortField = 'level';
    public $sortDirection = 'asc';
    
    protected $queryString = ['search', 'status', 'sortField', 'sortDirection'];
    
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
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
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
                      ->orWhere('slug', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status !== '', function($query) {
                $query->where('is_active', $this->status === 'active');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function deleteRole($id)
    {
        $role = Role::find($id);
        
        if (!$role) {
            $this->dispatch('error', 'Ruolo non trovato');
            return;
        }
        
        // Impedisci eliminazione ruoli di sistema
        if (in_array($role->slug, ['super_admin', 'admin', 'editor', 'viewer'])) {
            $this->dispatch('error', 'Non puoi eliminare i ruoli di sistema.');
            return;
        }
        
        if ($role->administrators()->count() > 0) {
            $this->dispatch('error', 'Non puoi eliminare un ruolo che ha amministratori associati.');
            return;
        }
        
        $role->delete();
        $this->dispatch('roleDeleted');
        $this->dispatch('success', 'Ruolo eliminato con successo!');
    }

    
    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        $role = Role::find($id);
        if ($role && $role->slug != 'super_admin') {
            $role->update(['is_active' => !$role->is_active]);
            $this->dispatch('success', 'Stato del ruolo aggiornato con successo!');
        }
    }
    
    public function render()
    {
        return view('livewire.roles-table', [
            'roles' => $this->roles
        ]);
    }
}