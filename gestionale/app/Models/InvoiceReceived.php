<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
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
        'created_by',  
        'updated_by',
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

    /**
     * Relazione polimorfica pagamenti
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(InvoicePayment::class, 'payable');
    }

    /**
     * Relazione polimorfica con i riepiloghi IVA
     */
    public function vatSummaries(): MorphMany
    {
        return $this->morphMany(InvoiceVatSummary::class, 'vatable');
    }

    /**
     * Relazione con l'amministratore che ha creato la fattura
     */
    public function creator()
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    /**
     * Relazione con l'amministratore che ha modificato la fattura
     */
    public function updater()
    {
        return $this->belongsTo(Administrator::class, 'updated_by');
    }

    public function getSupplierNameAttribute(): string
    {
        return $this->entity?->ragione_sociale ?? $this->entity?->nome . ' ' . $this->entity?->cognome ?? 'N/D';
    }

    public function getOwnershipNameAttribute(): string
    {
        return $this->ownership?->Rag_Soc_intest ?? $this->ownership?->RagSocialePr ?? 'N/D';
    }

    public function getStatusLabelAttribute(): string
    {
        $statuses = config('gestionale.invoice_status', []);
        
        return $statuses[$this->status]['label'] ?? $this->status;
    }

    public function getTypeInvoiceLabelAttribute(): string
    {
        $tipoDocumento = config('gestionale.tipo_documento', []);
        
        return $tipoDocumento[$this->type_invoice] ?? $this->type_invoice;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        $statuses = config('gestionale.invoice_status', []);
        
        return $statuses[$this->status]['badge_class'] ?? 'bg-gray-100 text-gray-800';
    }

    public function getXmlContentSafe(): ?string
    {
        $result = DB::table('invoices_received')
            ->where('id', $this->id)
            ->value('xml_content');
        
        if ($result) {
            return $this->removeAttachmentsFromXml($result);
        }
        
        if ($this->xml_filename) {
            $paths = [
                'xml_fatture/' . $this->xml_filename,
                $this->xml_filename,
            ];
            
            foreach ($paths as $path) {
                if (Storage::disk('local')->exists($path)) {
                    $content = Storage::disk('local')->get($path);
                    return $this->removeAttachmentsFromXml($content);
                }
            }
        }
        
        return null;
    }
    
    private function removeAttachmentsFromXml($xmlString): string
    {
        $xmlString = preg_replace('/<Allegati>.*?<\/Allegati>/is', '', $xmlString);
        $xmlString = preg_replace('/<Allegato>.*?<\/Allegato>/is', '', $xmlString);
        $xmlString = preg_replace('/<FatturaFirmata>.*?<\/FatturaFirmata>/is', '', $xmlString);
        $xmlString = preg_replace('/\n\s*\n/', "\n", $xmlString);
        return $xmlString;
    }
}