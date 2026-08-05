<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityCoordinate extends Model
{
    protected $table = 'activities_coordinates';
    protected $primaryKey = 'id_att_LatLong';
    public $timestamps = false;

    protected $fillable = [
        'NoteAtt',
        'Lat_inizio',
        'Lat_fine',
        'ha',
        'verificato',
        'Attivita_id_attivita',
    ];

    /**
     * Relazione inversa con l'attività principale
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'Attivita_id_attivita', 'id');
    }
}