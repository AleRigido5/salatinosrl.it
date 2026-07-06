<?php
// app/Models/StaffAttendance.php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    public $timestamps = false; // Usiamo created_at e updated_at manuali
    
    protected $table = 'staff_attendances';
    
    protected $fillable = [
        'id_staff',
        'id_ownership',
        'date',
        'is_present',
        'created_by',
        'updated_by',
    ];
    
    protected $casts = [
        'date' => 'date',
        'is_present' => 'boolean',
    ];
    
    // Relazioni
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'id_staff', 'id_personale');
    }
    
    public function ownership(): BelongsTo
    {
        return $this->belongsTo(Ownership::class, 'id_ownership', 'id_proprieta');
    }
    
    // Scopes utili
    public function scopeForMonth($query, int $year, int $month)
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        return $query->whereBetween('date', [$start, $end]);
    }
    
    public function scopeForStaff($query, int $staffId)
    {
        return $query->where('id_staff', $staffId);
    }
    
    public function scopeForOwnership($query, int $ownershipId)
    {
        return $query->where('id_ownership', $ownershipId);
    }
    
    public function scopeForDate($query, string $date)
    {
        return $query->where('date', $date);
    }
    
    // Helper per salvare in bulk
    public static function saveMany(array $changes): array
    {
        $results = [];
        $now = now();
        $userId = auth()->guard('admin')->id();
        
        foreach ($changes as $change) {
            $staffId = $change['staff_id'];
            $date = $change['date'];
            $ownershipId = $change['ownership_id'] ?? null;
            $isPresent = (bool) $change['checked'];
            
            // Aggiorna o crea
            $attendance = self::updateOrCreate(
                [
                    'id_staff' => $staffId,
                    'id_ownership' => $ownershipId,
                    'date' => $date,
                ],
                [
                    'is_present' => $isPresent,
                    'updated_by' => $userId,
                    'updated_at' => $now,
                ]
            );
            
            // Se è nuova, imposta created_by e created_at
            if ($attendance->wasRecentlyCreated) {
                $attendance->created_by = $userId;
                $attendance->created_at = $now;
                $attendance->save();
            }
            
            $results[] = [
                'date' => $date,
                'ownership' => $ownershipId,
                'is_present' => $isPresent,
                'status' => $attendance->wasRecentlyCreated ? 'created' : 'updated',
            ];
        }
        
        return $results;
    }
    
    // Statistiche per un dipendente in un mese
    public static function getStatsForStaff(int $staffId, int $year, int $month): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        
        $attendances = self::where('id_staff', $staffId)
            ->whereBetween('date', [$start, $end])
            ->where('is_present', true)
            ->get();
        
        // Conta giorni unici (senza duplicare per ownership)
        $uniqueDays = $attendances->groupBy('date')->count();
        
        return [
            'total_presences' => $attendances->count(),
            'unique_days' => $uniqueDays,
            'by_ownership' => $attendances->groupBy('id_ownership')->map->count(),
        ];
    }
    
    // Riepilogo mensile per tutti i dipendenti
    public static function getMonthlyReport(int $year, int $month, ?int $ownershipId = null): array
    {
        $query = self::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->where('is_present', true);
        
        if ($ownershipId) {
            $query->where('id_ownership', $ownershipId);
        }
        
        $attendances = $query->get();
        
        // Raggruppa per staff
        $report = [];
        foreach ($attendances as $att) {
            $staffId = $att->id_staff;
            if (!isset($report[$staffId])) {
                $report[$staffId] = [
                    'staff_id' => $staffId,
                    'total' => 0,
                    'days' => [],
                ];
            }
            $report[$staffId]['total']++;
            if (!in_array($att->date, $report[$staffId]['days'])) {
                $report[$staffId]['days'][] = $att->date;
            }
        }
        
        // Aggiungi i nomi dei dipendenti
        $staffIds = array_keys($report);
        $staffNames = Staff::whereIn('id_personale', $staffIds)
            ->get()
            ->mapWithKeys(fn($s) => [$s->id_personale => $s->CognomePers . ' ' . $s->NomePers]);
        
        foreach ($report as $staffId => &$data) {
            $data['name'] = $staffNames[$staffId] ?? 'Sconosciuto';
            $data['unique_days'] = count($data['days']);
            unset($data['days']);
        }
        
        return $report;
    }
}