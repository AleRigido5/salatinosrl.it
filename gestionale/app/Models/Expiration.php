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

    protected $fillable = [
        'id_ownership',
        'id_entities',
        'id_settings',
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

    // ==================== RELAZIONI ====================
    
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    // Per clienti/fornitori (tabella entities)
    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    // PER PERSONALE (STAFF) - stessa colonna id_entities
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'id_entities', 'id_personale');
    }

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

    // ==================== HELPER ====================
    
    public function getTipologiaNameAttribute()
    {
        return $this->setting ? $this->setting->valore : 'N/D';
    }

    public function getLinkedEntityNameAttribute()
    {
        if ($this->staff) {
            return $this->staff->full_name;
        }
        
        if ($this->entity) {
            return $this->entity->full_name;
        }
        
        return '-';
    }
    
    public function getLinkedEntityTypeAttribute()
    {
        if ($this->staff) {
            return 'Personale';
        }
        
        if ($this->entity) {
            return $this->entity->entity_type === 'fornitore' ? 'Fornitore' : 'Cliente';
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

    // ==================== ACCESSOR ====================
    
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