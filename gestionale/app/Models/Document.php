<?php
// app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use SoftDeletes;

    protected $table = 'documents';
    
    protected $fillable = [
        'titolo',
        'note',
        'path_doc',
        'file_name',
        'table_ref',
        'id_ref'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Ottiene l'URL del documento (supporta S3 e locale)
     */
    public function getUrlAttribute()
    {
        if (empty($this->path_doc) || empty($this->file_name)) {
            return null;
        }
        
        // Verifica se è un percorso S3
        if (str_starts_with($this->path_doc, 's3://')) {
            $path = str_replace('s3://', '', $this->path_doc);
            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');
            return $disk->url($path . '/' . $this->file_name);
        }
        
        // Fallback per file locali
        return asset($this->path_doc . '/' . $this->file_name);
    }
    
    /**
     * Verifica se il documento è su S3
     */
    public function getIsOnS3Attribute()
    {
        return str_starts_with($this->path_doc, 's3://');
    }
    
    /**
     * Ottiene il percorso su S3
     */
    public function getS3PathAttribute()
    {
        if ($this->is_on_s3) {
            return str_replace('s3://', '', $this->path_doc) . '/' . $this->file_name;
        }
        return null;
    }

    /**
     * Ottiene l'estensione del file
     */
    public function getExtensionAttribute()
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Ottiene l'icona per il tipo di file
     */
    public function getIconAttribute()
    {
        return match($this->extension) {
            'pdf' => 'fa-file-pdf text-red-600',
            'jpg', 'jpeg' => 'fa-file-image text-blue-600',
            'png' => 'fa-file-image text-green-600',
            default => 'fa-file text-gray-600'
        };
    }
}