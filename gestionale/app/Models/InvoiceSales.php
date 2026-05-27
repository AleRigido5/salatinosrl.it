<?php
// app/Models/InvoiceSales.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceSales extends Model
{
    use SoftDeletes;

    protected $table = 'invoices_sales';  // ← Assicurati sia questo
    
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
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function invoiceSeries()
    {
        return $this->belongsTo(InvoiceSeries::class, 'id_invoice_series');
    }
    
    // Accessor per nome cliente
    public function getCustomerNameAttribute()
    {
        return $this->entity?->ragione_sociale ?? $this->entity?->nome . ' ' . $this->entity?->cognome ?? 'N/D';
    }
    
    // Accessor per nome proprietà
    public function getOwnershipNameAttribute()
    {
        return $this->ownership?->RagAbbrev ?? $this->ownership?->Rag_Soc_intest ?? 'N/D';
    }
}