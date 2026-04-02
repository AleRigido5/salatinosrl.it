<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use App\Livewire\Admin\RolesTable;
use App\Livewire\Admin\UserTable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Livewire::component('admin.roles-table', RolesTable::class);
        Livewire::component('admin.user-table', UserTable::class);
    }
}
