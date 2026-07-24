<?php
// app/Models/CreditNoteInvoiceRelation.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteInvoiceRelation extends Model
{
    protected $table = 'credit_note_invoice_relations';
    
    protected $fillable = [
        'credit_note_id',
        'invoice_id',
        'allocated_amount'
    ];
    
    protected $casts = [
        'allocated_amount' => 'decimal:2'
    ];
    
    /**
     * Relazione con la nota di credito
     */
    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'credit_note_id');
    }
    
    /**
     * Relazione con la fattura chiusa
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'invoice_id');
    }
}