<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
        'phone',
        'avatar',
        'last_login_at',
        'last_login_ip',
        'metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Ruoli disponibili
     */
    public static function getRoles(): array
    {
        return [
            'user' => 'Utente Standard',
            'premium' => 'Utente Premium',
            'vip' => 'Utente VIP',
            'moderator' => 'Moderatore',
        ];
    }

    /**
     * Permessi disponibili
     */
    public static function getAvailablePermissions(): array
    {
        return [
            'access_dashboard' => 'Accesso Dashboard',
            'edit_profile' => 'Modifica Profilo',
            'view_public_content' => 'Visualizza Contenuti Pubblici',
            'create_content' => 'Creare Contenuti',
            'edit_content' => 'Modificare Contenuti',
            'delete_content' => 'Cancellare Contenuti',
            'post_comments' => 'Postare Commenti',
            'edit_comments' => 'Modificare Commenti',
            'delete_comments' => 'Cancellare Commenti',
            'access_premium_content' => 'Accesso Contenuti Premium',
            'download_files' => 'Download Files',
            'no_ads' => 'Nessuna Pubblicità',
        ];
    }

    /**
     * Ottieni permessi base per ruolo
     */
    public function getRolePermissions(): array
    {
        return match($this->role) {
            'moderator' => [
                'access_dashboard',
                'edit_profile',
                'view_public_content',
                'create_content',
                'edit_content',
                'delete_content',
                'post_comments',
                'edit_comments',
                'delete_comments',
            ],
            'vip' => [
                'access_dashboard',
                'edit_profile',
                'view_public_content',
                'create_content',
                'edit_content',
                'post_comments',
                'edit_comments',
                'access_premium_content',
                'download_files',
                'no_ads',
            ],
            'premium' => [
                'access_dashboard',
                'edit_profile',
                'view_public_content',
                'create_content',
                'post_comments',
                'access_premium_content',
                'download_files',
                'no_ads',
            ],
            default => [
                'access_dashboard',
                'edit_profile',
                'view_public_content',
                'post_comments',
            ],
        };
    }

    /**
     * Verifica se l'utente ha un permesso specifico
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->getRolePermissions());
    }

    /**
     * Ottieni tutti i permessi dell'utente
     */
    public function getAllPermissions(): array
    {
        $permissions = $this->getRolePermissions();
        
        if ($this->permissions) {
            $permissions = array_merge($permissions, $this->permissions);
        }

        return array_unique($permissions);
    }

    /**
     * Aggiorna permessi utente
     */
    public function updatePermissions(array $permissions): void
    {
        $this->update(['permissions' => $permissions]);
    }

    /**
     * Verifica se l'account è attivo
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Aggiorna ultimo accesso
     */
    public function updateLastLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}