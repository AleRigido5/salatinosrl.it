<?php
// app/Models/InstallmentTransaction.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'installment_transactions';
    
    protected $fillable = [
        'id_accounting_entries',
        'id_invoice_payment',
        'allocated_amount',
    ];
    
    protected $casts = [
        'allocated_amount' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];
    
    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class, 'id_accounting_entries');
    }
    
    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class, 'id_invoice_payment');
    }
}