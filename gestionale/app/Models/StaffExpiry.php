<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffExpiry extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_expiry';
    protected $primaryKey = 'id';

    protected $fillable = [
        'data_inizio',
        'data_fine',
        'titolo',
        'tipologia',
        'tipologia_id',
        'id_proprieta',
        'qualifica',
        'note',
        'valid'
    ];

    protected $casts = [
        'data_inizio' => 'date',
        'data_fine' => 'date',
        'valid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relazione con la proprietà (ownership)
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_proprieta', 'id_proprieta');
    }

    // Relazione con la tipologia (settings)
    public function tipologiaSetting()
    {
        return $this->belongsTo(Setting::class, 'tipologia_id', 'id');
    }

    // Scope per elementi validi (attivi)
    public function scopeActive($query)
    {
        return $query->where('valid', 1);
    }

    // Scope per scadenze in scadenza (nei prossimi 30 giorni)
    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('data_fine', '<=', now()->addDays($days))
                    ->where('data_fine', '>=', now())
                    ->where('valid', 1);
    }

    // Scope per scadenze scadute
    public function scopeExpired($query)
    {
        return $query->where('data_fine', '<', now())
                    ->where('valid', 1);
    }

    // Accessor per lo stato formattato
    public function getStatusLabelAttribute()
    {
        if (!$this->valid) return 'Disattivo';
        
        if ($this->data_fine && $this->data_fine < now()) {
            return 'Scaduto';
        }
        
        if ($this->data_fine && $this->data_fine <= now()->addDays(30)) {
            return 'In scadenza';
        }
        
        return 'Attivo';
    }

    // Accessor per lo stato badge
    public function getStatusBadgeAttribute()
    {
        if (!$this->valid) {
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

    // Accessor per lo stato badge compatto
    public function getStatusBadgeSimpleAttribute()
    {
        if (!$this->valid) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Disattivo</span>';
        }
        
        if ($this->data_fine && $this->data_fine < now()) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Scaduto</span>';
        }
        
        return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Attivo</span>';
    }

    // Accessor per il nome della proprietà
    public function getOwnershipNameAttribute()
    {
        return $this->ownership ? $this->ownership->RagSocialePr : '-';
    }

    // Accessor per il nome della tipologia
    public function getTipologiaNameAttribute()
    {
        return $this->tipologiaSetting ? $this->tipologiaSetting->valore : $this->tipologia;
    }
}