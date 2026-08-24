<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseCategory extends Model
{
    protected $table = 'warehouse_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'sort_order',
        'valid',
    ];

    protected $casts = [
        'valid' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(WarehouseProduct::class, 'id_category');
    }

    public function isMainCategory(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Nome completo "Categoria > Sottocategoria", utile per liste piatte
     * (select, filtri) dove il contesto della categoria padre serve subito.
     */
    public function getFullNameAttribute(): string
    {
        return $this->parent
            ? $this->parent->name . ' > ' . $this->name
            : $this->name;
    }
}