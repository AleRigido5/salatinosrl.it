<?php
// app/Models/Account.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    protected $table = 'accounts';
    
    protected $fillable = [
        'code',
        'name',
        'type',
        'category',
        'parent_id',
        'is_active',
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // Relazione gerarchica: conto padre
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }
    
    // Relazione gerarchica: conti figli
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }
    
    // Scritture dove questo conto è in DARE
    public function debitEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'debit_account_id');
    }
    
    // Scritture dove questo conto è in AVERE
    public function creditEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'credit_account_id');
    }
    
    // Accessor per tipo formattato
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            'attivo' => 'Attivo',
            'passivo' => 'Passivo',
            'costo' => 'Costo',
            'ricavo' => 'Ricavo',
            'patrimonio_netto' => 'Patrimonio Netto',
        ];
        
        return $labels[$this->type] ?? $this->type;
    }
    
    // Accessor per badge class
    public function getTypeBadgeClassAttribute(): string
    {
        $classes = [
            'attivo' => 'bg-green-100 text-green-800',
            'passivo' => 'bg-red-100 text-red-800',
            'costo' => 'bg-orange-100 text-orange-800',
            'ricavo' => 'bg-blue-100 text-blue-800',
            'patrimonio_netto' => 'bg-purple-100 text-purple-800',
        ];
        
        return $classes[$this->type] ?? 'bg-gray-100 text-gray-800';
    }
    
    // Scope per conti attivi
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    // Scope per tipo
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    // Metodo per ottenere il percorso completo del conto
    public function getFullPathAttribute(): string
    {
        $path = [$this->code . ' - ' . $this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->code . ' - ' . $parent->name);
            $parent = $parent->parent;
        }
        
        return implode(' > ', $path);
    }
}