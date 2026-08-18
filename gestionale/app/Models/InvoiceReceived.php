<?php
// app/Models/InvoiceReceived.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'closes_invoice_id',
        'n_invoice',
        'data_invoice',
        'importo_totale',
        'allocated_amount', // Aggiunto per tracciare l'allocazione delle NC
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
        'allocated_amount' => 'decimal:2', // Cast per il campo allocato
        'imported_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $attributes = [
        'divisa' => 'EUR',
        'status' => 'bozza',
        'allocated_amount' => 0.00 // Valore predefinito
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

    public function isCreditNote(): bool
    {
        return $this->type_invoice === self::TYPE_TD04;
    }

    // ==================== RELAZIONI PER NOTE DI CREDITO MULTIPLE ====================

    /**
     * Relazione inversa: fatture chiuse da questa nota di credito (via tabella ponte)
     */
    public function closedInvoices(): HasMany
    {
        return $this->hasMany(CreditNoteInvoiceRelation::class, 'credit_note_id');
    }

    /**
     * Relazione: note di credito che chiudono questa fattura (via tabella ponte)
     */
    public function closingCreditNotes(): HasMany
    {
        return $this->hasMany(CreditNoteInvoiceRelation::class, 'invoice_id');
    }

    // ==================== METODI PER COMPATIBILITÀ CON VECCHIA STRUTTURA ====================

    /**
     * @deprecated Usa closingCreditNotes() per supporto multiple NC
     * Mantenuto per compatibilità con codice esistente
     */
    public function closedInvoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceReceived::class, 'closes_invoice_id');
    }

    /**
     * @deprecated Usa closedInvoices() per supporto multiple NC
     * Mantenuto per compatibilità con codice esistente
     */
    public function closingCreditNote(): HasOne
    {
        return $this->hasOne(InvoiceReceived::class, 'closes_invoice_id');
    }

    // ==================== METODI PER LA GESTIONE DELLE NC ====================

    /**
     * Aggiorna il totale allocato della fattura
     * Questo metodo somma tutte le allocazioni dalla tabella ponte
     */
    public function updateAllocatedAmount(): void
    {
        $total = CreditNoteInvoiceRelation::where('invoice_id', $this->id)->sum('allocated_amount');
        $this->allocated_amount = round($total, 2);
        $this->save();
    }

    /**
     * Ottiene il totale già allocato di questa NC (per le NC)
     */
    public function getAllocatedAmountAttribute($value): float
    {
        // Se è una nota di credito, calcola dinamicamente l'allocazione
        if ($this->isCreditNote()) {
            return (float) $this->closedInvoices()->sum('allocated_amount');
        }
        
        // Per le fatture normali, usa il campo memorizzato
        return (float) $value;
    }

    /**
     * Ottiene il residuo disponibile (per NC) o il residuo da pagare (per fatture)
     */
    public function getRemainingAmountAttribute(): float
    {
        if ($this->isCreditNote()) {
            // Per le NC: importo residuo disponibile per chiudere altre fatture
            return max(0, (float) $this->importo_totale - $this->allocated_amount);
        } else {
            // Per le fatture: importo residuo da pagare (considerando cash + NC)
            $totalAllocated = CreditNoteInvoiceRelation::where('invoice_id', $this->id)->sum('allocated_amount');
            $cashPaid = $this->payments()->sum('paid_amount');
            return round(max(0, (float) $this->importo_totale - $cashPaid - $totalAllocated), 2);
        }
    }

    /**
     * Verifica se la NC è completamente utilizzata
     */
    public function isFullyUsed(): bool
    {
        if (!$this->isCreditNote()) {
            return false;
        }
        return $this->remaining_amount <= 0.01;
    }

    /**
     * Verifica se questa fattura è completamente pagata (cash + NC)
     */
    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0.01;
    }

    /**
     * Verifica se questa fattura è chiusa da una o più note di credito
     */
    public function isClosedByCreditNote(): bool
    {
        // Controlla prima la nuova struttura (tabella ponte)
        if ($this->closingCreditNotes()->exists()) {
            return true;
        }
        
        // Compatibilità con vecchia struttura
        if ($this->closingCreditNote()->exists()) {
            return true;
        }
        
        return false;
    }

    /**
     * Ottiene la prima NC che chiude questa fattura (per compatibilità)
     */
    public function getClosingCreditNoteAttribute()
    {
        // Prima controlla la nuova struttura
        $relation = $this->closingCreditNotes()->first();
        if ($relation) {
            return $relation->creditNote;
        }
        
        // Compatibilità con vecchia struttura
        return $this->closingCreditNote()->first();
    }

    /**
     * Ottiene tutte le NC che chiudono questa fattura
     */
    public function getClosingCreditNotesListAttribute()
    {
        return $this->closingCreditNotes()->with('creditNote')->get();
    }

    /**
     * Ottiene l'elenco dei numeri delle fatture chiuse da questa NC
     */
    public function getClosedInvoicesNumbersAttribute(): string
    {
        $numbers = $this->closedInvoices()
            ->with('invoice')
            ->get()
            ->pluck('invoice.n_invoice')
            ->filter()
            ->toArray();
            
        return implode(', ', $numbers);
    }

    /**
     * Ottiene il totale delle fatture chiuse da questa NC
     */
    public function getClosedInvoicesTotalAttribute(): float
    {
        return (float) $this->closedInvoices()->sum('allocated_amount');
    }

    /**
     * Ottiene il numero di fatture chiuse da questa NC
     */
    public function getClosedInvoicesCountAttribute(): int
    {
        return $this->closedInvoices()->count();
    }

    // ==================== METODI PER IL CALCOLO DEL RESIDUO ====================

    /**
     * Calcola il residuo effettivo della fattura considerando:
     * - Pagamenti cash
     * - Note di credito allocate
     */
    public function calculateEffectiveResidual(): float
    {
        // Somma dei pagamenti cash
        $cashPaid = $this->payments()->sum('paid_amount');
        
        // Somma delle note di credito allocate
        $creditAllocated = CreditNoteInvoiceRelation::where('invoice_id', $this->id)
            ->sum('allocated_amount');
        
        // Residuo effettivo
        $residual = (float) $this->importo_totale - $cashPaid - $creditAllocated;
        
        return round(max(0, $residual), 2);
    }

    /**
     * Aggiorna tutte le scadenze della fattura con il residuo effettivo
     */
    public function updatePaymentsResidual(): void
    {
        $payments = $this->payments;
        $totalAllocated = CreditNoteInvoiceRelation::where('invoice_id', $this->id)->sum('allocated_amount');
        
        foreach ($payments as $payment) {
            $cashPaid = $payment->paid_amount;
            $residual = (float) $this->importo_totale - $cashPaid - $totalAllocated;
            $residual = round(max(0, $residual), 2);
            
            $payment->skipAutoStatus = true;
            $payment->residual_amount = $residual;
            
            if ($residual <= 0.01) {
                $payment->status = 'closed_credit_note';
                $payment->paid_at = now();
            } else {
                if ($cashPaid > 0) {
                    $payment->status = 'partially_paid';
                } else {
                    $payment->status = 'issued';
                }
                $payment->paid_at = null;
            }
            
            $payment->save();
        }
    }

    // ==================== METODI ESISTENTI (NON MODIFICATI) ====================

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