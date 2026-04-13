<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';
    protected $primaryKey = 'id';

    // DISABILITA i timestamp (created_at, updated_at)
    public $timestamps = false;

    protected $fillable = [
        'id_categories',
        'Titolo',
        'Descrizione',
        'Descr_fattura',
        'Stato',
        'Prezzo_un',
        'UnitaMisura_id_unita'
    ];

    protected $casts = [
        'Stato' => 'boolean',
        'Prezzo_un' => 'decimal:3',
    ];

    // Relazione con la categoria (tabella settings)
    public function category()
    {
        return $this->belongsTo(Setting::class, 'id_categories', 'id')
            ->where('tabella_riferimento', 'services');
    }

    // Relazione con l'unità di misura
    public function unitaMisura()
    {
        return $this->belongsTo(UnitaMisura::class, 'UnitaMisura_id_unita', 'id_um');
    }

    // Scope per servizi attivi
    public function scopeActive($query)
    {
        return $query->where('Stato', 1);
    }

    // Accessor per il nome della categoria
    public function getCategoryNameAttribute()
    {
        return $this->category ? $this->category->valore : 'Senza categoria';
    }

    // Accessor per lo stato formattato
    public function getStatusLabelAttribute()
    {
        return $this->Stato ? 'Attivo' : 'Disattivo';
    }

    // Accessor per lo stato badge
    public function getStatusBadgeAttribute()
    {
        return $this->Stato 
            ? '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Attivo</span>'
            : '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Disattivo</span>';
    }
}