<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $table = 'activities';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_cost_centers',
        'id_services',
        'id_entities',
        'data_activities',
        'note',
        'invoice_references',
        'imponibile',
        'costi_mat',
        'Lat_Long',
        'ha',
        'ordine',
        'Stagione_id_stagione',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'data_activities' => 'date',
        'imponibile' => 'decimal:2',
        'costi_mat' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // ==================== RELAZIONI ====================

    /**
     * Relazione con il centro di costo
     */
    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'id_cost_centers', 'id');
    }

    /**
     * Relazione con il servizio
     */
    public function service()
    {
        return $this->belongsTo(Service::class, 'id_services', 'id');
    }

    /**
     * Relazione con l'entità (cliente/fornitore)
     */
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    /**
     * Relazione con le immagini dell'attività
     */
    public function images()
    {
        return $this->hasMany(ActivityImage::class, 'activity_id', 'id')
            ->orderBy('order')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Conta le immagini dell'attività
     */
    public function getImagesCountAttribute()
    {
        return $this->images()->count();
    }

    /**
     * Relazione con la proprietà (ownership)
     * AGGIUNTA PER IL CALENDARIO PRESENZE
     */
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    /**
     * Relazione con il personale associato (tramite pivot)
     */
    public function staff()
    {
        return $this->belongsToMany(Staff::class, 'activities_staff_lnk', 'id_activities', 'id_staff')
            ->withPivot('id_ownership', 'n_ore', 'costo_orario', 'spese', 'contributo', 'contributo_ore', 'note', 'data_att', 'att_start', 'att_end')
            ->withTimestamps();
    }

    /**
     * Relazione con i dettagli del personale (modello pivot esteso)
     */
    public function staffDetails()
    {
        return $this->hasMany(ActivityStaffLink::class, 'id_activities', 'id');
    }

    /**
     * Relazione con le sotto-attività / blocchi lat-long (activities_coordinates)
     */
    public function coordinates()
    {
        return $this->hasMany(ActivityCoordinate::class, 'Attivita_id_attivita', 'id')
            ->orderBy('id_att_LatLong');
    }

    // ==================== ACCESSOR ====================

    /**
     * Nome visualizzato dell'attività
     */
    public function getDisplayNameAttribute()
    {
        $parts = [];
        
        if ($this->service) {
            $parts[] = $this->service->name;
        }
        
        if ($this->costCenter) {
            $parts[] = $this->costCenter->name;
        }
        
        if ($this->entity) {
            $parts[] = $this->entity->ragione_sociale ?? ($this->entity->nome . ' ' . $this->entity->cognome);
        }
        
        return implode(' - ', $parts) ?: 'Attività #' . $this->id;
    }

    /**
     * Importo totale (imponibile + costi mat)
     */
    public function getTotalAmountAttribute()
    {
        return ($this->imponibile ?? 0) + ($this->costi_mat ?? 0);
    }

    /**
     * Formatta l'importo
     */
    public function getImponibileFormattedAttribute()
    {
        return $this->imponibile ? '€ ' . number_format($this->imponibile, 2, ',', '.') : '-';
    }

    /**
     * Formatta i costi mat
     */
    public function getCostiMatFormattedAttribute()
    {
        return $this->costi_mat ? '€ ' . number_format($this->costi_mat, 2, ',', '.') : '-';
    }

    /**
     * Formatta il totale
     */
    public function getTotalFormattedAttribute()
    {
        return '€ ' . number_format($this->total_amount, 2, ',', '.');
    }

    /**
     * Data formattata
     */
    public function getDataFormattedAttribute()
    {
        return $this->data_activities ? $this->data_activities->format('d/m/Y') : '-';
    }

    /**
     * Ore totali del personale
     */
    public function getTotalHoursAttribute()
    {
        return $this->staffDetails->sum(function($detail) {
            return floatval($detail->n_ore);
        });
    }

    // ==================== SCOPE ====================

    /**
     * Filtro per data
     */
    public function scopeByDate($query, $date)
    {
        if ($date) {
            return $query->whereDate('data_activities', $date);
        }
        return $query;
    }

    /**
     * Filtro per range date
     */
    public function scopeDateBetween($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('data_activities', [$startDate, $endDate]);
        }
        return $query;
    }

    /**
     * Filtro per centro di costo
     */
    public function scopeByCostCenter($query, $costCenterId)
    {
        if ($costCenterId) {
            return $query->where('id_cost_centers', $costCenterId);
        }
        return $query;
    }

    /**
     * Filtro per servizio
     */
    public function scopeByService($query, $serviceId)
    {
        if ($serviceId) {
            return $query->where('id_services', $serviceId);
        }
        return $query;
    }

    /**
     * Filtro per entità
     */
    public function scopeByEntity($query, $entityId)
    {
        if ($entityId) {
            return $query->where('id_entities', $entityId);
        }
        return $query;
    }

    /**
     * Filtro ricerca generica
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        
        $searchTerm = '%' . $search . '%';
        return $query->where(function($q) use ($searchTerm) {
            $q->where('invoice_references', 'like', $searchTerm)
              ->orWhere('note', 'like', $searchTerm)
              ->orWhere('ordine', 'like', $searchTerm)
              ->orWhere('ha', 'like', $searchTerm);
        });
    }
}