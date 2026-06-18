<?php
// app/Http/Controllers/Admin/StaffAttendanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Expiration;
use App\Models\Ownership;
use App\Models\Activity;
use App\Models\ActivityStaffLink;
use App\Models\StaffAttendance;
use App\Models\CostCenter;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;

class StaffAttendanceController extends Controller
{
    /**
     * Display a listing of staff for attendance management.
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staff = Staff::where('valid', 1)
            ->with('gruppo')
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();

        $ownerships = Ownership::where('valid', 1)
            ->orderBy('RagAbbrev')
            ->get();

        $defaultOwnership = Ownership::where('RagAbbrev', 'LIKE', '%Agricola Salatino%')->first();

        $currentMonth = $request->get('month', now()->format('Y-m'));
        [$currentYear, $currentMonthNum] = explode('-', $currentMonth);

        $selectedMonth = $currentMonthNum;
        $selectedYear = $currentYear;
        $currentYear = now()->year;

        $dateFrom = $request->get('date_from', Carbon::createFromDate($currentYear, $currentMonthNum, 1)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::createFromDate($currentYear, $currentMonthNum, 1)->endOfMonth()->format('Y-m-d'));

        $currentDate = Carbon::createFromDate($currentYear, $currentMonthNum, 1);
        $previousMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        $properties = $ownerships;

        return view('admin.staff.attendance.index', compact(
            'staff', 'ownerships', 'defaultOwnership', 'currentMonth', 'currentYear', 'currentMonthNum',
            'selectedMonth', 'selectedYear', 'dateFrom', 'dateTo', 'previousMonth', 'nextMonth', 'properties'
        ));
    }

    /**
     * Show attendance for a specific staff member in a given month.
     */
    public function show(Request $request, $staffId, $year, $month)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staff = Staff::with([
            'expirations' => function ($q) {
                $q->where(function ($query) {
                    $query->where('titolo', 'LIKE', '%Malattia%')
                        ->orWhere('titolo', 'LIKE', '%Ferie%')
                        ->orWhere('titolo', 'LIKE', '%Permesso%')
                        ->orWhere('titolo', 'LIKE', '%Assunzione%');
                });
            },
            'gruppo'
        ])->findOrFail($staffId);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        // ── Ottieni la data di assunzione ──────────────────────────────────
        $assunzioneExp = $staff->expirations->filter(function ($exp) {
            return str_contains(strtolower($exp->titolo ?? ''), 'assunzione');
        })->sortBy('data_inizio')->first();

        $dataAssunzione  = $assunzioneExp && $assunzioneExp->data_inizio
            ? Carbon::parse($assunzioneExp->data_inizio)->startOfDay()
            : null;
        $dataLicenziamento = $assunzioneExp && $assunzioneExp->data_fine
            ? Carbon::parse($assunzioneExp->data_fine)->startOfDay()
            : null;

        // ═══════════════════════════════════════════════════════════════════
        // ⭐ FILTRO PER PROPRIETÀ
        // ═══════════════════════════════════════════════════════════════════
        $selectedOwnershipId = $request->get('ownership_id', '');
        
