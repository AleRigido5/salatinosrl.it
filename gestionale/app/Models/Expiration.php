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
    
    // ==================== RELAZIONI ====================
    
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

    // ==================== RELAZIONI PER LE ENTITÀ ASSOCIATE ====================
    
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'id_references', 'id_personale')
            ->where('expiration.table_references', self::TABLE_STAFF);
    }
    
    public function vehicles()
    {
        return $this->belongsToMany(
            Vehicles::class,
            'vehicles_expiry_lnk',
            'id_expiration',
            'id_vehicles'
        );
    }
    
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_references', 'id_cliente')
            ->where('expiration.table_references', self::TABLE_ENTITY);
    }
    
    public function entityLegacy()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }
    
    public function ownershipLegacy()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    // ==================== DOCUMENTI ====================
    
    /**
     * Relazione con i documenti per STAFF
     */
    public function staffDocuments()
    {
        return $this->hasMany(Document::class, 'id_ref', 'id')
            ->where('table_ref', 'expiration-staff');
    }
    
    /**
     * Relazione con i documenti per VEHICLES
     */
    public function vehicleDocuments()
    {
        return $this->hasMany(Document::class, 'id_ref', 'id')
            ->where('table_ref', 'expiration-vehicles');
    }
    
    /**
     * Conta totale documenti (entrambi i tipi)
     */
    public function getDocumentsCountAttribute()
    {
        return $this->staffDocuments()->count() + $this->vehicleDocuments()->count();
    }

    // ==================== HELPER PER OTTENERE L'ENTITÀ ASSOCIATA ====================
    
    public function getLinkedEntityAttribute()
    {
        if ($this->table_references === self::TABLE_STAFF && $this->id_references) {
            return Staff::find($this->id_references);
        }
        
        if ($this->table_references === self::TABLE_VEHICLE) {
            return $this->vehicles;
        }
        
        if ($this->table_references === self::TABLE_ENTITY && $this->id_references) {
            return Entity::find($this->id_references);
        }
        
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
        if ($this->table_references === self::TABLE_VEHICLE) {
            $vehicles = $this->vehicles;
            if ($vehicles && $vehicles->count() > 0) {
                $names = [];
                foreach ($vehicles as $vehicle) {
                    $names[] = $vehicle->targa ?: ($vehicle->marca . ' ' . $vehicle->modello);
                }
                return implode(', ', $names);
            }
            return '-';
        }
        
        $entity = $this->getLinkedEntityAttribute();
        
        if (!$entity) {
            return '-';
        }
        
        if (isset($entity->full_name)) {
            return $entity->full_name;
        }
        
        if (isset($entity->ragione_sociale)) {
            return $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
        }
        
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
        
        if ($this->table_references === self::TABLE_VEHICLE) {
            return 'Mezzo';
        }
        
        if ($this->table_references === self::TABLE_ENTITY) {
            $entity = $this->getLinkedEntityAttribute();
            if ($entity && isset($entity->entity_type)) {
                return $entity->entity_type === 'fornitore' ? 'Fornitore' : 'Cliente';
            }
            return 'Cliente/Fornitore';
        }
        
        if ($this->table_references === self::TABLE_OWNERSHIP) {
            return 'Proprietà';
        }
        
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
    
    public function scopeForStaff($query, $staffId)
    {
        return $query->where(function($q) use ($staffId) {
            $q->where('table_references', self::TABLE_STAFF)
              ->where('id_references', $staffId)
              ->orWhere(function($q2) use ($staffId) {
                  $q2->whereNull('table_references')
                     ->where('id_entities', $staffId);
              });
        });
    }
    
    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where(function($q) use ($vehicleId) {
            $q->where('table_references', self::TABLE_VEHICLE)
              ->whereHas('vehicles', function($q2) use ($vehicleId) {
                  $q2->where('vehicles.id', $vehicleId);
              })
              ->orWhere(function($q3) use ($vehicleId) {
                  $q3->whereNull('table_references')
                     ->where('id_entities', $vehicleId);
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