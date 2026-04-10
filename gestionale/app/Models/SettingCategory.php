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
        'valid'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'ordinamento' => 'integer'
    ];

    // Relazione con le impostazioni
    public function settings()
    {
        return $this->hasMany(Setting::class, 'category_id');
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