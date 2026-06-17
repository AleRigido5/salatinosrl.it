<?php
// app/Models/StaffAttendance.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $table = 'staff_attendance';
    protected $primaryKey = 'id';

    protected $fillable = [
        'staff_id',
        'date',
        'is_present',
        'ownership_id',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'is_present' => 'boolean',
    ];

    // ==================== RELAZIONI ====================

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id_personale');
    }

    public function ownership()
    {
        return $this->belongsTo(Ownership::class, 'ownership_id', 'id_proprieta');
    }

    // ==================== SCOPES ====================

    public function scopeForMonth($query, $year, $month)
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }
}