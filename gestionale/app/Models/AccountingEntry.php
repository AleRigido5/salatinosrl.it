<?php
// app/Models/AccountingEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingEntry extends Model
{
    use SoftDeletes;

    protected $table = 'accounting_entries';
    
    protected $fillable = [
        'entry_date',
        'description',
        'type',
        'id_payments_methods',
        'bank_account_id',
        'invoice_id',
        'invoice_payment_id',
        'amount',
    ];
    
    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];

    protected $dates = ['deleted_at'];
    
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'id_payments_methods');
    }
    
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }
    
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'invoice_id');
    }
    
    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class, 'invoice_payment_id');
    }
    
    public function installmentTransactions(): HasMany
    {
        return $this->hasMany(InstallmentTransaction::class, 'id_accounting_entries');
    }
    
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'entrata' ? 'Entrata' : 'Uscita';
    }
    
    public function getTypeBadgeClassAttribute(): string
    {
        return $this->type === 'entrata' 
            ? 'bg-green-100 text-green-800' 
            : 'bg-red-100 text-red-800';
    }
    
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2, ',', '.') . ' €';
    }
}