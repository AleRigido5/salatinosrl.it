<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoicePayment extends Model
{
    protected $table = 'invoice_payments';
    
    protected $fillable = [
        'payable_id',
        'payable_type',
        'due_date',
        'amount',
        'payment_method',
        'iban',
        'paid_at',
        'status',
    ];
    
    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
        'amount' => 'decimal:2',
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_CANCELLED = 'cancelled';
    
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
    
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'In attesa',
            self::STATUS_PAID => 'Pagato',
            self::STATUS_OVERDUE => 'Scaduto',
            self::STATUS_CANCELLED => 'Annullato',
        ];
        return $labels[$this->status] ?? $this->status;
    }
    
    /**
     * Ottiene l'etichetta della modalità di pagamento dal config
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return config('gestionale.modalita_pagamento.' . $this->payment_method, $this->payment_method);
    }
    
    /**
     * Ottiene il badge dello stato
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $badges = [
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_PAID => 'bg-green-100 text-green-800',
            self::STATUS_OVERDUE => 'bg-red-100 text-red-800',
            self::STATUS_CANCELLED => 'bg-gray-100 text-gray-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}