<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Config;

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
    
    protected $attributes = [
        'status' => 'issued', // Stato di default "Emessa"
    ];
    
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
    
    /**
     * Ottiene l'etichetta dello stato DAL CONFIG
     */
    public function getStatusLabelAttribute(): string
    {
        $invoiceStatuses = Config::get('gestionale.invoice_status', []);
        
        return $invoiceStatuses[$this->status]['label'] ?? $this->status;
    }
    
    /**
     * Ottiene l'etichetta della modalità di pagamento DAL CONFIG
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $modalita = Config::get('gestionale.modalita_pagamento', []);
        
        return $modalita[$this->payment_method] ?? $this->payment_method;
    }
    
    /**
     * Ottiene il badge dello stato DAL CONFIG
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $invoiceStatuses = Config::get('gestionale.invoice_status', []);
        
        return $invoiceStatuses[$this->status]['badge_class'] ?? 'bg-gray-100 text-gray-800';
    }
}