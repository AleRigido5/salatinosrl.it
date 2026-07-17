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
    
    /**
     * Ottiene l'entità (cliente/fornitore) tramite le transazioni collegate
     */
    public function getEntityAttribute()
    {
        // 1. Prova dalla fattura
        if ($this->invoice && $this->invoice->entity) {
            return $this->invoice->entity;
        }
        
        // 2. Prova dal pagamento
        if ($this->invoicePayment && $this->invoicePayment->payable && $this->invoicePayment->payable->entity) {
            return $this->invoicePayment->payable->entity;
        }
        
        // 3. Prova dalle installment_transactions
        $installment = $this->installmentTransactions()->first();
        if ($installment && $installment->invoicePayment && $installment->invoicePayment->payable) {
            $payable = $installment->invoicePayment->payable;
            if ($payable && isset($payable->entity)) {
                return $payable->entity;
            }
        }
        
        return null;
    }
    
    /**
     * Ottiene il nome dell'entità
     */
    public function getEntityNameAttribute(): string
    {
        $entity = $this->entity;
        if ($entity) {
            return $entity->ragione_sociale ?? $entity->nome . ' ' . $entity->cognome ?? 'N/D';
        }
        return '-';
    }
    
    /**
     * Ottiene la proprietà tramite le transazioni collegate
     */
    public function getOwnershipAttribute()
    {
        // 1. Prova dalla fattura
        if ($this->invoice && $this->invoice->ownership) {
            return $this->invoice->ownership;
        }
        
        // 2. Prova dal pagamento
        if ($this->invoicePayment && $this->invoicePayment->payable && 
            method_exists($this->invoicePayment->payable, 'ownership')) {
            return $this->invoicePayment->payable->ownership ?? null;
        }
        
        // 3. Prova dal conto bancario
        if ($this->bankAccount && $this->bankAccount->ownership) {
            return $this->bankAccount->ownership;
        }
        
        // 4. Prova dalle installment_transactions
        $installment = $this->installmentTransactions()->first();
        if ($installment && $installment->invoicePayment && $installment->invoicePayment->payable) {
            $payable = $installment->invoicePayment->payable;
            if ($payable && method_exists($payable, 'ownership') && $payable->ownership) {
                return $payable->ownership;
            }
        }
        
        return null;
    }
    
    /**
     * Ottiene il nome della proprietà
     */
    public function getOwnershipNameAttribute(): string
    {
        $ownership = $this->ownership;
        if ($ownership) {
            return $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? 'N/D';
        }
        return '-';
    }
    
    /**
     * Ottiene il numero di fattura collegato
     */
    public function getLinkedInvoiceNumberAttribute(): string
    {
        // 1. Prova dalla fattura
        if ($this->invoice) {
            return $this->invoice->n_invoice;
        }
        
        // 2. Prova dal pagamento
        if ($this->invoicePayment && $this->invoicePayment->payable && isset($this->invoicePayment->payable->n_invoice)) {
            return $this->invoicePayment->payable->n_invoice;
        }
        
        // 3. Prova dalle installment_transactions
        $installment = $this->installmentTransactions()->first();
        if ($installment && $installment->invoicePayment && $installment->invoicePayment->payable) {
            $payable = $installment->invoicePayment->payable;
            if ($payable && isset($payable->n_invoice)) {
                return $payable->n_invoice;
            }
        }
        
        return '-';
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