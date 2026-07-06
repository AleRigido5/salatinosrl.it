<?php
// app/Http/Controllers/Admin/StaffAttendanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Ownership;
use App\Models\Activity;
use App\Models\StaffAttendance;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class StaffAttendanceController extends Controller
{
    /**
     * Festivi nazionali italiani fissi (formato m-d)
     */
    private $festiviNazionali = [
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
     * Nomi dei giorni della settimana in italiano
     */
    private $giorniSettimana = [
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mer',
        'Thu' => 'Gio',
        'Fri' => 'Ven',
        'Sat' => 'Sab',
        'Sun' => 'Dom',
    ];

    /**
     * Nomi dei mesi in italiano
     */
    private $mesi = [
        1 => 'Gennaio',
        2 => 'Febbraio',
        3 => 'Marzo',
        4 => 'Aprile',
        5 => 'Maggio',
        6 => 'Giugno',
        7 => 'Luglio',
        8 => 'Agosto',
        9 => 'Settembre',
        10 => 'Ottobre',
        11 => 'Novembre',
        12 => 'Dicembre',
    ];

    /**
     * Recupera le ownership filtrate per SALATINO
     */
    private function getFilteredOwnerships()
    {
        $ownerships = Ownership::where('valid', 1)
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

        return $ownerships;
    }

    /**
     * Controlla se una data è festiva (domenica o festivo nazionale)
     */
    private function isFestivo(Carbon $date)
    {
        return $date->isSunday() || in_array($date->format('m-d'), $this->festiviNazionali);
    }

    /**
     * Restituisce TUTTI i periodi di assunzione di un dipendente
     */
    private function getAssunzionePeriods($person, $selectedOwnershipId = null)
    {
        $assunzioni = $person->expirations->filter(function($e) {
            return $e->table_references === 'staff' &&
                str_contains(strtolower($e->titolo ?? ''), 'assunzione');
        });

        if (!empty($selectedOwnershipId)) {
            $assunzioni = $assunzioni->filter(function($e) use ($selectedOwnershipId) {
                return isset($e->id_ownership) && $e->id_ownership == $selectedOwnershipId;
            });
        }

        return $assunzioni->map(function ($e) {
            return [
                'inizio' => $e->data_inizio ? Carbon::parse($e->data_inizio)->startOfDay() : null,
                'fine'   => $e->data_fine   ? Carbon::parse($e->data_fine)->endOfDay()     : null,
            ];
        })->values();
    }

    /**
     * Verifica se una data ricade in uno qualsiasi dei periodi di assunzione
     */
    private function isDateInAssunzionePeriods(Carbon $date, $periods)
    {
        if ($periods->isEmpty()) {
            return true;
        }

        foreach ($periods as $periodo) {
            if ($periodo['inizio'] && $date->lt($periodo['inizio'])) continue;
            if ($periodo['fine']   && $date->gt($periodo['fine']))   continue;
            return true;
        }

        return false;
    }

    /**
     * Recupera le assenze (malattia, ferie, permesso) per un dipendente in un periodo
     */
    private function getAssenzeForPeriod($person, Carbon $start, Carbon $end)
    {
        return $person->expirations->filter(function($e) use ($start, $end) {
            if ($e->table_references !== 'staff') return false;
            
            $tipiAssenza = ['malattia', 'ferie', 'permesso', 'assenza'];
            $found = false;
            foreach ($tipiAssenza as $tipo) {
                if (str_contains(strtolower($e->titolo ?? ''), $tipo)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) return false;
            
            $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
            $df = $e->data_fine   ? Carbon::parse($e->data_fine)   : null;
            if (!$di) return false;
            if ($df) {
                return $di <= $end && $df >= $start;
            }
            return $di >= $start && $di <= $end;
        });
    }

    /**
     * Recupera la causale di assenza per una data specifica
     */
    private function getCausaleForDate($assenze, $date)
    {
        $expiration = null;
        foreach ($assenze as $e) {
            $di = $e->data_inizio ? Carbon::parse($e->data_inizio)->format('Y-m-d') : null;
            $df = $e->data_fine   ? Carbon::parse($e->data_fine)->format('Y-m-d')   : null;
            if ($di && $df && $di <= $date && $df >= $date) {
                $expiration = $e;
                break;
            }
        }

        if (!$expiration) return null;

        $t = strtolower($expiration->titolo ?? '');
        if (str_contains($t, 'malattia')) return 'malattia';
        if (str_contains($t, 'ferie')) return 'ferie';
        if (str_contains($t, 'permesso')) return 'permesso';
        if (str_contains($t, 'assenza')) return 'assenza';

        return null;
    }

    /**
     * ============================================================
     * INDEX - Matrice presenze di tutti i dipendenti
     * ============================================================
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        // Parametri
        $currentMonth = $request->get('month', now()->format('Y-m'));
        list($selectedYear, $selectedMonth) = explode('-', $currentMonth);
        $selectedOwnershipId = $request->get('ownership_id', '');

        // Ownerships
        $ownerships = $this->getFilteredOwnerships();

        // Date del mese
        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        // Staff validi con relazioni
        $allStaff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        // Filtra dipendenti per ownership o eventi nel mese
        $staff = $allStaff->filter(function($person) use ($selectedOwnershipId, $startOfMonth, $endOfMonth) {
            if (empty($selectedOwnershipId)) {
                return true;
            }

            // Controlla se ha assunzione per questa ownership
            $assunzioni = $person->expirations->filter(function($e) use ($selectedOwnershipId) {
                return $e->table_references === 'staff' &&
                    str_contains(strtolower($e->titolo ?? ''), 'assunzione') &&
                    isset($e->id_ownership) && $e->id_ownership == $selectedOwnershipId;
            });

            if ($assunzioni->isNotEmpty()) {
                return true;
            }

            // Controlla se ha eventi (malattia, ferie, etc) nel mese per questa ownership
            $hasEvent = null;
            $tipiEvento = ['malattia', 'ferie', 'permesso', 'assenza'];
            
            foreach ($person->expirations as $e) {
                if ($e->table_references !== 'staff') continue;
                
                $found = false;
                foreach ($tipiEvento as $tipo) {
                    if (str_contains(strtolower($e->titolo ?? ''), $tipo)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) continue;
                
                if (!empty($selectedOwnershipId) && isset($e->id_ownership) && $e->id_ownership != $selectedOwnershipId) {
                    continue;
                }
                
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine) : null;
                if (!$di) continue;
                if ($df) {
                    if ($di <= $endOfMonth && $df >= $startOfMonth) {
                        $hasEvent = $e;
                        break;
                    }
                } else {
                    if ($di >= $startOfMonth && $di <= $endOfMonth) {
                        $hasEvent = $e;
                        break;
                    }
                }
            }

            return $hasEvent !== null;
        });

        $staffIds = $staff->pluck('id_personale')->toArray();

        // ── PRESENZE DAL DATABASE ──
        $attendanceQuery = StaffAttendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('id_staff', $staffIds);

        if (!empty($selectedOwnershipId)) {
            $attendanceQuery->where('id_ownership', $selectedOwnershipId);
        }

        $attendances = $attendanceQuery->get();

        $attendanceIndex = [];
        foreach ($attendances as $att) {
            $staffId = $att->id_staff;
            $date = $att->date->format('Y-m-d');
            $ownershipId = $att->id_ownership;

            if (!isset($attendanceIndex[$staffId])) {
                $attendanceIndex[$staffId] = [];
            }
            if (!isset($attendanceIndex[$staffId][$date])) {
                $attendanceIndex[$staffId][$date] = [];
            }

            $attendanceIndex[$staffId][$date][$ownershipId] = [
                'is_present' => $att->is_present,
            ];
        }

        // ── ATTIVITÀ ──
        $activitiesQuery = Activity::whereHas('staffDetails', function($q) use ($staffIds) {
                $q->whereIn('id_staff', $staffIds);
            })
            ->with(['staffDetails' => function($q) use ($staffIds) {
                $q->whereIn('id_staff', $staffIds);
            }])
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

        // ── COSTRUISCI MATRICE ──
        $presenzeMatrix = [];
        $totaliGiornate = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;

            $assunzioniPeriodi = $this->getAssunzionePeriods($person, $selectedOwnershipId);
            $assenze = $this->getAssenzeForPeriod($person, $startOfMonth, $endOfMonth);

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');
                $isFestivo = $this->isFestivo($giorno);
                $isInAss = $this->isDateInAssunzionePeriods($giorno, $assunzioniPeriodi);
                $causale = $this->getCausaleForDate($assenze, $dk);

                // Controlla presenza nel database
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
                    'has_activity'      => isset($activityIndex[$pid][$dk]) && $activityIndex[$pid][$dk] > 0,
                ];
            }
        }

        $staffPerGruppo = $staff->groupBy(function($p) {
            return $p->gruppo->nome ?? 'Senza categoria';
        });

        return view('admin.staff.attendance.index', compact(
            'staff',
            'staffPerGruppo',
            'ownerships',
            'giorni',
            'presenzeMatrix',
            'totaliGiornate',
            'selectedYear',
            'selectedMonth',
            'selectedOwnershipId'
        ) + ['festiviNazionali' => $this->festiviNazionali]);
    }

    /**
     * ============================================================
     * SHOW - Presenze di un singolo dipendente
     * ============================================================
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

        // Periodi di assunzione
        $assunzioniPeriodi = $this->getAssunzionePeriods($staff);

        // Ultima assunzione per info
        $assExpUltima = null;
        foreach ($staff->expirations as $e) {
            if ($e->table_references === 'staff' && str_contains(strtolower($e->titolo ?? ''), 'assunzione')) {
                if ($assExpUltima === null) {
                    $assExpUltima = $e;
                } else {
                    // Scegli quella con data_fine più recente o null
                    if ($e->data_fine === null) {
                        $assExpUltima = $e;
                    } elseif ($assExpUltima->data_fine !== null && $e->data_fine > $assExpUltima->data_fine) {
                        $assExpUltima = $e;
                    }
                }
            }
        }

        $dataAssunzione = $assExpUltima && $assExpUltima->data_inizio
            ? Carbon::parse($assExpUltima->data_inizio)->startOfDay()
            : null;
        $dataLicenziamento = $assExpUltima && $assExpUltima->data_fine
            ? Carbon::parse($assExpUltima->data_fine)->endOfDay()
            : null;

        // Assenze nel mese
        $assenze = $this->getAssenzeForPeriod($staff, $startDate, $endDate);

        // Ownerships
        $ownerships = $this->getFilteredOwnerships();

        // ── ATTIVITÀ ──
        $activities = Activity::whereHas('staffDetails', function($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            })
            ->with([
                'staffDetails' => function($q) use ($staffId) {
                    $q->where('id_staff', $staffId);
                },
                'costCenter',
            ])
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->get();

        $activitiesByDate = [];
        foreach ($activities as $act) {
            $key = Carbon::parse($act->data_activities)->format('Y-m-d');
            if (!isset($activitiesByDate[$key])) {
                $activitiesByDate[$key] = collect();
            }
            $activitiesByDate[$key]->push($act);
        }

        $ownershipMap = Ownership::where('valid', 1)->get()->keyBy('id_proprieta');

        // ── PRESENZE DAL DATABASE ──
        $attendances = StaffAttendance::where('id_staff', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $savedIndex = [];
        $totaliPerOwnership = [];
        foreach ($ownerships as $ow) {
            $totaliPerOwnership[$ow->id_proprieta] = 0;
        }

        foreach ($attendances as $att) {
            $date = $att->date->format('Y-m-d');
            $owId = $att->id_ownership;

            if ($owId && !$ownerships->contains('id_proprieta', $owId)) {
                continue;
            }

            if (!isset($savedIndex[$date])) {
                $savedIndex[$date] = [];
            }
            $savedIndex[$date][$owId] = [
                'is_present' => $att->is_present,
            ];

            if ($att->is_present && $owId && isset($totaliPerOwnership[$owId])) {
                $totaliPerOwnership[$owId]++;
            }
        }

        // ── COSTRUISCI ARRAY PRESENZE ──
        $presenze = [];
        $current = $startDate->copy();

        while ($current <= $endDate) {
            $dk = $current->format('Y-m-d');
            $isSunday = $current->isSunday();
            $isFestivo = $this->isFestivo($current);
            $isInAss = $this->isDateInAssunzionePeriods($current, $assunzioniPeriodi);
            $causale = $this->getCausaleForDate($assenze, $dk);

            // Attività del giorno
            $dayActivities = isset($activitiesByDate[$dk]) ? $activitiesByDate[$dk] : collect();
            $oreGiorno = 0;
            $dettagli = [];
            $cantieriNomi = [];
            $ownershipIds = [];
            $localita = null;

            foreach ($dayActivities as $act) {
                $sd = $act->staffDetails->first();
                if (!$sd) continue;

                $ore = floatval($sd->n_ore ?? 0);
                if ($ore == 0 && $sd->att_start && $sd->att_end) {
                    try {
                        $ore = max(0, Carbon::parse($sd->att_start)->diffInHours(Carbon::parse($sd->att_end)));
                    } catch (\Exception $e) {
                        $ore = 0;
                    }
                }
                $oreGiorno += $ore;

                $owId = null;
                $owName = null;
                $ccName = null;

                if ($act->costCenter) {
                    $cc = $act->costCenter;
                    $ccName = $cc->Nome ?? null;

                    if (!$localita && !empty($cc->Localita)) {
                        $localita = $cc->Localita;
                    }

                    if ($cc->table_references === 'ownership' && $cc->id_references) {
                        $owId = $cc->id_references;
                        $owName = isset($ownershipMap[$owId]) ? $ownershipMap[$owId]->RagAbbrev : null;
                        if ($ownerships->contains('id_proprieta', $owId)) {
                            $ownershipIds[] = $owId;
                            if ($owName) $cantieriNomi[] = $owName;
                        }
                    }
                }

                $dettagli[] = [
                    'id_ownership' => $owId,
                    'nome_ownership' => $owName ?? $ccName ?? 'N/A',
                    'cost_center_name' => $ccName,
                    'localita' => $localita,
                    'att_start' => $sd->att_start ?? null,
                    'att_end' => $sd->att_end ?? null,
                    'n_ore' => $ore,
                    'is_ownership' => ($act->costCenter && $act->costCenter->table_references === 'ownership'),
                ];
            }

            $ownershipIdsUnici = array_unique($ownershipIds);
            $cantieriNomiUnici = array_unique($cantieriNomi);
            $numCantieri = count($cantieriNomiUnici);

            // ownership_checked dal database
            $ownershipChecked = [];
            foreach ($ownerships as $ow) {
                $owId = $ow->id_proprieta;
                $ownershipChecked[$owId] = isset($savedIndex[$dk][$owId])
                    ? ($savedIndex[$dk][$owId]['is_present'] ?? false)
                    : false;
            }

            $giornoSettimana = isset($this->giorniSettimana[$current->isoFormat('ddd')]) 
                ? $this->giorniSettimana[$current->isoFormat('ddd')] 
                : $current->isoFormat('ddd');

            $presenze[$dk] = [
                'data' => $dk,
                'giorno_settimana' => $giornoSettimana,
                'giorno_num' => $current->day,
                'is_sunday' => $isSunday,
                'is_festivo' => $isFestivo,
                'is_in_assunzione' => $isInAss,
                'causale' => $causale,
                'ore' => $isInAss ? $oreGiorno : 0,
                'localita' => $localita,
                'dettagli' => $isInAss ? $dettagli : [],
                'cantieri_string' => implode(', ', $cantieriNomiUnici),
                'num_cantieri' => $numCantieri,
                'has_multiple' => $numCantieri > 1,
                'ownership_checked' => $ownershipChecked,
                'has_activity' => $oreGiorno > 0,
                'saved_data' => isset($savedIndex[$dk]) ? $savedIndex[$dk] : null,
            ];

            $current->addDay();
        }

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
            'totaliPerOwnership'
        ) + ['festiviNazionali' => $this->festiviNazionali]);
    }

    /**
     * ============================================================
     * SAVE - Salva le presenze nel database
     * ============================================================
     */
    public function save(Request $request)
    {
        try {
            if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permessi insufficienti'
                ], 403);
            }

            $changes = $request->input('changes', []);

            if (empty($changes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessuna modifica da salvare'
                ]);
            }

            // Validazione
            foreach ($changes as $change) {
                $validator = validator($change, [
                    'staff_id' => 'required|integer|exists:staff,id_personale',
                    'date' => 'required|date_format:Y-m-d',
                    'checked' => 'required|boolean',
                    'ownership_id' => 'nullable|integer|exists:ownership,id_proprieta',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Dati non validi: ' . $validator->errors()->first(),
                    ], 422);
                }
            }

            // Salva usando il modello
            $results = StaffAttendance::saveMany($changes);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' presenze salvate con successo',
                'results' => $results,
                'saved_count' => count($results),
            ]);

        } catch (\Exception $e) {
            Log::error('Save attendance error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * GET STAFF DATA - API per AJAX
     * ============================================================
     */
    public function getStaffData(Request $request, $staffId)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $staff = Staff::with(['gruppo', 'expirations'])->findOrFail($staffId);
        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // Presenze del mese
        $attendances = StaffAttendance::where('id_staff', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->where('is_present', true)
            ->get();

        $totalPresenze = $attendances->count();
        $uniqueDays = $attendances->groupBy('date')->count();

        // Ore lavorate (da attività)
        $activities = Activity::whereHas('staffDetails', function($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            })
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->with('staffDetails')
            ->get();

        $totalOre = 0;
        foreach ($activities as $act) {
            $sd = $act->staffDetails->first();
            if ($sd) {
                $ore = floatval($sd->n_ore ?? 0);
                if ($ore == 0 && $sd->att_start && $sd->att_end) {
                    try {
                        $ore = max(0, Carbon::parse($sd->att_start)->diffInHours(Carbon::parse($sd->att_end)));
                    } catch (\Exception $e) {
                        $ore = 0;
                    }
                }
                $totalOre += $ore;
            }
        }

        $attendancesByDate = [];
        foreach ($attendances->groupBy('date') as $date => $items) {
            $attendancesByDate[$date->format('Y-m-d')] = $items->map(function($item) {
                return [
                    'ownership_id' => $item->id_ownership,
                    'ownership_name' => $item->ownership ? $item->ownership->RagAbbrev : 'N/A',
                ];
            });
        }

        return response()->json([
            'success' => true,
            'staff' => [
                'id' => $staff->id_personale,
                'name' => $staff->CognomePers . ' ' . $staff->NomePers,
                'gruppo' => $staff->gruppo ? $staff->gruppo->nome : 'Senza categoria',
            ],
            'stats' => [
                'total_presenze' => $totalPresenze,
                'unique_days' => $uniqueDays,
                'total_ore' => round($totalOre, 1),
                'media_giorno' => $uniqueDays > 0 ? round($totalOre / $uniqueDays, 1) : 0,
            ],
            'attendances' => $attendancesByDate,
        ]);
    }

    /**
     * ============================================================
     * REPORT - Riepilogo mensile di tutti i dipendenti
     * ============================================================
     */
    public function report(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $ownershipId = $request->get('ownership_id');

        $ownerships = $this->getFilteredOwnerships();

        // Riepilogo da database
        $report = StaffAttendance::getMonthlyReport($year, $month, $ownershipId);

        // Aggiungi dettagli per ogni dipendente
        $staffIds = array_keys($report);
        if (!empty($staffIds)) {
            $staffData = Staff::whereIn('id_personale', $staffIds)
                ->with(['gruppo'])
                ->get()
                ->keyBy('id_personale');

            foreach ($report as $staffId => &$data) {
                $staff = isset($staffData[$staffId]) ? $staffData[$staffId] : null;
                $data['name'] = $staff ? $staff->CognomePers . ' ' . $staff->NomePers : 'Sconosciuto';
                $data['gruppo'] = $staff && $staff->gruppo ? $staff->gruppo->nome : 'Senza categoria';
            }

            // Ordina per gruppo e nome
            usort($report, function($a, $b) {
                if ($a['gruppo'] !== $b['gruppo']) {
                    return strcmp($a['gruppo'], $b['gruppo']);
                }
                return strcmp($a['name'], $b['name']);
            });
        }

        // Statistiche totali
        $totalStaff = count($report);
        $totalPresenze = array_sum(array_column($report, 'total'));
        $totalUniqueDays = array_sum(array_column($report, 'unique_days'));
        $mediaPerStaff = $totalStaff > 0 ? round($totalUniqueDays / $totalStaff, 1) : 0;

        return view('admin.staff.attendance.report', compact(
            'report',
            'year',
            'month',
            'ownershipId',
            'ownerships',
            'totalStaff',
            'totalPresenze',
            'totalUniqueDays',
            'mediaPerStaff'
        ));
    }

    /**
     * ============================================================
     * EXPORT PDF - Matrice presenze
     * ============================================================
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $currentMonth = $request->get('month', now()->format('Y-m'));
        list($selectedYear, $selectedMonth) = explode('-', $currentMonth);
        $selectedOwnershipId = $request->get('ownership_id', '');

        $ownerships = $this->getFilteredOwnerships();

        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        // Staff
        $allStaff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        $staff = $allStaff->filter(function($person) use ($selectedOwnershipId, $startOfMonth, $endOfMonth) {
            if (empty($selectedOwnershipId)) return true;

            $assunzioni = $person->expirations->filter(function($e) use ($selectedOwnershipId) {
                return $e->table_references === 'staff' &&
                    str_contains(strtolower($e->titolo ?? ''), 'assunzione') &&
                    isset($e->id_ownership) && $e->id_ownership == $selectedOwnershipId;
            });

            if ($assunzioni->isNotEmpty()) return true;

            $tipiEvento = ['malattia', 'ferie', 'permesso', 'assenza'];
            foreach ($person->expirations as $e) {
                if ($e->table_references !== 'staff') continue;
                
                $found = false;
                foreach ($tipiEvento as $tipo) {
                    if (str_contains(strtolower($e->titolo ?? ''), $tipo)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) continue;
                
                if (!empty($selectedOwnershipId) && isset($e->id_ownership) && $e->id_ownership != $selectedOwnershipId) {
                    continue;
                }
                
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine) : null;
                if (!$di) continue;
                if ($df) {
                    if ($di <= $endOfMonth && $df >= $startOfMonth) return true;
                } else {
                    if ($di >= $startOfMonth && $di <= $endOfMonth) return true;
                }
            }

            return false;
        });

        $staffIds = $staff->pluck('id_personale')->toArray();

        // Presenze dal database
        $attendanceQuery = StaffAttendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('id_staff', $staffIds);

        if (!empty($selectedOwnershipId)) {
            $attendanceQuery->where('id_ownership', $selectedOwnershipId);
        }

        $attendances = $attendanceQuery->get();

        $attendanceIndex = [];
        foreach ($attendances as $att) {
            $staffId = $att->id_staff;
            $date = $att->date->format('Y-m-d');
            $ownershipId = $att->id_ownership;

            if (!isset($attendanceIndex[$staffId])) {
                $attendanceIndex[$staffId] = [];
            }
            if (!isset($attendanceIndex[$staffId][$date])) {
                $attendanceIndex[$staffId][$date] = [];
            }

            $attendanceIndex[$staffId][$date][$ownershipId] = [
                'is_present' => $att->is_present,
            ];
        }

        // Attività
        $activitiesQuery = Activity::whereHas('staffDetails', function($q) use ($staffIds) {
                $q->whereIn('id_staff', $staffIds);
            })
            ->with(['staffDetails' => function($q) use ($staffIds) {
                $q->whereIn('id_staff', $staffIds);
            }])
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

        // Matrice
        $presenzeMatrix = [];
        $totaliGiornate = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;
            $assunzioniPeriodi = $this->getAssunzionePeriods($person, $selectedOwnershipId);
            $assenze = $this->getAssenzeForPeriod($person, $startOfMonth, $endOfMonth);

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');
                $isFestivo = $this->isFestivo($giorno);
                $isInAss = $this->isDateInAssunzionePeriods($giorno, $assunzioniPeriodi);
                $causale = $this->getCausaleForDate($assenze, $dk);

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
                    'has_activity' => isset($activityIndex[$pid][$dk]) && $activityIndex[$pid][$dk] > 0,
                ];
            }
        }

        $staffPerGruppo = $staff->groupBy(function($p) {
            return $p->gruppo->nome ?? 'Senza categoria';
        });

        $pdf = PDF::loadView('admin.staff.attendance.export-pdf', compact(
            'staff',
            'staffPerGruppo',
            'ownerships',
            'giorni',
            'presenzeMatrix',
            'totaliGiornate',
            'selectedYear',
            'selectedMonth',
            'selectedOwnershipId'
        ) + ['festiviNazionali' => $this->festiviNazionali]);

        $pdf->setPaper('A3', 'landscape');

        return $pdf->download('presenze_' . $selectedYear . '_' . $selectedMonth . '.pdf');
    }

    /**
     * ============================================================
     * EXPORT EXCEL - Matrice presenze
     * ============================================================
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $currentMonth = $request->get('month', now()->format('Y-m'));
        list($selectedYear, $selectedMonth) = explode('-', $currentMonth);
        $selectedOwnershipId = $request->get('ownership_id', '');

        $ownerships = $this->getFilteredOwnerships();

        $startOfMonth = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfDay();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $giorni = collect(CarbonPeriod::create($startOfMonth, $endOfMonth)->toArray());

        // Staff e dati (stessa logica dell'export PDF)
        $allStaff = Staff::where('valid', 1)
            ->with(['gruppo', 'expirations'])
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        $staff = $allStaff->filter(function($person) use ($selectedOwnershipId, $startOfMonth, $endOfMonth) {
            if (empty($selectedOwnershipId)) return true;

            $assunzioni = $person->expirations->filter(function($e) use ($selectedOwnershipId) {
                return $e->table_references === 'staff' &&
                    str_contains(strtolower($e->titolo ?? ''), 'assunzione') &&
                    isset($e->id_ownership) && $e->id_ownership == $selectedOwnershipId;
            });

            if ($assunzioni->isNotEmpty()) return true;

            $tipiEvento = ['malattia', 'ferie', 'permesso', 'assenza'];
            foreach ($person->expirations as $e) {
                if ($e->table_references !== 'staff') continue;
                
                $found = false;
                foreach ($tipiEvento as $tipo) {
                    if (str_contains(strtolower($e->titolo ?? ''), $tipo)) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) continue;
                
                if (!empty($selectedOwnershipId) && isset($e->id_ownership) && $e->id_ownership != $selectedOwnershipId) {
                    continue;
                }
                
                $di = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $df = $e->data_fine   ? Carbon::parse($e->data_fine) : null;
                if (!$di) continue;
                if ($df) {
                    if ($di <= $endOfMonth && $df >= $startOfMonth) return true;
                } else {
                    if ($di >= $startOfMonth && $di <= $endOfMonth) return true;
                }
            }

            return false;
        });

        $staffIds = $staff->pluck('id_personale')->toArray();

        // Presenze
        $attendanceQuery = StaffAttendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->whereIn('id_staff', $staffIds);

        if (!empty($selectedOwnershipId)) {
            $attendanceQuery->where('id_ownership', $selectedOwnershipId);
        }

        $attendances = $attendanceQuery->get();

        $attendanceIndex = [];
        foreach ($attendances as $att) {
            $staffId = $att->id_staff;
            $date = $att->date->format('Y-m-d');
            $ownershipId = $att->id_ownership;

            if (!isset($attendanceIndex[$staffId])) {
                $attendanceIndex[$staffId] = [];
            }
            if (!isset($attendanceIndex[$staffId][$date])) {
                $attendanceIndex[$staffId][$date] = [];
            }

            $attendanceIndex[$staffId][$date][$ownershipId] = [
                'is_present' => $att->is_present,
            ];
        }

        // Attività
        $activitiesQuery = Activity::whereHas('staffDetails', function($q) use ($staffIds) {
                $q->whereIn('id_staff', $staffIds);
            })
            ->with(['staffDetails' => function($q) use ($staffIds) {
                $q->whereIn('id_staff', $staffIds);
            }])
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

        // Matrice
        $presenzeMatrix = [];
        $totaliGiornate = [];

        foreach ($staff as $person) {
            $pid = $person->id_personale;
            $assunzioniPeriodi = $this->getAssunzionePeriods($person, $selectedOwnershipId);
            $assenze = $this->getAssenzeForPeriod($person, $startOfMonth, $endOfMonth);

            $presenzeMatrix[$pid] = [];
            $totaliGiornate[$pid] = 0;

            foreach ($giorni as $giorno) {
                $dk = $giorno->format('Y-m-d');
                $isFestivo = $this->isFestivo($giorno);
                $isInAss = $this->isDateInAssunzionePeriods($giorno, $assunzioniPeriodi);
                $causale = $this->getCausaleForDate($assenze, $dk);

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
                    'has_activity' => isset($activityIndex[$pid][$dk]) && $activityIndex[$pid][$dk] > 0,
                ];
            }
        }

        $staffPerGruppo = $staff->groupBy(function($p) {
            return $p->gruppo->nome ?? 'Senza categoria';
        });

        // ── CREAZIONE EXCEL ──
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Titolo
        $titolo = 'GESTIONE PRESENZE ' . strtoupper(
            Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('it')->isoFormat('MMMM YYYY')
        );
        $sheet->setCellValue('A' . $row, $titolo);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row++;

        // Intestazione colonne
        $sheet->setCellValue('A' . $row, 'DIPENDENTE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(30);

        $col = 2;
        foreach ($giorni as $giorno) {
            $isFestivo = in_array($giorno->format('m-d'), $this->festiviNazionali) || $giorno->isSunday();
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
                $personPresenze = isset($presenzeMatrix[$person->id_personale]) ? $presenzeMatrix[$person->id_personale] : [];
                $nGG = isset($totaliGiornate[$person->id_personale]) ? $totaliGiornate[$person->id_personale] : 0;

                $sheet->setCellValue('A' . $row, strtoupper($person->CognomePers) . ' ' . $person->NomePers);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);

                $col = 2;
                foreach ($giorni as $giorno) {
                    $dk = $giorno->format('Y-m-d');
                    $isFestivo = in_array($giorno->format('m-d'), $this->festiviNazionali) || $giorno->isSunday();

                    $cellData = isset($personPresenze[$dk]) ? $personPresenze[$dk] : null;
                    $isInAssunzione = $cellData ? $cellData['is_in_assunzione'] : false;
                    $causale = $cellData ? $cellData['causale'] : null;
                    $isPresent = $cellData ? $cellData['checked'] : false;
                    $hasActivity = $cellData ? $cellData['has_activity'] : false;

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

        // Bordi
        $lastRow = $row - 1;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $lastRow)->applyFromArray($styleArray);

        // Download
        $filename = 'presenze_' . $selectedYear . '_' . $selectedMonth . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    /**
     * ============================================================
     * EXPORT REPORT EXCEL - Riepilogo mensile
     * ============================================================
     */
    public function exportReportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $ownershipId = $request->get('ownership_id');

        $report = StaffAttendance::getMonthlyReport($year, $month, $ownershipId);

        // Aggiungi nomi
        $staffIds = array_keys($report);
        if (!empty($staffIds)) {
            $staffData = Staff::whereIn('id_personale', $staffIds)
                ->with(['gruppo'])
                ->get()
                ->keyBy('id_personale');

            foreach ($report as $staffId => &$data) {
                $staff = isset($staffData[$staffId]) ? $staffData[$staffId] : null;
                $data['name'] = $staff ? $staff->CognomePers . ' ' . $staff->NomePers : 'Sconosciuto';
                $data['gruppo'] = $staff && $staff->gruppo ? $staff->gruppo->nome : 'Senza categoria';
            }

            usort($report, function($a, $b) {
                if ($a['gruppo'] !== $b['gruppo']) {
                    return strcmp($a['gruppo'], $b['gruppo']);
                }
                return strcmp($a['name'], $b['name']);
            });
        }

        // ── CREAZIONE EXCEL ──
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;

        // Titolo
        $titolo = 'RIEPILOGO PRESENZE ' . strtoupper(
            Carbon::createFromDate($year, $month, 1)->locale('it')->isoFormat('MMMM YYYY')
        );
        $sheet->setCellValue('A' . $row, $titolo);
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $row++;

        // Intestazione
        $headers = ['GRUPPO', 'DIPENDENTE', 'PRESENZE TOTALI', 'GIORNI UNICI'];
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, $row], $header);
            $sheet->getStyle([$col, $row])->getFont()->setBold(true);
            $sheet->getStyle([$col, $row])->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E5E7EB');
            $col++;
        }
        $row++;

        // Dati
        $totalUniqueDays = 0;
        foreach ($report as $data) {
            $sheet->setCellValue('A' . $row, $data['gruppo']);
            $sheet->setCellValue('B' . $row, $data['name']);
            $sheet->setCellValue('C' . $row, $data['total']);
            $sheet->setCellValue('D' . $row, $data['unique_days']);
            $totalUniqueDays += $data['unique_days'];
            $row++;
        }

        // Riga totali
        $sheet->setCellValue('A' . $row, 'TOTALE');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $sheet->setCellValue('C' . $row, array_sum(array_column($report, 'total')));
        $sheet->setCellValue('D' . $row, $totalUniqueDays);

        // Formatta colonne
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Bordi
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $row)->applyFromArray($styleArray);

        // Download
        $filename = 'riepilogo_presenze_' . $year . '_' . $month . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    /**
     * ============================================================
     * DELETE - Cancella una presenza
     * ============================================================
     */
    public function delete(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json([
                'success' => false,
                'message' => 'Permessi insufficienti'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'staff_id' => 'required|integer|exists:staff,id_personale',
                'date' => 'required|date_format:Y-m-d',
                'ownership_id' => 'nullable|integer|exists:ownership,id_proprieta',
            ]);

            $query = StaffAttendance::where('id_staff', $validated['staff_id'])
                ->where('date', $validated['date']);
            
            if (!empty($validated['ownership_id'])) {
                $query->where('id_ownership', $validated['ownership_id']);
            }
            
            $deleted = $query->delete();

            return response()->json([
                'success' => true,
                'message' => 'Presenza eliminata con successo',
                'deleted' => $deleted > 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Delete attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * BULK SAVE - Salva multiple presenze (per import)
     * ============================================================
     */
    public function bulkSave(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json([
                'success' => false,
                'message' => 'Permessi insufficienti'
            ], 403);
        }

        try {
            $data = $request->validate([
                'staff_id' => 'required|integer|exists:staff,id_personale',
                'attendances' => 'required|array',
                'attendances.*.date' => 'required|date_format:Y-m-d',
                'attendances.*.checked' => 'required|boolean',
                'attendances.*.ownership_id' => 'nullable|integer|exists:ownership,id_proprieta',
            ]);

            $changes = [];
            foreach ($data['attendances'] as $att) {
                $changes[] = [
                    'staff_id' => $data['staff_id'],
                    'date' => $att['date'],
                    'checked' => $att['checked'],
                    'ownership_id' => isset($att['ownership_id']) ? $att['ownership_id'] : null,
                ];
            }

            $results = StaffAttendance::saveMany($changes);

            return response()->json([
                'success' => true,
                'message' => count($results) . ' presenze salvate con successo',
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk save attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ============================================================
     * STATS - Statistiche rapide per dashboard
     * ============================================================
     */
    public function stats(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403);
        }

        $year = $request->get('year', now()->year);
        $month = $request->get('month', now()->month);
        $ownershipId = $request->get('ownership_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = $startDate->copy()->endOfMonth();

        // Staff attivi nel periodo
        $allStaff = Staff::where('valid', 1)->with('expirations')->get();

        $activeStaff = $allStaff->filter(function($person) use ($ownershipId, $startDate, $endDate) {
            if (empty($ownershipId)) return true;

            foreach ($person->expirations as $e) {
                if ($e->table_references !== 'staff') continue;
                if (!str_contains(strtolower($e->titolo ?? ''), 'assunzione')) continue;
                if (isset($e->id_ownership) && $e->id_ownership != $ownershipId) continue;
                
                $inizio = $e->data_inizio ? Carbon::parse($e->data_inizio) : null;
                $fine = $e->data_fine ? Carbon::parse($e->data_fine) : null;
                if ($inizio && $inizio <= $endDate && (!$fine || $fine >= $startDate)) {
                    return true;
                }
            }

            return false;
        });

        $activeStaffIds = $activeStaff->pluck('id_personale')->toArray();

        // Presenze del mese
        $attendanceQuery = StaffAttendance::whereBetween('date', [$startDate, $endDate])
            ->where('is_present', true);

        if (!empty($ownershipId)) {
            $attendanceQuery->where('id_ownership', $ownershipId);
        }

        if (!empty($activeStaffIds)) {
            $attendanceQuery->whereIn('id_staff', $activeStaffIds);
        }

        $attendances = $attendanceQuery->get();

        // Statistiche
        $totalPresenze = $attendances->count();
        $uniqueStaff = $attendances->groupBy('id_staff')->count();
        $uniqueDays = $attendances->groupBy('date')->count();

        // Ore lavorate (da attività)
        $activitiesQuery = Activity::whereHas('staffDetails', function($q) use ($activeStaffIds) {
                $q->whereIn('id_staff', $activeStaffIds);
            })
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->with('staffDetails');

        $activities = $activitiesQuery->get();

        $totalOre = 0;
        foreach ($activities as $act) {
            foreach ($act->staffDetails as $sd) {
                if (!in_array($sd->id_staff, $activeStaffIds)) continue;
                $ore = floatval($sd->n_ore ?? 0);
                if ($ore == 0 && $sd->att_start && $sd->att_end) {
                    try {
                        $ore = max(0, Carbon::parse($sd->att_start)->diffInHours(Carbon::parse($sd->att_end)));
                    } catch (\Exception $e) {
                        $ore = 0;
                    }
                }
                $totalOre += $ore;
            }
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'period' => [
                    'year' => $year,
                    'month' => $month,
                    'month_name' => isset($this->mesi[(int)$month]) ? $this->mesi[(int)$month] : '',
                ],
                'staff' => [
                    'total' => count($activeStaffIds),
                    'with_presences' => $uniqueStaff,
                ],
                'attendance' => [
                    'total_presenze' => $totalPresenze,
                    'unique_days' => $uniqueDays,
                    'media_giorno' => $uniqueDays > 0 ? round($totalPresenze / $uniqueDays, 1) : 0,
                    'media_staff' => count($activeStaffIds) > 0 ? round($uniqueDays / count($activeStaffIds), 1) : 0,
                ],
                'hours' => [
                    'total' => round($totalOre, 1),
                    'media_giorno' => $uniqueDays > 0 ? round($totalOre / $uniqueDays, 1) : 0,
                    'media_staff' => count($activeStaffIds) > 0 ? round($totalOre / count($activeStaffIds), 1) : 0,
                ],
            ],
        ]);
    }
}