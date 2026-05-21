<?php
// app/Models/AccountingEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends Model
{
    protected $table = 'accounting_entries';
    
    protected $fillable = [
        'entry_date',
        'description',
        'type',
        'id_payments_methods',
        'invoice_id',
        'invoice_payment_id',
        'debit_account_id',
        'credit_account_id',
        'amount',
    ];
    
    protected $casts = [
        'entry_date' => 'date',
        'amount' => 'decimal:2',
    ];
    
    // Relazioni
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'id_payments_methods');
    }
    
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'invoice_id');
    }
    
    public function invoicePayment(): BelongsTo
    {
        return $this->belongsTo(InvoicePayment::class, 'invoice_payment_id');
    }
    
    // CORRETTO: usa il namespace completo o importa Account
    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }
    
    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }
    
    public function installmentTransactions()
    {
        return $this->hasMany(InstallmentTransaction::class, 'id_accounting_entries');
    }
    
    // Accessor per tipo formattato
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
    
    // Accessor per saldo (utile per verifiche)
    public function getDebitCreditCheckAttribute(): bool
    {
        // Verifica che la scrittura sia in pareggio (solo per controllo)
        return $this->debit_account_id !== $this->credit_account_id;
    }
}