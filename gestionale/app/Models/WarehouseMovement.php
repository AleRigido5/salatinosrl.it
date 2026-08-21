<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseMovement extends Model
{
    protected $table = 'warehouse_movements';

    const TYPE_IN = 'entrata';
    const TYPE_OUT = 'uscita';

    protected $fillable = [
        'id_product',
        'type',
        'quantity',
        'movement_date',
        'reference_type',
        'reference_id',
        'note',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'movement_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(WarehouseProduct::class, 'id_product');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    public function isManual(): bool
    {
        return empty($this->reference_type);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_IN ? 'Entrata' : 'Uscita';
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return $this->type === self::TYPE_IN
            ? 'bg-green-100 text-green-800'
            : 'bg-red-100 text-red-800';
    }
}