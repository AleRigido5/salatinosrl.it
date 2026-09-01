<?php

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
        'emittente',  // <--- NUOVO CAMPO
        'opening_balance',
        'valid',
        'default_invoice',
    ];
    
    protected $casts = [
        'opening_balance' => 'decimal:2',
        'valid' => 'boolean',
        'default_invoice' => 'boolean',
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
    
    public function scopeDefaultInvoice($query)
    {
        return $query->where('default_invoice', 1);
    }
    
    public function getFormattedIbanAttribute(): string
    {
        if (!$this->iban) return '';
        return chunk_split($this->iban, 4, ' ');
    }
    
    // Accessor per il nome della banca
    public function getBankNameAttribute(): string
    {
        return $this->emittente ?? $this->name ?? 'Banca non specificata';
    }
}