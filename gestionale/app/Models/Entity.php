<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'entities';
    protected $primaryKey = 'id_cliente';
    
    // IMPORTANTE: Laravel di solito assume 'id', ma noi usiamo 'id_cliente'
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'entity_type',
        'ragione_sociale',
        'nome',
        'cognome',
        'persona_riferimento',
        'email',
        'pec',
        'password',
        'stato',
        'partita_iva',
        'codice_fiscale',
        'id_gruppo',
        'valid',
        'data_inserimento',
    ];

    protected $casts = [
        'valid' => 'boolean',
        'data_inserimento' => 'datetime',
    ];

    /**
     * Relazione con i contatti
     * Foreign key: id_entities sulla tabella contacts
     * Local key: id_cliente sulla tabella entities
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'id_entities', 'id_cliente');
    }

    /**
     * Ottieni il telefono (id_settings = 1)
     */
    public function getPhoneAttribute()
    {
        $contact = $this->contacts->firstWhere('id_settings', 1);
        return $contact ? $contact->valore : null;
    }
    
    /**
     * Ottieni il cellulare (id_settings = 2)
     */
    public function getMobileAttribute()
    {
        $contact = $this->contacts->firstWhere('id_settings', 2);
        return $contact ? $contact->valore : null;
    }
    
    /**
     * Ottieni l'email (cerca in id_settings=4 o tra quelli che sembrano email)
     */
    public function getEmailContactAttribute()
    {
        // Prima cerca id_settings=4
        $contact = $this->contacts->firstWhere('id_settings', 4);
        if ($contact) {
            return $contact->valore;
        }
        
        // Poi cerca tra id_settings=3 che sembrano email
        $emailContact = $this->contacts->first(function($c) {
            return $c->id_settings == 3 && filter_var($c->valore, FILTER_VALIDATE_EMAIL);
        });
        
        if ($emailContact) {
            return $emailContact->valore;
        }
        
        // Infine usa il campo email dell'entità
        return $this->email;
    }
    
    /**
     * Nome completo
     */
    public function getFullNameAttribute()
    {
        if ($this->ragione_sociale) {
            return $this->ragione_sociale;
        }
        return trim($this->nome . ' ' . $this->cognome);
    }

    /**
     * Tipologie di entità disponibili
     */
    public static function getEntityTypes()
    {
        return [
            'cliente' => 'Cliente',
            'fornitore' => 'Fornitore',
            'entrambi' => 'Entrambi',
        ];
    }

    /**
     * Verifica se è cliente
     */
    public function isCliente()
    {
        return in_array($this->entity_type, ['cliente', 'entrambi']);
    }

    /**
     * Verifica se è fornitore
     */
    public function isFornitore()
    {
        return in_array($this->entity_type, ['fornitore', 'entrambi']);
    }
}