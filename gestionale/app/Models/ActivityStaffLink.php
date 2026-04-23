<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityStaffLink extends Model
{
    use HasFactory;

    protected $table = 'activities_staff_lnk';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id_activities',
        'id_staff',
        'id_ownership',
        'n_ore',
        'costo_orario',
        'spese',
        'contributo',
        'contributo_ore',
        'note',
        'data_att',
        'att_start',
        'att_end'
    ];

    protected $casts = [
        'data_att' => 'date',
        'att_start' => 'datetime',
        'att_end' => 'datetime',
        'costo_orario' => 'decimal:2',
        'spese' => 'decimal:2',
        'contributo' => 'integer',
        'contributo_ore' => 'integer'
    ];

    /**
     * Relazione con l'attività
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'id_activities', 'id');
    }

    /**
     * Relazione con il personale
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'id_staff', 'id_personale');
    }

    /**
     * Relazione con la proprietà
     */
    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }

    /**
     * Calcola il costo totale (ore * costo_orario + spese)
     */
    public function getTotalCostAttribute()
    {
        $ore = floatval($this->n_ore);
        $costoOrario = floatval($this->costo_orario ?? 0);
        $spese = floatval($this->spese ?? 0);
        
        return ($ore * $costoOrario) + $spese;
    }

    /**
     * Formatta il costo totale
     */
    public function getTotalCostFormattedAttribute()
    {
        return '€ ' . number_format($this->total_cost, 2, ',', '.');
    }
}