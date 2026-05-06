<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceRow extends Model
{
    use SoftDeletes;
    
    protected $table = 'invoice_row';
    protected $primaryKey = 'id';
    
    protected $fillable = [
        'document_id',
        'document_type',
        'id_cost_center',
        'description',
        'quantity',
        'unit_price',
        'vat_rate_id',
        'discount_percentage',
        'total'
    ];
    
    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:4',
        'discount_percentage' => 'decimal:2',
        'total' => 'decimal:2',
    ];
    
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'id_cost_center', 'id');
    }
    
    public function vatRate()
    {
        return $this->belongsTo(VatRate::class, 'vat_rate_id');
    }
}