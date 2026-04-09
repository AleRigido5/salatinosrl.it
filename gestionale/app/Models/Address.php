<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'address';
    protected $primaryKey = 'id_indirizzo';
    public $timestamps = false;
    
    protected $fillable = [
        'clienti_id_cliente',
        'sede',
        'indirizzo',
        'citta',
        'provincia',
        'nazione',
        'cap',
        'telefono',
        'cellulare',
        'fax',
    ];
    
    /**
     * Relazione inversa con Entity
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'clienti_id_cliente', 'id_cliente');
    }
}