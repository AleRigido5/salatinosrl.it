<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminTask extends Model
{
    use SoftDeletes;

    protected $table = 'admin_tasks';

    protected $fillable = [
        'title',
        'description',
        'practice_number',
        'channel',
        'task_date',
        'due_date',
        'id_category',
        'id_entities',
        'id_ownership',
        'priority',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'task_date' => 'date',
        'due_date' => 'date',
        'priority' => 'integer',
        'deleted_at' => 'datetime',
    ];

    const STATUS_WAITING = 'waiting';
    const STATUS_ASSOCIATED = 'associated';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_WAITING => ['label' => 'In attesa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            self::STATUS_ASSOCIATED => ['label' => 'Associato', 'badge_class' => 'bg-blue-100 text-blue-800'],
            self::STATUS_COMPLETED => ['label' => 'Completato', 'badge_class' => 'bg-green-100 text-green-800'],
            self::STATUS_EXPIRED => ['label' => 'Scaduto', 'badge_class' => 'bg-red-100 text-red-800'],
        ];
    }

    /**
     * Colore associato a ciascuna priorità (1-5), come richiesto:
     * 1 verde, 2 celeste, 3 arancio, 4 giallo, 5 rosso.
     */
    public static function priorityColors(): array
    {
        return [
            1 => ['label' => 'Bassa', 'color' => '#22c55e', 'badge_class' => 'bg-green-100 text-green-800'],
            2 => ['label' => 'Medio-bassa', 'color' => '#38bdf8', 'badge_class' => 'bg-sky-100 text-sky-800'],
            3 => ['label' => 'Media', 'color' => '#f97316', 'badge_class' => 'bg-orange-100 text-orange-800'],
            4 => ['label' => 'Medio-alta', 'color' => '#eab308', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            5 => ['label' => 'Alta', 'color' => '#ef4444', 'badge_class' => 'bg-red-100 text-red-800'],
        ];
    }

    /**
     * Le categorie NON hanno una tabella dedicata: sono righe della
     * tabella generica "settings" (tabella_riferimento = 'admin_tasks'),
     * lo stesso sistema già usato per Contatti/Scadenze/Servizi/ecc.
     * ASSUNZIONE: il modello Eloquent per quella tabella si chiama
     * App\Models\Setting — se nel progetto ha un altro nome, cambia
     * solo il riferimento alla classe qui sotto.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Setting::class, 'id_category');
    }

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'id_entities', 'id_cliente');
    }

    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    public function tags(): BelongsToMany
    {
        // La tabella pivot ha solo created_at (niente updated_at), quindi
        // niente withTimestamps() qui: altrimenti Laravel cerca anche la
        // colonna updated_at e va in errore.
        return $this->belongsToMany(AdminTaskTag::class, 'admin_task_tag_task', 'admin_task_id', 'admin_task_tag_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AdminTaskComment::class, 'admin_task_id')->orderByDesc('created_at');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status]['label'] ?? $this->status;
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return self::statusOptions()[$this->status]['badge_class'] ?? 'bg-gray-100 text-gray-800';
    }

    public function getPriorityColorAttribute(): string
    {
        return self::priorityColors()[$this->priority]['color'] ?? '#84cc16';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && !in_array($this->status, [self::STATUS_COMPLETED]);
    }
}