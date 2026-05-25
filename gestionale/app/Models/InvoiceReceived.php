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
        'is_manual', 
        'xml_filename',
        'xml_content',
        'attachment',
        'file_hash',
        'imported_at',
        'created_by',  
        'updated_by',
        'fornitore_slug',
        'attachments_folder',
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

    /**
     * Verifica se la fattura ha allegati
     */
    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->getFirstAttachmentUrlAttribute());
    }

    /**
     * Ottiene l'URL del primo allegato (per l'icona nella tabella)
     */
    public function getFirstAttachmentUrlAttribute(): ?string
    {
        // Metodo 1: Dal campo attachment (JSON)
        if ($this->attachment) {
            $attachments = is_string($this->attachment) ? json_decode($this->attachment, true) : $this->attachment;
            if (is_array($attachments) && !empty($attachments)) {
                return $attachments[0];
            }
            // Se è una stringa semplice (URL diretto)
            if (is_string($this->attachment) && filter_var($this->attachment, FILTER_VALIDATE_URL)) {
                return $this->attachment;
            }
        }
        
        // Metodo 2: Dalla cartella fornitore_slug su S3
        if (!empty($this->fornitore_slug)) {
            try {
                $disk = Storage::disk('s3');
                $folderPath = 'invoice-received/' . $this->fornitore_slug;
                
                if ($disk->exists($folderPath)) {
                    $files = $disk->files($folderPath);
                    if (!empty($files)) {
                        $bucket = config('filesystems.disks.s3.bucket', 'gestionale-152146163010-eu-north-1-an');
                        $region = config('filesystems.disks.s3.region', 'eu-north-1');
                        return "https://{$bucket}.s3.{$region}.amazonaws.com/{$files[0]}";
                    }
                }
            } catch (\Exception $e) {
                Log::error('Errore nel recupero allegati da S3: ' . $e->getMessage());
            }
        }
        
        // Metodo 3: Dalla cartella attachments_folder
        if (!empty($this->attachments_folder)) {
            try {
                $disk = Storage::disk('s3');
                if ($disk->exists($this->attachments_folder)) {
                    $files = $disk->files($this->attachments_folder);
                    if (!empty($files)) {
                        $bucket = config('filesystems.disks.s3.bucket', 'gestionale-152146163010-eu-north-1-an');
                        $region = config('filesystems.disks.s3.region', 'eu-north-1');
                        return "https://{$bucket}.s3.{$region}.amazonaws.com/{$files[0]}";
                    }
                }
            } catch (\Exception $e) {
                Log::error('Errore nel recupero allegati da attachments_folder: ' . $e->getMessage());
            }
        }
        
        return null;
    }

    /**
     * Ottiene tutti gli allegati della fattura
     */
    public function getAllAttachmentsAttribute(): array
    {
        $attachments = [];
        
        // Metodo 1: Dal campo attachment JSON
        if ($this->attachment) {
            $decoded = is_string($this->attachment) ? json_decode($this->attachment, true) : $this->attachment;
            if (is_array($decoded)) {
                foreach ($decoded as $url) {
                    $attachments[] = [
                        'url' => $url,
                        'name' => basename($url),
                        'type' => 'json'
                    ];
                }
            } elseif (is_string($this->attachment) && filter_var($this->attachment, FILTER_VALIDATE_URL)) {
                $attachments[] = [
                    'url' => $this->attachment,
                    'name' => basename($this->attachment),
                    'type' => 'direct'
                ];
            }
        }
        
        // Metodo 2: Dalla cartella su S3 (fornitore_slug)
        if (empty($attachments) && !empty($this->fornitore_slug)) {
            try {
                $disk = Storage::disk('s3');
                $folderPath = 'invoice-received/' . $this->fornitore_slug;
                
                if ($disk->exists($folderPath)) {
                    $files = $disk->files($folderPath);
                    $bucket = config('filesystems.disks.s3.bucket', 'gestionale-152146163010-eu-north-1-an');
                    $region = config('filesystems.disks.s3.region', 'eu-north-1');
                    
                    foreach ($files as $file) {
                        $attachments[] = [
                            'url' => "https://{$bucket}.s3.{$region}.amazonaws.com/{$file}",
                            'name' => basename($file),
                            'path' => $file,
                            'size' => $disk->size($file),
                            'last_modified' => $disk->lastModified($file),
                            'type' => 's3'
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Errore nel recupero allegati da S3 (fornitore_slug): ' . $e->getMessage());
            }
        }
        
        // Metodo 3: Dalla cartella attachments_folder
        if (empty($attachments) && !empty($this->attachments_folder)) {
            try {
                $disk = Storage::disk('s3');
                if ($disk->exists($this->attachments_folder)) {
                    $files = $disk->files($this->attachments_folder);
                    $bucket = config('filesystems.disks.s3.bucket', 'gestionale-152146163010-eu-north-1-an');
                    $region = config('filesystems.disks.s3.region', 'eu-north-1');
                    
                    foreach ($files as $file) {
                        $attachments[] = [
                            'url' => "https://{$bucket}.s3.{$region}.amazonaws.com/{$file}",
                            'name' => basename($file),
                            'path' => $file,
                            'size' => $disk->size($file),
                            'last_modified' => $disk->lastModified($file),
                            'type' => 's3'
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Errore nel recupero allegati da S3 (attachments_folder): ' . $e->getMessage());
            }
        }
        
        return $attachments;
    }

    /**
     * Ottiene gli allegati della fattura da S3 (metodo originale per compatibilità)
     */
    public function getAttachmentsAttribute(): array
    {
        return $this->getAllAttachmentsAttribute();
    }

    /**
     * Salva un allegato per la fattura
     */
    public function saveAttachment($fileContent, $fileName)
    {
        if (empty($this->fornitore_slug)) {
            return false;
        }
        
        $path = 'invoice-received/' . $this->fornitore_slug . '/' . $fileName;
        return Storage::disk('s3')->put($path, $fileContent);
    }
}