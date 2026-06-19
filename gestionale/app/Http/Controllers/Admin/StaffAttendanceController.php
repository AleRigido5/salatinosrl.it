<?php
// app/Http/Controllers/Admin/StaffAttendanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Ownership;
use App\Models\Activity;
use App\Models\StaffAttendanceJson;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class StaffAttendanceController extends Controller
{
    // Festivi nazionali italiani fissi (m-d)
    private array $festiviNazionali = [
        '01-01', // Capodanno
        '01-06', // Epifania
        '04-25', // Liberazione
        '05-01', // Festa del Lavoro
        '06-02', // Repubblica
        '08-15', // Ferragosto
        '11-01', // Ognissanti
        '12-08', // Immacolata
        '12-25', // Natale
        '12-26', // Santo Stefano
    ];

    /**
     * Recupera le ownership: AGRICOLA SALATINO SRL e VITICOLTORI SALATINO SS
     * Usa LIKE per essere più flessibile e catturare tutte le varianti
     */
    private function getFilteredOwnerships()
    {
        return Ownership::where('valid', 1)
            ->where(function($query) {
                $query->where('RagAbbrev', 'LIKE', '%AGRICOLA%SALATINO%')
                      ->orWhere('RagAbbrev', 'LIKE', '%VIT%SALATINO%')
                      ->orWhere('RagAbbrev', 'LIKE', '%VITICOLTORI%SALATINO%')
                      ->orWhere('RagAbbrev', 'LIKE', '%VITIVINICOLA%SALATINO%')
                      ->orWhere('Rag_Soc_intest', 'LIKE', '%AGRICOLA%SALATINO%')
                      ->orWhere('Rag_Soc_intest', 'LIKE', '%VIT%SALATINO%')
                      ->orWhere('Rag_Soc_intest', 'LIKE', '%VITICOLTORI%SALATINO%')
                      ->orWhere('Rag_Soc_intest', 'LIKE', '%VITIVINICOLA%SALATINO%');
            })
            ->orderBy('RagAbbrev')
            ->get();
    }

    /**
     * VIEW INDEX — Matrice tutti i dipendenti × giorni del mese
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        // ── Parametri mese ────────────────────────────────────────────────
        $currentMonth = $request->get('month', now()->format('Y-m'));
        [$selectedYear, $selectedMonth] = explode('-', $currentMonth);
        $selectedOwnershipId = $request->get('ownership_id', '');

        // ── Ownerships filtrate ───────────────────────────────────────────
        $ownerships = $this->getFilteredOwnerships();
        
        // 🔍 DEBUG: Se non trova nessuna ownership, mostra TUTTE per debug
        if ($ownerships->isEmpty()) {
            // Prova a cercare con "SALATINO" generico
            $ownerships = Ownership::where('valid', 1)
                ->where(function($query) {
                    $query->where('RagAbbrev', 'LIKE', '%SALATINO%')
                          ->orWhere('Rag_Soc_intest', 'LIKE', '%SALATINO%');
                })
                ->orderBy('RagAbbrev')
                ->get();
        }
        
        // Se ancora non trova, prendi le prime 2 ownership (per test)
        if ($ownerships->isEmpty()) {
            $ownerships = Ownership::where('valid', 1)
                ->limit(2)
                ->orderBy('RagAbbrev')
                ->get();
        }
        
        // Default: prima ownership disponibile
        if (empty($selectedOwnershipId) && $ownerships->isNotEmpty()) {
            $selectedOwnershipId = $ownerships->first()->id_proprieta;
        }

        $dateFrom = $request->get('date_from', Carbon::createFromDate($selectedYear, $selectedMonth, 1)->format('Y-m-d'));
        $dateTo   = $request->get('date_to', Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth()->format('Y-m-d'));

        $startDate = Carbon::parse($dateFrom)->startOfDay();
        $endDate   = Carbon::parse($dateTo)->endOfDay();

        // ── Genera giorni del mese ─────────────────────────────────────────
        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        // ── Dipendenti ────────────────────────────────────────────────────
        $staff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        // ── Presenze salvate in JSON per tutto il mese ─────────────────────
        $staffIds = $staff->pluck('id_personale')->toArray();
        
        // Leggi le presenze da JSON per ogni staff
        $attendanceIndex = [];
        foreach ($staffIds as $sid) {
            $jsonData = StaffAttendanceJson::getForMonth($sid, (int) $selectedYear, (int) $selectedMonth);
            if ($jsonData && isset($jsonData['presenze'])) {
                foreach ($jsonData['presenze'] as $presenza) {
                    $date = $presenza['data'];
                    $owId = $presenza['id_ownership'] ?? null;
                    
                    // Filtra per ownership se selezionata
                    if ($selectedOwnershipId && $owId != $selectedOwnershipId) {
                        continue;
                    }
                    
                    $attendanceIndex[$sid][$date][$owId] = $presenza;
                }
            }
        }

        // ── Attività del mese (SOLO PER INFORMAZIONE) ──────────────────────
        $activitiesQuery = Activity::whereHas('staffDetails', fn($q) => $q->whereIn('id_staff', $staffIds))
            ->with(['staffDetails' => fn($q) => $q->whereIn('id_staff', $staffIds)])
            ->whereBetween('data_activities', [$startOfMonth, $endOfMonth]);

        if ($selectedOwnershipId) {
            $activitiesQuery->whereHas('costCenter', fn($q) => $q
                ->where('table_references', 'ownership')
                ->where('id_references', $selectedOwnershipId));
        }

        $activities = $activitiesQuery->get();

        // Indicizza attività: [staff_id][date] => info
        $activityIndex = [];
        foreach ($activities as $act) {
            $dk = Carbon::parse($act->data_activities)->format('Y-m-d');
            foreach ($act->staffDetails as $sd) {
                if (!isset($activityIndex[$sd->id_staff][$dk])) {
                    $activityIndex[$sd->id_staff][$dk] = 0;
                }
                $activityIndex[$sd->id_staff][$dk]++;
            }
        }

        // ── Costruisci matrice presenze ────────────────────────────────────
        $presenzeMatrix  = [];
        $totaliGiornate  = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;

            // Data assunzione
            $assExp = $person->expirations->filter(fn($e) =>
                str_contains(strtolower($e->titolo ?? ''), 'assunzione')
            )->sortBy('data_inizio')->first();

            $dataAss = $assExp && $assExp->data_inizio
                ? Carbon::parse($assExp->data_inizio)->startOfDay() : null;
            $dataLic = $assExp && $assExp->data_fine
                ? Carbon::parse($assExp->data_fine)->startOfDay() : null;

            // Assenze
            $assenze = $person->expirations->filter(fn($e) => collect(['malattia','ferie','permesso'])
                ->contains(fn($k) => str_contains(strtolower($e->titolo ?? ''), $k)));

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');

                $isInAss = true;
                if ($dataAss && $giorno->lt($dataAss)) $isInAss = false;
                if ($dataLic && $giorno->gt($dataLic)) $isInAss = false;

                $hasCausale = $assenze->contains(function ($e) use ($dk) {
                    $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
                    $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
                    return $di && $df && $di <= $dk && $df >= $dk;
                });

                // ✅ PRESENZA: SOLO DA JSON
                $checked = false;
                if ($isInAss && !$giorno->isSunday() && !$hasCausale) {
                    if (isset($attendanceIndex[$pid][$dk])) {
                        foreach ($attendanceIndex[$pid][$dk] as $att) {
                            if ($att['is_present'] ?? false) {
                                $checked = true;
                                break;
                            }
                        }
                    }
                }

                if ($checked) $totaliGiornate[$pid]++;

                $presenzeMatrix[$pid][$dk] = [
                    'checked'           => $checked,
                    'is_in_assunzione'  => $isInAss,
                    'causale'           => $hasCausale ? 'assenza' : null,
                    'is_sunday'         => $giorno->isSunday(),
                    'has_activity'      => ($activityIndex[$pid][$dk] ?? 0) > 0,
                ];
            }
        }

        $festiviNazionali = $this->festiviNazionali;

        return view('admin.staff.attendance.index', compact(
            'staff', 'ownerships', 'giorni', 'presenzeMatrix', 'totaliGiornate',
            'selectedYear', 'selectedMonth', 'selectedOwnershipId',
            'dateFrom', 'dateTo', 'festiviNazionali'
        ));
    }

    /**
     * VIEW SHOW — Inserisci/Modifica presenze di un singolo dipendente
     */
    public function show(Request $request, $staffId, $year, $month)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $staff = Staff::with([
            'expirations' => fn($q) => $q->where(fn($q2) => $q2
                ->where('titolo', 'LIKE', '%Malattia%')
                ->orWhere('titolo', 'LIKE', '%Ferie%')
                ->orWhere('titolo', 'LIKE', '%Permesso%')
                ->orWhere('titolo', 'LIKE', '%Assunzione%')),
            'gruppo',
        ])->findOrFail($staffId);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        // ── Assunzione ────────────────────────────────────────────────────
        $assExp = $staff->expirations->filter(fn($e) =>
            str_contains(strtolower($e->titolo ?? ''), 'assunzione')
        )->sortBy('data_inizio')->first();

        $dataAssunzione   = $assExp && $assExp->data_inizio ? Carbon::parse($assExp->data_inizio)->startOfDay() : null;
        $dataLicenziamento = $assExp && $assExp->data_fine  ? Carbon::parse($assExp->data_fine)->startOfDay()  : null;

        // ── Assenze ───────────────────────────────────────────────────────
        $assenze = $staff->expirations->filter(fn($e) => collect(['malattia','ferie','permesso'])
            ->contains(fn($k) => str_contains(strtolower($e->titolo ?? ''), $k)));

        // ── Ownership filtrate ────────────────────────────────────────────
        $ownerships = $this->getFilteredOwnerships();
        
        // 🔍 DEBUG: Se non trova nessuna ownership, mostra TUTTE per debug
        if ($ownerships->isEmpty()) {
            $ownerships = Ownership::where('valid', 1)
                ->where(function($query) {
                    $query->where('RagAbbrev', 'LIKE', '%SALATINO%')
                          ->orWhere('Rag_Soc_intest', 'LIKE', '%SALATINO%');
                })
                ->orderBy('RagAbbrev')
                ->get();
        }
        
        if ($ownerships->isEmpty()) {
            $ownerships = Ownership::where('valid', 1)
                ->limit(2)
                ->orderBy('RagAbbrev')
                ->get();
        }

        // ── Attività del dipendente nel mese (SOLO PER INFORMAZIONE) ──────
        $activities = Activity::whereHas('staffDetails', fn($q) => $q->where('id_staff', $staffId))
            ->with([
                'staffDetails' => fn($q) => $q->where('id_staff', $staffId),
                'costCenter',
            ])
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->get();

        $activitiesByDate = $activities->groupBy(fn($a) =>
            Carbon::parse($a->data_activities)->format('Y-m-d'));

        $ownershipMap = Ownership::where('valid', 1)->get()->keyBy('id_proprieta');

        // ── Presenze salvate in JSON ──────────────────────────────────────
        $jsonData = StaffAttendanceJson::getStaff($staffId, (int) $year, (int) $month);
        
        // Indicizza: [date][ownership_id] => presenza
        $savedIndex = [];
        $totaliPerOwnership = [];
        foreach ($ownerships as $ow) {
            $totaliPerOwnership[$ow->id_proprieta] = 0;
        }

        if ($jsonData && isset($jsonData['presenze'])) {
            foreach ($jsonData['presenze'] as $presenza) {
                $date = $presenza['data'];
                $owId = $presenza['id_ownership'] ?? null;
                
                // Filtra solo per le ownership selezionate
                if ($owId && !$ownerships->contains('id_proprieta', $owId)) {
                    continue;
                }
                
                $savedIndex[$date][$owId] = $presenza;
                
                if (($presenza['is_present'] ?? false)) {
                    if ($owId && isset($totaliPerOwnership[$owId])) {
                        $totaliPerOwnership[$owId]++;
                    }
                }
            }
        }

        // ── Costruzione array $presenze ───────────────────────────────────
        $presenze       = [];
        $giorniMap = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Gio','Fri'=>'Ven','Sat'=>'Sab','Sun'=>'Dom'];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dk        = $current->format('Y-m-d');
            $isSunday  = $current->isSunday();

            $isInAss = true;
            if ($dataAssunzione    && $current->lt($dataAssunzione))    $isInAss = false;
            if ($dataLicenziamento && $current->gt($dataLicenziamento)) $isInAss = false;

            // Causale
            $expiration = $assenze->first(function ($e) use ($dk) {
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
                return $di && $df && $di <= $dk && $df >= $dk;
            });
            $causale = null;
            if ($expiration) {
                $t = strtolower($expiration->titolo ?? '');
                if (str_contains($t, 'malattia'))      $causale = 'malattia';
                elseif (str_contains($t, 'ferie'))     $causale = 'ferie';
                elseif (str_contains($t, 'permesso'))  $causale = 'permesso';
            }

            // ── Attività del giorno (SOLO PER INFORMAZIONE) ──────────────
            $dayActivities = $isInAss ? $activitiesByDate->get($dk, collect()) : collect();
            $oreGiorno = 0;
            $dettagli  = [];
            $cantieriNomi = [];
            $ownershipIds = [];

            foreach ($dayActivities as $act) {
                $sd = $act->staffDetails->first();
                if (!$sd) continue;

                $ore = floatval($sd->n_ore ?? 0);
                if ($ore == 0 && $sd->att_start && $sd->att_end) {
                    try {
                        $ore = max(0, Carbon::parse($sd->att_start)->diffInHours(Carbon::parse($sd->att_end)));
                    } catch (\Exception $e) { $ore = 0; }
                }
                $oreGiorno += $ore;

                $owId   = null;
                $owName = null;
                $ccName = null;
                if ($act->costCenter) {
                    $cc     = $act->costCenter;
                    $ccName = $cc->Nome ?? null;
                    if ($cc->table_references === 'ownership' && $cc->id_references) {
                        $owId   = $cc->id_references;
                        $owName = $ownershipMap[$owId]->RagAbbrev ?? null;
                        // Filtra solo per le ownership selezionate
                        if ($ownerships->contains('id_proprieta', $owId)) {
                            $ownershipIds[] = $owId;
                            if ($owName) $cantieriNomi[] = $owName;
                        }
                    }
                }

                $dettagli[] = [
                    'id_ownership'    => $owId,
                    'nome_ownership'  => $owName ?? $ccName ?? 'N/A',
                    'cost_center_name'=> $ccName,
                    'att_start'       => $sd->att_start ?? null,
                    'att_end'         => $sd->att_end   ?? null,
                    'n_ore'           => $ore,
                    'is_ownership'    => ($act->costCenter && $act->costCenter->table_references === 'ownership'),
                ];
            }

            $ownershipIdsUnici = array_unique($ownershipIds);
            $cantieriNomiUnici = array_unique($cantieriNomi);
            $numCantieri       = count($cantieriNomiUnici);

            // ── ownership_checked: PER OGNI OWNERSHIP, LEGGI DA JSON ──────
            $ownershipChecked = [];
            foreach ($ownerships as $ow) {
                $owId = $ow->id_proprieta;

                if (isset($savedIndex[$dk][$owId])) {
                    $ownershipChecked[$owId] = $savedIndex[$dk][$owId]['is_present'] ?? false;
                } else {
                    $ownershipChecked[$owId] = false;
                }
            }

            // Prepara i dati per la vista
            $presenze[$dk] = [
                'data'              => $dk,
                'giorno_settimana'  => $giorniMap[$current->isoFormat('ddd')] ?? $current->isoFormat('ddd'),
                'giorno_num'        => $current->day,
                'is_sunday'         => $isSunday,
                'is_in_assunzione'  => $isInAss,
                'causale'           => $causale,
                'ore'               => $isInAss ? $oreGiorno : 0,
                'dettagli'          => $isInAss ? $dettagli  : [],
                'cantieri_string'   => implode(', ', $cantieriNomiUnici),
                'num_cantieri'      => $numCantieri,
                'has_multiple'      => $numCantieri > 1,
                'ownership_checked' => $ownershipChecked,
                'has_activity'      => $oreGiorno > 0,
                'json_data'         => $savedIndex[$dk] ?? null,
            ];

            $current->addDay();
        }

        return view('admin.staff.attendance.show', compact(
            'staff', 'year', 'month', 'presenze', 'ownerships',
            'startDate', 'endDate',
            'dataAssunzione', 'dataLicenziamento',
            'totaliPerOwnership'
        ));
    }

    /**
     * SAVE — Salva presenze in JSON
     */
    public function save(Request $request)
    {
        try {
            if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
                return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
            }

            $changes = $request->input('changes', []);
            
            if (empty($changes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna modifica da salvare',
                ]);
            }

            // Valida i dati
            foreach ($changes as $change) {
                $validator = validator($change, [
                    'staff_id'     => 'required|integer|exists:staff,id_personale',
                    'date'         => 'required|date_format:Y-m-d',
                    'checked'      => 'required|boolean',
                    'ownership_id' => 'nullable|integer|exists:ownership,id_proprieta',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dati non validi: ' . $validator->errors()->first(),
                    ], 422);
                }
            }

            // Salva su JSON
            $results = StaffAttendanceJson::saveMany($changes);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' presenze salvate con successo',
                'results' => $results,
                'saved_count' => count($results),
            ]);

        } catch (\Exception $e) {
            Log::error('Save attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DEBUG: Mostra tutte le ownership per vedere i nomi reali
     */
    public function debugOwnerships()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }
        
        $all = Ownership::where('valid', 1)->get(['id_proprieta', 'RagAbbrev', 'Rag_Soc_intest']);
        return response()->json($all);
    }

    /**
     * Visualizza i backup JSON disponibili
     */
    public function backups(Request $request, ?int $staffId = null)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $backups = StaffAttendanceJson::listAll();
        
        if ($staffId) {
            $backups = array_filter($backups, fn($b) => $b['staff_id'] === $staffId);
        }

        if ($request->has('year')) {
            $year = (int) $request->get('year');
            $backups = array_filter($backups, fn($b) => $b['year'] === $year);
        }

        if ($request->has('month')) {
            $month = (int) $request->get('month');
            $backups = array_filter($backups, fn($b) => $b['month'] === $month);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'backups' => array_values($backups),
            ]);
        }

        $staffList = $staffId ? Staff::find($staffId) : null;
        
        return view('admin.staff.attendance.backups', compact('backups', 'staffId', 'staffList'));
    }

    /**
     * Scarica un file JSON di backup
     */
    public function download(string $filename)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $path = 'attendance_data/' . $filename;
        if (!Storage::disk('local')->exists($path)) {
            abort(404, 'File non trovato');
        }

        $fullPath = Storage::disk('local')->path($path);

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Importa dati da un file JSON
     */
    public function import(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:json|max:2048',
        ]);

        try {
            $content = file_get_contents($request->file('file')->getPathname());
            $data = json_decode($content, true);

            if (!$data || !isset($data['presenze'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'File JSON non valido',
                ], 422);
            }

            $changes = [];
            $staffId = $data['dipendente_id'] ?? null;
            
            if (!$staffId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID dipendente non trovato nel file',
                ], 422);
            }

            foreach ($data['presenze'] as $presenza) {
                $changes[] = [
                    'staff_id' => (int) $staffId,
                    'date' => $presenza['data'],
                    'checked' => $presenza['is_present'] ?? false,
                    'ownership_id' => $presenza['id_ownership'] ?? null,
                ];
            }

            $results = StaffAttendanceJson::saveMany($changes);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' presenze importate con successo',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Import attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export PDF (placeholder)
     */
    public function exportPdf(Request $request)
    {
        return redirect()->back()->with('info', 'Export PDF in fase di sviluppo');
    }

    /**
     * Export Excel (placeholder)
     */
    public function exportExcel(Request $request)
    {
        return redirect()->back()->with('info', 'Export Excel in fase di sviluppo');
    }
}