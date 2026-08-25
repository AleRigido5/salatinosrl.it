<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseDdtRow extends Model
{
    protected $table = 'warehouse_ddt_rows';

    protected $fillable = [
        'id_ddt',
        'id_product',
        'description',
        'quantity',
        'unit_of_measure',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function ddt(): BelongsTo
    {
        return $this->belongsTo(WarehouseDdt::class, 'id_ddt');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(WarehouseProduct::class, 'id_product');
    }
}