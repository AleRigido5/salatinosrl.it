<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expiration extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'expiration';
    protected $primaryKey = 'id';
    protected $dates = ['deleted_at'];

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
    
    // ==================== TIPOLOGIE SCADENZE STAFF ====================
    
    const TYPE_ASSUNZIONE = 'Assunzione';
    const TYPE_VISITA_MEDICA = 'Visita medica';
    const TYPE_CONTRATTO = 'Contratto';
    const TYPE_CORSO_FORMATIVO = 'Corso formativo';
    const TYPE_PATENTE = 'Patente';
    const TYPE_ALTRO = 'Altro';
    
    // ==================== RELAZIONI ====================
    
    /**
     * Relazione con il setting (tipologia scadenza)
     */
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
    
    /**
     * Relazione con Staff (personale)
     * Usata quando table_references = 'staff'
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'id_references', 'id_personale')
            ->where('table_references', self::TABLE_STAFF);
    }
    
    /**
     * Relazione con Vehicles (mezzi)
     * Usata quando table_references = 'vehicles'
     */
    public function vehicles()
    {
        return $this->belongsToMany(
            Vehicles::class,
            'vehicles_expiry_lnk',
            'id_expiration',
            'id_vehicles'
        );
    }
    
    /**
     * Relazione con Entity (clienti/fornitori)
     * Usata quando table_references = 'entities'
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_references', 'id_cliente');
    }
    
    /**
     * Relazione legacy con Entity tramite id_entities
     */
    public function entityLegacy()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }
    
    /**
     * Relazione legacy con Ownership tramite id_ownership
     */
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
    
    /**
     * Ottiene l'entità associata alla scadenza (Staff, Veicolo, Entity, Ownership)
     */
    public function getLinkedEntityAttribute()
    {
        // Controllo STAFF
        if ($this->table_references === self::TABLE_STAFF && $this->id_references) {
            return Staff::find($this->id_references);
        }
        
        // Controllo VEHICLE
        if ($this->table_references === self::TABLE_VEHICLE) {
            return $this->vehicles;
        }
        
        // Controllo ENTITY
        if ($this->table_references === self::TABLE_ENTITY && $this->id_references) {
            return Entity::find($this->id_references);
        }
        
        // Controllo ENTITY legacy
        if ($this->id_entities) {
            return $this->entityLegacy;
        }
        
        // Controllo OWNERSHIP legacy
        if ($this->id_ownership) {
            return $this->ownershipLegacy;
        }
        
        return null;
    }
    
    /**
     * Ottiene il nome dell'entità associata
     */
    public function getLinkedEntityNameAttribute()
    {
        // Controllo STAFF
        if ($this->table_references === self::TABLE_STAFF && $this->id_references) {
            $staff = Staff::find($this->id_references);
            if ($staff) {
                return $staff->full_name;
            }
            return '-';
        }
        
        // Controllo VEHICLE
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
        
        // Controllo ENTITY o altri
        $entity = $this->getLinkedEntityAttribute();
        
        if (!$entity) {
            return '-';
        }
        
        // Per Staff
        if (isset($entity->full_name)) {
            return $entity->full_name;
        }
        
        // Per Entity
        if (isset($entity->ragione_sociale)) {
            return $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome);
        }
        
        // Per Ownership
        if (isset($entity->RagSocialePr)) {
            return $entity->RagSocialePr;
        }
        
        // Per Vehicles
        if (isset($entity->targa)) {
            return $entity->targa ?: ($entity->marca . ' ' . $entity->modello);
        }
        
        return '-';
    }
    
    /**
     * Ottiene il tipo dell'entità associata
     */
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

    /**
     * Ottiene il nome dello staff associato (se è una scadenza staff)
     */
    public function getStaffNameAttribute()
    {
        if ($this->table_references === self::TABLE_STAFF && $this->id_references) {
            $staff = Staff::find($this->id_references);
            if ($staff) {
                return $staff->full_name;
            }
        }
        return null;
    }

    /**
     * Ottiene l'ID dello staff associato (se è una scadenza staff)
     */
    public function getStaffIdAttribute()
    {
        if ($this->table_references === self::TABLE_STAFF && $this->id_references) {
            return $this->id_references;
        }
        return null;
    }

    // ==================== HELPER PER SCADENZE STAFF ====================
    
    /**
     * Ottiene l'ultima scadenza di Assunzione per uno staff
     */
    public static function getLastAssunzioneForStaff($staffId)
    {
        return self::where('table_references', self::TABLE_STAFF)
            ->where('id_references', $staffId)
            ->where('titolo', self::TYPE_ASSUNZIONE)
            ->orderBy('data_fine', 'desc')
            ->first();
    }
    
    /**
     * Ottiene l'ultima scadenza di Visita Medica per uno staff
     */
    public static function getLastVisitaMedicaForStaff($staffId)
    {
        return self::where('table_references', self::TABLE_STAFF)
            ->where('id_references', $staffId)
            ->where('titolo', self::TYPE_VISITA_MEDICA)
            ->orderBy('data_fine', 'desc')
            ->first();
    }
    
    /**
     * Ottiene tutte le scadenze di un tipo specifico per uno staff
     */
    public static function getExpirationsForStaffByType($staffId, $titolo)
    {
        return self::where('table_references', self::TABLE_STAFF)
            ->where('id_references', $staffId)
            ->where('titolo', $titolo)
            ->orderBy('data_fine', 'desc')
            ->get();
    }
    
    /**
     * Verifica se esiste una scadenza di Assunzione valida (non scaduta)
     */
    public static function hasValidAssunzioneForStaff($staffId)
    {
        $assunzione = self::getLastAssunzioneForStaff($staffId);
        return $assunzione && $assunzione->data_fine && $assunzione->data_fine >= now();
    }
    
    /**
     * Verifica se esiste una scadenza di Visita Medica valida (non scaduta)
     */
    public static function hasValidVisitaMedicaForStaff($staffId)
    {
        $visita = self::getLastVisitaMedicaForStaff($staffId);
        return $visita && $visita->data_fine && $visita->data_fine >= now();
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
              ->where('id_references', $staffId);
        });
    }
    
    public function scopeForStaffByType($query, $staffId, $titolo)
    {
        return $query->forStaff($staffId)
            ->where('titolo', $titolo);
    }
    
    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where(function($q) use ($vehicleId) {
            $q->where('table_references', self::TABLE_VEHICLE)
              ->whereHas('vehicles', function($q2) use ($vehicleId) {
                  $q2->where('vehicles.id', $vehicleId);
              });
        });
    }
    
    public function scopeStaffExpirations($query)
    {
        return $query->where('table_references', self::TABLE_STAFF);
    }
    
    public function scopeAssunzione($query)
    {
        return $query->where('titolo', self::TYPE_ASSUNZIONE);
    }
    
    public function scopeVisitaMedica($query)
    {
        return $query->where('titolo', self::TYPE_VISITA_MEDICA);
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
        
        if (!$this->data_fine) {
            return 'Nessuna scadenza';
        }
        
        if ($this->data_fine < now()) {
            return 'Scaduto';
        }
        
        if ($this->data_fine <= now()->addDays(30)) {
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
        
        if (!$this->data_fine) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nessuna scadenza</span>';
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
    
    /**
     * Restituisce i giorni mancanti alla scadenza
     */
    public function getDaysLeftAttribute()
    {
        if (!$this->data_fine) {
            return null;
        }
        
        $now = now();
        $end = \Carbon\Carbon::parse($this->data_fine);
        
        if ($end < $now) {
            return -$now->diffInDays($end);
        }
        
        return $now->diffInDays($end);
    }
    
    /**
     * Restituisce se la scadenza è scaduta
     */
    public function getIsExpiredAttribute()
    {
        return $this->data_fine && $this->data_fine < now();
    }
    
    /**
     * Restituisce se la scadenza è in scadenza (entro 30 giorni)
     */
    public function getIsExpiringSoonAttribute()
    {
        return $this->data_fine && 
               $this->data_fine >= now() && 
               $this->data_fine <= now()->addDays(30);
    }
    
    /**
     * Restituisce la classe CSS per lo stato della scadenza
     */
    public function getStatusClassAttribute()
    {
        if ($this->is_expired) {
            return 'text-red-600 line-through';
        }
        
        if ($this->is_expiring_soon) {
            return 'text-orange-600 font-semibold';
        }
        
        return 'text-gray-700';
    }
    
    /**
     * Restituisce il badge HTML per lo stato della scadenza
     */
    public function getExpirationBadgeAttribute()
    {
        if (!$this->data_fine) {
            return '<span class="text-xs text-gray-400 italic">Nessuna scadenza</span>';
        }
        
        $daysLeft = $this->days_left;
        $formattedDate = $this->data_fine->format('d/m/Y');
        
        if ($this->is_expired) {
            $days = abs($daysLeft);
            return '<div class="flex flex-col">
                        <span class="text-sm font-mono text-red-600 line-through">' . $formattedDate . '</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                            Scaduta da ' . $days . ' gg
                        </span>
                    </div>';
        }
        
        if ($this->is_expiring_soon) {
            return '<div class="flex flex-col">
                        <span class="text-sm font-mono text-orange-600 font-semibold">' . $formattedDate . '</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 mt-1">
                            Scade tra ' . $daysLeft . ' gg
                        </span>
                    </div>';
        }
        
        if ($daysLeft && $daysLeft > 30) {
            return '<div class="flex flex-col">
                        <span class="text-sm font-mono text-green-600">' . $formattedDate . '</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mt-1">
                            Scade tra ' . $daysLeft . ' gg
                        </span>
                    </div>';
        }
        
        return '<div class="flex flex-col">
                    <span class="text-sm font-mono text-gray-700">' . $formattedDate . '</span>
                </div>';
    }

    // ==================== MUTATOR ====================
    
    public function setDataFineAttribute($value)
    {
        $this->attributes['data_fine'] = $value ?: null;
    }
}