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
    
    protected $queryString = ['search', 'roleFilter', 'statusFilter', 'sortField', 'sortDirection'];
    
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
        
        return Administrator::query()
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
            })
            ->when(!$currentAdmin->isSuperAdmin(), function($q) use ($currentAdmin) {
                // Non mostra admin con livello superiore o uguale se non è super admin
                $q->whereHas('role', function($roleQuery) use ($currentAdmin) {
                    $roleQuery->where('level', '>', $currentAdmin->role->level);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function deleteAdministrator($id)
    {
        $admin = Administrator::find($id);
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$admin) {
            session()->flash('error', 'Amministratore non trovato');
            return;
        }
        
        // Non puoi eliminare te stesso
        if ($admin->id === $currentAdmin->id) {
            session()->flash('error', 'Non puoi eliminare il tuo account!');
            return;
        }
        
        // Non puoi eliminare super admin se non sei super admin
        if ($admin->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            session()->flash('error', 'Non puoi eliminare un Super Amministratore!');
            return;
        }
        
        $admin->delete();
        session()->flash('success', 'Amministratore eliminato con successo!');
        $this->dispatch('administratorDeleted');
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