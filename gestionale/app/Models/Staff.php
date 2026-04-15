<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'id_personale';
    
    // DISABILITA i timestamp (importante!)
    public $timestamps = false;
    
    protected $fillable = [
        'NomePers',
        'CognomePers',
        'Soprannome',
        'IndirPers',
        'CittaPers',
        'ProvPers',
        'CapPers',
        'TelPers',
        'CellPers',
        'EmailPers',
        'PasswPers',
        'StatoPers',
        'CodFiscPers',
        'DataNascPers',
        'LuogoNasc',
        'valid',
        'id_gruppo'
    ];
    
    protected $casts = [
        'valid' => 'boolean',
        'DataNascPers' => 'date'
    ];
    
    // Accessor per nome completo
    public function getFullNameAttribute()
    {
        return trim($this->NomePers . ' ' . $this->CognomePers);
    }
    
    // Scope per personale attivo
    public function scopeActive($query)
    {
        return $query->where('valid', 1);
    }
    
    // Relazione con il gruppo personale
    public function gruppo()
    {
        return $this->belongsTo(Setting::class, 'id_gruppo');
    }
}