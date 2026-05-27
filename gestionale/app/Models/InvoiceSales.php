<?php
// app/Models/InvoiceSales.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InvoiceSales extends Model
{
    use SoftDeletes;

    protected $table = 'invoices_sales';

    protected $fillable = [
        'id_ownership',
        'id_entities',
        'id_invoice_series',
        'type_invoice',
        'n_invoice',
        'data_invoice',
        'importo_totale',
        'causale',
        'divisa',
        'status',
        'sdi_id',
        'is_manual',
        'xml_filename',
        'xml_content',
        'attachment',
        'file_hash',
        'imported_at',
        'created_by',
        'updated_by',
        'customer_slug',
        'attachments_folder',
    ];

    protected $casts = [
        'data_invoice' => 'date',
        'importo_totale' => 'decimal:2',
        'imported_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relazioni
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function invoiceSeries(): BelongsTo
    {
        return $this->belongsTo(InvoiceSeries::class, 'id_invoice_series');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(InvoiceRow::class, 'document_id')->where('document_type', 'invoice_sales');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(InvoicePayment::class, 'payable');
    }

    public function vatSummaries(): MorphMany
    {
        return $this->morphMany(InvoiceVatSummary::class, 'vatable');
    }

    public function creator()
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Administrator::class, 'updated_by');
    }

    // Accessor
    public function getCustomerNameAttribute(): string
    {
        return $this->entity?->ragione_sociale ?? $this->entity?->nome . ' ' . $this->entity?->cognome ?? 'N/D';
    }

    public function getOwnershipNameAttribute(): string
    {
        return $this->ownership?->RagAbbrev ?? $this->ownership?->Rag_Soc_intest ?? 'N/D';
    }
}