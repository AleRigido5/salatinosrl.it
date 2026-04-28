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
    
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = true;

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
        'codice_sdi',
        'id_gruppo',
        'valid',
        'data_inserimento',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'data_inserimento' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELAZIONI ====================
    
    /**
     * Relazione con l'amministratore che ha creato il record
     */
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    /**
     * Relazione con l'amministratore che ha modificato il record
     */
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    /**
     * Relazione con i contatti
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'id_entities', 'id_cliente');
    }

    /**
     * Relazione con gli indirizzi
     */
    public function addresses()
    {
        return $this->hasMany(Address::class, 'clienti_id_cliente', 'id_cliente');
    }

    /**
     * Relazione con le attività
     */
    public function activities()
    {
        return $this->hasMany(Activity::class, 'id_entities', 'id_cliente');
    }

    /**
     * Relazione con i centri di costo
     */
    public function costCenters()
    {
        return $this->hasMany(CostCenter::class, 'id_references', 'id_cliente')
            ->where('table_references', 'entities');
    }

    // ==================== ACCESSOR ====================
    
    /**
     * Nome completo dell'entità
     */
    public function getFullNameAttribute()
    {
        if ($this->ragione_sociale) {
            return $this->ragione_sociale;
        }
        return trim($this->nome . ' ' . $this->cognome);
    }

    /**
     * Ottieni l'indirizzo principale
     */
    public function getPrimaryAddressAttribute()
    {
        if ($this->addresses && $this->addresses->isNotEmpty()) {
            return $this->addresses->firstWhere('sede', 'principale') ?? $this->addresses->first();
        }
        return null;
    }

    /**
     * Ottieni la città dell'indirizzo principale
     */
    public function getCityAttribute()
    {
        $address = $this->primary_address;
        return $address ? $address->citta : null;
    }

    /**
     * Ottieni la provincia dell'indirizzo principale
     */
    public function getProvinceAttribute()
    {
        $address = $this->primary_address;
        return $address ? $address->provincia : null;
    }

    /**
     * Ottieni l'indirizzo completo
     */
    public function getFullAddressAttribute()
    {
        $address = $this->primary_address;
        if (!$address) return null;
        
        $parts = [];
        if ($address->indirizzo) $parts[] = $address->indirizzo;
        if ($address->citta) $parts[] = $address->citta;
        if ($address->provincia) $parts[] = $address->provincia;
        if ($address->cap) $parts[] = $address->cap;
        
        return implode(', ', $parts);
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
     * Ottieni l'email dal contatto o dal campo email
     */
    public function getEmailContactAttribute()
    {
        $contact = $this->contacts->firstWhere('id_settings', 4);
        if ($contact) {
            return $contact->valore;
        }
        
        $emailContact = $this->contacts->first(function($c) {
            return $c->id_settings == 3 && filter_var($c->valore, FILTER_VALIDATE_EMAIL);
        });
        
        if ($emailContact) {
            return $emailContact->valore;
        }
        
        return $this->email;
    }

    // ==================== METODI DI UTILITÀ ====================
    
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

    /**
     * Verifica se l'entità può essere eliminata
     */
    public function canBeDeleted()
    {
        // Controlla se ha attività collegate
        if ($this->activities()->exists()) {
            return false;
        }
        
        // Controlla se ha centri di costo collegati
        if ($this->costCenters()->exists()) {
            return false;
        }
        
        return true;
    }

    // ==================== TRACCIAMENTO ====================
    
    /**
     * Ottiene le informazioni di tracciamento come array
     */
    public function getTrackingInfoAttribute()
    {
        $info = [];
        
        if ($this->created_at && $this->createdBy) {
            $info['created'] = [
                'by' => $this->createdBy->name,
                'by_id' => $this->createdBy->id,
                'by_email' => $this->createdBy->email,
                'at' => $this->created_at->format('d/m/Y H:i:s'),
                'full' => "{$this->createdBy->name} - {$this->created_at->format('d/m/Y H:i:s')}"
            ];
        } elseif ($this->created_at) {
            $info['created'] = [
                'by' => 'Sistema',
                'by_id' => null,
                'by_email' => null,
                'at' => $this->created_at->format('d/m/Y H:i:s'),
                'full' => "Sistema - {$this->created_at->format('d/m/Y H:i:s')}"
            ];
        }
        
        if ($this->updated_at && $this->updatedBy && $this->created_at != $this->updated_at) {
            $info['updated'] = [
                'by' => $this->updatedBy->name,
                'by_id' => $this->updatedBy->id,
                'by_email' => $this->updatedBy->email,
                'at' => $this->updated_at->format('d/m/Y H:i:s'),
                'full' => "{$this->updatedBy->name} - {$this->updated_at->format('d/m/Y H:i:s')}",
                'relative' => $this->updated_at->diffForHumans()
            ];
        } elseif ($this->updated_at && $this->created_at != $this->updated_at) {
            $info['updated'] = [
                'by' => 'Sistema',
                'by_id' => null,
                'by_email' => null,
                'at' => $this->updated_at->format('d/m/Y H:i:s'),
                'full' => "Sistema - {$this->updated_at->format('d/m/Y H:i:s')}",
                'relative' => $this->updated_at->diffForHumans()
            ];
        }
        
        return $info;
    }
}