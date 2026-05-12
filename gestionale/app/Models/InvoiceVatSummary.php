<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceVatSummary extends Model
{
    protected $table = 'invoice_vat_summaries';
    
    protected $fillable = [
        'invoice_id',
        'tax_rate',
        'sdi_nature',
        'taxable_amount',
        'tax_amount',
        'vat_law_reference',
        'esigibilita_iva',
    ];
    
    protected $casts = [
        'tax_rate' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
    ];
    
    /**
     * Relazione con la fattura
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'invoice_id');
    }
    
    /**
     * Ottiene l'etichetta della natura
     */
    public function getNatureLabelAttribute(): string
    {
        return config('gestionale.natura_operazione.' . $this->sdi_nature, $this->sdi_nature);
    }
    
    /**
     * Ottiene l'etichetta dell'esigibilità IVA
     */
    public function getEsigibilitaLabelAttribute(): string
    {
        $labels = [
            'I' => 'Immediata',
            'D' => 'Differita',
            'S' => 'Scissione dei pagamenti',
        ];
        return $labels[$this->esigibilita_iva] ?? $this->esigibilita_iva;
    }
}