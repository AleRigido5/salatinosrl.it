<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff';
    protected $primaryKey = 'id_personale';
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'NomePers',
        'CognomePers',
        'Soprannome',
        'IndirPers',
        'CittaPers',
        'ProvPers',
        'CapPers',
        'TelPers',
        'CellPers',
        'EmailPers',
        'PasswPers',
        'IbanPers',
        'CodFiscPers',
        'DataNascPers',
        'LuogoNasc',
        'valid',
        'created_by',
        'updated_by',
        'id_gruppo'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'DataNascPers' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // ==================== RELAZIONI ====================
    
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    public function gruppo()
    {
        return $this->belongsTo(Setting::class, 'id_gruppo', 'id');
    }

    /**
     * Relazione con le scadenze tramite Expiration
     * Usata per recuperare tutte le scadenze associate a questo staff
     */
    public function expirations()
    {
        return $this->hasMany(Expiration::class, 'id_references', 'id_personale')
            ->where('table_references', Expiration::TABLE_STAFF);
    }

    /**
     * Relazione con le scadenze di Assunzione
     */
    public function assunzioneExpirations()
    {
        return $this->expirations()
            ->where('titolo', Expiration::TYPE_ASSUNZIONE)
            ->orderBy('data_fine', 'desc');
    }

    /**
     * Relazione con le scadenze di Visita Medica
     */
    public function visitaMedicaExpirations()
    {
        return $this->expirations()
            ->where('titolo', Expiration::TYPE_VISITA_MEDICA)
            ->orderBy('data_fine', 'desc');
    }

    /**
     * Relazione con tutte le scadenze di un tipo specifico
     */
    public function expirationsByType($titolo)
    {
        return $this->expirations()
            ->where('titolo', $titolo)
            ->orderBy('data_fine', 'desc');
    }

    // ==================== ACCESSOR ====================

    /**
     * Ottiene l'ultima scadenza di Assunzione
     */
    public function getLastAssunzioneExpirationAttribute()
    {
        return $this->assunzioneExpirations()->first();
    }

    /**
     * Ottiene l'ultima scadenza di Visita Medica
     */
    public function getLastVisitaMedicaExpirationAttribute()
    {
        return $this->visitaMedicaExpirations()->first();
    }

    /**
     * Ottiene la data dell'ultima Assunzione
     */
    public function getLastAssunzioneDateAttribute()
    {
        $expiration = $this->last_assunzione_expiration;
        return $expiration ? $expiration->data_fine : null;
    }

    /**
     * Ottiene la data dell'ultima Visita Medica
     */
    public function getLastVisitaMedicaDateAttribute()
    {
        $expiration = $this->last_visita_medica_expiration;
        return $expiration ? $expiration->data_fine : null;
    }

    /**
     * Ottiene il nome completo del dipendente
     */
    public function getFullNameAttribute()
    {
        return trim($this->NomePers . ' ' . $this->CognomePers);
    }

    /**
     * Ottiene il nome visualizzato con soprannome
     */
    public function getDisplayNameAttribute()
    {
        $name = $this->full_name;
        if ($this->Soprannome) {
            $name .= ' (' . $this->Soprannome . ')';
        }
        return $name ?: '-';
    }

    /**
     * Verifica se il dipendente ha una Assunzione valida (non scaduta)
     */
    public function getHasValidAssunzioneAttribute()
    {
        $assunzione = $this->last_assunzione_expiration;
        return $assunzione && $assunzione->data_fine && $assunzione->data_fine >= now();
    }

    /**
     * Verifica se il dipendente ha una Visita Medica valida (non scaduta)
     */
    public function getHasValidVisitaMedicaAttribute()
    {
        $visita = $this->last_visita_medica_expiration;
        return $visita && $visita->data_fine && $visita->data_fine >= now();
    }

    /**
     * Ottiene lo stato del dipendente (attivo/inattivo)
     */
    public function getStatusLabelAttribute()
    {
        return $this->valid ? 'Attivo' : 'Inattivo';
    }

    /**
     * Ottiene il badge HTML per lo stato
     */
    public function getStatusBadgeAttribute()
    {
        if ($this->valid) {
            return '<span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Attivo</span>';
        }
        return '<span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Inattivo</span>';
    }

    // ==================== SCOPE ====================
    
    public function scopeActive($query)
    {
        return $query->where('valid', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('valid', false);
    }

    public function scopeSearch($query, $search)
    {
        if (empty($search)) return $query;
        
        $searchTerm = '%' . $search . '%';
        return $query->where(function($q) use ($searchTerm) {
            $q->where('NomePers', 'like', $searchTerm)
              ->orWhere('CognomePers', 'like', $searchTerm)
              ->orWhere('Soprannome', 'like', $searchTerm)
              ->orWhere('CellPers', 'like', $searchTerm)
              ->orWhere('EmailPers', 'like', $searchTerm)
              ->orWhere('CodFiscPers', 'like', $searchTerm)
              ->orWhere('IbanPers', 'like', $searchTerm);
        });
    }

    public function scopeOrderByFullName($query, $direction = 'asc')
    {
        return $query->orderBy('CognomePers', $direction)->orderBy('NomePers', $direction);
    }

    public function scopeWithExpirationStatus($query)
    {
        return $query->with([
            'assunzioneExpirations' => function($q) {
                $q->where('data_fine', '>=', now()->subDays(30))
                  ->orderBy('data_fine', 'desc');
            },
            'visitaMedicaExpirations' => function($q) {
                $q->where('data_fine', '>=', now()->subDays(30))
                  ->orderBy('data_fine', 'desc');
            }
        ]);
    }
}