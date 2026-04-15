<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SettingCategory extends Model
{
    use SoftDeletes;

    protected $table = 'settings_categories';

    protected $fillable = [
        'titolo',
        'slug',
        'descrizione',
        'tabella_riferimento',
        'ordinamento',
        'valid',
        'created_by',      // <-- AGGIUNTO
        'updated_by'       // <-- AGGIUNTO
    ];

    protected $casts = [
        'valid' => 'boolean',
        'ordinamento' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // RELAZIONI PER IL TRACCIAMENTO
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    // Relazione con le impostazioni
    public function settings()
    {
        return $this->hasMany(Setting::class, 'category_id');
    }
    
    // Helper per il tracciamento
    public function getTrackingInfoAttribute()
    {
        $info = [];
        
        if ($this->created_at && $this->createdBy) {
            $info['created'] = [
                'by' => $this->createdBy->name,
                'at' => $this->created_at->format('d/m/Y H:i'),
                'full' => "{$this->createdBy->name} - {$this->created_at->format('d/m/Y H:i')}"
            ];
        }
        
        if ($this->updated_at && $this->updatedBy && $this->created_at != $this->updated_at) {
            $info['updated'] = [
                'by' => $this->updatedBy->name,
                'at' => $this->updated_at->format('d/m/Y H:i'),
                'full' => "{$this->updatedBy->name} - {$this->updated_at->format('d/m/Y H:i')}"
            ];
        }
        
        return $info;
    }

    // Scope per categorie attive
    public function scopeActive($query)
    {
        return $query->where('valid', true);
    }

    // Scope per ordinamento
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordinamento')->orderBy('titolo');
    }
}