<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicles extends Model
{
    use SoftDeletes;

    protected $table = 'vehicles';
    protected $primaryKey = 'id';

    protected $fillable = [
        'targa',
        'marca',
        'modello',
        'tipologia',
        'immatricolazione',
        'valid',
        'id_ownership',
        'note',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'immatricolazione' => 'date',
        'valid' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // ==================== RELAZIONI PER IL TRACCIAMENTO ====================
    
    /**
     * Relazione con l'amministratore che ha creato il record
     */
    public function createdBy()
    {
        return $this->belongsTo(Administrator::class, 'created_by', 'id');
    }
    
    /**
     * Relazione con l'amministratore che ha modificato il record
     */
    public function updatedBy()
    {
        return $this->belongsTo(Administrator::class, 'updated_by', 'id');
    }

    // ==================== RELAZIONI ESISTENTI ====================
    
    // Relazione con la proprietà (ownership)
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    // Relazione many-to-many con le scadenze
    public function expirations()
    {
        return $this->belongsToMany(
            Expiration::class,
            'vehicles_expiry_lnk',
            'id_vehicles',
            'id_expiration'
        );
    }

    // ==================== ACCESSOR ====================
    
    // Accessor per il nome completo
    public function getFullNameAttribute()
    {
        $name = $this->targa ?? 'Senza targa';
        if ($this->marca) {
            $name .= ' - ' . $this->marca;
        }
        if ($this->modello) {
            $name .= ' ' . $this->modello;
        }
        return $name;
    }
    
    // Accessor per il nome della proprietà
    public function getProprietaNomeAttribute()
    {
        if (!$this->ownership) {
            return 'Non specificata';
        }
        
        if (!empty($this->ownership->RagAbbrev)) {
            return $this->ownership->RagAbbrev;
        }
        if (!empty($this->ownership->Rag_Soc_intest)) {
            return $this->ownership->Rag_Soc_intest;
        }
        if (!empty($this->ownership->RagSocialePr)) {
            return $this->ownership->RagSocialePr;
        }
        return 'Proprietà ' . $this->ownership->id_proprieta;
    }
    
    // Accessor per l'anno (gestisce date '0000-00-00')
    public function getAnnoImmatricolazioneAttribute()
    {
        if (!$this->immatricolazione || $this->immatricolazione == '0000-00-00') {
            return null;
        }
        
        try {
            return date('Y', strtotime($this->immatricolazione));
        } catch (\Exception $e) {
            return null;
        }
    }

    // ==================== SCOPE ====================
    
    // Scope per la ricerca
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }
        
        $searchTerm = '%' . $term . '%';
        return $query->where(function($q) use ($searchTerm) {
            $q->where('targa', 'like', $searchTerm)
              ->orWhere('marca', 'like', $searchTerm)
              ->orWhere('modello', 'like', $searchTerm)
              ->orWhere('note', 'like', $searchTerm);
        });
    }
}