<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\User;

#[Layout('admin.layouts.app')]

class UserTable extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';

    public function updatingSearch() 
    { 
        $this->resetPage(); 
    }

    public function render()
    {
        $users = User::query()
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, fn($q) => $q->where('role', $this->roleFilter))
            ->when($this->statusFilter, function($q) {
                $q->where('is_active', $this->statusFilter === 'active');
            })
            ->latest()
            ->paginate(10);

        // Passa i dati alla view
        return view('livewire.admin.user-table', [
            'users' => $users,
            'roles' => [
                'user' => 'Utente',
                'moderator' => 'Moderatore',
                'vip' => 'VIP',
                'premium' => 'Premium'
            ]
        ]);
    }
}