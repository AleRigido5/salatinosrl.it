<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Activity;
use App\Models\ActivityStaffLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class StaffController extends Controller
{
    /**
     * Display a listing of the staff.
     */
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
            }
            abort(403, 'Non hai i permessi necessari per visualizzare il personale.');
        }
        
        if (request()->ajax()) {
            $staff = Staff::orderBy('CognomePers')->orderBy('NomePers')->get();
            return response()->json(['success' => true, 'data' => $staff]);
        }
        
        return view('admin.staff.index');
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_staff')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.staff.create');
    }
    
    /**
     * Store a newly created staff in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $request->validate([
            'NomePers' => 'nullable|string|max:255',
            'CognomePers' => 'nullable|string|max:255',
            'Soprannome' => 'nullable|string|max:255',
            'IndirPers' => 'nullable|string|max:255',
            'CittaPers' => 'nullable|string|max:50',
            'ProvPers' => 'nullable|string|max:5',
            'CapPers' => 'nullable|string|max:10',
            'TelPers' => 'nullable|string|max:20',
            'CellPers' => 'nullable|string|max:20',
            'EmailPers' => 'nullable|email|max:255',
            'CodFiscPers' => 'nullable|string|max:20',
            'DataNascPers' => 'nullable|date',
            'LuogoNasc' => 'nullable|string|max:50',
        ]);
        
        try {
            $staff = Staff::create([
                'NomePers' => $request->NomePers,
                'CognomePers' => $request->CognomePers,
                'Soprannome' => $request->Soprannome,
                'IndirPers' => $request->IndirPers,
                'CittaPers' => $request->CittaPers,
                'ProvPers' => $request->ProvPers,
                'CapPers' => $request->CapPers,
                'TelPers' => $request->TelPers,
                'CellPers' => $request->CellPers,
                'EmailPers' => $request->EmailPers,
                'CodFiscPers' => $request->CodFiscPers,
                'DataNascPers' => $request->DataNascPers,
                'LuogoNasc' => $request->LuogoNasc,
                'valid' => true
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Personale aggiunto con successo!',
                'data' => $staff
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API search for staff (AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $results = Staff::where('valid', 1)
            ->where(function($q) use ($query) {
                $q->where('NomePers', 'like', '%' . $query . '%')
                ->orWhere('CognomePers', 'like', '%' . $query . '%')
                ->orWhereRaw("CONCAT(NomePers, ' ', CognomePers) LIKE ?", ['%' . $query . '%'])
                ->orWhereRaw("CONCAT(CognomePers, ' ', NomePers) LIKE ?", ['%' . $query . '%']);
            })
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->limit(10)
            ->get(['id_personale', 'NomePers', 'CognomePers']);
        
        // Add full_name field
        $results->transform(function($item) {
            $item->full_name = trim($item->CognomePers . ' ' . $item->NomePers);
            return $item;
        });
        
        return response()->json($results);
    }
    
    /**
     * Display the specified staff.
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $staff = Staff::findOrFail($id);
        
        return response()->json(['success' => true, 'data' => $staff]);
    }
    
    /**
     * Show the form for editing the specified staff.
     */
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $staff = Staff::findOrFail($id);
        return view('admin.staff.edit', compact('staff'));
    }
    
    /**
     * Update the specified staff in storage.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $staff = Staff::findOrFail($id);
        
        $request->validate([
            'NomePers' => 'nullable|string|max:255',
            'CognomePers' => 'nullable|string|max:255',
            'Soprannome' => 'nullable|string|max:255',
            'IndirPers' => 'nullable|string|max:255',
            'CittaPers' => 'nullable|string|max:50',
            'ProvPers' => 'nullable|string|max:5',
            'CapPers' => 'nullable|string|max:10',
            'TelPers' => 'nullable|string|max:20',
            'CellPers' => 'nullable|string|max:20',
            'EmailPers' => 'nullable|email|max:255',
            'CodFiscPers' => 'nullable|string|max:20',
            'DataNascPers' => 'nullable|date',
            'LuogoNasc' => 'nullable|string|max:50',
        ]);
        
        try {
            $staff->update([
                'NomePers' => $request->NomePers,
                'CognomePers' => $request->CognomePers,
                'Soprannome' => $request->Soprannome,
                'IndirPers' => $request->IndirPers,
                'CittaPers' => $request->CittaPers,
                'ProvPers' => $request->ProvPers,
                'CapPers' => $request->CapPers,
                'TelPers' => $request->TelPers,
                'CellPers' => $request->CellPers,
                'EmailPers' => $request->EmailPers,
                'CodFiscPers' => $request->CodFiscPers,
                'DataNascPers' => $request->DataNascPers,
                'LuogoNasc' => $request->LuogoNasc,
                'valid' => $request->boolean('valid', $staff->valid)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Personale aggiornato con successo!',
                'data' => $staff
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified staff from storage.
     */
    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        try {
            $staff = Staff::findOrFail($id);
            $name = trim($staff->NomePers . ' ' . $staff->CognomePers);
            $staff->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Personale '{$name}' eliminato con successo!"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle staff status (active/inactive).
     */
    public function toggleStatus($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        try {
            $staff = Staff::findOrFail($id);
            $staff->update(['valid' => !$staff->valid]);
            
            $status = $staff->valid ? 'attivato' : 'disattivato';
            
            return response()->json([
                'success' => true,
                'message' => "Personale {$status} con successo!",
                'data' => $staff
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il cambio di stato: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display activity report for a staff member.
     */
    public function activityReport($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activity_report')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $staff = Staff::findOrFail($id);
        
        // Gestione mese/anno o range personalizzato
        $selectedMonth = $request->get('month', Carbon::now()->format('m'));
        $selectedYear = $request->get('year', Carbon::now()->format('Y'));
        $currentYear = Carbon::now()->year;
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
            $selectedMonth = $dateFrom->format('m');
            $selectedYear = $dateFrom->format('Y');
        } else {
            $dateFrom = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
            $dateTo = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->endOfMonth();
        }
        
        // Query attività per questo staff filtrando per data_activities
        $activities = Activity::whereHas('staffDetails', function($q) use ($staff) {
            $q->where('id_staff', $staff->id_personale);
        })
        ->with([
            'costCenter', 
            'service', 
            'entity',
            'staffDetails' => function($q) use ($staff) {
                $q->where('id_staff', $staff->id_personale);
            }
        ])
        ->whereBetween('data_activities', [$dateFrom, $dateTo])
        ->orderBy('data_activities', 'asc')
        ->get();
        
        // Calcolo statistiche
        $totalHours = 0;
        $totalMaturato = 0;
        $totalSpese = 0;
        $totalCostoOrario = 0;
        $activityCount = 0;
        
        foreach ($activities as $activity) {
            $staffDetail = $activity->staffDetails->first();
            if ($staffDetail) {
                $ore = floatval($staffDetail->n_ore ?? 0);
                $costoOrario = floatval($staffDetail->costo_orario ?? 0);
                $spese = floatval($staffDetail->spese ?? 0);
                
                $totalHours += $ore;
                $totalMaturato += $ore * $costoOrario;
                $totalSpese += $spese;
                if ($ore > 0) {
                    $totalCostoOrario += $costoOrario;
                    $activityCount++;
                }
            }
        }
        
        // Giornate effettive calcolate con 7 ore giornaliere
        $hoursPerDay = 7;
        $totalWorkingDays = $totalHours / $hoursPerDay;
        $averageHourlyCost = $activityCount > 0 ? $totalCostoOrario / $activityCount : 0;
        
        // Mesi per navigazione
        $currentDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1);
        $previousMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();
        
        return view('admin.staff.activity-report', compact(
            'staff', 'activities', 'dateFrom', 'dateTo',
            'selectedMonth', 'selectedYear', 'currentYear',
            'previousMonth', 'nextMonth',
            'totalHours', 'totalMaturato', 'totalSpese',
            'totalWorkingDays', 'averageHourlyCost', 'hoursPerDay'
        ));
    }

    // ==================== METODI PER TOOLTIP (AJAX) ====================

    /**
     * Update ore for staff detail
     */
    public function updateOre($staffDetailId, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $staffDetail = ActivityStaffLink::findOrFail($staffDetailId);
            $value = $request->input('value');
            
            $staffDetail->update([
                'n_ore' => floatval($value)
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update costo orario for staff detail
     */
    public function updateCostoOrario($staffDetailId, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $staffDetail = ActivityStaffLink::findOrFail($staffDetailId);
            $value = $request->input('value');
            
            $staffDetail->update([
                'costo_orario' => floatval($value)
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Bulk update costo orario for multiple staff details
     */
    public function bulkUpdateCosto($staffId, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $costoOrario = floatval($request->input('costo_orario'));
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            
            // Prendi tutti gli staffDetail per questo staff nel periodo
            $query = ActivityStaffLink::whereHas('activity', function($q) use ($dateFrom, $dateTo) {
                if ($dateFrom && $dateTo) {
                    $q->whereBetween('data_activities', [$dateFrom, $dateTo]);
                }
            })->where('id_staff', $staffId);
            
            $count = $query->count();
            $query->update(['costo_orario' => $costoOrario]);
            
            return response()->json([
                'success' => true,
                'updated' => $count,
                'message' => "Aggiornati $count record"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update spese for staff detail
     */
    public function updateSpese($staffDetailId, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $staffDetail = ActivityStaffLink::findOrFail($staffDetailId);
            $value = $request->input('value');
            
            $staffDetail->update([
                'spese' => floatval($value)
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update note for activity (tabella activities)
     */
    public function updateActivityNote($activityId, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $activity = Activity::findOrFail($activityId);
            $value = $request->input('value');
            
            $activity->update([
                'note' => $value
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update staff note in activities_staff_lnk
     */
    public function updateStaffNote($staffDetailId, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $staffDetail = ActivityStaffLink::findOrFail($staffDetailId);
            $value = $request->input('value');
            
            $staffDetail->update([
                'note' => $value
            ]);
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ==================== METODI PER ESPORTAZIONE ====================

    /**
     * Export report to PDF
     */
    public function exportReportPdf($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activity_report')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $staff = Staff::findOrFail($id);
        
        // Gestione date
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
        } elseif ($request->has('month') && $request->has('year')) {
            $dateFrom = Carbon::createFromDate($request->year, $request->month, 1)->startOfMonth();
            $dateTo = Carbon::createFromDate($request->year, $request->month, 1)->endOfMonth();
        } else {
            $dateFrom = Carbon::now()->startOfMonth();
            $dateTo = Carbon::now()->endOfMonth();
        }
        
        // Query attività
        $activities = Activity::whereHas('staffDetails', function($q) use ($staff) {
            $q->where('id_staff', $staff->id_personale);
        })
        ->with([
            'costCenter', 
            'service', 
            'entity.addresses',
            'staffDetails' => function($q) use ($staff) {
                $q->where('id_staff', $staff->id_personale);
            }
        ])
        ->whereBetween('data_activities', [$dateFrom, $dateTo])
        ->orderBy('data_activities', 'asc')
        ->get();
        
        // Prepara i dati per il PDF (solo i campi richiesti)
        $reportData = [];
        $totalHours = 0;
        $totalMaturato = 0;
        $totalSpese = 0;
        
        foreach ($activities as $activity) {
            $staffDetail = $activity->staffDetails->first();
            if ($staffDetail) {
                $ore = floatval($staffDetail->n_ore ?? 0);
                $costoOrario = floatval($staffDetail->costo_orario ?? 0);
                $spese = floatval($staffDetail->spese ?? 0);
                
                // Ottieni località
                $costCenter = $activity->costCenter;
                $entity = $activity->entity;
                $localita = $costCenter && $costCenter->Localita ? $costCenter->Localita : '-';
                if ($localita == '-' && $entity && $entity->addresses && $entity->addresses->isNotEmpty()) {
                    $primaryAddress = $entity->addresses->firstWhere('sede', 'principale') ?? $entity->addresses->first();
                    $localita = $primaryAddress->citta ?? '-';
                }
                
                $serviceName = $activity->service ? $activity->service->Titolo : '-';
                $displayLocalitaServizio = $localita != '-' ? $localita . ' / ' . $serviceName : $serviceName;
                
                $reportData[] = [
                    'data' => Carbon::parse($activity->data_activities)->format('d/m/Y'),
                    'localita_servizio' => $displayLocalitaServizio,
                    'ore' => $ore,
                    'costo_orario' => $costoOrario,
                    'spese' => $spese,
                ];
                
                $totalHours += $ore;
                $totalMaturato += $ore * $costoOrario;
                $totalSpese += $spese;
            }
        }
        
        // Creazione HTML per il PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Report Attività ' . e($staff->CognomePers) . ' ' . e($staff->NomePers) . '</title>
            <style>
                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 10px;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #4CAF50;
                    padding-bottom: 10px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 16px;
                    color: #333;
                }
                .header p {
                    margin: 5px 0 0;
                    color: #666;
                    font-size: 10px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
                }
                th {
                    background-color: #4CAF50;
                    color: white;
                    padding: 8px;
                    text-align: center;
                    font-size: 10px;
                }
                td {
                    border: 1px solid #ddd;
                    padding: 6px;
                    text-align: center;
                    font-size: 9px;
                }
                td:first-child, td:nth-child(2) {
                    text-align: left;
                }
                .footer {
                    margin-top: 20px;
                    text-align: right;
                    font-size: 9px;
                    color: #666;
                    border-top: 1px solid #ddd;
                    padding-top: 10px;
                }
                .totals {
                    margin-top: 15px;
                    text-align: right;
                    font-size: 10px;
                    font-weight: bold;
                }
                .totals table {
                    width: auto;
                    float: right;
                    margin-top: 0;
                }
                .totals td {
                    border: none;
                    padding: 4px 8px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Report Attività - ' . e($staff->NomePers) . ' ' . e($staff->CognomePers) . '</h1>
                <p>Periodo: ' . $dateFrom->format('d/m/Y') . ' - ' . $dateTo->format('d/m/Y') . '</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Località / Servizio</th>
                        <th>N. Ore</th>
                        <th>Costo €/h</th>
                        <th>Spese (€)</th>
                    </tr>
                </thead>
                <tbody>';
        
        if (empty($reportData)) {
            $html .= '<tr><td colspan="5" style="text-align: center;">Nessuna attività trovata</td></tr>';
        } else {
            foreach ($reportData as $item) {
                $html .= '
                    <tr>
                        <td>' . e($item['data']) . '</td>
                        <td>' . e($item['localita_servizio']) . '</td>
                        <td>' . number_format($item['ore'], 1) . '</td>
                        <td>€ ' . number_format($item['costo_orario'], 2) . '</td>
                        <td>€ ' . number_format($item['spese'], 2) . '</td>
                    </tr>';
            }
        }
        
        $totalGenerico = $totalMaturato + $totalSpese;
        
        $html .= '
                </tbody>
            </table>
            
            <div class="totals">
                <table>
                    <tr>
                        <td><strong>Totale Ore:</strong></td>
                        <td>' . number_format($totalHours, 1) . ' h</td>
                    </tr>
                    <tr>
                        <td><strong>Totale Maturato:</strong></td>
                        <td>€ ' . number_format($totalMaturato, 2) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Totale Spese:</strong></td>
                        <td>€ ' . number_format($totalSpese, 2) . '</td>
                    </tr>
                    <tr style="border-top: 1px solid #ddd;">
                        <td><strong>TOTALE GENERALE:</strong></td>
                        <td><strong>€ ' . number_format($totalGenerico, 2) . '</strong></td>
                    </tr>
                </table>
            </div>
            
            <div class="footer">
                Documento generato il ' . Carbon::now()->format('d/m/Y H:i:s') . '
            </div>
        </body>
        </html>';
        
        // Genera PDF direttamente dall'HTML
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        
        // Download diretto
        return $pdf->download("report_{$staff->CognomePers}_{$staff->NomePers}_{$dateFrom->format('Y-m-d')}_{$dateTo->format('Y-m-d')}.pdf");
    }

    /**
     * Export report to Excel
     */
    public function exportReportExcel($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activity_report')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $staff = Staff::findOrFail($id);
        
        // Gestione date
        if ($request->has('date_from') && $request->has('date_to')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $dateTo = Carbon::parse($request->date_to)->endOfDay();
        } elseif ($request->has('month') && $request->has('year')) {
            $dateFrom = Carbon::createFromDate($request->year, $request->month, 1)->startOfMonth();
            $dateTo = Carbon::createFromDate($request->year, $request->month, 1)->endOfMonth();
        } else {
            $dateFrom = Carbon::now()->startOfMonth();
            $dateTo = Carbon::now()->endOfMonth();
        }
        
        // Query attività con tutti i dati
        $activities = Activity::whereHas('staffDetails', function($q) use ($staff) {
            $q->where('id_staff', $staff->id_personale);
        })
        ->with([
            'costCenter', 
            'service', 
            'entity.addresses',
            'staffDetails' => function($q) use ($staff) {
                $q->where('id_staff', $staff->id_personale);
            }
        ])
        ->whereBetween('data_activities', [$dateFrom, $dateTo])
        ->orderBy('data_activities', 'asc')
        ->get();
        
        // Crea nuovo foglio Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Titolo del foglio
        $sheet->setTitle('Report Attività');
        
        // Intestazioni (tutti i campi)
        $headers = [
            'A1' => 'Data',
            'B1' => 'Cliente',
            'C1' => 'Cantiere',
            'D1' => 'Località',
            'E1' => 'Servizio',
            'F1' => 'N. Ore',
            'G1' => 'Costo €/h',
            'H1' => 'Maturato (€)',
            'I1' => 'Spese (€)',
            'J1' => 'Totale (€)',
            'K1' => 'Note Attività',
            'L1' => 'Note Staff',
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Stile intestazioni
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ];
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);
        
        // Popola i dati
        $row = 2;
        $totalHours = 0;
        $totalMaturato = 0;
        $totalSpese = 0;
        
        foreach ($activities as $activity) {
            $staffDetail = $activity->staffDetails->first();
            if ($staffDetail) {
                $entity = $activity->entity;
                $costCenter = $activity->costCenter;
                
                $clienteNome = $entity ? ($entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome)) : '-';
                $cantiereNome = $costCenter ? $costCenter->Nome : '-';
                $localita = $costCenter && $costCenter->Localita ? $costCenter->Localita : '-';
                
                if ($localita == '-' && $entity && $entity->addresses && $entity->addresses->isNotEmpty()) {
                    $primaryAddress = $entity->addresses->firstWhere('sede', 'principale') ?? $entity->addresses->first();
                    $localita = $primaryAddress->citta ?? '-';
                }
                
                $serviceName = $activity->service ? $activity->service->Titolo : '-';
                $ore = floatval($staffDetail->n_ore ?? 0);
                $costoOrario = floatval($staffDetail->costo_orario ?? 0);
                $spese = floatval($staffDetail->spese ?? 0);
                $maturato = $ore * $costoOrario;
                $totale = $maturato + $spese;
                
                $sheet->setCellValue('A' . $row, Carbon::parse($activity->data_activities)->format('d/m/Y'));
                $sheet->setCellValue('B' . $row, $clienteNome);
                $sheet->setCellValue('C' . $row, $cantiereNome);
                $sheet->setCellValue('D' . $row, $localita);
                $sheet->setCellValue('E' . $row, $serviceName);
                $sheet->setCellValue('F' . $row, $ore);
                $sheet->setCellValue('G' . $row, $costoOrario);
                $sheet->setCellValue('H' . $row, $maturato);
                $sheet->setCellValue('I' . $row, $spese);
                $sheet->setCellValue('J' . $row, $totale);
                $sheet->setCellValue('K' . $row, $activity->note ?? '');
                $sheet->setCellValue('L' . $row, $staffDetail->note ?? '');
                
                $totalHours += $ore;
                $totalMaturato += $maturato;
                $totalSpese += $spese;
                $row++;
            }
        }
        
        // Riga totali
        $sheet->setCellValue('E' . $row, 'TOTALI:');
        $sheet->setCellValue('F' . $row, $totalHours);
        $sheet->setCellValue('H' . $row, $totalMaturato);
        $sheet->setCellValue('I' . $row, $totalSpese);
        $sheet->setCellValue('J' . $row, $totalMaturato + $totalSpese);
        
        // Stile riga totali
        $sheet->getStyle('E' . $row . ':J' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF3C7']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        
        // Unisci celle per la riga totali
        $sheet->mergeCells('A' . $row . ':D' . $row);
        $sheet->mergeCells('G' . $row . ':G' . $row);
        
        // Stile per i dati
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A2:L' . ($row - 1))->applyFromArray($dataStyle);
        
        // Allineamento numeri a destra
        $sheet->getStyle('F2:F' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('G2:G' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('H2:H' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('I2:I' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('J2:J' . ($row - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Formatta i numeri
        $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode('0.00');
        $sheet->getStyle('G2:G' . ($row - 1))->getNumberFormat()->setFormatCode('€ #,##0.00');
        $sheet->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('€ #,##0.00');
        $sheet->getStyle('I2:I' . ($row - 1))->getNumberFormat()->setFormatCode('€ #,##0.00');
        $sheet->getStyle('J2:J' . ($row - 1))->getNumberFormat()->setFormatCode('€ #,##0.00');
        
        // Auto-size delle colonne
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Aggiungi filtro
        $sheet->setAutoFilter('A1:L' . ($row - 1));
        
        // Blocca la prima riga
        $sheet->freezePane('A2');
        
        // Crea il file e scarica
        $writer = new Xlsx($spreadsheet);
        $filename = "report_{$staff->CognomePers}_{$staff->NomePers}_{$dateFrom->format('Y-m-d')}_{$dateTo->format('Y-m-d')}.xlsx";
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');
        
        $writer->save('php://output');
        exit;
    }
}