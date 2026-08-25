<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WarehouseDdt extends Model
{
    use SoftDeletes;

    protected $table = 'warehouse_ddt';

    const TYPE_PURCHASE = 'acquisto';
    const TYPE_SALE = 'vendita';

    const STATUS_DRAFT = 'bozza';
    const STATUS_ISSUED = 'emesso';

    protected $fillable = [
        'type',
        'ddt_number',
        'ddt_date',
        'id_entities',
        'id_ownership',
        'causale',
        'status',
        'issued_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ddt_date' => 'date',
        'issued_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(WarehouseDdtRow::class, 'id_ddt')->orderBy('id');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'updated_by');
    }

    public function isIssued(): bool
    {
        return $this->status === self::STATUS_ISSUED;
    }

    public function isPurchase(): bool
    {
        return $this->type === self::TYPE_PURCHASE;
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->isPurchase() ? 'Acquisto' : 'Vendita';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isIssued() ? 'Emesso' : 'Bozza';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->isIssued()
            ? 'bg-green-100 text-green-800'
            : 'bg-yellow-100 text-yellow-800';
    }

    /**
     * Tipo di movimento di magazzino che questo DDT genera quando emesso:
     * un DDT di acquisto fa ENTRARE merce, uno di vendita la fa USCIRE.
     */
    public function movementType(): string
    {
        return $this->isPurchase() ? WarehouseMovement::TYPE_IN : WarehouseMovement::TYPE_OUT;
    }

    /**
     * Valore usato in warehouse_movements.reference_type per collegare
     * ogni movimento generato al DDT che l'ha originato.
     */
    public function referenceType(): string
    {
        return 'ddt_' . $this->type;
    }
}