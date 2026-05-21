<?php
// app/Models/PaymentMethod.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $table = 'payment_methods';
    
    protected $fillable = [
        'code', 'name', 'description', 'is_active', 'sort_order'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    /**
     * Ottiene tutti i metodi di pagamento attivi ordinati
     */
    public static function getActiveMethods()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Ottiene l'array per i select (code => name)
     */
    public static function getSelectArray()
    {
        return self::where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'code')
            ->toArray();
    }
    
    /**
     * Ottiene l'etichetta del metodo di pagamento dato il codice
     */
    public static function getLabel($code)
    {
        if (!$code) return 'Non specificato';
        
        $method = self::where('code', $code)->first();
        return $method ? $method->name : $code;
    }
}