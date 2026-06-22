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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

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
     * Controlla se una data è festiva (domenica o festivo nazionale)
     */
    private function isFestivo(Carbon $date): bool
    {
        return $date->isSunday() || in_array($date->format('m-d'), $this->festiviNazionali);
    }

    /**
     * Trova l'assunzione più recente per un dipendente
     */
    private function findLatestAssunzione($person, $selectedOwnershipId)
    {
        $assunzioni = $person->expirations->filter(fn($e) =>
            $e->table_references === 'staff' && 
            str_contains(strtolower($e->titolo ?? ''), 'assunzione')
        );

        if (!empty($selectedOwnershipId)) {
            $assunzioni = $assunzioni->filter(fn($e) =>
                isset($e->id_ownership) && $e->id_ownership == $selectedOwnershipId
            );
        }

        $assunzioni = $assunzioni->sort(function($a, $b) {
            if ($a->data_fine === null && $b->data_fine === null) return 0;
            if ($a->data_fine === null) return 1;
            if ($b->data_fine === null) return -1;
            return $b->data_fine <=> $a->data_fine;
        });

        return $assunzioni->first();
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
            $ownerships = Ownership::where('valid', 1)->limit(2)->orderBy('RagAbbrev')->get();
        }

        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        // ── TUTTI i dipendenti validi con gruppo ──────────────────────────
        $allStaff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        // ── Filtra dipendenti ─────────────────────────────────────────────
        $staff = $allStaff->filter(function($person) use ($selectedOwnershipId, $startOfMonth, $endOfMonth) {
            if (empty($selectedOwnershipId)) {
                return true;
            }

            $assExp = $this->findLatestAssunzione($person, $selectedOwnershipId);
            if ($assExp) {
                return true;
            }

            $hasEvent = $person->expirations->filter(fn($e) =>
                $e->table_references === 'staff' && 
                collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                    str_contains(strtolower($e->titolo ?? ''), $k)
                )
            )->first(function($e) use ($startOfMonth, $endOfMonth) {
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine) : null;
                
                if (!$di) return false;
                if ($df) {
                    return $di <= $endOfMonth && $df >= $startOfMonth;
                }
                return $di >= $startOfMonth && $di <= $endOfMonth;
            });

            return $hasEvent !== null;
        });

        $staffIds = $staff->pluck('id_personale')->toArray();

        // ── Presenze salvate in JSON ───────────────────────────────────────
        $attendanceIndex = [];
        foreach ($staffIds as $sid) {
            $jsonData = StaffAttendanceJson::getForMonth($sid, (int) $selectedYear, (int) $selectedMonth);
            if ($jsonData && isset($jsonData['presenze'])) {
                foreach ($jsonData['presenze'] as $presenza) {
                    $date = $presenza['data'];
                    $owId = $presenza['id_ownership'] ?? null;

                    if (!empty($selectedOwnershipId) && $owId != $selectedOwnershipId) {
                        continue;
                    }

                    $attendanceIndex[$sid][$date][$owId] = $presenza;
                }
            }
        }

        // ── Attività del mese - TUTTE le attività ──────────────────────────
        $activitiesQuery = Activity::whereHas('staffDetails', fn($q) => $q->whereIn('id_staff', $staffIds))
            ->with(['staffDetails' => fn($q) => $q->whereIn('id_staff', $staffIds)])
            ->whereBetween('data_activities', [$startOfMonth, $endOfMonth]);

        $activities = $activitiesQuery->get();

        $activityIndex = [];
        foreach ($activities as $act) {
            $dk = Carbon::parse($act->data_activities)->format('Y-m-d');
            foreach ($act->staffDetails as $sd) {
                if (!in_array($sd->id_staff, $staffIds)) continue;
                if (!isset($activityIndex[$sd->id_staff][$dk])) {
                    $activityIndex[$sd->id_staff][$dk] = 0;
                }
                $activityIndex[$sd->id_staff][$dk]++;
            }
        }

        // ── Costruisci matrice presenze ────────────────────────────────────
        $presenzeMatrix = [];
        $totaliGiornate = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;

            $assExp = $this->findLatestAssunzione($person, $selectedOwnershipId);

            $dataAss = $assExp && $assExp->data_inizio
                ? Carbon::parse($assExp->data_inizio)->startOfDay() : null;
            $dataLic = $assExp && $assExp->data_fine
                ? Carbon::parse($assExp->data_fine)->endOfDay() : null;

            $assenze = $person->expirations->filter(fn($e) =>
                $e->table_references === 'staff' && 
                collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                    str_contains(strtolower($e->titolo ?? ''), $k)
                )
            );

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');
                $isFestivo = $this->isFestivo($giorno);

                $isInAss = true;
                if ($dataAss && $giorno->lt($dataAss)) $isInAss = false;
                if ($dataLic && $giorno->gt($dataLic)) $isInAss = false;

                $expiration = $assenze->first(function ($e) use ($dk) {
                    $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
                    $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
                    return $di && $df && $di <= $dk && $df >= $dk;
                });

                $causale = null;
                if ($expiration) {
                    $t = strtolower($expiration->titolo ?? '');
                    if (str_contains($t, 'malattia'))        $causale = 'malattia';
                    elseif (str_contains($t, 'ferie'))       $causale = 'ferie';
                    elseif (str_contains($t, 'permesso'))    $causale = 'permesso';
                    elseif (str_contains($t, 'assenza'))     $causale = 'assenza';
                }

                $checked = false;
                if ($isInAss && !$isFestivo && !$causale) {
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
                    'causale'           => $causale,
                    'is_sunday'         => $giorno->isSunday(),
                    'is_festivo'        => $isFestivo,
                    'has_activity'      => ($activityIndex[$pid][$dk] ?? 0) > 0,
                ];
            }
        }

        $staffPerGruppo = $staff->groupBy(fn($p) => $p->gruppo->nome ?? 'Senza categoria');
        $festiviNazionali = $this->festiviNazionali;

        return view('admin.staff.attendance.index', compact(
            'staff', 'staffPerGruppo', 'ownerships', 'giorni', 'presenzeMatrix', 'totaliGiornate',
            'selectedYear', 'selectedMonth', 'selectedOwnershipId',
            'festiviNazionali'
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
            'expirations' => function($q) {
                $q->where('table_references', 'staff');
            },
            'gruppo',
        ])->findOrFail($staffId);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        // ── Assunzione ────────────────────────────────────────────────────
        $assExp = $staff->expirations->filter(fn($e) =>
            str_contains(strtolower($e->titolo ?? ''), 'assunzione')
        )->sort(function($a, $b) {
            if ($a->data_fine === null && $b->data_fine === null) return 0;
            if ($a->data_fine === null) return 1;
            if ($b->data_fine === null) return -1;
            return $b->data_fine <=> $a->data_fine;
        })->first();

        $dataAssunzione    = $assExp && $assExp->data_inizio ? Carbon::parse($assExp->data_inizio)->startOfDay() : null;
        $dataLicenziamento = $assExp && $assExp->data_fine  ? Carbon::parse($assExp->data_fine)->endOfDay() : null;

        // ── Assenze (malattia, ferie, permesso) ──────────────────────────
        $assenze = $staff->expirations->filter(fn($e) => 
            collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                str_contains(strtolower($e->titolo ?? ''), $k)
            )
        );

        // ── Ownership filtrate ────────────────────────────────────────────
        $ownerships = $this->getFilteredOwnerships();

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
            $ownerships = Ownership::where('valid', 1)->limit(2)->orderBy('RagAbbrev')->get();
        }

        // ── Attività del dipendente nel mese ──────────────────────────────
        $activities = Activity::whereHas('staffDetails', fn($q) => $q->where('id_staff', $staffId))
            ->with([
                'staffDetails'  => fn($q) => $q->where('id_staff', $staffId),
                'costCenter',
            ])
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->get();

        $activitiesByDate = $activities->groupBy(fn($a) =>
            Carbon::parse($a->data_activities)->format('Y-m-d'));

        $ownershipMap = Ownership::where('valid', 1)->get()->keyBy('id_proprieta');

        // ── Presenze salvate in JSON ──────────────────────────────────────
        $jsonData = StaffAttendanceJson::getStaff($staffId, (int) $year, (int) $month);

        $savedIndex = [];
        $totaliPerOwnership = [];
        foreach ($ownerships as $ow) {
            $totaliPerOwnership[$ow->id_proprieta] = 0;
        }

        if ($jsonData && isset($jsonData['presenze'])) {
            foreach ($jsonData['presenze'] as $presenza) {
                $date = $presenza['data'];
                $owId = $presenza['id_ownership'] ?? null;

                if ($owId && !$ownerships->contains('id_proprieta', $owId)) {
                    continue;
                }

                $savedIndex[$date][$owId] = $presenza;

                if (($presenza['is_present'] ?? false) && $owId && isset($totaliPerOwnership[$owId])) {
                    $totaliPerOwnership[$owId]++;
                }
            }
        }

        // ── Costruzione array $presenze ───────────────────────────────────
        $presenze  = [];
        $giorniMap = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mer','Thu'=>'Gio','Fri'=>'Ven','Sat'=>'Sab','Sun'=>'Dom'];

        $current = $startDate->copy();
        while ($current <= $endDate) {
            $dk       = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isFestivo = $this->isFestivo($current);

            $isInAss = true;
            if ($dataAssunzione    && $current->lt($dataAssunzione))    $isInAss = false;
            if ($dataLicenziamento && $current->gt($dataLicenziamento)) $isInAss = false;

            $expiration = $assenze->first(function ($e) use ($dk) {
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
                return $di && $df && $di <= $dk && $df >= $dk;
            });
            
            $causale = null;
            if ($expiration) {
                $t = strtolower($expiration->titolo ?? '');
                if (str_contains($t, 'malattia'))        $causale = 'malattia';
                elseif (str_contains($t, 'ferie'))       $causale = 'ferie';
                elseif (str_contains($t, 'permesso'))    $causale = 'permesso';
                elseif (str_contains($t, 'assenza'))     $causale = 'assenza';
            }

            // ── Attività del giorno ───────────────────────────────────────
            $dayActivities = $activitiesByDate->get($dk, collect());
            $oreGiorno     = 0;
            $dettagli      = [];
            $cantieriNomi  = [];
            $ownershipIds  = [];
            $localita      = null;

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
                    $cc = $act->costCenter;
                    $ccName = $cc->Nome ?? null;
                    
                    if (!$localita && !empty($cc->Localita)) {
                        $localita = $cc->Localita;
                    }

                    if ($cc->table_references === 'ownership' && $cc->id_references) {
                        $owId   = $cc->id_references;
                        $owName = $ownershipMap[$owId]->RagAbbrev ?? null;
                        if ($ownerships->contains('id_proprieta', $owId)) {
                            $ownershipIds[] = $owId;
                            if ($owName) $cantieriNomi[] = $owName;
                        }
                    }
                }

                $dettagli[] = [
                    'id_ownership'     => $owId,
                    'nome_ownership'   => $owName ?? $ccName ?? 'N/A',
                    'cost_center_name' => $ccName,
                    'localita'         => $localita,
                    'att_start'        => $sd->att_start ?? null,
                    'att_end'          => $sd->att_end   ?? null,
                    'n_ore'            => $ore,
                    'is_ownership'     => ($act->costCenter && $act->costCenter->table_references === 'ownership'),
                ];
            }

            $ownershipIdsUnici = array_unique($ownershipIds);
            $cantieriNomiUnici = array_unique($cantieriNomi);
            $numCantieri       = count($cantieriNomiUnici);

            // ── ownership_checked da JSON ─────────────────────────────────
            $ownershipChecked = [];
            foreach ($ownerships as $ow) {
                $owId = $ow->id_proprieta;
                $ownershipChecked[$owId] = isset($savedIndex[$dk][$owId])
                    ? ($savedIndex[$dk][$owId]['is_present'] ?? false)
                    : false;
            }

            $presenze[$dk] = [
                'data'              => $dk,
                'giorno_settimana'  => $giorniMap[$current->isoFormat('ddd')] ?? $current->isoFormat('ddd'),
                'giorno_num'        => $current->day,
                'is_sunday'         => $isSunday,
                'is_festivo'        => $isFestivo,
                'is_in_assunzione'  => $isInAss,
                'causale'           => $causale,
                'ore'               => $isInAss ? $oreGiorno : 0,
                'localita'          => $localita,
                'dettagli'          => $isInAss ? $dettagli : [],
                'cantieri_string'   => implode(', ', $cantieriNomiUnici),
                'num_cantieri'      => $numCantieri,
                'has_multiple'      => $numCantieri > 1,
                'ownership_checked' => $ownershipChecked,
                'has_activity'      => $oreGiorno > 0,
                'json_data'         => $savedIndex[$dk] ?? null,
            ];

            $current->addDay();
        }

        $festiviNazionali = $this->festiviNazionali;

        return view('admin.staff.attendance.show', compact(
            'staff', 
            'year', 
            'month',
            'presenze', 
            'ownerships',
            'startDate', 
            'endDate',
            'dataAssunzione', 
            'dataLicenziamento',
            'totaliPerOwnership', 
            'festiviNazionali'
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
                return response()->json(['success' => false, 'message' => 'Nessuna modifica da salvare']);
            }

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

            $results = StaffAttendanceJson::saveMany($changes);

            return response()->json([
                'success'     => true,
                'message'     => count($results) . ' presenze salvate con successo',
                'results'     => $results,
                'saved_count' => count($results),
            ]);

        } catch (\Exception $e) {
            Log::error('Save attendance error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * BACKUPS — Visualizza i backup JSON disponibili
     */
    public function backups(Request $request, ?int $staffId = null)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) abort(403);

        $backups = StaffAttendanceJson::listAll();
        if ($staffId) $backups = array_filter($backups, fn($b) => $b['staff_id'] === $staffId);
        if ($request->has('year'))  $backups = array_filter($backups, fn($b) => $b['year']  === (int)$request->get('year'));
        if ($request->has('month')) $backups = array_filter($backups, fn($b) => $b['month'] === (int)$request->get('month'));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'backups' => array_values($backups)]);
        }

        $staffList = $staffId ? Staff::find($staffId) : null;
        return view('admin.staff.attendance.backups', compact('backups', 'staffId', 'staffList'));
    }

    /**
     * DOWNLOAD — Scarica un file JSON di backup
     */
    public function download(string $filename)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) abort(403);

        $path = 'attendance_data/' . $filename;
        if (!Storage::disk('local')->exists($path)) abort(404, 'File non trovato');

        return response()->download(Storage::disk('local')->path($path), $filename, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * IMPORT — Importa dati da un file JSON
     */
    public function import(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) abort(403);

        $request->validate(['file' => 'required|file|mimes:json|max:2048']);

        try {
            $content = file_get_contents($request->file('file')->getPathname());
            $data    = json_decode($content, true);

            if (!$data || !isset($data['presenze'])) {
                return response()->json(['success' => false, 'message' => 'File JSON non valido'], 422);
            }

            $staffId = $data['dipendente_id'] ?? null;
            if (!$staffId) {
                return response()->json(['success' => false, 'message' => 'ID dipendente non trovato nel file'], 422);
            }

            $changes = [];
            foreach ($data['presenze'] as $presenza) {
                $changes[] = [
                    'staff_id'     => (int) $staffId,
                    'date'         => $presenza['data'],
                    'checked'      => $presenza['is_present'] ?? false,
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
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * EXPORT PDF — Esporta la matrice in PDF
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $currentMonth = $request->get('month', now()->format('Y-m'));
        [$selectedYear, $selectedMonth] = explode('-', $currentMonth);
        $selectedOwnershipId = $request->get('ownership_id', '');

        $ownerships = $this->getFilteredOwnerships();
        if ($ownerships->isEmpty()) {
            $ownerships = Ownership::where('valid', 1)
                ->where(function($query) {
                    $query->where('RagAbbrev', 'LIKE', '%SALATINO%')
                          ->orWhere('Rag_Soc_intest', 'LIKE', '%SALATINO%');
                })
                ->orderBy('RagAbbrev')
                ->get();
        }

        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        $allStaff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        $staff = $allStaff->filter(function($person) use ($selectedOwnershipId, $startOfMonth, $endOfMonth) {
            if (empty($selectedOwnershipId)) {
                return true;
            }

            $assExp = $this->findLatestAssunzione($person, $selectedOwnershipId);
            if ($assExp) {
                return true;
            }

            $hasEvent = $person->expirations->filter(fn($e) =>
                $e->table_references === 'staff' && 
                collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                    str_contains(strtolower($e->titolo ?? ''), $k)
                )
            )->first(function($e) use ($startOfMonth, $endOfMonth) {
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine) : null;
                if (!$di) return false;
                if ($df) {
                    return $di <= $endOfMonth && $df >= $startOfMonth;
                }
                return $di >= $startOfMonth && $di <= $endOfMonth;
            });

            return $hasEvent !== null;
        });

        $staffIds = $staff->pluck('id_personale')->toArray();

        $attendanceIndex = [];
        foreach ($staffIds as $sid) {
            $jsonData = StaffAttendanceJson::getForMonth($sid, (int) $selectedYear, (int) $selectedMonth);
            if ($jsonData && isset($jsonData['presenze'])) {
                foreach ($jsonData['presenze'] as $presenza) {
                    $date = $presenza['data'];
                    $owId = $presenza['id_ownership'] ?? null;
                    if (!empty($selectedOwnershipId) && $owId != $selectedOwnershipId) {
                        continue;
                    }
                    $attendanceIndex[$sid][$date][$owId] = $presenza;
                }
            }
        }

        $activitiesQuery = Activity::whereHas('staffDetails', fn($q) => $q->whereIn('id_staff', $staffIds))
            ->with(['staffDetails' => fn($q) => $q->whereIn('id_staff', $staffIds)])
            ->whereBetween('data_activities', [$startOfMonth, $endOfMonth]);

        $activities = $activitiesQuery->get();

        $activityIndex = [];
        foreach ($activities as $act) {
            $dk = Carbon::parse($act->data_activities)->format('Y-m-d');
            foreach ($act->staffDetails as $sd) {
                if (!in_array($sd->id_staff, $staffIds)) continue;
                if (!isset($activityIndex[$sd->id_staff][$dk])) {
                    $activityIndex[$sd->id_staff][$dk] = 0;
                }
                $activityIndex[$sd->id_staff][$dk]++;
            }
        }

        $presenzeMatrix = [];
        $totaliGiornate = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;

            $assExp = $this->findLatestAssunzione($person, $selectedOwnershipId);

            $dataAss = $assExp && $assExp->data_inizio
                ? Carbon::parse($assExp->data_inizio)->startOfDay() : null;
            $dataLic = $assExp && $assExp->data_fine
                ? Carbon::parse($assExp->data_fine)->endOfDay() : null;

            $assenze = $person->expirations->filter(fn($e) =>
                $e->table_references === 'staff' && 
                collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                    str_contains(strtolower($e->titolo ?? ''), $k)
                )
            );

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');
                $isFestivo = $this->isFestivo($giorno);

                $isInAss = true;
                if ($dataAss && $giorno->lt($dataAss)) $isInAss = false;
                if ($dataLic && $giorno->gt($dataLic)) $isInAss = false;

                $expiration = $assenze->first(function ($e) use ($dk) {
                    $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
                    $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
                    return $di && $df && $di <= $dk && $df >= $dk;
                });

                $causale = null;
                if ($expiration) {
                    $t = strtolower($expiration->titolo ?? '');
                    if (str_contains($t, 'malattia'))        $causale = 'malattia';
                    elseif (str_contains($t, 'ferie'))       $causale = 'ferie';
                    elseif (str_contains($t, 'permesso'))    $causale = 'permesso';
                    elseif (str_contains($t, 'assenza'))     $causale = 'assenza';
                }

                $checked = false;
                if ($isInAss && !$isFestivo && !$causale) {
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
                    'checked' => $checked,
                    'is_in_assunzione' => $isInAss,
                    'causale' => $causale,
                    'is_sunday' => $giorno->isSunday(),
                    'is_festivo' => $isFestivo,
                    'has_activity' => ($activityIndex[$pid][$dk] ?? 0) > 0,
                ];
            }
        }

        $staffPerGruppo = $staff->groupBy(fn($p) => $p->gruppo->nome ?? 'Senza categoria');
        $festiviNazionali = $this->festiviNazionali;

        $pdf = PDF::loadView('admin.staff.attendance.export-pdf', compact(
            'staff', 'staffPerGruppo', 'ownerships', 'giorni', 'presenzeMatrix', 'totaliGiornate',
            'selectedYear', 'selectedMonth', 'selectedOwnershipId',
            'festiviNazionali'
        ));

        $pdf->setPaper('A3', 'landscape');
        
        return $pdf->download('presenze_' . $selectedYear . '_' . $selectedMonth . '.pdf');
    }

    /**
     * EXPORT EXCEL — Esporta la matrice in Excel
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $currentMonth = $request->get('month', now()->format('Y-m'));
        [$selectedYear, $selectedMonth] = explode('-', $currentMonth);
        $selectedOwnershipId = $request->get('ownership_id', '');

        $ownerships = $this->getFilteredOwnerships();
        if ($ownerships->isEmpty()) {
            $ownerships = Ownership::where('valid', 1)
                ->where(function($query) {
                    $query->where('RagAbbrev', 'LIKE', '%SALATINO%')
                        ->orWhere('Rag_Soc_intest', 'LIKE', '%SALATINO%');
                })
                ->orderBy('RagAbbrev')
                ->get();
        }

        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        $allStaff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        $staff = $allStaff->filter(function($person) use ($selectedOwnershipId, $startOfMonth, $endOfMonth) {
            if (empty($selectedOwnershipId)) {
                return true;
            }

            $assExp = $this->findLatestAssunzione($person, $selectedOwnershipId);
            if ($assExp) {
                return true;
            }

            $hasEvent = $person->expirations->filter(fn($e) =>
                $e->table_references === 'staff' && 
                collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                    str_contains(strtolower($e->titolo ?? ''), $k)
                )
            )->first(function($e) use ($startOfMonth, $endOfMonth) {
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine) : null;
                if (!$di) return false;
                if ($df) {
                    return $di <= $endOfMonth && $df >= $startOfMonth;
                }
                return $di >= $startOfMonth && $di <= $endOfMonth;
            });

            return $hasEvent !== null;
        });

        $staffIds = $staff->pluck('id_personale')->toArray();

        $attendanceIndex = [];
        foreach ($staffIds as $sid) {
            $jsonData = StaffAttendanceJson::getForMonth($sid, (int) $selectedYear, (int) $selectedMonth);
            if ($jsonData && isset($jsonData['presenze'])) {
                foreach ($jsonData['presenze'] as $presenza) {
                    $date = $presenza['data'];
                    $owId = $presenza['id_ownership'] ?? null;
                    if (!empty($selectedOwnershipId) && $owId != $selectedOwnershipId) {
                        continue;
                    }
                    $attendanceIndex[$sid][$date][$owId] = $presenza;
                }
            }
        }

        $activitiesQuery = Activity::whereHas('staffDetails', fn($q) => $q->whereIn('id_staff', $staffIds))
            ->with(['staffDetails' => fn($q) => $q->whereIn('id_staff', $staffIds)])
            ->whereBetween('data_activities', [$startOfMonth, $endOfMonth]);

        $activities = $activitiesQuery->get();

        $activityIndex = [];
        foreach ($activities as $act) {
            $dk = Carbon::parse($act->data_activities)->format('Y-m-d');
            foreach ($act->staffDetails as $sd) {
                if (!in_array($sd->id_staff, $staffIds)) continue;
                if (!isset($activityIndex[$sd->id_staff][$dk])) {
                    $activityIndex[$sd->id_staff][$dk] = 0;
                }
                $activityIndex[$sd->id_staff][$dk]++;
            }
        }

        $presenzeMatrix = [];
        $totaliGiornate = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;

            $assExp = $this->findLatestAssunzione($person, $selectedOwnershipId);

            $dataAss = $assExp && $assExp->data_inizio
                ? Carbon::parse($assExp->data_inizio)->startOfDay() : null;
            $dataLic = $assExp && $assExp->data_fine
                ? Carbon::parse($assExp->data_fine)->endOfDay() : null;

            $assenze = $person->expirations->filter(fn($e) =>
                $e->table_references === 'staff' && 
                collect(['malattia','ferie','permesso','assenza'])->contains(fn($k) => 
                    str_contains(strtolower($e->titolo ?? ''), $k)
                )
            );

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');
                $isFestivo = $this->isFestivo($giorno);

                $isInAss = true;
                if ($dataAss && $giorno->lt($dataAss)) $isInAss = false;
                if ($dataLic && $giorno->gt($dataLic)) $isInAss = false;

                $expiration = $assenze->first(function ($e) use ($dk) {
                    $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
                    $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
                    return $di && $df && $di <= $dk && $df >= $dk;
                });

                $causale = null;
                if ($expiration) {
                    $t = strtolower($expiration->titolo ?? '');
                    if (str_contains($t, 'malattia'))        $causale = 'malattia';
                    elseif (str_contains($t, 'ferie'))       $causale = 'ferie';
                    elseif (str_contains($t, 'permesso'))    $causale = 'permesso';
                    elseif (str_contains($t, 'assenza'))     $causale = 'assenza';
                }

                $checked = false;
                if ($isInAss && !$isFestivo && !$causale) {
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
                    'checked' => $checked,
                    'is_in_assunzione' => $isInAss,
                    'causale' => $causale,
                    'is_sunday' => $giorno->isSunday(),
                    'is_festivo' => $isFestivo,
                    'has_activity' => ($activityIndex[$pid][$dk] ?? 0) > 0,
                ];
            }
        }

        $staffPerGruppo = $staff->groupBy(fn($p) => $p->gruppo->nome ?? 'Senza categoria');
        $festiviNazionali = $this->festiviNazionali;

        // ── CREA EXCEL ─────────────────────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Titolo
        $sheet->setCellValue('A' . $row, 'GESTIONE PRESENZE ' . strtoupper(Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('it')->isoFormat('MMMM YYYY')));
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row++;

        // Intestazione colonne
        $sheet->setCellValue('A' . $row, 'DIPENDENTE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(30);

        $col = 2;
        foreach ($giorni as $giorno) {
            $isFestivo = in_array($giorno->format('m-d'), $festiviNazionali) || $giorno->isSunday();
            $cell = $sheet->getCell([$col, $row]);
            $cell->setValue($giorno->format('j'));
            $sheet->getColumnDimensionByColumn($col)->setWidth(4);
            
            if ($isFestivo) {
                $sheet->getStyle([$col, $row])->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FEF2F2');
                $sheet->getStyle([$col, $row])->getFont()->getColor()->setRGB('DC2626');
            }
            $col++;
        }

        // N.GG
        $sheet->setCellValue([$col, $row], 'N.GG');
        $sheet->getColumnDimensionByColumn($col)->setWidth(8);
        $sheet->getStyle([$col, $row])->getFont()->setBold(true);
        $col++;

        // Dati
        $row++;

        foreach ($staffPerGruppo as $gruppoNome => $persone) {
            // Intestazione gruppo
            $sheet->setCellValue('A' . $row, $gruppoNome);
            $sheet->mergeCells('A' . $row . ':' . $sheet->getHighestColumn() . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('1F2937');
            $sheet->getStyle('A' . $row)->getFont()->getColor()->setRGB('FFFFFF');
            $row++;

            foreach ($persone as $person) {
                $personPresenze = $presenzeMatrix[$person->id_personale] ?? [];
                $nGG = $totaliGiornate[$person->id_personale] ?? 0;

                $sheet->setCellValue('A' . $row, strtoupper($person->CognomePers) . ' ' . $person->NomePers);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);

                $col = 2;
                foreach ($giorni as $giorno) {
                    $dk = $giorno->format('Y-m-d');
                    $isFestivo = in_array($giorno->format('m-d'), $festiviNazionali) || $giorno->isSunday();
                    
                    $cellData = $personPresenze[$dk] ?? null;
                    $isInAssunzione = $cellData['is_in_assunzione'] ?? false;
                    $causale = $cellData['causale'] ?? null;
                    $isPresent = $cellData['checked'] ?? false;
                    $hasActivity = $cellData['has_activity'] ?? false;

                    $value = '';
                    $bgColor = null;
                    $textColor = null;

                    if ($isFestivo) {
                        $bgColor = 'FEF2F2';
                        $textColor = 'DC2626';
                    } elseif (!$isInAssunzione) {
                        $bgColor = 'F3F4F6';
                        $textColor = '9CA3AF';
                    } elseif ($causale === 'malattia') {
                        $bgColor = 'EFF6FF';
                        $textColor = '1D4ED8';
                        $value = 'M';
                    } elseif ($causale === 'ferie' || $causale === 'permesso') {
                        $bgColor = 'FEFCE8';
                        $textColor = '92400E';
                        $value = $causale === 'ferie' ? 'F' : 'P';
                    } elseif ($isPresent) {
                        $value = 'X';
                        $textColor = '000000';
                    } elseif ($hasActivity) {
                        $value = '●';
                        $textColor = '3B82F6';
                    }

                    $cell = $sheet->getCell([$col, $row]);
                    $cell->setValue($value);
                    $sheet->getStyle([$col, $row])->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    if ($bgColor) {
                        $sheet->getStyle([$col, $row])->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB($bgColor);
                    }

                    if ($textColor) {
                        $sheet->getStyle([$col, $row])->getFont()->getColor()->setRGB($textColor);
                    }

                    $col++;
                }

                $sheet->setCellValue([$col, $row], $nGG);
                $sheet->getStyle([$col, $row])->getFont()->setBold(true);

                $row++;
            }
        }

        // BORDI
        $lastRow = $row - 1;
        $lastCol = $col;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray($styleArray);

        // DOWNLOAD
        $filename = 'presenze_' . $selectedYear . '_' . $selectedMonth . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }
}