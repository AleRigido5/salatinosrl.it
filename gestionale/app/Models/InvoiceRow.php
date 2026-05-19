<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceRow extends Model
{
    use SoftDeletes;

    protected $table = 'invoice_row';

    protected $fillable = [
        'document_id',
        'document_type',
        'id_cost_center',
        'id_vehicle',
        'description',
        'quantity',
        'unit_price',
        'vat_rate_id',
        'vat_rate',        // AGGIUNGI QUESTO
        'discount_percentage',
        'total'
    ];

    protected $casts = [
        'quantity' => 'decimal:8',
        'unit_price' => 'decimal:8',
        'discount_percentage' => 'decimal:2',
        'vat_rate' => 'decimal:2',  // AGGIUNGI QUESTO
        'total' => 'decimal:6'
    ];

    public function invoiceReceived(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'document_id')
            ->where('document_type', 'invoice_received');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'id_cost_center');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicles::class, 'id_vehicle');
    }

    protected static function booted()
    {
        static::saving(function ($row) {
            $subtotal = $row->quantity * $row->unit_price;
            $discount = $subtotal * ($row->discount_percentage / 100);
            $row->total = round($subtotal - $discount, 6);
        });
    }
}