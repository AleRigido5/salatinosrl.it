<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CostCenter;
use App\Models\Service;
use App\Models\Entity;
use App\Models\Staff;
use App\Models\ActivityStaffLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        return view('admin.activities.index');
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_activities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $costCenters = CostCenter::where('valid', 1)->orderBy('Nome')->get();
        $services = Service::where('Stato', 1)->orderBy('Titolo')->get();
        $entities = Entity::where('valid', 1)
            ->orderBy('ragione_sociale')
            ->orderBy('nome')
            ->get();
        $staffList = Staff::where('valid', 1)
            ->orderBy('CognomePers')
            ->orderBy('NomePers')
            ->get();
        
        return view('admin.activities.create', compact('costCenters', 'services', 'entities', 'staffList'));
    }

    public function edit($id)
    {
        $activity = Activity::with(['costCenter', 'service', 'entity', 'staffDetails.staff'])->findOrFail($id);
        return view('admin.activities.edit', compact('activity'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'data_activities' => 'required|date',
            'id_cost_centers' => 'required|exists:cost_centers,id',
            'id_entities' => 'nullable|exists:entities,id_cliente', // MODIFICATO: nullable
            'id_services' => 'required|exists:services,id',
            'invoice_references' => 'nullable|string|max:255',
            'imponibile' => 'nullable|numeric|min:0',
            'costi_mat' => 'nullable|numeric|min:0',
            'note' => 'nullable|string',
            'staff' => 'array',
            'staff.*.id_staff' => 'required|exists:staff,id_personale',
            'staff.*.n_ore' => 'required|numeric|min:0.5',
            'staff.*.spese' => 'nullable|numeric|min:0',
            'staff.*.note' => 'nullable|string',
        ]);

        $activity = Activity::findOrFail($id);
        
        $activity->update([
            'data_activities' => $validated['data_activities'],
            'id_cost_centers' => $validated['id_cost_centers'],
            'id_entities' => $validated['id_entities'] ?? null, // Può essere null
            'id_services' => $validated['id_services'],
            'invoice_references' => $validated['invoice_references'] ?? null,
            'imponibile' => $validated['imponibile'] ?? 0,
            'costi_mat' => $validated['costi_mat'] ?? 0,
            'note' => $validated['note'] ?? null,
        ]);
        
        // Aggiorna il personale associato
        $activity->staffDetails()->delete();
        
        if (!empty($validated['staff'])) {
            foreach ($validated['staff'] as $staffItem) {
                $activity->staffDetails()->create([
                    'id_staff' => $staffItem['id_staff'],
                    'n_ore' => $staffItem['n_ore'],
                    'spese' => $staffItem['spese'] ?? 0,
                    'note' => $staffItem['note'] ?? null,
                    'data_att' => $validated['data_activities'] ?? null,
                ]);
            }
        }
        
        return redirect()->route('admin.activities.index')
            ->with('success', 'Attività modificata con successo!');
    }

    public function store(Request $request)
    {
        // Log per debug
        Log::info('=== STORE ACTIVITY ===');
        Log::info('Request data:', $request->all());
        
        if (!Auth::guard('admin')->user()->hasPermission('create_activities')) {
            return redirect()->route('admin.activities.index')
                ->with('error', 'Permessi insufficienti');
        }
        
        // VALIDAZIONE CORRETTA - id_entities è NULLABLE, invoice_references è NULLABLE
        $validated = $request->validate([
            'data_activities' => 'nullable|date',
            'id_cost_centers' => 'required|exists:cost_centers,id',
            'id_services' => 'required|exists:services,id',
            'id_entities' => 'nullable|exists:entities,id_cliente', // NULLABLE
            'invoice_references' => 'nullable|string|max:255', // NULLABLE
            'note' => 'nullable|string',
            'imponibile' => 'nullable|numeric|min:0',
            'costi_mat' => 'nullable|numeric|min:0',
            'staff' => 'array',
            'staff.*.id_staff' => 'required|exists:staff,id_personale',
            'staff.*.n_ore' => 'required|numeric|min:0.5',
            'staff.*.spese' => 'nullable|numeric|min:0',
            'staff.*.note' => 'nullable|string',
        ]);
        
        Log::info('Validazione superata:', $validated);
        
        try {
            DB::beginTransaction();
            
            // Creazione activity con campi opzionali
            $activityData = [
                'id_cost_centers' => $validated['id_cost_centers'],
                'id_services' => $validated['id_services'],
                'id_entities' => $validated['id_entities'] ?? null, // Può essere null
                'data_activities' => $validated['data_activities'] ?? null,
                'note' => $validated['note'] ?? null,
                'invoice_references' => $validated['invoice_references'] ?? '', // Default vuoto se NOT NULL
                'imponibile' => $validated['imponibile'] ?? 0,
                'costi_mat' => $validated['costi_mat'] ?? 0,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
            ];
            
            // Se invoice_references è NOT NULL in DB, assicurati che abbia un valore
            if (!isset($activityData['invoice_references']) || $activityData['invoice_references'] === null) {
                $activityData['invoice_references'] = '';
            }
            
            Log::info('Dati per creazione activity:', $activityData);
            
            $activity = Activity::create($activityData);
            
            Log::info('Activity creata con ID: ' . $activity->id);
            
            // Salva staff
            if (!empty($validated['staff'])) {
                foreach ($validated['staff'] as $staffItem) {
                    if (!empty($staffItem['id_staff']) && !empty($staffItem['n_ore'])) {
                        ActivityStaffLink::create([
                            'id_activities' => $activity->id,
                            'id_staff' => $staffItem['id_staff'],
                            'n_ore' => $staffItem['n_ore'],
                            'spese' => $staffItem['spese'] ?? 0,
                            'note' => $staffItem['note'] ?? null,
                            'data_att' => $validated['data_activities'] ?? null,
                            'created_by' => Auth::guard('admin')->id(),
                        ]);
                        Log::info("Staff inserito: ID {$staffItem['id_staff']}, Ore {$staffItem['n_ore']}");
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.activities.index')
                ->with('success', 'Attività creata con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('ERRORE creazione attività: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()->withInput()
                ->with('error', 'Errore durante la creazione: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $activity = Activity::with(['costCenter', 'service', 'entity', 'staffDetails.staff'])
            ->findOrFail($id);
        
        return view('admin.activities.show', compact('activity'));
    }

    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_activities')) {
            return redirect()->route('admin.activities.index')
                ->with('error', 'Permessi insufficienti');
        }
        
        try {
            $activity = Activity::findOrFail($id);
            $activity->delete();
            
            return redirect()->route('admin.activities.index')
                ->with('success', 'Attività eliminata con successo!');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }

    // API METHODS
    public function updateLatLong(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);
            $activity->Lat_Long = $request->value;
            $activity->save();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateHa(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);
            $activity->ha = $request->value;
            $activity->save();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function updateInvoiceRef(Request $request, $id)
    {
        try {
            $activity = Activity::findOrFail($id);
            $activity->invoice_references = $request->value;
            $activity->save();
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}