        if (empty($selectedOwnershipId)) {
            $defaultOwnership = Ownership::where('RagAbbrev', 'LIKE', '%Agricola Salatino%')->first();
            if ($defaultOwnership) {
                $selectedOwnershipId = $defaultOwnership->id_proprieta;
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // ⭐ RANGE DI RICERCA ATTIVITÀ
        // ═══════════════════════════════════════════════════════════════════
        $activityStartDate = $dataAssunzione ? $dataAssunzione->copy() : $startDate->copy();
        $currentYearStart = Carbon::createFromDate($year, 1, 1)->startOfDay();
        if ($activityStartDate->gt($currentYearStart)) {
            $activityStartDate = $currentYearStart;
        }
        
        $activityEndDate = $endDate->copy();
        if ($dataLicenziamento && $activityEndDate->gt($dataLicenziamento)) {
            $activityEndDate = $dataLicenziamento->copy();
        }

        // ── Attività del dipendente ───────────────────────────────────────
        $activitiesQuery = Activity::whereHas('staffDetails', function ($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            })
            ->with([
                'staffDetails' => function ($q) use ($staffId) {
                    $q->where('id_staff', $staffId);
                },
                'costCenter',
            ])
            ->whereBetween('data_activities', [$activityStartDate, $activityEndDate]);

        // ⭐ APPLICA FILTRO PROPRIETÀ (solo se è un ownership)
        if ($selectedOwnershipId) {
            $activitiesQuery->whereHas('costCenter', function ($q) use ($selectedOwnershipId) {
                $q->where('table_references', 'ownership')
                ->where('id_references', $selectedOwnershipId);
            });
        }

        $activities = $activitiesQuery->get();

        // Raggruppa per data
        $activitiesByDate = $activities->groupBy(function ($act) {
            return Carbon::parse($act->data_activities)->format('Y-m-d');
        });

        // ── Assenze (malattia / ferie / permesso) ─────────────────────────
        $assenze = $staff->expirations->filter(function ($exp) {
            $t = strtolower($exp->titolo ?? '');
            return str_contains($t, 'malattia')
                || str_contains($t, 'ferie')
                || str_contains($t, 'permesso');
        });

        // ── LEGGI LE PRESENZE SALVATE DAL DATABASE ────────────────────────
        $savedAttendances = StaffAttendance::where('staff_id', $staffId)
            ->whereBetween('date', [$activityStartDate, $activityEndDate])
            ->get()
            ->keyBy('date');

        // ── Pre-carica tutte le ownership per riferimento ─────────────────
        $ownerships = Ownership::where('valid', 1)->get()->keyBy('id_proprieta');

        // ── Costruzione matrice giorni ────────────────────────────────────
        $presenze        = [];
        $totalOre        = 0;
        $totalGiornateEffettive = 0;
        $totalGiornateMesse     = 0;
        $totalGiornateAssenza   = 0;

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey   = $currentDate->format('Y-m-d');
            $isSunday  = $currentDate->isSunday();

            // ── VERIFICA SE IL GIORNO È NEL PERIODO DI ASSUNZIONE ────────
            $isInAssunzione = true;
            if ($dataAssunzione && $currentDate->lt($dataAssunzione)) {
                $isInAssunzione = false;
            }
            if ($dataLicenziamento && $currentDate->gt($dataLicenziamento)) {
                $isInAssunzione = false;
            }

            // ── FILTRA LE ATTIVITÀ: mostrale SOLO se dentro il periodo ──
            $dayActivities = $isInAssunzione 
                ? ($activitiesByDate->get($dateKey, collect()))
                : collect();

            // ── CAUSALE da scadenze ──────────────────────────────────────
            $expiration = $assenze->filter(function ($exp) use ($dateKey) {
                $di = $exp->data_inizio ? Carbon::parse($exp->data_inizio)->format('Y-m-d') : null;
                $df = $exp->data_fine   ? Carbon::parse($exp->data_fine)->format('Y-m-d')   : null;
                return $di && $df && $di <= $dateKey && $df >= $dateKey;
            })->first();

            $causale = null;
            if ($expiration) {
                $t = strtolower($expiration->titolo ?? '');
                if (str_contains($t, 'malattia')) {
                    $causale = 'malattia';
                    if ($isInAssunzione) $totalGiornateAssenza++;
                } elseif (str_contains($t, 'ferie')) {
                    $causale = 'ferie';
                    if ($isInAssunzione) $totalGiornateAssenza++;
                } elseif (str_contains($t, 'permesso')) {
                    $causale = 'permesso';
                    if ($isInAssunzione) $totalGiornateAssenza++;
                }
            }

            // ── DETTAGLI ORARI ──────────────────────────────────────────
            $dettagliOrari = [];
            $oreGiorno = 0;
            $ownershipIds = [];
            $cantieriNomi = [];
            $hasInvalidCostCenter = false;

            if ($isInAssunzione) {
                foreach ($dayActivities as $activity) {
                    $sd = $activity->staffDetails->first();
                    
                    if ($sd) {
                        $oreAttivita = floatval($sd->n_ore ?? 0);
                        
                        // Se non ci sono ore ma ci sono orari, calcolale
                        if ($oreAttivita == 0 && $sd->att_start && $sd->att_end) {
                            try {
                                $inizio = Carbon::parse($sd->att_start);
                                $fine = Carbon::parse($sd->att_end);
                                $oreAttivita = $inizio->diffInHours($fine);
                                $oreAttivita = max(0, $oreAttivita);
                            } catch (\Exception $e) {
                                $oreAttivita = 0;
                            }
                        }
                        
                        $oreGiorno += $oreAttivita;

                        // ⭐ RECUPERA OWNERSHIP DAL CENTRO DI COSTO
                        $ownershipId = null;
                        $ownershipName = null;
                        $costCenterName = null;
                        
                        if ($activity->costCenter) {
                            $cc = $activity->costCenter;
                            $costCenterName = $cc->Nome ?? null;
                            
                            // Se è un ownership, prendi il nome dalla tabella ownership
                            if ($cc->table_references === 'ownership' && $cc->id_references) {
                                $ownershipId = $cc->id_references;
                                if (isset($ownerships[$ownershipId])) {
                                    $ownershipName = $ownerships[$ownershipId]->RagAbbrev;
                                    $ownershipIds[] = $ownershipId;
                                    $cantieriNomi[] = $ownershipName;
                                }
                            } else {
                                // ⭐ Se è un entities o altro, usa il nome del centro di costo
                                $hasInvalidCostCenter = true;
                                if ($costCenterName) {
                                    $cantieriNomi[] = $costCenterName;
                                }
                            }
                        }

                        $dettaglio = [
                            'id_ownership' => $ownershipId,
                            'nome_ownership' => $ownershipName ?? $costCenterName ?? 'N/A',
                            'att_start' => $sd->att_start ?? null,
                            'att_end' => $sd->att_end ?? null,
                            'n_ore' => $oreAttivita,
                            'note' => $sd->note ?? '',
                            'is_ownership' => ($activity->costCenter && $activity->costCenter->table_references === 'ownership'),
                            'cost_center_name' => $costCenterName,
                        ];
                        
                        if ($dettaglio['att_start'] || $dettaglio['att_end'] || $dettaglio['n_ore'] > 0) {
                            $dettagliOrari[] = $dettaglio;
                        }
                    }
                }

                // Se non ci sono dettagli orari ma ci sono attività
                if ($dayActivities->count() > 0 && empty($dettagliOrari)) {
                    $firstActivity = $dayActivities->first();
                    $sd = $firstActivity->staffDetails->first();
                    if ($sd) {
                        $oreAttivita = floatval($sd->n_ore ?? 0);
                        
                        $ownershipId = null;
                        $ownershipName = null;
                        $costCenterName = null;
                        
                        if ($firstActivity->costCenter) {
                            $cc = $firstActivity->costCenter;
                            $costCenterName = $cc->Nome ?? null;
                            
                            if ($cc->table_references === 'ownership' && $cc->id_references) {
                                $ownershipId = $cc->id_references;
                                if (isset($ownerships[$ownershipId])) {
                                    $ownershipName = $ownerships[$ownershipId]->RagAbbrev;
                                    $ownershipIds[] = $ownershipId;
                                    $cantieriNomi[] = $ownershipName;
                                }
                            } else {
                                $hasInvalidCostCenter = true;
                                if ($costCenterName) {
                                    $cantieriNomi[] = $costCenterName;
                                }
                            }
                        }
                        
                        $dettagliOrari[] = [
                            'id_ownership' => $ownershipId,
                            'nome_ownership' => $ownershipName ?? $costCenterName ?? 'N/A',
                            'att_start' => $sd->att_start ?? null,
                            'att_end' => $sd->att_end ?? null,
                            'n_ore' => $oreAttivita,
                            'note' => $sd->note ?? '',
                            'is_ownership' => ($firstActivity->costCenter && $firstActivity->costCenter->table_references === 'ownership'),
                            'cost_center_name' => $costCenterName,
                        ];
                    }
                }
            }

            // ── Ownership unici ──────────────────────────────────────────
            $ownershipIdsUnici = array_unique($ownershipIds);
            $cantieriNomiUnici = array_unique($cantieriNomi);
            $numCantieri = count($cantieriNomiUnici);
            
            // Ownership principale (il primo)
            $ownershipId = !empty($ownershipIdsUnici) ? $ownershipIdsUnici[0] : null;
            $cantieriString = implode(', ', $cantieriNomiUnici);

            // ── SHOW CHECKBOX ──────────────────────────────────────────
            $showCheckbox = !$isSunday && !$causale && $isInAssunzione;

            // ── GIORNATE EFFETTIVE ───────────────────────────────────────
            if (!$isSunday && !$causale && $isInAssunzione) {
                $totalGiornateEffettive++;
            }

            // ── CHECKED: PRIORITÀ AI DATI SALVATI ──────────────────────
            $savedAttendance = $savedAttendances->get($dateKey);
            
            if ($savedAttendance) {
                $checked = (bool) $savedAttendance->is_present;
                $savedOwnershipId = $savedAttendance->ownership_id;
                if ($savedOwnershipId) {
                    $ownershipId = $savedOwnershipId;
                }
                $savedId = $savedAttendance->id;
                $isSaved = true;
            } else {
                $checked = $isInAssunzione ? $dayActivities->count() > 0 : false;
                $savedId = null;
                $isSaved = false;
            }
            
            if ($checked && $isInAssunzione) {
                $totalGiornateMesse++;
            }

            // ── ORE TOTALI ──────────────────────────────────────────────
            if ($isInAssunzione) {
                $totalOre += $oreGiorno;
            }

            $presenze[$dateKey] = [
                'data'             => $dateKey,
                'giorno_settimana' => $currentDate->isoFormat('ddd'),
                'giorno_num'       => $currentDate->day,
                'is_sunday'        => $isSunday,
                'is_in_assunzione' => $isInAssunzione,
                'causale'          => $causale,
                'ore'              => $isInAssunzione ? $oreGiorno : 0,
                'id_ownership'     => $ownershipId,
                'ownership_ids'    => $ownershipIdsUnici,
                'cantieri_nomi'    => $cantieriNomiUnici,
                'cantieri_string'  => $isInAssunzione ? $cantieriString : '',
                'show_checkbox'    => $showCheckbox,
                'checked'          => $checked && $isInAssunzione,
                'num_cantieri'     => $isInAssunzione ? $numCantieri : 0,
                'has_multiple'     => $isInAssunzione && $numCantieri > 1,
                'dettagli'         => $isInAssunzione ? $dettagliOrari : [],
                'attivita_count'   => $isInAssunzione ? $dayActivities->count() : 0,
                'saved_id'         => $savedId,
                'is_saved'         => $isSaved,
                'has_activities_outside' => !$isInAssunzione && $activitiesByDate->has($dateKey),
                'has_non_ownership' => $hasInvalidCostCenter, // ⭐ Flag per attività con centri di costo non ownership
            ];

            $currentDate->addDay();
        }

        // ── Ownership selezionata per la vista ───────────────────────────
        $selectedOwnershipName = '';
        if ($selectedOwnershipId) {
            $ow = Ownership::find($selectedOwnershipId);
            if ($ow) {
                $selectedOwnershipName = $ow->RagAbbrev;
            }
        }

        $ownerships = Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();

        // Debug
        Log::info('Presenze generate:', [
            'totale_giorni' => count($presenze),
            'attivita_trovate' => $activities->count(),
            'filter_ownership' => $selectedOwnershipId
        ]);

        return view('admin.staff.attendance.show', compact(
            'staff', 'year', 'month', 'presenze',
            'totalOre', 'totalGiornateEffettive', 'totalGiornateMesse', 'totalGiornateAssenza',
            'startDate', 'endDate', 'ownerships',
            'dataAssunzione', 'dataLicenziamento',
            'selectedOwnershipId', 'selectedOwnershipName'
        ));
    }

