<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cost_centers';

    // Forza l'uso degli accessor
    protected $appends = ['nome_display', 'localita_display', 'coltura_display', 'competenza_display'];

    protected $fillable = [
        'id_references',
        'table_references',
        'Nome',
        'Contrada',
        'Localita',
        'Foglio',
        'Particella',
        'Superficie',
        'Coltura',
        'Note',
        'valid',
        'CostoH',
        'NumH',
        'Competenza',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'valid' => 'boolean',
        'Superficie' => 'decimal:4',
        'CostoH' => 'decimal:2',
        'NumH' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ==================== RELAZIONI ====================

    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_references', 'id_proprieta');
    }

    public function entity()
    {
        return $this->belongsTo(Entity::class, 'id_references', 'id_cliente');
    }

    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    // ==================== ACCESSOR ====================

    public function getReferenceNameAttribute()
    {
        if ($this->table_references === 'ownership') {
            $ownership = $this->ownership;
            if ($ownership) {
                return $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? 'Proprietà';
            }
            return 'Proprietà non trovata';
        }
        
        if ($this->table_references === 'entities') {
            $entity = $this->entity;
            if ($entity) {
                return $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome);
            }
            return 'Entità non trovata';
        }
        
        return 'N/D';
    }

    public function getReferenceTypeAttribute()
    {
        return $this->table_references === 'ownership' ? 'Proprietà' : 'Cliente/Fornitore';
    }

    // Accessor per i campi con maiuscole
    public function getNomeAttribute()
    {
        return $this->attributes['Nome'] ?? '-';
    }
    
    public function getNomeDisplayAttribute()
    {
        return $this->attributes['Nome'] ?? '-';
    }

    public function getContradaAttribute()
    {
        return $this->attributes['Contrada'] ?? '-';
    }

    public function getLocalitaAttribute()
    {
        return $this->attributes['Localita'] ?? '-';
    }
    
    public function getLocalitaDisplayAttribute()
    {
        return $this->attributes['Localita'] ?? '-';
    }

    public function getColturaAttribute()
    {
        return $this->attributes['Coltura'] ?? '-';
    }
    
    public function getColturaDisplayAttribute()
    {
        return $this->attributes['Coltura'] ?? '-';
    }

    public function getCompetenzaAttribute()
    {
        return $this->attributes['Competenza'] ?? '-';
    }
    
    public function getCompetenzaDisplayAttribute()
    {
        return $this->attributes['Competenza'] ?? '-';
    }

    public function getCostoHFormattedAttribute()
    {
        return '€ ' . number_format($this->attributes['CostoH'] ?? 0, 2);
    }

    public function getSuperficieFormattedAttribute()
    {
        return number_format($this->attributes['Superficie'] ?? 0, 2) . ' ha';
    }

    public function getNumHAttribute()
    {
        return $this->attributes['NumH'] ?? 0;
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

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }
        
        $searchTerm = '%' . $term . '%';
        return $query->where(function($q) use ($searchTerm) {
            $q->where('Nome', 'like', $searchTerm)
              ->orWhere('Contrada', 'like', $searchTerm)
              ->orWhere('Localita', 'like', $searchTerm)
              ->orWhere('Coltura', 'like', $searchTerm)
              ->orWhere('Competenza', 'like', $searchTerm);
        });
    }

    public function scopeByReference($query, $type, $id)
    {
        return $query->where('table_references', $type)->where('id_references', $id);
    }
}