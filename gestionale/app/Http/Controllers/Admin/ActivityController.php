<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\CostCenter;
use App\Models\Service;
use App\Models\Entity;
use App\Models\Staff;
use App\Models\ActivityStaffLink;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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
        
        $filters = session('activities_filters', []);
        
        try {
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
            
            DB::beginTransaction();
            
            $activity = Activity::findOrFail($id);
            
            // 1. AGGIORNA I CAMPI BASE - id_entities può essere null
            $activity->update([
                'data_activities' => $validated['data_activities'],
                'id_cost_centers' => $validated['id_cost_centers'],
                'id_entities' => !empty($validated['id_entities']) ? $validated['id_entities'] : null,
                'id_services' => $validated['id_services'],
                'invoice_references' => $validated['invoice_references'] ?? '',
                'note' => $validated['note'] ?? null,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            Log::info('Step 1: Activity aggiornata - ID: ' . $activity->id . ', id_entities: ' . ($activity->id_entities ?? 'NULL'));
            
            // 2. RECUPERA I COSTI ORARI ESISTENTI PRIMA DI ELIMINARE
            // Mappa id_staff => costo_orario, così possiamo ripristinarlo se lo stesso
            // membro dello staff è ancora presente dopo la modifica.
            $existingCosti = $activity->staffDetails()
                ->pluck('costo_orario', 'id_staff');
            
            Log::info('Step 2a: Costi orari esistenti recuperati - Totale: ' . $existingCosti->count());
            
            // 3. ELIMINA TUTTI GLI STAFF ESISTENTI
            $deleted = $activity->staffDetails()->delete();
            Log::info('Step 2b: Staff eliminati - Count: ' . $deleted);
            
            // 4. INSERISCI I NUOVI STAFF (ripristinando il costo_orario se già presente)
            $staffInserted = 0;
            if (!empty($validated['staff'])) {
                Log::info('Step 3: Inizio inserimento nuovi staff - Totale da inserire: ' . count($validated['staff']));
                
                foreach ($validated['staff'] as $index => $staffItem) {
                    if (!empty($staffItem['id_staff']) && !empty($staffItem['n_ore'])) {
                        
                        // Se questo membro dello staff era già associato a questa attività,
                        // riusa il suo costo_orario storico. Altrimenti resta null.
                        $costoOrario = $existingCosti->get($staffItem['id_staff']) ?? null;
                        
                        $newStaff = $activity->staffDetails()->create([
                            'id_activities' => $activity->id,
                            'id_staff' => $staffItem['id_staff'],
                            'n_ore' => floatval($staffItem['n_ore']),
                            'costo_orario' => $costoOrario,
                            'spese' => isset($staffItem['spese']) ? floatval($staffItem['spese']) : 0,
                            'note' => $staffItem['note'] ?? null,
                            'data_att' => $validated['data_activities'] ?? null,
                            'created_by' => Auth::guard('admin')->id(),
                            'updated_by' => Auth::guard('admin')->id(),
                        ]);
                        $staffInserted++;
                        Log::info("Step 3: Staff inserito - ID_staff: {$staffItem['id_staff']}, Ore: {$staffItem['n_ore']}, Costo orario: " . ($costoOrario ?? 'NULL (nuovo)') . ", Link ID: {$newStaff->id}");
                    }
                }
            }
            
            DB::commit();
            Log::info('Transazione DB committata con successo');
            Log::info('=== UPDATE COMPLETATO CON SUCCESSO ===');
            Log::info("Activity ID: {$activity->id}, Staff inseriti: {$staffInserted}");
            
            return redirect()->route('admin.activities.index', $filters)
                ->with('success', "Attività modificata con successo!");
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('ERRORE DI VALIDAZIONE: ' . json_encode($e->errors()));
            
            return redirect()->route('admin.activities.index', $filters)
                ->with('error', 'Errore di validazione: ' . implode(', ', array_merge(...array_values($e->errors()))));
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ERRORE UPDATE: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
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

    public function exportPdf(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activities')) {
            abort(403);
        }

        // Imposta date di default se non specificate
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->startOfMonth();
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfMonth();

        $query = Activity::with([
            'costCenter:id,Nome,Localita,table_references',
            'service:id,Titolo,Descrizione',
            'entity:id_cliente,ragione_sociale,nome,cognome,partita_iva',
            'staffDetails.staff:id_personale,NomePers,CognomePers',
        ]);

        // 1. FILTRO DATA
        $query->whereBetween('data_activities', [$dateFrom, $dateTo]);

        // 2. FILTRO CENTRO DI COSTO
        if ($request->filled('cost_center_filter')) {
            $query->where('id_cost_centers', $request->cost_center_filter);
        }

        // 3. FILTRO SERVIZIO
        if ($request->filled('service_filter')) {
            $query->where('id_services', $request->service_filter);
        }

        // 4. FILTRO ENTITA' (Cliente/Fornitore)
        if ($request->filled('entity_filter')) {
            $query->where('id_entities', $request->entity_filter);
        }

        // 5. FILTRO POSIZIONI (Aperte/Interne)
        if ($request->filled('position_filter')) {
            if ($request->position_filter === 'aperte') {
                // ATTIVITÀ APERTE: clienti esterni + fattura vuota
                $query->where(function($q) {
                    $q->whereNull('activities.invoice_references')
                    ->orWhere('activities.invoice_references', '');
                })->whereExists(function($q) {
                    $q->select(DB::raw(1))
                    ->from('cost_centers')
                    ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                    ->where('cost_centers.table_references', 'entities');
                });
            } elseif ($request->position_filter === 'interne') {
                // ATTIVITÀ INTERNE: clienti interni (NON esterni)
                $query->whereNotExists(function($q) {
                    $q->select(DB::raw(1))
                    ->from('cost_centers')
                    ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                    ->where('cost_centers.table_references', 'entities');
                });
            }
        }

        // 6. RICERCA GENERICA
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_references', 'like', $searchTerm)
                ->orWhere('note', 'like', $searchTerm)
                ->orWhere('ha', 'like', $searchTerm)
                ->orWhere('Lat_Long', 'like', $searchTerm);
            });
        }

        // Ordina per data
        $query->orderBy('data_activities', 'desc');
        
        $activities = $query->get();

        // Costruisci l'HTML del PDF
        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; margin: 15px; }
            .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #84cc16; padding-bottom: 8px; }
            .header h1 { margin: 0; font-size: 14px; color: #333; }
            .header p { margin: 3px 0 0; color: #666; font-size: 9px; }
            .filters-info { background-color: #f3f4f6; padding: 5px; margin-bottom: 10px; font-size: 8px; border-radius: 4px; }
            .filters-info span { color: #059669; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #84cc16; color: white; padding: 6px; text-align: left; font-size: 9px; }
            td { border: 1px solid #ddd; padding: 4px 6px; font-size: 8px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .footer { margin-top: 15px; text-align: right; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
            .badge { background-color: #84cc16; color: white; padding: 2px 6px; border-radius: 4px; font-size: 7px; display: inline-block; }
        </style>
        </head><body>';

        // Header
        $html .= '<div class="header">
            <h1>Report Attività</h1>
            <p>Periodo: ' . $dateFrom->format('d/m/Y') . ' - ' . $dateTo->format('d/m/Y') . '</p>
        </div>';

        // Filtri attivi (opzionale)
        $activeFilters = [];
        if ($request->filled('cost_center_filter')) $activeFilters[] = 'Centro di Costo';
        if ($request->filled('service_filter')) $activeFilters[] = 'Servizio';
        if ($request->filled('entity_filter')) $activeFilters[] = 'Cliente/Fornitore';
        if ($request->filled('position_filter')) $activeFilters[] = 'Posizione: ' . $request->position_filter;
        if ($request->filled('search')) $activeFilters[] = 'Ricerca: ' . $request->search;
        
        if (!empty($activeFilters)) {
            $html .= '<div class="filters-info">Filtri attivi: <span>' . implode(' | ', $activeFilters) . '</span></div>';
        }

        $html .= '<table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Cantiere</th>
                    <th>Servizio</th>
                    <th>Personale (Ore)</th>
                    <th>Note</th>
                    <th>ha</th>
                    <th>Lat/Long</th>
                    <th>Rif. Fattura</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($activities as $activity) {
            $entity = $activity->entity;
            $clienteNome = $entity ? ($entity->ragione_sociale ?: trim($entity->nome . ' ' . $entity->cognome)) : '-';
            $cantiere = $activity->costCenter->Nome ?? '-';
            $servizio = $activity->service->Titolo ?? '-';
            
            // Calcola totale ore e lista personale
            $personaleHtml = '';
            foreach ($activity->staffDetails as $sd) {
                $staffName = trim(($sd->staff->CognomePers ?? '') . ' ' . ($sd->staff->NomePers ?? ''));
                $ore = number_format(floatval($sd->n_ore), 1);
                $personaleHtml .= $staffName . ' (' . $ore . 'h)<br>';
            }
            if (empty($personaleHtml)) $personaleHtml = '-';

            $html .= '<tr>
                <td>' . e(Carbon::parse($activity->data_activities)->format('d/m/Y')) . '</td>
                <td>' . e($clienteNome) . '</td>
                <td>' . e($cantiere) . '</td>
                <td>' . e($servizio) . '</td>
                <td>' . $personaleHtml . '</td>
                <td>' . e($activity->note ?? '-') . '</td>
                <td>' . ($activity->ha ? number_format(floatval($activity->ha), 2) : '-') . '</td>
                <td>' . e($activity->Lat_Long ?? '-') . '</td>
                <td>' . e($activity->invoice_references ?? '-') . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
            <div class="footer">
                Totale attività: ' . $activities->count() . ' — Generato il ' . Carbon::now()->format('d/m/Y H:i') . '
            </div>
            </body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'landscape');
        return $pdf->download("attivita_{$dateFrom->format('Y-m-d')}_{$dateTo->format('Y-m-d')}.pdf");
    }

    public function exportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_activities')) {
            abort(403);
        }

        // Imposta date di default se non specificate
        $dateFrom = $request->date_from
            ? Carbon::parse($request->date_from)->startOfDay()
            : Carbon::now()->startOfMonth();
        $dateTo = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : Carbon::now()->endOfMonth();

        $query = Activity::with([
            'costCenter:id,Nome,Localita,table_references',
            'service:id,Titolo,Descrizione',
            'entity:id_cliente,ragione_sociale,nome,cognome,partita_iva',
            'staffDetails.staff:id_personale,NomePers,CognomePers',
        ]);

        // 1. FILTRO DATA
        $query->whereBetween('data_activities', [$dateFrom, $dateTo]);

        // 2. FILTRO CENTRO DI COSTO
        if ($request->filled('cost_center_filter')) {
            $query->where('id_cost_centers', $request->cost_center_filter);
        }

        // 3. FILTRO SERVIZIO
        if ($request->filled('service_filter')) {
            $query->where('id_services', $request->service_filter);
        }

        // 4. FILTRO ENTITA' (Cliente/Fornitore)
        if ($request->filled('entity_filter')) {
            $query->where('id_entities', $request->entity_filter);
        }

        // 5. FILTRO POSIZIONI (Aperte/Interne)
        if ($request->filled('position_filter')) {
            if ($request->position_filter === 'aperte') {
                $query->where(function($q) {
                    $q->whereNull('activities.invoice_references')
                    ->orWhere('activities.invoice_references', '');
                })->whereExists(function($q) {
                    $q->select(DB::raw(1))
                    ->from('cost_centers')
                    ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                    ->where('cost_centers.table_references', 'entities');
                });
            } elseif ($request->position_filter === 'interne') {
                $query->whereNotExists(function($q) {
                    $q->select(DB::raw(1))
                    ->from('cost_centers')
                    ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                    ->where('cost_centers.table_references', 'entities');
                });
            }
        }

        // 6. RICERCA GENERICA
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('invoice_references', 'like', $searchTerm)
                ->orWhere('note', 'like', $searchTerm)
                ->orWhere('ha', 'like', $searchTerm)
                ->orWhere('Lat_Long', 'like', $searchTerm);
            });
        }

        $query->orderBy('data_activities', 'desc');
        $activities = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attività');

        // INTESTAZIONI
        $headers = [
            'A1' => 'Data',
            'B1' => 'Cliente',
            'C1' => 'Cantiere',
            'D1' => 'Servizio',
            'E1' => 'Personale (Ore)',
            'F1' => 'Note',
            'G1' => 'ha',
            'H1' => 'Lat/Long',
            'I1' => 'Rif. Fattura'
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Stile intestazioni
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '84cc16']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        // DATI
        $row = 2;
        foreach ($activities as $activity) {
            $entity = $activity->entity;
            $clienteNome = $entity ? ($entity->ragione_sociale ?: trim($entity->nome . ' ' . $entity->cognome)) : '-';
            $cantiere = $activity->costCenter->Nome ?? '-';
            $servizio = $activity->service->Titolo ?? '-';
            
            // Costruisci la stringa del personale
            $personaleStr = '';
            foreach ($activity->staffDetails as $index => $sd) {
                $staffName = trim(($sd->staff->CognomePers ?? '') . ' ' . ($sd->staff->NomePers ?? ''));
                $ore = number_format(floatval($sd->n_ore), 1);
                $personaleStr .= $staffName . ' (' . $ore . 'h)';
                if ($index < $activity->staffDetails->count() - 1) $personaleStr .= "\n";
            }
            if (empty($personaleStr)) $personaleStr = '-';

            $sheet->setCellValue('A' . $row, Carbon::parse($activity->data_activities)->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $clienteNome);
            $sheet->setCellValue('C' . $row, $cantiere);
            $sheet->setCellValue('D' . $row, $servizio);
            $sheet->setCellValue('E' . $row, $personaleStr);
            $sheet->setCellValue('F' . $row, $activity->note ?? '');
            $sheet->setCellValue('G' . $row, $activity->ha ? number_format(floatval($activity->ha), 2) : '');
            $sheet->setCellValue('H' . $row, $activity->Lat_Long ?? '');
            $sheet->setCellValue('I' . $row, $activity->invoice_references ?? '');

            // Abilita wrap text per la colonna E
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
            
            // Righe alternate
            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }

            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);

            $row++;
        }

        // Riga totale attività
        $sheet->setCellValue('A' . $row, 'Totale attività: ' . $activities->count());
        $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);
        $sheet->mergeCells('A' . $row . ':I' . $row);

        // Auto-size colonne
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Imposta altezza riga 1
        $sheet->getRowDimension(1)->setRowHeight(25);
        
        // Filtro automatico
        $sheet->setAutoFilter('A1:I' . ($row - 1));
        
        // Congela la prima riga
        $sheet->freezePane('A2');

        $filename = "attivita_{$dateFrom->format('Y-m-d')}_{$dateTo->format('Y-m-d')}.xlsx";

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}