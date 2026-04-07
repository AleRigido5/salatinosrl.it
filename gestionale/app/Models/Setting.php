<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Setting extends Model
{
    use SoftDeletes;
    
    protected $table = 'settings';
    
    protected $fillable = [
        'tabella_riferimento',
        'valore',
        'descrizione',
        'ordinamento',
        'valid',
    ];
    
    protected $casts = [
        'valid' => 'boolean',
        'ordinamento' => 'integer',
    ];
    
    /**
     * Relazione con i contatti
     */
    public function contacts()
    {
        return $this->hasMany(Contact::class, 'id_settings');
    }
}