<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InvoiceVatSummary extends Model
{
    protected $table = 'invoice_vat_summaries';
    
    protected $fillable = [
        'vatable_id',
        'vatable_type',
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
    
    // Relazione polimorfica con la fattura
    public function vatable(): MorphTo
    {
        return $this->morphTo();
    }
    
    // Accessor per la natura operazione
    public function getNatureLabelAttribute(): string
    {
        $naturaOperazione = config('gestionale.natura_operazione', []);
        return $naturaOperazione[$this->sdi_nature] ?? $this->sdi_nature;
    }
    
    // Accessor per l'esigibilità IVA
    public function getEsigibilitaLabelAttribute(): string
    {
        $esigibilita = [
            'I' => 'Immediata',
            'D' => 'Differita',
            'S' => 'Scissione dei pagamenti',
        ];
        return $esigibilita[$this->esigibilita_iva] ?? $this->esigibilita_iva;
    }
}