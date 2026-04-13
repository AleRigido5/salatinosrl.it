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
            ['name' => 'Accesso Dashboard', 'slug' => 'access_dashboard', 'group' => 'dashboard', 'sort_order' => 1],
            
            // Gestione Amministratori
            ['name' => 'Visualizza Amministratori', 'slug' => 'view_administrators', 'group' => 'administrators', 'sort_order' => 10],
            ['name' => 'Crea Amministratori', 'slug' => 'create_administrators', 'group' => 'administrators', 'sort_order' => 11],
            ['name' => 'Modifica Amministratori', 'slug' => 'edit_administrators', 'group' => 'administrators', 'sort_order' => 12],
            ['name' => 'Elimina Amministratori', 'slug' => 'delete_administrators', 'group' => 'administrators', 'sort_order' => 13],
            
            // Gestione Ruoli
            ['name' => 'Visualizza Ruoli', 'slug' => 'view_roles', 'group' => 'roles', 'sort_order' => 20],
            ['name' => 'Crea Ruoli', 'slug' => 'create_roles', 'group' => 'roles', 'sort_order' => 21],
            ['name' => 'Modifica Ruoli', 'slug' => 'edit_roles', 'group' => 'roles', 'sort_order' => 22],
            ['name' => 'Elimina Ruoli', 'slug' => 'delete_roles', 'group' => 'roles', 'sort_order' => 23],
            ['name' => 'Gestisci Permessi Ruoli', 'slug' => 'manage_role_permissions', 'group' => 'roles', 'sort_order' => 24],
            
            // Gestione Utenti (normali)
            ['name' => 'Visualizza Utenti', 'slug' => 'view_users', 'group' => 'users', 'sort_order' => 30],
            ['name' => 'Crea Utenti', 'slug' => 'create_users', 'group' => 'users', 'sort_order' => 31],
            ['name' => 'Modifica Utenti', 'slug' => 'edit_users', 'group' => 'users', 'sort_order' => 32],
            ['name' => 'Elimina Utenti', 'slug' => 'delete_users', 'group' => 'users', 'sort_order' => 33],
            ['name' => 'Gestisci Permessi Utenti', 'slug' => 'manage_user_permissions', 'group' => 'users', 'sort_order' => 34],
            
            // Gestione Entità (Clienti/Fornitori)
            ['name' => 'Visualizza Entità', 'slug' => 'view_entities', 'group' => 'entities', 'sort_order' => 40],
            ['name' => 'Crea Entità', 'slug' => 'create_entities', 'group' => 'entities', 'sort_order' => 41],
            ['name' => 'Modifica Entità', 'slug' => 'edit_entities', 'group' => 'entities', 'sort_order' => 42],
            ['name' => 'Elimina Entità', 'slug' => 'delete_entities', 'group' => 'entities', 'sort_order' => 43],
            ['name' => 'Esporta Entità', 'slug' => 'export_entities', 'group' => 'entities', 'sort_order' => 44],
            
            // Gestione Personale (Staff)
            ['name' => 'Visualizza Personale', 'slug' => 'view_staff', 'group' => 'staff', 'sort_order' => 50],
            ['name' => 'Crea Personale', 'slug' => 'create_staff', 'group' => 'staff', 'sort_order' => 51],
            ['name' => 'Modifica Personale', 'slug' => 'edit_staff', 'group' => 'staff', 'sort_order' => 52],
            ['name' => 'Elimina Personale', 'slug' => 'delete_staff', 'group' => 'staff', 'sort_order' => 53],
            ['name' => 'Esporta Personale', 'slug' => 'export_staff', 'group' => 'staff', 'sort_order' => 54],
            
            // Gestione Mezzi (Vehicles)
            ['name' => 'Visualizza Mezzi', 'slug' => 'view_vehicles', 'group' => 'vehicles', 'sort_order' => 60],
            ['name' => 'Crea Mezzi', 'slug' => 'create_vehicles', 'group' => 'vehicles', 'sort_order' => 61],
            ['name' => 'Modifica Mezzi', 'slug' => 'edit_vehicles', 'group' => 'vehicles', 'sort_order' => 62],
            ['name' => 'Elimina Mezzi', 'slug' => 'delete_vehicles', 'group' => 'vehicles', 'sort_order' => 63],
            ['name' => 'Esporta Mezzi', 'slug' => 'export_vehicles', 'group' => 'vehicles', 'sort_order' => 64],
            
            // Gestione Servizi
            ['name' => 'Visualizza Servizi', 'slug' => 'view_services', 'group' => 'services', 'sort_order' => 70],
            ['name' => 'Crea Servizi', 'slug' => 'create_services', 'group' => 'services', 'sort_order' => 71],
            ['name' => 'Modifica Servizi', 'slug' => 'edit_services', 'group' => 'services', 'sort_order' => 72],
            ['name' => 'Elimina Servizi', 'slug' => 'delete_services', 'group' => 'services', 'sort_order' => 73],
            ['name' => 'Esporta Servizi', 'slug' => 'export_services', 'group' => 'services', 'sort_order' => 74],
            
            // Gestione Contatti
            ['name' => 'Visualizza Contatti', 'slug' => 'view_contacts', 'group' => 'contacts', 'sort_order' => 80],
            ['name' => 'Crea Contatti', 'slug' => 'create_contacts', 'group' => 'contacts', 'sort_order' => 81],
            ['name' => 'Modifica Contatti', 'slug' => 'edit_contacts', 'group' => 'contacts', 'sort_order' => 82],
            ['name' => 'Elimina Contatti', 'slug' => 'delete_contacts', 'group' => 'contacts', 'sort_order' => 83],
            
            // Gestione Impostazioni
            ['name' => 'Visualizza Impostazioni', 'slug' => 'view_settings', 'group' => 'settings', 'sort_order' => 90],
            ['name' => 'Modifica Impostazioni', 'slug' => 'edit_settings', 'group' => 'settings', 'sort_order' => 91],
            ['name' => 'Accesso Impostazioni', 'slug' => 'access_settings', 'group' => 'settings', 'sort_order' => 92],
            
            // Sistema
            ['name' => 'Visualizza Log', 'slug' => 'view_logs', 'group' => 'system', 'sort_order' => 100],
            ['name' => 'Gestisci Backup', 'slug' => 'manage_backups', 'group' => 'system', 'sort_order' => 101],
            ['name' => 'Accesso Cestino', 'slug' => 'access_trash', 'group' => 'system', 'sort_order' => 102],
        ];
    }

    /**
     * Ottieni i permessi raggruppati
     */
    public static function getGroupedPermissions()
    {
        $permissions = self::where('is_active', true)->orderBy('sort_order')->get();
        return $permissions->groupBy('group');
    }

    /**
     * Ottieni i gruppi di permessi disponibili
     */
    public static function getGroups()
    {
        return [
            'dashboard' => 'Dashboard',
            'administrators' => 'Amministratori',
            'roles' => 'Ruoli e Permessi',
            'users' => 'Utenti',
            'entities' => 'Clienti / Fornitori',
            'staff' => 'Personale',
            'vehicles' => 'Mezzi',
            'services' => 'Servizi',
            'contacts' => 'Contatti',
            'settings' => 'Impostazioni',
            'system' => 'Sistema',
        ];
    }
}