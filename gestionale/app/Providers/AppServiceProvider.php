<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Registra il componente StaffTable
        Livewire::component('admin.staff.staff-table', \App\Livewire\Admin\StaffTable::class);
    }
}