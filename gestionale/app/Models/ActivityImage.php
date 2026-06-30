<?php
// app/Models/ActivityImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ActivityImage extends Model
{
    use SoftDeletes;

    protected $table = 'activity_images';

    protected $fillable = [
        'activity_id',
        'path_doc',
        'file_name',
        'titolo',
        'note',
        'created_by',
        'updated_by',
        'order'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id', 'id');
    }

    /**
     * Ottiene l'URL del documento (supporta S3 e locale)
     * Usa SEMPRE un URL temporaneo firmato per S3, valido sia per la galleria che per il modal.
     */
    public function getUrlAttribute()
    {
        if (empty($this->path_doc) || empty($this->file_name)) {
            return null;
        }

        if (str_starts_with($this->path_doc, 's3://')) {
            $path = str_replace('s3://', '', $this->path_doc) . '/' . $this->file_name;

            /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
            $disk = Storage::disk('s3');

            // URL temporaneo firmato, funziona sia su bucket privati che pubblici
            return $disk->temporaryUrl($path, now()->addHours(2));
        }

        // Fallback per file locali
        return asset($this->path_doc . '/' . $this->file_name);
    }

    public function getIsOnS3Attribute()
    {
        return str_starts_with($this->path_doc, 's3://');
    }

    public function getS3PathAttribute()
    {
        if ($this->is_on_s3) {
            return str_replace('s3://', '', $this->path_doc) . '/' . $this->file_name;
        }
        return null;
    }

    public function getExtensionAttribute()
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    public function getIconAttribute()
    {
        return match($this->extension) {
            'pdf' => 'fa-file-pdf text-red-600',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'fa-file-image text-blue-600',
            default => 'fa-file text-gray-600'
        };
    }
}