<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;

    protected $table = 'settings';
    protected $primaryKey = 'id';

    protected $fillable = [
        'category_id',
        'tabella_riferimento',
        'valore',
        'descrizione',
        'ordinamento',
        'valid',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'ordinamento' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /**
     * Boot method per automatismi
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($setting) {
            // Se tabella_riferimento è vuoto, prova a prenderlo dalla categoria
            if (empty($setting->tabella_riferimento) && $setting->category_id) {
                $category = SettingCategory::find($setting->category_id);
                if ($category && $category->tabella_riferimento) {
                    $setting->tabella_riferimento = $category->tabella_riferimento;
                }
            }
        });
        
        static::updating(function ($setting) {
            // Se tabella_riferimento è vuoto e la categoria ha un valore, aggiorna
            if (empty($setting->tabella_riferimento) && $setting->category_id) {
                $category = SettingCategory::find($setting->category_id);
                if ($category && $category->tabella_riferimento) {
                    $setting->tabella_riferimento = $category->tabella_riferimento;
                }
            }
        });
    }

    // RELAZIONI PER IL TRACCIAMENTO
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    // Relazione con la categoria
    public function category()
    {
        return $this->belongsTo(SettingCategory::class, 'category_id');
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

    // Scope per tabella di riferimento
    public function scopeByTable($query, $table)
    {
        return $query->where('tabella_riferimento', $table);
    }

    // Scope per categoria
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Scope per elementi attivi
    public function scopeActive($query)
    {
        return $query->where('valid', true);
    }

    // Scope per ordinamento
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordinamento')->orderBy('valore');
    }
}