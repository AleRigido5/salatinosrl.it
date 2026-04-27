<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Activity;
use App\Models\ActivityStaffLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $activityIds = ActivityStaffLink::where('id_staff', $staff->id_personale)
            ->pluck('id_activities');
        
        $activities = Activity::with(['costCenter', 'service', 'entity', 'staffDetails' => function($q) use ($staff) {
            $q->where('id_staff', $staff->id_personale);
        }])
        ->whereIn('id', $activityIds)
        ->whereBetween('data_activities', [$dateFrom, $dateTo])
        ->orderBy('data_activities', 'desc')
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
                $costoOrario = floatval($staffDetail->costo_h ?? 0);
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
        
        $totalWorkingDays = $totalHours / 8;
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
            'totalWorkingDays', 'averageHourlyCost'
        ));
    }
}