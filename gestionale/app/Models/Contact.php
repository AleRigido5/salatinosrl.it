<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;
    
    protected $table = 'contacts';
    
    protected $fillable = [
        'id_entities',
        'id_settings',
        'valore',
        'principale',
        'note',
    ];
    
    protected $casts = [
        'principale' => 'boolean',
    ];
    
    /**
     * Relazione con l'entità
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_entities');
    }
    
    /**
     * Relazione con il setting (tipo contatto)
     */
    public function setting()
    {
        return $this->belongsTo(Setting::class, 'id_settings');
    }
    
    /**
     * Ottieni il tipo di contatto
     */
    public function getTipoAttribute()
    {
        return $this->setting ? $this->setting->valore : 'Sconosciuto';
    }

}