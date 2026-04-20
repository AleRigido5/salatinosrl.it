<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ownership extends Model
{
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

    // Accessor per il nome visualizzabile nel select
    public function getNomeAttribute()
    {
        // Priorità: RagAbbrev > Rag_Soc_intest > RagSocialePr
        if (!empty($this->RagAbbrev)) {
            return $this->RagAbbrev;
        }
        if (!empty($this->Rag_Soc_intest)) {
            return $this->Rag_Soc_intest;
        }
        if (!empty($this->RagSocialePr)) {
            $nome = $this->RagSocialePr;
            if (strlen($nome) > 50) {
                $nome = substr($nome, 0, 47) . '...';
            }
            return $nome;
        }
        return 'Proprietà ' . $this->id_proprieta;
    }
    
    // Accessor per il nome abbreviato
    public function getAbbrevNameAttribute()
    {
        return $this->RagAbbrev ?: ($this->Rag_Soc_intest ?: $this->RagSocialePr);
    }

    // Relazione con i vehicles
    public function vehicles()
    {
        return $this->hasMany(Vehicles::class, 'id_ownership', 'id_proprieta');
    }
}