<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceReceived extends Model
{
    use SoftDeletes;
    
    protected $table = 'invoices_received';
    protected $primaryKey = 'id';
    
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
        'sdi_id'
    ];
    
    protected $casts = [
        'data_ownership' => 'array',
        'data_entities' => 'array',
        'data_invoice' => 'date',
        'importo_totale' => 'decimal:2',
    ];
    
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }
    
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }
    
    public function rows()
    {
        return $this->hasMany(InvoiceRow::class, 'document_id')->where('document_type', 'invoice_received');
    }
    
    public function getStatusLabelAttribute()
    {
        $statuses = config('gestionale.invoice_status', []);
        return $statuses[$this->status]['label'] ?? ucfirst($this->status);
    }
    
    public function getStatusBadgeClassAttribute()
    {
        $statuses = config('gestionale.invoice_status', []);
        return $statuses[$this->status]['badge_class'] ?? 'bg-gray-100 text-gray-800';
    }
    
    public function getStatusIconAttribute()
    {
        $statuses = config('gestionale.invoice_status', []);
        return $statuses[$this->status]['icon'] ?? 'fa-file';
    }
    
    public function getTypeLabelAttribute()
    {
        $types = config('gestionale.invoice_types', []);
        return $types[$this->type_invoice] ?? $this->type_invoice;
    }
}