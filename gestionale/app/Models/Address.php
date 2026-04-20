<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $table = 'address';
    protected $primaryKey = 'id_indirizzo';
    public $incrementing = true;
    protected $keyType = 'int';
    
    // Disabilita i timestamps se non esistono nella tabella
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
        'fax'
    ];

    /**
     * Relazione con l'entità (cliente/fornitore)
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'clienti_id_cliente', 'id_cliente');
    }
}