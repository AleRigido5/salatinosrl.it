<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserTable extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public $perPage = 15;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    protected $queryString = ['search', 'roleFilter', 'statusFilter', 'sortField', 'sortDirection'];
    
    protected $listeners = ['userDeleted' => 'refreshTable', 'refreshTable' => '$refresh'];
    
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
        return [
            'user' => 'Utente Standard',
            'premium' => 'Utente Premium',
            'vip' => 'Utente VIP',
            'moderator' => 'Moderatore',
        ];
    }
    
    public function getUsersProperty()
    {
        return User::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, fn($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter !== '', function($q) {
                $q->where('is_active', $this->statusFilter === 'active');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }
    
    public function deleteUser($id)
    {
        $user = User::find($id);
        
        if (!$user) {
            session()->flash('error', 'Utente non trovato');
            return;
        }
        
        $user->delete();
        session()->flash('success', 'Utente eliminato con successo!');
        $this->dispatch('userDeleted');
    }
    
    public function toggleStatus($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update(['is_active' => !$user->is_active]);
            $status = $user->is_active ? 'attivato' : 'disattivato';
            session()->flash('success', "Utente {$status} con successo!");
            $this->dispatch('refreshTable');
        }
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
        return view('livewire.admin.user-table', [
            'users' => $this->users,
            'roles' => $this->roles
        ]);
    }
}