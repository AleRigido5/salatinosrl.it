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
            
            // Gestione Entità (Clienti/Fornitori)
            ['name' => 'Visualizzare Entità', 'slug' => 'view_entities', 'group' => 'entities', 'icon' => '🏢', 'sort_order' => 30],
            ['name' => 'Creare Entità', 'slug' => 'create_entities', 'group' => 'entities', 'icon' => '➕', 'sort_order' => 31],
            ['name' => 'Modificare Entità', 'slug' => 'edit_entities', 'group' => 'entities', 'icon' => '✏️', 'sort_order' => 32],
            ['name' => 'Eliminare Entità', 'slug' => 'delete_entities', 'group' => 'entities', 'icon' => '🗑️', 'sort_order' => 33],
            
            // Gestione Contatti
            ['name' => 'Visualizzare Contatti', 'slug' => 'view_contacts', 'group' => 'contacts', 'icon' => '📞', 'sort_order' => 40],
            ['name' => 'Gestire Contatti', 'slug' => 'manage_contacts', 'group' => 'contacts', 'icon' => '✏️', 'sort_order' => 41],
            
            // Gestione Settings (Impostazioni Sistema)
            ['name' => 'Accesso Settings', 'slug' => 'access_settings', 'group' => 'settings', 'icon' => '⚙️', 'sort_order' => 50],
            ['name' => 'Visualizzare Settings', 'slug' => 'view_settings', 'group' => 'settings', 'icon' => '👁️', 'sort_order' => 51],
            ['name' => 'Modificare Settings', 'slug' => 'edit_settings', 'group' => 'settings', 'icon' => '✏️', 'sort_order' => 52],
            ['name' => 'Creare Settings', 'slug' => 'create_settings', 'group' => 'settings', 'icon' => '➕', 'sort_order' => 53],
            ['name' => 'Eliminare Settings', 'slug' => 'delete_settings', 'group' => 'settings', 'icon' => '🗑️', 'sort_order' => 54],
            
            // Gestione Utenti (normali)
            ['name' => 'Visualizzare Utenti', 'slug' => 'view_users', 'group' => 'users', 'icon' => '👤', 'sort_order' => 60],
            ['name' => 'Creare Utenti', 'slug' => 'create_users', 'group' => 'users', 'icon' => '➕', 'sort_order' => 61],
            ['name' => 'Modificare Utenti', 'slug' => 'edit_users', 'group' => 'users', 'icon' => '✏️', 'sort_order' => 62],
            ['name' => 'Eliminare Utenti', 'slug' => 'delete_users', 'group' => 'users', 'icon' => '🗑️', 'sort_order' => 63],
            
            // Gestione Indirizzi
            ['name' => 'Visualizzare Indirizzi', 'slug' => 'view_addresses', 'group' => 'addresses', 'icon' => '📍', 'sort_order' => 70],
            ['name' => 'Creare Indirizzi', 'slug' => 'create_addresses', 'group' => 'addresses', 'icon' => '➕', 'sort_order' => 71],
            ['name' => 'Modificare Indirizzi', 'slug' => 'edit_addresses', 'group' => 'addresses', 'icon' => '✏️', 'sort_order' => 72],
            ['name' => 'Eliminare Indirizzi', 'slug' => 'delete_addresses', 'group' => 'addresses', 'icon' => '🗑️', 'sort_order' => 73],
            
            // Gestione Cestino (Soft Delete)
            ['name' => 'Visualizzare Cestino', 'slug' => 'view_trash', 'group' => 'trash', 'icon' => '🗑️', 'sort_order' => 80],
            ['name' => 'Ripristinare Elementi', 'slug' => 'restore_trash', 'group' => 'trash', 'icon' => '🔄', 'sort_order' => 81],
            ['name' => 'Eliminare Definitivamente', 'slug' => 'force_delete_trash', 'group' => 'trash', 'icon' => '💀', 'sort_order' => 82],
            ['name' => 'Svuotare Cestino', 'slug' => 'empty_trash', 'group' => 'trash', 'icon' => '🧹', 'sort_order' => 83],
            
            // Operazioni Bulk (massive)
            ['name' => 'Operazioni Massive Entità', 'slug' => 'bulk_entities', 'group' => 'bulk', 'icon' => '📦', 'sort_order' => 90],
            ['name' => 'Operazioni Massive Cestino', 'slug' => 'bulk_trash', 'group' => 'bulk', 'icon' => '📦', 'sort_order' => 91],
            
            // Export (esportazioni)
            ['name' => 'Esportare CSV', 'slug' => 'export_csv', 'group' => 'export', 'icon' => '📄', 'sort_order' => 100],
            ['name' => 'Esportare PDF', 'slug' => 'export_pdf', 'group' => 'export', 'icon' => '📄', 'sort_order' => 101],
            
            // Sistema
            ['name' => 'Accesso Log Sistema', 'slug' => 'access_system_logs', 'group' => 'system', 'icon' => '📋', 'sort_order' => 110],
            ['name' => 'Backup Database', 'slug' => 'backup_database', 'group' => 'system', 'icon' => '💾', 'sort_order' => 111],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}