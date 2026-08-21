<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseProduct extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_products';

    protected $fillable = [
        'sku',
        'name',
        'description',
        'unit_of_measure',
        'quantity',
        'valid',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'valid' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(WarehouseMovement::class, 'id_product')->orderByDesc('movement_date');
    }

    public function creator()
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Administrator::class, 'updated_by');
    }

    public function getQuantityLabelAttribute(): string
    {
        $qty = number_format((float) $this->quantity, 2, ',', '.');
        return $this->unit_of_measure ? "{$qty} {$this->unit_of_measure}" : $qty;
    }
}