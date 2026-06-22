<?php
// app/Models/CommunicationComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommunicationComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'communication_id',
        'testo',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relazioni
    public function communication()
    {
        return $this->belongsTo(Communication::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
}