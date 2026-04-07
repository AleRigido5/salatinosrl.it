<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
        'level',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    /**
     * Relazione con i permessi
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Relazione con gli amministratori
     */
    public function administrators()
    {
        return $this->hasMany(Administrator::class);
    }

    /**
     * Verifica se ha un permesso
     */
    public function hasPermission($permissionSlug)
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Assegna permessi
     */
    public function assignPermissions($permissions)
    {
        $this->permissions()->sync($permissions);
    }

    /**
     * Aggiunge un permesso
     */
    public function addPermission($permissionId)
    {
        $this->permissions()->attach($permissionId);
    }

    /**
     * Rimuove un permesso
     */
    public function removePermission($permissionId)
    {
        $this->permissions()->detach($permissionId);
    }

    /**
     * Ottieni tutti i permessi come array
     */
    public function getPermissionsArray()
    {
        return $this->permissions->pluck('slug')->toArray();
    }
}