<?php
// app/Models/Staff.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = 'staff';
    protected $primaryKey = 'id_personale';

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

    public function expirations()
    {
        return $this->hasMany(Expiration::class, 'id_references', 'id_personale')
            ->where('table_references', Expiration::TABLE_STAFF);
    }

    public function assunzioneExpirations()
    {
        return $this->expirations()
            ->where('titolo', Expiration::TYPE_ASSUNZIONE)
            ->orderBy('data_fine', 'desc');
    }

    public function visitaMedicaExpirations()
    {
        return $this->expirations()
            ->where('titolo', Expiration::TYPE_VISITA_MEDICA)
            ->orderBy('data_fine', 'desc');
    }

    public function getLastAssunzioneExpirationAttribute()
    {
        return $this->assunzioneExpirations()->first();
    }

    public function getLastVisitaMedicaExpirationAttribute()
    {
        return $this->visitaMedicaExpirations()->first();
    }

    public function getLastAssunzioneDateAttribute()
    {
        $expiration = $this->last_assunzione_expiration;
        return $expiration ? $expiration->data_fine : null;
    }

    public function getLastVisitaMedicaDateAttribute()
    {
        $expiration = $this->last_visita_medica_expiration;
        return $expiration ? $expiration->data_fine : null;
    }

    public function getFullNameAttribute()
    {
        return trim($this->NomePers . ' ' . $this->CognomePers);
    }

    public function getDisplayNameAttribute()
    {
        $name = $this->full_name;
        if ($this->Soprannome) {
            $name .= ' (' . $this->Soprannome . ')';
        }
        return $name ?: '-';
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
              ->orWhere('CodFiscPers', 'like', $searchTerm);
        });
    }

    public function scopeOrderByFullName($query, $direction = 'asc')
    {
        return $query->orderBy('CognomePers', $direction)->orderBy('NomePers', $direction);
    }
}