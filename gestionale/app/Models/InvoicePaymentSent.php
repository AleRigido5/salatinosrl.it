<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoicePaymentSent extends Model
{
    use SoftDeletes;
    
    protected $table = 'invoice_payments';
    
    protected $fillable = [
        'payable_id',
        'payable_type',
        'due_date',
        'amount',
        'paid_amount',
        'residual_amount',
        'payment_method',
        'iban',
        'paid_at',
        'status',
    ];
    
    protected $casts = [
        'due_date' => 'date',
        'paid_at' => 'date',
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'residual_amount' => 'decimal:2',
    ];
    
    // Relazione polimorfica inversa
    public function payable()
    {
        return $this->morphTo();
    }
    
    // Relazione con la fattura di vendita
    public function invoice()
    {
        return $this->belongsTo(InvoiceSent::class, 'payable_id', 'id')
            ->where('payable_type', 'App\\Models\\InvoiceSent');
    }
    
    // Scope per filtrare solo i pagamenti delle vendite
    public function scopeForSentInvoices($query)
    {
        return $query->where('payable_type', 'App\\Models\\InvoiceSent');
    }
    
    // Scope per filtrare solo i pagamenti in attesa/scaduti
    public function scopePending($query)
    {
        return $query->whereIn('status', ['issued', 'overdue']);
    }
    
    // Accessor per lo stato formattato
    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'paid' => 'Pagato',
            'issued' => 'In attesa',
            'overdue' => 'Scaduto',
            default => $this->status
        };
    }
    
    // Accessor per la data formattata
    public function getFormattedDueDateAttribute()
    {
        return $this->due_date ? $this->due_date->format('d/m/Y') : '-';
    }
    
    // Accessor per l'importo formattato
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2, ',', '.') . ' €';
    }
}