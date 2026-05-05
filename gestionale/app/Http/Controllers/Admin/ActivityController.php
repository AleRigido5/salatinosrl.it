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
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activities')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        // Salva i filtri correnti nella sessione prima del redirect
        if ($request->has('reset_filters')) {
            session()->forget('activities_filters');
        } elseif ($request->query()) {
            session(['activities_filters' => $request->query()]);
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
        Log::info('=== EDIT ACTIVITY - ID: ' . $id . ' ===');
        
        $activity = Activity::with(['costCenter', 'service', 'entity', 'staffDetails.staff'])->findOrFail($id);
        
        Log::info('Activity trovata - ID: ' . $activity->id . ', Data: ' . $activity->data_activities);
        Log::info('Staff associati: ' . $activity->staffDetails->count());
        
        return view('admin.activities.edit', compact('activity'));
    }

    public function update(Request $request, $id)
    {
        Log::info('=== UPDATE ACTIVITY - INIZIO ===');
        Log::info('Activity ID: ' . $id);
        Log::info('Request data:', $request->all());
        
        // Recupera i filtri dalla request
        $filters = $request->input('filters', []);
        Log::info('Filtri da mantenere:', $filters);
        
        try {
            // VALIDAZIONE
            Log::info('Step 0: Validazione dati...');
            
            $validated = $request->validate([
                'data_activities' => 'required|date',
                'id_cost_centers' => 'required|exists:cost_centers,id',
                'id_entities' => 'nullable|exists:entities,id_cliente',
                'id_services' => 'required|exists:services,id',
                'invoice_references' => 'nullable|string|max:255',
                'note' => 'nullable|string',
                'staff' => 'array',
                'staff.*.id_staff' => 'required|exists:staff,id_personale',
                'staff.*.n_ore' => 'required|numeric|min:0.5',
                'staff.*.spese' => 'nullable|numeric|min:0',
                'staff.*.note' => 'nullable|string',
            ]);
            
            Log::info('Validazione passata con successo');
            
            DB::beginTransaction();
            Log::info('Transazione DB avviata');
            
            $activity = Activity::findOrFail($id);
            Log::info('Activity trovata - ID: ' . $activity->id);
            
            // 1. AGGIORNA I CAMPI BASE
            $activity->update([
                'data_activities' => $validated['data_activities'],
                'id_cost_centers' => $validated['id_cost_centers'],
                'id_entities' => $validated['id_entities'] ?? null,
                'id_services' => $validated['id_services'],
                'invoice_references' => $validated['invoice_references'] ?? '',
                'note' => $validated['note'] ?? null,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            Log::info('Step 1: Activity aggiornata - ID: ' . $activity->id);
            
            // 2. ELIMINA TUTTI GLI STAFF ESISTENTI
            $deleted = $activity->staffDetails()->delete();
            Log::info('Step 2: Staff eliminati - Count: ' . $deleted);
            
            // 3. INSERISCI I NUOVI STAFF
            $staffInserted = 0;
            if (!empty($validated['staff'])) {
                Log::info('Step 3: Inizio inserimento nuovi staff - Totale da inserire: ' . count($validated['staff']));
                
                foreach ($validated['staff'] as $index => $staffItem) {
                    Log::info('Processo staff item ' . $index . ': ' . json_encode($staffItem));
                    
                    if (!empty($staffItem['id_staff']) && !empty($staffItem['n_ore'])) {
                        $newStaff = $activity->staffDetails()->create([
                            'id_activities' => $activity->id,
                            'id_staff' => $staffItem['id_staff'],
                            'n_ore' => floatval($staffItem['n_ore']),
                            'spese' => isset($staffItem['spese']) ? floatval($staffItem['spese']) : 0,
                            'note' => $staffItem['note'] ?? null,
                            'data_att' => $validated['data_activities'] ?? null,
                            'created_by' => Auth::guard('admin')->id(),
                            'updated_by' => Auth::guard('admin')->id(),
                        ]);
                        $staffInserted++;
                        Log::info("Step 3: Staff inserito - ID_staff: {$staffItem['id_staff']}, Ore: {$staffItem['n_ore']}, Link ID: {$newStaff->id}");
                    } else {
                        Log::warning('Staff item ' . $index . ' saltato - dati mancanti');
                    }
                }
            } else {
                Log::info('Step 3: Nessuno staff da inserire');
            }
            
            DB::commit();
            Log::info('Transazione DB committata con successo');
            Log::info('=== UPDATE COMPLETATO CON SUCCESSO ===');
            Log::info("Activity ID: {$activity->id}, Staff inseriti: {$staffInserted}");
            
            // Redirect alla pagina delle attività con i filtri
            return redirect()->route('admin.activities.index', $filters)
                ->with('success', "Attività modificata con successo! ({$staffInserted} persone associate)");
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('ERRORE DI VALIDAZIONE: ' . json_encode($e->errors()));
            
            return redirect()->route('admin.activities.index', $filters)
                ->with('error', 'Errore di validazione: ' . implode(', ', array_merge(...array_values($e->errors()))));
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRORE UPDATE: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Redirect alla pagina delle attività con l'errore
            return redirect()->route('admin.activities.index', $filters)
                ->with('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        Log::info('=== STORE ACTIVITY - INIZIO ===');
        Log::info('Request data:', $request->all());
        
        if (!Auth::guard('admin')->user()->hasPermission('create_activities')) {
            return redirect()->route('admin.activities.index')
                ->with('error', 'Permessi insufficienti');
        }
        
        // Prepara i dati dello staff
        $staffData = [];
        if ($request->has('staff') && is_array($request->staff)) {
            foreach ($request->staff as $index => $staffItem) {
                if (empty($staffItem['id_staff'])) {
                    continue;
                }
                
                $staffData[] = [
                    'id_staff' => $staffItem['id_staff'],
                    'n_ore' => isset($staffItem['n_ore']) ? floatval(str_replace(',', '.', $staffItem['n_ore'])) : 0,
                    'spese' => isset($staffItem['spese']) ? floatval(str_replace(',', '.', $staffItem['spese'])) : 0,
                    'note' => $staffItem['note'] ?? null,
                ];
            }
        }
        
        Log::info('Staff data preparato:', $staffData);
        
        try {
            // Validazione
            $validated = $request->validate([
                'data_activities' => 'nullable|date',
                'id_cost_centers' => 'required|exists:cost_centers,id',
                'id_services' => 'required|exists:services,id',
                'id_entities' => 'nullable|exists:entities,id_cliente',
                'note' => 'nullable|string',
                'imponibile' => 'nullable|numeric|min:0',
                'costi_mat' => 'nullable|numeric|min:0',
            ]);
            
            // Validazione personalizzata per lo staff
            if (empty($staffData)) {
                return redirect()->route('admin.activities.index')
                    ->with('error', 'Aggiungi almeno un membro del personale');
            }
            
            foreach ($staffData as $index => $staffItem) {
                if ($staffItem['n_ore'] <= 0) {
                    return redirect()->route('admin.activities.index')
                        ->with('error', "Il membro del personale " . ($index + 1) . " deve avere ore maggiori di zero");
                }
            }
            
            DB::beginTransaction();
            Log::info('Transazione DB avviata');
            
            // Creazione activity
            $activity = Activity::create([
                'id_cost_centers' => $validated['id_cost_centers'],
                'id_services' => $validated['id_services'],
                'id_entities' => $validated['id_entities'] ?? null,
                'data_activities' => $validated['data_activities'] ?? null,
                'note' => $validated['note'] ?? null,
                'invoice_references' => '',
                'imponibile' => $validated['imponibile'] ?? 0,
                'costi_mat' => $validated['costi_mat'] ?? 0,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            Log::info('Activity creata con ID: ' . $activity->id);
            
            // Salva STAFF
            $staffInserted = 0;
            foreach ($staffData as $staffItem) {
                $staffLink = ActivityStaffLink::create([
                    'id_activities' => $activity->id,
                    'id_staff' => $staffItem['id_staff'],
                    'n_ore' => $staffItem['n_ore'],
                    'spese' => $staffItem['spese'],
                    'note' => $staffItem['note'],
                    'data_att' => $validated['data_activities'] ?? null,
                    'created_by' => Auth::guard('admin')->id(),
                ]);
                $staffInserted++;
                Log::info("Staff inserito: ID_staff={$staffItem['id_staff']}, Ore={$staffItem['n_ore']}");
            }
            
            DB::commit();
            Log::info('Transazione DB committata con successo');
            
            return redirect()->route('admin.activities.index')
                ->with('success', "Attività creata con successo! ({$staffInserted} persone associate)");
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('ERRORE DI VALIDAZIONE STORE: ' . json_encode($e->errors()));
            
            return redirect()->route('admin.activities.index')
                ->with('error', 'Errore di validazione: ' . implode(', ', array_merge(...array_values($e->errors()))));
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRORE CREAZIONE ATTIVITA: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return redirect()->route('admin.activities.index')
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
            Log::error('ERRORE ELIMINAZIONE: ' . $e->getMessage());
            
            return redirect()->route('admin.activities.index')
                ->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
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
            Log::error('ERRORE UPDATE LAT/LONG: ' . $e->getMessage());
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
            Log::error('ERRORE UPDATE HA: ' . $e->getMessage());
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
            Log::error('ERRORE UPDATE INVOICE REF: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}