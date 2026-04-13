<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// RIMUOVI: use Illuminate\Database\Eloquent\SoftDeletes;

class UnitaMisura extends Model
{
    use HasFactory;
    // RIMUOVI: use SoftDeletes;

    protected $table = 'unita_misura';
    protected $primaryKey = 'id_um';
    
    // IMPORTANTE: Disabilita i timestamp se la tabella non ha created_at/updated_at
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'codice',      
        'valid',
        'categoria',
        'ordinamento'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'ordinamento' => 'integer',
    ];

    // Relazione con i servizi
    public function services()
    {
        return $this->hasMany(Service::class, 'UnitaMisura_id_unita', 'id_um');
    }

    // Scope per unità attive
    public function scopeActive($query)
    {
        return $query->where('valid', 1);
    }

    // Accessor per il nome completo (nome + codice)
    public function getNomeCompletoAttribute()
    {
        if ($this->codice) {
            return $this->nome . ' (' . $this->codice . ')';
        }
        return $this->nome;
    }

    // Accessor per lo stato formattato
    public function getStatusLabelAttribute()
    {
        return $this->valid ? 'Attiva' : 'Disattiva';
    }

    // Accessor per lo stato badge
    public function getStatusBadgeAttribute()
    {
        return $this->valid 
            ? '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Attiva</span>'
            : '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Disattiva</span>';
    }
}