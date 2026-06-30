<?php
// app/Models/Communication.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class Communication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'data',
        'testo',
        'contatto',
        'id_entities',
        'mittente',
        'allegato',
        'allegato_tipo',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'data' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relazioni
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    public function comments()
    {
        return $this->hasMany(CommunicationComment::class)->orderBy('created_at', 'asc');
    }

    // Accessor per il numero di commenti
    public function getCommentsCountAttribute()
    {
        return $this->comments()->count();
    }

    /**
     * Ottiene l'URL dell'allegato (supporta S3 e locale)
     */
    public function getAllegatoUrlAttribute()
    {
        if (empty($this->allegato)) {
            return null;
        }
        
        // Verifica se è su S3 (path inizia con 's3://')
        if (str_starts_with($this->path_doc, 's3://')) {
            $path = str_replace('s3://', '', $this->path_doc);
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            return $disk->url($path . '/' . $this->file_name);
        }
        
        // Fallback per file locali (compatibilità con vecchi allegati)
        return Storage::url($this->allegato);
    }
    
    /**
     * Verifica se l'allegato è su S3
     */
    public function getAllegatoIsOnS3Attribute()
    {
        return str_starts_with($this->allegato, 's3://');
    }
    
    /**
     * Ottiene il percorso su S3
     */
    public function getAllegatoS3PathAttribute()
    {
        if ($this->allegato_is_on_s3) {
            return str_replace('s3://', '', $this->allegato);
        }
        return null;
    }

    // Accessor per il nome dell'allegato
    public function getAllegatoNomeAttribute()
    {
        if ($this->allegato) {
            // Rimuovi il prefisso s3:// se presente
            $path = str_replace('s3://', '', $this->allegato);
            return basename($path);
        }
        return null;
    }

    // Accessor per l'icona dell'allegato
    public function getAllegatoIconAttribute()
    {
        $tipi = [
            'jpg' => 'fa-file-image',
            'jpeg' => 'fa-file-image',
            'png' => 'fa-file-image',
            'gif' => 'fa-file-image',
            'webp' => 'fa-file-image',
            'pdf' => 'fa-file-pdf',
            'doc' => 'fa-file-word',
            'docx' => 'fa-file-word',
            'xls' => 'fa-file-excel',
            'xlsx' => 'fa-file-excel',
            'eml' => 'fa-file-envelope',
        ];

        return $tipi[$this->allegato_tipo] ?? 'fa-file';
    }

    // Accessor per la classe CSS del tipo allegato
    public function getAllegatoColorAttribute()
    {
        $tipi = [
            'jpg' => 'text-red-500',
            'jpeg' => 'text-red-500',
            'png' => 'text-red-500',
            'gif' => 'text-red-500',
            'webp' => 'text-red-500',
            'pdf' => 'text-red-600',
            'doc' => 'text-blue-500',
            'docx' => 'text-blue-500',
            'xls' => 'text-green-500',
            'xlsx' => 'text-green-500',
            'eml' => 'text-yellow-500',
        ];

        return $tipi[$this->allegato_tipo] ?? 'text-gray-500';
    }
}