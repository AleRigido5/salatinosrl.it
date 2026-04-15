<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Administrator extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'administrators';
    
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active',
        'avatar',
        'phone',
        'last_login_at',
        'last_login_ip',
        'created_by',      // <-- AGGIUNTO
        'updated_by'       // <-- AGGIUNTO
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // RELAZIONI PER IL TRACCIAMENTO (self-referencing)
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    /**
     * Relazione con il ruolo
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Verifica se ha un permesso specifico
     */
    public function hasPermission($permissionSlug)
    {
        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermission($permissionSlug);
    }

    /**
     * Verifica se ha un ruolo specifico
     */
    public function hasRole($roleSlug)
    {
        return $this->role && $this->role->slug === $roleSlug;
    }

    /**
     * Verifica se è super admin
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Verifica se è admin
     */
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Verifica se l'account è attivo
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Aggiorna ultimo accesso
     */
    public function updateLastLogin($ip)
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }
}