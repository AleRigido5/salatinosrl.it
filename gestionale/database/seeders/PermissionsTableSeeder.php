<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'Accesso Dashboard', 'slug' => 'access_dashboard', 'group' => 'dashboard', 'icon' => '📊', 'sort_order' => 1],
            
            // Gestione Amministratori
            ['name' => 'Visualizza Amministratori', 'slug' => 'view_administrators', 'group' => 'administrators', 'icon' => '👥', 'sort_order' => 10],
            ['name' => 'Creare Amministratori', 'slug' => 'create_administrators', 'group' => 'administrators', 'icon' => '➕', 'sort_order' => 11],
            ['name' => 'Modificare Amministratori', 'slug' => 'edit_administrators', 'group' => 'administrators', 'icon' => '✏️', 'sort_order' => 12],
            ['name' => 'Eliminare Amministratori', 'slug' => 'delete_administrators', 'group' => 'administrators', 'icon' => '🗑️', 'sort_order' => 13],
            
            // Gestione Ruoli
            ['name' => 'Visualizza Ruoli', 'slug' => 'view_roles', 'group' => 'roles', 'icon' => '🎭', 'sort_order' => 20],
            ['name' => 'Creare Ruoli', 'slug' => 'create_roles', 'group' => 'roles', 'icon' => '➕', 'sort_order' => 21],
            ['name' => 'Modificare Ruoli', 'slug' => 'edit_roles', 'group' => 'roles', 'icon' => '✏️', 'sort_order' => 22],
            ['name' => 'Eliminare Ruoli', 'slug' => 'delete_roles', 'group' => 'roles', 'icon' => '🗑️', 'sort_order' => 23],
            ['name' => 'Gestire Permessi Ruoli', 'slug' => 'manage_role_permissions', 'group' => 'roles', 'icon' => '🔑', 'sort_order' => 24],
            
            // Gestione Utenti
            ['name' => 'Visualizzare Utenti', 'slug' => 'view_users', 'group' => 'users', 'icon' => '👤', 'sort_order' => 30],
            ['name' => 'Creare Utenti', 'slug' => 'create_users', 'group' => 'users', 'icon' => '➕', 'sort_order' => 31],
            ['name' => 'Modificare Utenti', 'slug' => 'edit_users', 'group' => 'users', 'icon' => '✏️', 'sort_order' => 32],
            ['name' => 'Eliminare Utenti', 'slug' => 'delete_users', 'group' => 'users', 'icon' => '🗑️', 'sort_order' => 33],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}