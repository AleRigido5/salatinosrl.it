<?php
// app/Models/Document.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    // Helper per ottenere l'URL del file
    public function getUrlAttribute()
    {
        return asset($this->path_doc . '/' . $this->file_name);
    }

    // Helper per estensione
    public function getExtensionAttribute()
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    // Helper per icona
    public function getIconAttribute()
    {
        return match($this->extension) {
            'pdf' => 'fa-file-pdf text-red-600',
            'jpg', 'jpeg' => 'fa-file-image text-blue-600',
            default => 'fa-file text-gray-600'
        };
    }
}