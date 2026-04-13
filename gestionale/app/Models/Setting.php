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
        'valid'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'ordinamento' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relazione con la categoria
    public function category()
    {
        return $this->belongsTo(SettingCategory::class, 'category_id');
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