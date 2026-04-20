<?php

namespace App\Models;

use App\Models\Address;
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

    // IMPORTANTE: ATTIVA I TIMESTAMP
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

    // ==================== RELAZIONI PER IL TRACCIAMENTO ====================
    
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
     * Verifica se l'entità può essere eliminata (non ha relazioni)
     * Per ora permette sempre l'eliminazione - TODO: aggiungere controlli sulle relazioni esistenti
     */
    public function canBeDeleted()
    {
        // TODO: Aggiungere controlli per verificare se esistono fatture, ordini, ecc. collegati
        // Per ora restituisce sempre true per permettere l'eliminazione
        // Quando avrai le tabelle di fatture, ordini, ecc., aggiungi i controlli qui
        
        return true;
    }

    // ==================== ACCESSOR PER IL TRACCIAMENTO ====================
    
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
    
    /**
     * Ottiene l'HTML del tracciamento
     */
    public function getTrackingHtmlAttribute()
    {
        $html = '<div class="text-xs text-gray-500 space-y-1">';
        
        if ($this->created_at && $this->createdBy) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Inserito:</span>';
            $html .= '<span class="text-gray-700">' . e($this->createdBy->name) . '</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->created_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '</div>';
        } elseif ($this->created_at) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Inserito:</span>';
            $html .= '<span class="text-gray-700">Sistema</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->created_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '</div>';
        }
        
        if ($this->updated_at && $this->updatedBy && $this->created_at != $this->updated_at) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Modificato:</span>';
            $html .= '<span class="text-gray-700">' . e($this->updatedBy->name) . '</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->updated_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '<span class="text-gray-400">(' . $this->updated_at->diffForHumans() . ')</span>';
            $html .= '</div>';
        } elseif ($this->updated_at && $this->created_at != $this->updated_at) {
            $html .= '<div class="flex items-center gap-1.5">';
            $html .= '<svg class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
            $html .= '<span class="font-medium text-gray-600">Modificato:</span>';
            $html .= '<span class="text-gray-700">Sistema</span>';
            $html .= '<span class="text-gray-400">•</span>';
            $html .= '<span class="text-gray-500">' . $this->updated_at->format('d/m/Y H:i:s') . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    // ==================== ALTRI ACCESSOR ====================
    
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