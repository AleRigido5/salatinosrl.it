<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceReceived extends Model
{
    use SoftDeletes;

    protected $table = 'invoices_received';

    protected $fillable = [
        'id_ownership',
        'id_entities',
        'data_ownership',
        'data_entities',
        'type_invoice',
        'n_invoice',
        'data_invoice',
        'importo_totale',
        'causale',
        'divisa',
        'status',
        'sdi_id',
        'xml_content',
        'xml_filename'
    ];

    protected $casts = [
        'data_ownership' => 'array',
        'data_entities' => 'array',
        'data_invoice' => 'date',
        'importo_totale' => 'decimal:6',
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

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(InvoiceRow::class, 'document_id')->where('document_type', 'invoice_received');
    }

    public function getSupplierNameAttribute(): string
    {
        $data = $this->data_entities;
        return $data['Denominazione'] ?? $data['Nome'] . ' ' . ($data['Cognome'] ?? '') ?? 'N/D';
    }

    public function getOwnershipNameAttribute(): string
    {
        $data = $this->data_ownership;
        return $data['Denominazione'] ?? 'N/D';
    }

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
}