    /**
     * Save attendance for a specific day (JSON endpoint).
     */
    public function save(Request $request)
    {
        try {
            Log::info('=== SAVE ATTENDANCE START ===');
            Log::info('Request data:', $request->all());
            
            if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Permessi insufficienti'
                ], 403);
            }

            DB::beginTransaction();

            $saved = 0;
            $results = [];
            $errors = [];

            if ($request->has('changes')) {
                $changes = $request->input('changes', []);
                Log::info('Saving multiple changes: ' . count($changes) . ' items');

                foreach ($changes as $index => $change) {
                    try {
                        Log::info("Processing change $index:", $change);
                        
                        // ⭐ VERIFICA CHE 'checked' SIA BOOLEAN
                        $validated = validator($change, [
                            'staff_id'   => 'required|integer|exists:staff,id_personale',
                            'date'       => 'required|date|date_format:Y-m-d',
                            'checked'    => 'required|boolean',
                            'ownership_id' => 'nullable|integer|exists:ownership,id_proprieta',
                        ])->validate();

                        // ⭐ CONVERTI ESPLICITAMENTE A BOOLEAN
                        $isPresent = filter_var($validated['checked'], FILTER_VALIDATE_BOOLEAN);
                        
                        Log::info("Saving: staff_id={$validated['staff_id']}, date={$validated['date']}, is_present=" . ($isPresent ? 'true' : 'false'));

                        $attendance = StaffAttendance::updateOrCreate(
                            [
                                'staff_id' => $validated['staff_id'],
                                'date' => $validated['date'],
                            ],
                            [
                                'is_present' => $isPresent ? 1 : 0, // ⭐ ASSICURATI CHE SIA 1 O 0
                                'ownership_id' => $validated['ownership_id'] ?? null,
                                'updated_by' => Auth::guard('admin')->id(),
                                'notes' => $validated['notes'] ?? null,
                            ]
                        );

                        Log::info("Attendance saved with ID: {$attendance->id}, is_present: {$attendance->is_present}");

                        $saved++;
                        $results[] = [
                            'date' => $validated['date'],
                            'success' => true,
                            'id' => $attendance->id,
                            'is_present' => (bool) $attendance->is_present
                        ];

                    } catch (\Exception $e) {
                        Log::error("Error saving change $index: " . $e->getMessage());
                        $errors[] = [
                            'index' => $index,
                            'error' => $e->getMessage()
                        ];
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $saved . ' presenze salvate con successo',
                    'results' => $results,
                    'saved_count' => $saved,
                    'errors' => $errors
                ]);

            } else {
                // Salvataggio singolo
                $validated = $request->validate([
                    'staff_id'   => 'required|integer|exists:staff,id_personale',
                    'date'       => 'required|date|date_format:Y-m-d',
                    'checked'    => 'required|boolean',
                    'ownership_id' => 'nullable|integer|exists:ownership,id_proprieta',
                ]);

                $isPresent = filter_var($validated['checked'], FILTER_VALIDATE_BOOLEAN);

                $attendance = StaffAttendance::updateOrCreate(
                    [
                        'staff_id' => $validated['staff_id'],
                        'date' => $validated['date'],
                    ],
                    [
                        'is_present' => $isPresent ? 1 : 0,
                        'ownership_id' => $validated['ownership_id'] ?? null,
                        'updated_by' => Auth::guard('admin')->id(),
                    ]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Presenza salvata con successo',
                    'id' => $attendance->id,
                    'is_present' => (bool) $attendance->is_present,
                    'was_created' => $attendance->wasRecentlyCreated
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Save attendance error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export attendance report to PDF.
     */
    public function exportPdf(Request $request, $staffId, $year, $month)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staff     = Staff::with(['gruppo'])->findOrFail($staffId);
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate   = $startDate->copy()->endOfMonth();
        $data = $this->getAttendanceData($staffId, $startDate, $endDate, $request->get('ownership_id'));
        
        $presenze = $data['presenze'];
        $totalOre = $data['totalOre'];
        $totalPresenti = $data['totalPresenti'];

        $html = $this->generatePdfHtml($staff, $startDate, $endDate, $presenze, $totalOre, $totalPresenti);
        $pdf  = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'presenze_' . $staff->CognomePers . '_' . $year . '-' . $month . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export attendance report to Excel.
     */
    public function exportExcel(Request $request, $staffId, $year, $month)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staff     = Staff::with(['gruppo'])->findOrFail($staffId);
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate   = $startDate->copy()->endOfMonth();
        $data = $this->getAttendanceData($staffId, $startDate, $endDate, $request->get('ownership_id'));
        
        $presenze = $data['presenze'];
        $totalOre = $data['totalOre'];
        $totalPresenti = $data['totalPresenti'];

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Presenze');

        // Header
        $sheet->setCellValue('A1', 'Report Presenze');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->setCellValue('A2', $staff->NomePers . ' ' . $staff->CognomePers);
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->setCellValue('A3', $startDate->isoFormat('MMMM YYYY'));

        // Intestazioni colonne
        $headers = ['Data', 'Giorno', 'Presente', 'Ore Totali', 'Causale', 'Azienda/Cantieri', 'Inizio', 'Fine', 'Ore', 'Stato'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '5', $header);
            $col++;
        }

        $headerStyle = [
            'font'      => ['bold' => true, 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle('A5:J5')->applyFromArray($headerStyle);

        $row = 6;

        foreach ($presenze as $data => $p) {
            $dettagli = $p['dettagli'] ?? [];
            $stato = $p['is_in_assunzione'] ? 'In assunzione' : 'Non assunto';
            
            if (empty($dettagli)) {
                $sheet->setCellValue('A' . $row, Carbon::parse($data)->format('d/m/Y'));
                $sheet->setCellValue('B' . $row, $p['giorno_settimana']);
                $sheet->setCellValue('C' . $row, $p['checked'] ? 'Sì' : 'No');
                $sheet->setCellValue('D' . $row, $p['ore'] > 0 ? number_format($p['ore'], 2) : '');
                $sheet->setCellValue('E' . $row, $p['causale'] ?? '');
                $sheet->setCellValue('F' . $row, $p['cantieri_string'] ?? '');
                $sheet->setCellValue('J' . $row, $stato);
                $row++;
            } else {
                foreach ($dettagli as $index => $dettaglio) {
                    $sheet->setCellValue('A' . $row, Carbon::parse($data)->format('d/m/Y'));
                    $sheet->setCellValue('B' . $row, $p['giorno_settimana']);
                    $sheet->setCellValue('C' . $row, $p['checked'] ? 'Sì' : 'No');
                    $sheet->setCellValue('D' . $row, number_format($dettaglio['n_ore'] ?? 0, 2));
                    $sheet->setCellValue('E' . $row, $index === 0 ? ($p['causale'] ?? '') : '');
                    $sheet->setCellValue('F' . $row, $dettaglio['nome_ownership'] ?? '');
                    $sheet->setCellValue('G' . $row, $dettaglio['att_start'] ? Carbon::parse($dettaglio['att_start'])->format('H:i') : '');
                    $sheet->setCellValue('H' . $row, $dettaglio['att_end'] ? Carbon::parse($dettaglio['att_end'])->format('H:i') : '');
                    $sheet->setCellValue('I' . $row, number_format($dettaglio['n_ore'] ?? 0, 2));
                    $sheet->setCellValue('J' . $row, $stato);
                    $row++;
                }
            }
        }

        $sheet->setCellValue('A' . $row, 'TOTALI:');
        $sheet->setCellValue('D' . $row, number_format($totalOre, 2));
        $sheet->setCellValue('C' . $row, $totalPresenti . ' presenze');
        $sheet->getStyle('A' . $row . ':J' . $row)->getFont()->setBold(true);

        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer   = new Xlsx($spreadsheet);
        $filename = 'presenze_' . $staff->CognomePers . '_' . $year . '-' . $month . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function getAttendanceData($staffId, $startDate, $endDate, $ownershipFilter = null)
    {
        $staff = Staff::with([
            'expirations' => function ($q) {
                $q->where(function ($query) {
                    $query->where('titolo', 'LIKE', '%Malattia%')
                          ->orWhere('titolo', 'LIKE', '%Ferie%')
                          ->orWhere('titolo', 'LIKE', '%Permesso%')
                          ->orWhere('titolo', 'LIKE', '%Assunzione%');
                });
            },
        ])->findOrFail($staffId);

        $assunzioneExp = $staff->expirations->filter(function ($exp) {
            return str_contains(strtolower($exp->titolo ?? ''), 'assunzione');
        })->sortBy('data_inizio')->first();

        $dataAssunzione = $assunzioneExp && $assunzioneExp->data_inizio
            ? Carbon::parse($assunzioneExp->data_inizio)->startOfDay()
            : null;
        $dataLicenziamento = $assunzioneExp && $assunzioneExp->data_fine
            ? Carbon::parse($assunzioneExp->data_fine)->startOfDay()
            : null;

        $activitiesQuery = Activity::whereHas('staffDetails', function ($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            })
            ->with([
                'staffDetails' => function ($q) use ($staffId) {
                    $q->where('id_staff', $staffId);
                }, 
                'costCenter'
            ])
            ->whereBetween('data_activities', [$startDate, $endDate]);

        if ($ownershipFilter) {
            $activitiesQuery->whereHas('costCenter', function ($q) use ($ownershipFilter) {
                $q->where('table_references', 'ownership')
                  ->where('id_references', $ownershipFilter);
            });
        }

        $activities = $activitiesQuery->get();
        $activitiesByDate = $activities->groupBy(function ($act) {
            return Carbon::parse($act->data_activities)->format('Y-m-d');
        });

        $assenze = $staff->expirations->filter(function ($exp) {
            $t = strtolower($exp->titolo ?? '');
            return str_contains($t, 'malattia')
                || str_contains($t, 'ferie')
                || str_contains($t, 'permesso');
        });

        $savedAttendances = StaffAttendance::where('staff_id', $staffId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy('date');

        $ownerships = Ownership::where('valid', 1)->get()->keyBy('id_proprieta');
        $presenze    = [];
        $totalOre    = 0;
        $totalPresenti = 0;
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey  = $currentDate->format('Y-m-d');
            $isSunday = $currentDate->isSunday();

            $isInAssunzione = true;
            if ($dataAssunzione && $currentDate->lt($dataAssunzione)) {
                $isInAssunzione = false;
            }
            if ($dataLicenziamento && $currentDate->gt($dataLicenziamento)) {
                $isInAssunzione = false;
            }

            $expiration = $assenze->filter(function ($exp) use ($dateKey) {
                $di = $exp->data_inizio ? Carbon::parse($exp->data_inizio)->format('Y-m-d') : null;
                $df = $exp->data_fine   ? Carbon::parse($exp->data_fine)->format('Y-m-d')   : null;
                return $di && $df && $di <= $dateKey && $df >= $dateKey;
            })->first();

            $causale = null;
            if ($expiration) {
                $t = strtolower($expiration->titolo ?? '');
                if (str_contains($t, 'malattia'))     $causale = 'malattia';
                elseif (str_contains($t, 'ferie'))    $causale = 'ferie';
                elseif (str_contains($t, 'permesso')) $causale = 'permesso';
            }

            $dayActivities = $isInAssunzione ? ($activitiesByDate->get($dateKey, collect())) : collect();
            $oreGiorno = 0;
            $dettagliOrari = [];
            $ownershipIds = [];
            $cantieriNomi = [];

            if ($isInAssunzione) {
                foreach ($dayActivities as $activity) {
                    $sd = $activity->staffDetails->first();
                    if ($sd) {
                        $oreAttivita = floatval($sd->n_ore ?? 0);
                        
                        if ($oreAttivita == 0 && $sd->att_start && $sd->att_end) {
                            try {
                                $inizio = Carbon::parse($sd->att_start);
                                $fine = Carbon::parse($sd->att_end);
                                $oreAttivita = $inizio->diffInHours($fine);
                                $oreAttivita = max(0, $oreAttivita);
                            } catch (\Exception $e) {
                                $oreAttivita = 0;
                            }
                        }
                        
                        $oreGiorno += $oreAttivita;
                        
                        $ownershipId = null;
                        $ownershipName = null;
                        if ($activity->costCenter && $activity->costCenter->table_references === 'ownership') {
                            $ownershipId = $activity->costCenter->id_references;
                            if (isset($ownerships[$ownershipId])) {
                                $ownershipName = $ownerships[$ownershipId]->RagAbbrev;
                                $ownershipIds[] = $ownershipId;
                                $cantieriNomi[] = $ownershipName;
                            }
                        }
                        
                        $dettagliOrari[] = [
                            'id_ownership' => $ownershipId,
                            'nome_ownership' => $ownershipName ?? 'N/A',
                            'att_start' => $sd->att_start ?? null,
                            'att_end' => $sd->att_end ?? null,
                            'n_ore' => $oreAttivita,
                            'note' => $sd->note ?? ''
                        ];
                    }
                }
            }

            $savedAttendance = $savedAttendances->get($dateKey);
            $checked = $savedAttendance ? (bool) $savedAttendance->is_present : ($isInAssunzione ? $dayActivities->count() > 0 : false);
            
            if ($checked && $isInAssunzione) {
                $totalPresenti++;
            }
            
            if ($isInAssunzione) {
                $totalOre += $oreGiorno;
            }

            $presenze[$dateKey] = [
                'data'             => $dateKey,
                'giorno_settimana' => $currentDate->isoFormat('ddd'),
                'giorno_num'       => $currentDate->day,
                'is_sunday'        => $isSunday,
                'is_in_assunzione' => $isInAssunzione,
                'causale'          => $causale,
                'ore'              => $isInAssunzione ? $oreGiorno : 0,
                'checked'          => $checked && $isInAssunzione,
                'dettagli'         => $isInAssunzione ? $dettagliOrari : [],
                'saved_id'         => $savedAttendance ? $savedAttendance->id : null,
                'ownership_ids'    => array_unique($ownershipIds),
                'cantieri_nomi'    => array_unique($cantieriNomi),
                'cantieri_string'  => $isInAssunzione ? implode(', ', array_unique($cantieriNomi)) : '',
                'num_cantieri'     => $isInAssunzione ? count(array_unique($ownershipIds)) : 0,
            ];

            $currentDate->addDay();
        }

        return [
            'presenze' => $presenze,
            'totalOre' => $totalOre,
            'totalPresenti' => $totalPresenti
        ];
    }

    private function generatePdfHtml($staff, $startDate, $endDate, $presenze, $totalOre, $totalPresenti)
    {
        $rows = '';

        foreach ($presenze as $data => $p) {
            $bgColor = $p['is_sunday'] ? '#FEE2E2' : ($p['causale'] ? '#FEF9C3' : 'transparent');
            
            $orariHtml = '';
            $dettagli = $p['dettagli'] ?? [];
            
            if (!empty($dettagli)) {
                $orariParts = [];
                foreach ($dettagli as $dettaglio) {
                    $part = '';
                    if ($dettaglio['nome_ownership']) {
                        $part .= '<strong>' . e($dettaglio['nome_ownership']) . '</strong> ';
                    }
                    if ($dettaglio['att_start'] && $dettaglio['att_end']) {
                        $start = Carbon::parse($dettaglio['att_start'])->format('H:i');
                        $end = Carbon::parse($dettaglio['att_end'])->format('H:i');
                        $part .= $start . '-' . $end;
                        if ($dettaglio['n_ore'] > 0) {
                            $part .= ' (' . number_format($dettaglio['n_ore'], 1) . 'h)';
                        }
                    } elseif ($dettaglio['n_ore'] > 0) {
                        $part .= number_format($dettaglio['n_ore'], 1) . 'h';
                    }
                    if (!empty($part)) {
                        $orariParts[] = $part;
                    }
                }
                $orariHtml = implode('<br>', $orariParts);
            } else {
                $orariHtml = $p['ore'] > 0 ? number_format($p['ore'], 1) . 'h' : '—';
            }

            $stato = $p['is_in_assunzione'] ? '' : ' <span style="color:#999;font-size:8px;">(Non assunto)</span>';

            $rows .= '<tr style="background:' . $bgColor . '">
                <td>' . Carbon::parse($data)->format('d/m/Y') . '</td>
                <td>' . $p['giorno_settimana'] . '</td>
                <td>' . ($p['checked'] ? '✓' : '') . '</td>
                <td>' . ($p['causale'] ? ucfirst($p['causale']) : '') . $stato . '</td>
                <td style="font-size:8px;text-align:left;">' . $orariHtml . '</td>
                <td style="font-size:8px;text-align:left;">' . ($p['cantieri_string'] ?? '—') . '</td>
            </tr>';
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Report Presenze</title>
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #65a30d; padding-bottom: 10px; }
            .header h1 { margin: 0; font-size: 18px; color: #333; }
            .header p { margin: 5px 0 0; color: #666; font-size: 11px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #f1f5f9; padding: 8px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold; }
            td { padding: 6px 8px; border: 1px solid #e2e8f0; text-align: center; vertical-align: middle; }
            .totals { margin-top: 20px; font-weight: bold; font-size: 11px; }
            .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #666; }
        </style></head><body>
        <div class="header">
            <h1>Report Presenze</h1>
            <p>' . e($staff->NomePers) . ' ' . e($staff->CognomePers) . '</p>
            <p>' . $startDate->isoFormat('MMMM YYYY') . '</p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Giorno</th>
                    <th>Presente</th>
                    <th>Causale/Stato</th>
                    <th>Dettaglio Orari</th>
                    <th>Cantieri</th>
                </tr>
            </thead>
            <tbody>' . $rows . '</tbody>
        </table>
        <div class="totals">
            <p>Totale Ore: ' . number_format($totalOre, 1) . ' h &nbsp;|&nbsp; Giornate Messe: ' . $totalPresenti . '</p>
        </div>
        <div class="footer">Generato il ' . Carbon::now()->format('d/m/Y H:i') . '</div>
        </body></html>';
    }
}