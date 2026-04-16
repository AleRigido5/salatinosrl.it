<?php
// app/Models/Expiration.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expiration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expiration';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_ownership',
        'id_entities',
        'id_settings',
        'id_references',
        'table_references',
        'data_inizio',
        'data_fine',
        'titolo',
        'codice',
        'subtitolo',
        'note',
        'importo',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
        'importo' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // ==================== COSTANTI PER I TIPI DI TABELLA ====================
    
    const TABLE_STAFF = 'staff';
    const TABLE_VEHICLE = 'vehicles';
    const TABLE_ENTITY = 'entities';
    const TABLE_OWNERSHIP = 'ownership';
    
    // ==================== RELAZIONI (SOLO QUELLE SEMPLICI) ====================
    
    public function setting()
    {
        return $this->belongsTo(Setting::class, 'id_settings', 'id');
    }

    // ==================== RELAZIONI PER IL TRACCIAMENTO ====================
    
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    // ==================== RELAZIONI RETROCOMPATIBILITÀ ====================
    
    /**
     * RETROCOMPATIBILITÀ: Per i record vecchi che usano ancora id_entities
     */
    public function entityLegacy()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }
    
    /**
     * RETROCOMPATIBILITÀ: Per i record vecchi che usano ancora id_ownership
     */
    public function ownershipLegacy()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    // ==================== HELPER PER OTTENERE L'ENTITÀ ASSOCIATA ====================
    
    /**
     * Ottiene l'entità associata (personale) - usa eager loading
     */
    public function getStaffAttribute()
    {
        if ($this->table_references === self::TABLE_STAFF && $this->id_references) {
            // Usa una cache statica per evitare query multiple nella stessa richiesta
            static $staffCache = [];
            if (!isset($staffCache[$this->id_references])) {
                $staffCache[$this->id_references] = Staff::find($this->id_references);
            }
            return $staffCache[$this->id_references];
        }
        return null;
    }
    
    /**
     * Ottiene l'entità associata (cliente/fornitore)
     */
    public function getEntityAttribute()
    {
        if ($this->table_references === self::TABLE_ENTITY && $this->id_references) {
            static $entityCache = [];
            if (!isset($entityCache[$this->id_references])) {
                $entityCache[$this->id_references] = Entity::find($this->id_references);
            }
            return $entityCache[$this->id_references];
        }
        return null;
    }
    
    /**
     * Ottiene l'entità associata in modo unificato
     */
    public function getLinkedEntityAttribute()
    {
        // Nuovo sistema polimorfico
        if ($this->table_references && $this->id_references) {
            if ($this->table_references === self::TABLE_STAFF) {
                return $this->staff;
            }
            if ($this->table_references === self::TABLE_ENTITY) {
                return $this->entity;
            }
            if ($this->table_references === self::TABLE_OWNERSHIP) {
                return Ownership::find($this->id_references);
            }
        }
        
        // Retrocompatibilità: vecchio sistema
        if ($this->id_entities) {
            return $this->entityLegacy;
        }
        
        if ($this->id_ownership) {
            return $this->ownershipLegacy;
        }
        
        return null;
    }
    
    public function getLinkedEntityNameAttribute()
    {
        $entity = $this->getLinkedEntityAttribute();
        
        if (!$entity) {
            return '-';
        }
        
        // Staff
        if (isset($entity->full_name)) {
            return $entity->full_name;
        }
        
        // Entity (cliente/fornitore)
        if (isset($entity->ragione_sociale)) {
            return $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
        }
        
        // Ownership
        if (isset($entity->RagSocialePr)) {
            return $entity->RagSocialePr;
        }
        
        return '-';
    }
    
    public function getLinkedEntityTypeAttribute()
    {
        if ($this->table_references === self::TABLE_STAFF) {
            return 'Personale';
        }
        
        if ($this->table_references === self::TABLE_ENTITY) {
            $entity = $this->entity;
            if ($entity && isset($entity->entity_type)) {
                return $entity->entity_type === 'fornitore' ? 'Fornitore' : 'Cliente';
            }
            return 'Cliente/Fornitore';
        }
        
        if ($this->table_references === self::TABLE_OWNERSHIP) {
            return 'Proprietà';
        }
        
        if ($this->table_references === self::TABLE_VEHICLE) {
            return 'Mezzo';
        }
        
        // Retrocompatibilità
        if ($this->id_entities && $this->entityLegacy) {
            $entity = $this->entityLegacy;
            return $entity->entity_type === 'fornitore' ? 'Fornitore' : 'Cliente';
        }
        
        if ($this->id_ownership) {
            return 'Proprietà';
        }
        
        return 'N/D';
    }

    // ==================== SCOPE ====================
    
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('data_fine', '<=', now()->addDays($days))
                    ->where('data_fine', '>=', now())
                    ->whereNull('deleted_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('data_fine', '<', now())
                    ->whereNull('deleted_at');
    }

    public function scopeBySetting($query, $settingId)
    {
        return $query->where('id_settings', $settingId);
    }
    
    /**
     * Scope per filtrare per staff specifico
     */
    public function scopeForStaff($query, $staffId)
    {
        return $query->where(function($q) use ($staffId) {
            $q->where('table_references', self::TABLE_STAFF)
              ->where('id_references', $staffId)
              // Retrocompatibilità: id_entities che punta a staff
              ->orWhere(function($q2) use ($staffId) {
                  $q2->whereNull('table_references')
                     ->where('id_entities', $staffId);
              });
        });
    }

    // ==================== ACCESSOR ====================
    
    public function getTipologiaNameAttribute()
    {
        return $this->setting ? $this->setting->valore : 'N/D';
    }
    
    public function getStatusLabelAttribute()
    {
        if ($this->trashed()) {
            return 'Disattivo';
        }
        
        if ($this->data_fine && $this->data_fine < now()) {
            return 'Scaduto';
        }
        
        if ($this->data_fine && $this->data_fine <= now()->addDays(30)) {
            return 'In scadenza';
        }
        
        return 'Attivo';
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->trashed()) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Disattivo</span>';
        }
        
        if ($this->data_fine && $this->data_fine < now()) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Scaduto</span>';
        }
        
        if ($this->data_fine && $this->data_fine <= now()->addDays(30)) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">In scadenza</span>';
        }
        
        return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Attivo</span>';
    }

    public function getImportoFormattedAttribute()
    {
        if (!$this->importo) {
            return '-';
        }
        return '€ ' . number_format($this->importo, 2, ',', '.');
    }
}