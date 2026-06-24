<?php
// app/Models/InvoiceSeries.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceSeries extends Model
{
    protected $table = 'invoice_series';

    protected $fillable = [
        'id_ownership',
        'name',
        'code',
        'year',
        'last_number',
        'allowed_document_type',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'year' => 'integer',
        'last_number' => 'integer',
    ];

    // Relazioni
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(InvoiceSent::class, 'id_invoice_series');
    }

    // Helper per generare il numero fattura formattato
    public function getFormattedNumberAttribute(): string
    {
        return $this->code . '/' . $this->last_number . '/' . $this->year;
    }

    // Incrementa il contatore
    public function incrementNumber(): int
    {
        $this->last_number += 1;
        $this->save();
        return $this->last_number;
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    // Ottiene o crea il sezionale per una proprietà e anno
    public static function getOrCreate(int $idOwnership, string $code, string $name, string $documentType): self
    {
        $series = self::where('id_ownership', $idOwnership)
            ->where('code', $code)
            ->where('year', date('Y'))
            ->first();

        if (!$series) {
            $series = self::create([
                'id_ownership' => $idOwnership,
                'name' => $name,
                'code' => $code,
                'year' => date('Y'),
                'last_number' => 0,
                'allowed_document_type' => $documentType,
            ]);
        }

        return $series;
    }
}