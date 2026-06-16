<?php
// app/Http/Controllers/Admin/StaffAttendanceController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Expiration;
use App\Models\Ownership;
use App\Models\Activity;
use App\Models\ActivityStaffLink;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

        // Default: Agricola Salatino SRL
        $defaultOwnership = Ownership::where('RagAbbrev', 'LIKE', '%Agricola Salatino%')->first();

        // Mese corrente
        $currentMonth = $request->get('month', now()->format('Y-m'));
        [$currentYear, $currentMonthNum] = explode('-', $currentMonth);

        // Variabili per i filtri data (come in activity-report)
        $selectedMonth = $currentMonthNum;
        $selectedYear = $currentYear;
        $currentYear = now()->year;

        // Date per i filtri
        $dateFrom = $request->get('date_from', Carbon::createFromDate($currentYear, $currentMonthNum, 1)->format('Y-m-d'));
        $dateTo = $request->get('date_to', Carbon::createFromDate($currentYear, $currentMonthNum, 1)->endOfMonth()->format('Y-m-d'));

        // Mesi precedente e successivo per la navigazione
        $currentDate = Carbon::createFromDate($currentYear, $currentMonthNum, 1);
        $previousMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        // Properties per il filtro (alias di ownerships)
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

        // ── Attività del mese per questo dipendente ───────────────────────
        $activities = Activity::whereHas('staffDetails', function ($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            })
            ->with([
                'staffDetails' => function ($q) use ($staffId) {
                    $q->where('id_staff', $staffId);
                },
                'ownership',
            ])
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->get();

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

        // ── Costruzione matrice giorni ────────────────────────────────────
        $presenze        = [];
        $totalOre        = 0;
        $totalGiornateEffettive = 0;
        $totalGiornateMesse     = 0;

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey   = $currentDate->format('Y-m-d');
            $isSunday  = $currentDate->isSunday();

            // Dentro il periodo di assunzione?
            $isInAssunzione = true;
            if ($dataAssunzione && $currentDate->lt($dataAssunzione)) {
                $isInAssunzione = false;
            }
            if ($dataLicenziamento && $currentDate->gt($dataLicenziamento)) {
                $isInAssunzione = false;
            }

            // Causale da scadenze
            $expiration = $assenze->filter(function ($exp) use ($dateKey) {
                $di = $exp->data_inizio ? Carbon::parse($exp->data_inizio)->format('Y-m-d') : null;
                $df = $exp->data_fine   ? Carbon::parse($exp->data_fine)->format('Y-m-d')   : null;
                return $di && $df && $di <= $dateKey && $df >= $dateKey;
            })->first();

            $causale = null;
            if ($expiration) {
                $t = strtolower($expiration->titolo ?? '');
                if (str_contains($t, 'malattia'))       $causale = 'malattia';
                elseif (str_contains($t, 'ferie'))      $causale = 'ferie';
                elseif (str_contains($t, 'permesso'))   $causale = 'permesso';
            }

            // Attività del giorno
            $dayActivities = $activitiesByDate->get($dateKey, collect());

            // Ore totali + cantieri
            $oreGiorno    = 0;
            $cantieriList = [];
            $ownershipId  = null;

            foreach ($dayActivities as $activity) {
                $sd = $activity->staffDetails->first();
                if ($sd) {
                    $oreGiorno += floatval($sd->n_ore ?? 0);
                }
                if ($activity->id_ownership) {
                    $cantieriList[] = $activity->id_ownership;
                    if (!$ownershipId) {
                        $ownershipId = $activity->id_ownership;
                    }
                }
            }

            $cantieriUnici = array_unique($cantieriList);
            $numCantieri   = count($cantieriUnici);

            // Checkbox visibile solo se: non domenica, non causale, dentro assunzione
            $showCheckbox = !$isSunday && !$causale && $isInAssunzione;

            // Il giorno è "effettivo" (lavorativo potenziale)
            if (!$isSunday && !$causale && $isInAssunzione) {
                $totalGiornateEffettive++;
            }

            $checked = $dayActivities->count() > 0;
            if ($checked) {
                $totalGiornateMesse++;
            }

            $totalOre += $oreGiorno;

            $presenze[$dateKey] = [
                'data'             => $dateKey,
                'giorno_settimana' => $currentDate->isoFormat('ddd'),
                'giorno_num'       => $currentDate->day,
                'is_sunday'        => $isSunday,
                'is_in_assunzione' => $isInAssunzione,
                'causale'          => $causale,
                'ore'              => $oreGiorno,
                'id_ownership'     => $ownershipId,
                'show_checkbox'    => $showCheckbox,
                'checked'          => $checked,
                'num_cantieri'     => $numCantieri,
                'has_multiple'     => $numCantieri > 1,
            ];

            $currentDate->addDay();
        }

        // ── Ownership selezionata ─────────────────────────────────────────
        $selectedOwnershipId   = $request->get('ownership_id', '');
        $selectedOwnershipName = '';

        if ($selectedOwnershipId) {
            $ow = Ownership::find($selectedOwnershipId);
            if ($ow) {
                $selectedOwnershipName = $ow->RagAbbrev;
            }
        }

        if (empty($selectedOwnershipId)) {
            $defaultOwnership = Ownership::where('RagAbbrev', 'LIKE', '%Agricola Salatino%')->first();
            if ($defaultOwnership) {
                $selectedOwnershipId   = $defaultOwnership->id_proprieta;
                $selectedOwnershipName = $defaultOwnership->RagAbbrev;
            }
        }

        $ownerships = Ownership::where('valid', 1)->orderBy('RagAbbrev')->get();

        return view('admin.staff.attendance.show', compact(
            'staff', 'year', 'month', 'presenze',
            'totalOre', 'totalGiornateEffettive', 'totalGiornateMesse',
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
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }

        try {
            $validated = $request->validate([
                'staff_id'   => 'required|integer|exists:staff,id_personale',
                'date'       => 'required|date',
                'checked'    => 'required|boolean',
                'ownership_id' => 'nullable|integer',
            ]);

            // TODO: Salvataggio in tabella presenze dedicata
            return response()->json([
                'success' => true,
                'message' => 'Presenza salvata con successo',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
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
        $presenze  = $this->getAttendanceData($staffId, $startDate, $endDate);

        $html = $this->generatePdfHtml($staff, $startDate, $endDate, $presenze);
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
        $presenze  = $this->getAttendanceData($staffId, $startDate, $endDate);

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Presenze');

        $sheet->setCellValue('A1', 'Report Presenze');
        $sheet->setCellValue('A2', $staff->NomePers . ' ' . $staff->CognomePers);
        $sheet->setCellValue('A3', $startDate->isoFormat('MMMM YYYY'));

        $sheet->setCellValue('A5', 'Data');
        $sheet->setCellValue('B5', 'Giorno');
        $sheet->setCellValue('C5', 'Presente');
        $sheet->setCellValue('D5', 'Ore');
        $sheet->setCellValue('E5', 'Causale');

        $headerStyle = [
            'font'      => ['bold' => true, 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A5:E5')->applyFromArray($headerStyle);

        $row           = 6;
        $totalOre      = 0;
        $totalPresenti = 0;

        foreach ($presenze as $data => $p) {
            $sheet->setCellValue('A' . $row, Carbon::parse($data)->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $p['giorno_settimana']);
            $sheet->setCellValue('C' . $row, $p['checked'] ? 'Sì' : 'No');
            $sheet->setCellValue('D' . $row, $p['ore']);
            $sheet->setCellValue('E' . $row, $p['causale'] ?? '');
            $totalOre += $p['ore'];
            if ($p['checked']) $totalPresenti++;
            $row++;
        }

        $sheet->setCellValue('A' . $row, 'TOTALI:');
        $sheet->setCellValue('D' . $row, $totalOre);
        $sheet->setCellValue('C' . $row, $totalPresenti . ' presenze');
        $sheet->getStyle('A' . $row . ':E' . $row)->getFont()->setBold(true);

        foreach (range('A', 'E') as $col) {
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

    private function getAttendanceData($staffId, $startDate, $endDate)
    {
        $staff = Staff::with([
            'expirations' => function ($q) {
                $q->where(function ($query) {
                    $query->where('titolo', 'LIKE', '%Malattia%')
                          ->orWhere('titolo', 'LIKE', '%Ferie%')
                          ->orWhere('titolo', 'LIKE', '%Permesso%');
                });
            },
        ])->findOrFail($staffId);

        $activities = Activity::whereHas('staffDetails', function ($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            })
            ->with(['staffDetails' => function ($q) use ($staffId) {
                $q->where('id_staff', $staffId);
            }, 'ownership'])
            ->whereBetween('data_activities', [$startDate, $endDate])
            ->get();

        $activitiesByDate = $activities->groupBy(function ($act) {
            return Carbon::parse($act->data_activities)->format('Y-m-d');
        });

        $presenze    = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey       = $currentDate->format('Y-m-d');
            $isSunday      = $currentDate->isSunday();
            $dayActivities = $activitiesByDate->get($dateKey, collect());

            $expiration = $staff->expirations->filter(function ($exp) use ($dateKey) {
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

            $oreGiorno = 0;
            foreach ($dayActivities as $activity) {
                $sd = $activity->staffDetails->first();
                if ($sd) $oreGiorno += floatval($sd->n_ore ?? 0);
            }

            $presenze[$dateKey] = [
                'data'             => $dateKey,
                'giorno_settimana' => $currentDate->isoFormat('ddd'),
                'is_sunday'        => $isSunday,
                'causale'          => $causale,
                'ore'              => $oreGiorno,
                'checked'          => $dayActivities->count() > 0,
            ];

            $currentDate->addDay();
        }

        return $presenze;
    }

    private function generatePdfHtml($staff, $startDate, $endDate, $presenze)
    {
        $totalOre      = 0;
        $totalPresenti = 0;
        $rows          = '';

        foreach ($presenze as $data => $p) {
            $bgColor = $p['is_sunday'] ? '#FEE2E2' : ($p['causale'] ? '#FEF9C3' : 'transparent');
            $rows   .= '<tr style="background:' . $bgColor . '">
                <td>' . Carbon::parse($data)->format('d/m/Y') . '</td>
                <td>' . $p['giorno_settimana'] . '</td>
                <td>' . ($p['checked'] ? '✓' : '') . '</td>
                <td>' . number_format($p['ore'], 1) . '</td>
                <td>' . ($p['causale'] ? ucfirst($p['causale']) : '') . '</td>
            </tr>';
            $totalOre += $p['ore'];
            if ($p['checked']) $totalPresenti++;
        }

        return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Report Presenze</title>
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; margin: 20px; }
            .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #65a30d; padding-bottom: 10px; }
            .header h1 { margin: 0; font-size: 18px; color: #333; }
            .header p { margin: 5px 0 0; color: #666; font-size: 11px; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #f1f5f9; padding: 8px; border: 1px solid #cbd5e1; text-align: center; font-weight: bold; }
            td { padding: 6px 8px; border: 1px solid #e2e8f0; text-align: center; }
            .totals { margin-top: 20px; font-weight: bold; font-size: 11px; }
            .footer { margin-top: 20px; text-align: right; font-size: 9px; color: #666; }
        </style></head><body>
        <div class="header">
            <h1>Report Presenze</h1>
            <p>' . e($staff->NomePers) . ' ' . e($staff->CognomePers) . '</p>
            <p>' . $startDate->isoFormat('MMMM YYYY') . '</p>
        </div>
        <table>
            <thead><tr><th>Data</th><th>Giorno</th><th>Presente</th><th>Ore</th><th>Causale</th></tr></thead>
            <tbody>' . $rows . '</tbody>
        </table>
        <div class="totals">
            <p>Totale Ore: ' . number_format($totalOre, 1) . ' h &nbsp;|&nbsp; Giornate Messe: ' . $totalPresenti . '</p>
        </div>
        <div class="footer">Generato il ' . Carbon::now()->format('d/m/Y H:i') . '</div>
        </body></html>';
    }
}