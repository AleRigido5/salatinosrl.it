<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VatRate extends Model
{
    protected $table = 'vat_rates';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'code',
        'rate',
        'description',
        'is_active'
    ];
    
    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];
}