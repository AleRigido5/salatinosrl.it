<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ownership extends Model
{
    use HasFactory;

    protected $table = 'ownership';
    protected $primaryKey = 'id_proprieta';
    public $timestamps = false;

    protected $fillable = [
        'RagSocialePr',
        'IndirizzoPr',
        'LocalitPr',
        'ProvinciaPr',
        'CapPr',
        'TelefonoPr',
        'CellularePr',
        'FaxPr',
        'WebPr',
        'EmailPr',
        'PivaPr',
        'NomeRapprPr',
        'IndirRapprPr',
        'LocalitaRapprPr',
        'ProvinciaRapprPr',
        'CapRapprPr',
        'CodFiscPr',
        'DataNascPr',
        'LocalitaNascPr',
        'DataIns',
        'RagAbbrev',
        'Rag_Soc_intest'
    ];

    protected $casts = [
        'DataNascPr' => 'date',
        'DataIns' => 'datetime'
    ];

    // Relazione con le scadenze del personale
    public function staffExpiries()
    {
        return $this->hasMany(StaffExpiry::class, 'id_proprieta', 'id_proprieta');
    }

    // Accessor per il nome abbreviato
    public function getAbbrevNameAttribute()
    {
        return $this->RagAbbrev ?: $this->RagSocialePr;
    }
}