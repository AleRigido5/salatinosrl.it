<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin (tutti i permessi)
        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Amministratore',
                'description' => 'Ha accesso totale a tutte le funzionalità del sistema',
                'level' => 1,
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // Admin
        $admin = Role::updateOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Amministratore',
                'description' => 'Gestisce amministratori, utenti, ruoli e permessi',
                'level' => 2,
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // Editor
        $editor = Role::updateOrCreate(
            ['slug' => 'editor'],
            [
                'name' => 'Editor',
                'description' => 'Gestisce contenuti e visualizza report',
                'level' => 3,
                'is_default' => false,
                'is_active' => true,
            ]
        );

        // Viewer
        $viewer = Role::updateOrCreate(
            ['slug' => 'viewer'],
            [
                'name' => 'Visualizzatore',
                'description' => 'Può solo visualizzare i dati senza modificarli',
                'level' => 4,
                'is_default' => true,
                'is_active' => true,
            ]
        );

        // Assegna permessi
        $allPermissions = Permission::pluck('id')->toArray();
        $superAdmin->permissions()->sync($allPermissions);

        $adminPermissions = Permission::whereIn('slug', [
            'access_dashboard',
            'view_administrators', 'create_administrators', 'edit_administrators',
            'view_roles', 'create_roles', 'edit_roles', 'manage_role_permissions',
            'view_entities', 'create_entities', 'edit_entities', 'delete_entities',
            'view_contacts', 'manage_contacts',
            'view_settings', 'edit_settings',
            'view_users', 'create_users', 'edit_users', 'delete_users',
        ])->pluck('id')->toArray();
        $admin->permissions()->sync($adminPermissions);

        $editorPermissions = Permission::whereIn('slug', [
            'access_dashboard',
            'view_entities',
            'view_contacts',
            'view_users',
        ])->pluck('id')->toArray();
        $editor->permissions()->sync($editorPermissions);

        $viewerPermissions = Permission::whereIn('slug', [
            'access_dashboard',
            'view_entities',
        ])->pluck('id')->toArray();
        $viewer->permissions()->sync($viewerPermissions);
    }
}