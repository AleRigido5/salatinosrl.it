<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'group',
        'icon',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relazione con i ruoli
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    /**
     * Permessi predefiniti del sistema
     */
    public static function getDefaultPermissions()
    {
        return [
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
            
            // Gestione Utenti (normali)
            ['name' => 'Visualizzare Utenti', 'slug' => 'view_users', 'group' => 'users', 'icon' => '👤', 'sort_order' => 30],
            ['name' => 'Creare Utenti', 'slug' => 'create_users', 'group' => 'users', 'icon' => '➕', 'sort_order' => 31],
            ['name' => 'Modificare Utenti', 'slug' => 'edit_users', 'group' => 'users', 'icon' => '✏️', 'sort_order' => 32],
            ['name' => 'Eliminare Utenti', 'slug' => 'delete_users', 'group' => 'users', 'icon' => '🗑️', 'sort_order' => 33],
            ['name' => 'Gestire Permessi Utenti', 'slug' => 'manage_user_permissions', 'group' => 'users', 'icon' => '🔐', 'sort_order' => 34],
            
            // Contenuti
            ['name' => 'Visualizzare Contenuti', 'slug' => 'view_content', 'group' => 'content', 'icon' => '📄', 'sort_order' => 40],
            ['name' => 'Creare Contenuti', 'slug' => 'create_content', 'group' => 'content', 'icon' => '✍️', 'sort_order' => 41],
            ['name' => 'Modificare Contenuti', 'slug' => 'edit_content', 'group' => 'content', 'icon' => '📝', 'sort_order' => 42],
            ['name' => 'Eliminare Contenuti', 'slug' => 'delete_content', 'group' => 'content', 'icon' => '🗑️', 'sort_order' => 43],
            ['name' => 'Pubblicare Contenuti', 'slug' => 'publish_content', 'group' => 'content', 'icon' => '📢', 'sort_order' => 44],
            
            // Ordini
            ['name' => 'Visualizzare Ordini', 'slug' => 'view_orders', 'group' => 'orders', 'icon' => '🛒', 'sort_order' => 50],
            ['name' => 'Modificare Ordini', 'slug' => 'edit_orders', 'group' => 'orders', 'icon' => '✏️', 'sort_order' => 51],
            ['name' => 'Eliminare Ordini', 'slug' => 'delete_orders', 'group' => 'orders', 'icon' => '🗑️', 'sort_order' => 52],
            ['name' => 'Esportare Ordini', 'slug' => 'export_orders', 'group' => 'orders', 'icon' => '📎', 'sort_order' => 53],
            
            // Report
            ['name' => 'Visualizzare Report', 'slug' => 'view_reports', 'group' => 'reports', 'icon' => '📈', 'sort_order' => 60],
            ['name' => 'Esportare Report', 'slug' => 'export_reports', 'group' => 'reports', 'icon' => '📎', 'sort_order' => 61],
            
            // Impostazioni
            ['name' => 'Visualizzare Impostazioni', 'slug' => 'view_settings', 'group' => 'settings', 'icon' => '⚙️', 'sort_order' => 70],
            ['name' => 'Modificare Impostazioni', 'slug' => 'edit_settings', 'group' => 'settings', 'icon' => '🔧', 'sort_order' => 71],
            
            // Sistema
            ['name' => 'Visualizzare Log', 'slug' => 'view_logs', 'group' => 'system', 'icon' => '📋', 'sort_order' => 80],
            ['name' => 'Gestire Backup', 'slug' => 'manage_backups', 'group' => 'system', 'icon' => '💾', 'sort_order' => 81],
        ];
    }
}