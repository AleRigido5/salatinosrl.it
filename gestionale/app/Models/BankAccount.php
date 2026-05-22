<?php
// app/Models/BankAccount.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $table = 'bank_accounts';
    
    protected $fillable = [
        'id_ownership',
        'name',
        'n_conto',
        'iban',
        'opening_balance',
        'valid',
    ];
    
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'valid' => 'boolean',
    ];
    
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }
    
    public function accountingEntries(): HasMany
    {
        return $this->hasMany(AccountingEntry::class, 'bank_account_id');
    }
    
    public function scopeActive($query)
    {
        return $query->where('valid', 1);
    }
    
    public function getFormattedIbanAttribute(): string
    {
        if (!$this->iban) return '';
        return chunk_split($this->iban, 4, ' ');
    }
}