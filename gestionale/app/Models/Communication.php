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

    // Accessor per il link dell'allegato
    public function getAllegatoUrlAttribute()
    {
        if ($this->allegato) {
            return Storage::url($this->allegato);
        }
        return null;
    }

    // Accessor per il nome dell'allegato
    public function getAllegatoNomeAttribute()
    {
        if ($this->allegato) {
            return basename($this->allegato);
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