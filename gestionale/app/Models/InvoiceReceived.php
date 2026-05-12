<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceReceived extends Model
{
    use SoftDeletes;

    protected $table = 'invoices_received';

    protected $fillable = [
        'id_ownership',
        'id_entities',
        'type_invoice',
        'n_invoice',
        'data_invoice',
        'importo_totale',
        'causale',
        'divisa',
        'status',
        'sdi_id',
        'xml_filename',
        'xml_content', 
        'file_hash',
        'imported_at',
    ];

    protected $casts = [
        'data_invoice' => 'date',
        'importo_totale' => 'decimal:2',
        'imported_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'divisa' => 'EUR',
        'status' => 'bozza'
    ];

    const STATUS_BOZZA = 'bozza';
    const STATUS_INVIATA = 'inviata';
    const STATUS_SCARTATA = 'scartata';
    const STATUS_CONSEGNATA = 'consegnata';

    const TYPE_TD01 = 'TD01';
    const TYPE_TD04 = 'TD04';
    const TYPE_TD05 = 'TD05';
    const TYPE_TD20 = 'TD20';

    /**
     * Relazione con la proprietà (ownership)
     */
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    /**
     * Relazione con l'entità (fornitore)
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    /**
     * Relazione con le righe della fattura
     */
    public function rows(): HasMany
    {
        return $this->hasMany(InvoiceRow::class, 'document_id')->where('document_type', 'invoice_received');
    }

    /**
     * Relazione polimorfica con i pagamenti
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(InvoicePayment::class, 'payable');
    }

    /**
     * Relazione con i riepiloghi IVA
     */
    public function vatSummaries(): HasMany
    {
        return $this->hasMany(InvoiceVatSummary::class, 'invoice_id');
    }

    /**
     * Ottiene il totale pagato
     */
    public function getTotalPaidAttribute(): float
    {
        return $this->payments()->where('status', InvoicePayment::STATUS_PAID)->sum('amount');
    }

    /**
     * Ottiene il residuo da pagare
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->importo_totale - $this->total_paid;
    }

    /**
     * Ottiene lo stato pagamento complessivo
     */
    public function getPaymentStatusAttribute(): string
    {
        if ($this->remaining_amount <= 0) {
            return 'paid';
        }
        if ($this->payments()->where('status', InvoicePayment::STATUS_PENDING)
                ->where('due_date', '<', now())->exists()) {
            return 'overdue';
        }
        return 'pending';
    }

    /**
     * Ottiene il contenuto XML della fattura (senza allegati)
     */
    public function getXmlContentAttribute(): ?string
    {
        $content = $this->getAttribute('xml_content');
        
        if (!empty($content)) {
            return $this->removeAttachmentsFromXml($content);
        }
        
        Log::warning('Nessun xml_content per fattura ID: ' . $this->id);
        return null;
    }
    
    /**
     * Rimuove gli allegati dall'XML per la visualizzazione
     */
    private function removeAttachmentsFromXml($xmlString)
    {
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        $xmlString = preg_replace('/\n\s*\n/', "\n", $xmlString);
        return $xmlString;
    }

    /**
     * Ottiene il nome del fornitore dalla relazione
     */
    public function getSupplierNameAttribute(): string
    {
        return $this->entity?->ragione_sociale ?? $this->entity?->nome . ' ' . $this->entity?->cognome ?? 'N/D';
    }

    /**
     * Ottiene il nome della proprietà dalla relazione
     */
    public function getOwnershipNameAttribute(): string
    {
        return $this->ownership?->Rag_Soc_intest ?? $this->ownership?->RagSocialePr ?? 'N/D';
    }

    /**
     * Ottiene lo stato formattato
     */
    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_BOZZA => 'Bozza',
            self::STATUS_INVIATA => 'Inviata',
            self::STATUS_SCARTATA => 'Scartata',
            self::STATUS_CONSEGNATA => 'Consegnata',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    /**
     * Ottiene il tipo documento formattato
     */
    public function getTypeInvoiceLabelAttribute(): string
    {
        $labels = [
            self::TYPE_TD01 => 'Fattura',
            self::TYPE_TD04 => 'Nota di Credito',
            self::TYPE_TD05 => 'Nota di Debito',
            self::TYPE_TD20 => 'Autofattura',
        ];
        return $labels[$this->type_invoice] ?? $this->type_invoice;
    }

    /**
     * Ottiene lo stato badge colorato
     */
    public function getStatusBadgeClassAttribute(): string
    {
        $badges = [
            self::STATUS_BOZZA => 'bg-yellow-100 text-yellow-800',
            self::STATUS_INVIATA => 'bg-blue-100 text-blue-800',
            self::STATUS_SCARTATA => 'bg-red-100 text-red-800',
            self::STATUS_CONSEGNATA => 'bg-green-100 text-green-800',
        ];
        return $badges[$this->status] ?? 'bg-gray-100 text-gray-800';
    }
}