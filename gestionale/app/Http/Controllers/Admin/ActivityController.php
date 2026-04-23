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

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_activities')) {
            return redirect()->route('admin.activities.index')
                ->with('error', 'Permessi insufficienti');
        }
        
        $request->validate([
            'data_activities' => 'nullable|date',
            'id_cost_centers' => 'required|exists:cost_centers,id',
            'id_services' => 'required|exists:services,id',
            'id_entities' => 'required|exists:entities,id_cliente',
            'note' => 'nullable|string',
            'staff' => 'array',
            'staff.*.id_staff' => 'required|exists:staff,id_personale',
            'staff.*.n_ore' => 'nullable|numeric',
            'staff.*.spese' => 'nullable|numeric',
            'staff.*.note' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get names for old input preservation
            $costCenter = CostCenter::find($request->id_cost_centers);
            $service = Service::find($request->id_services);
            $entity = Entity::find($request->id_entities);
            
            // Create activity
            $activity = Activity::create([
                'id_cost_centers' => $request->id_cost_centers,
                'id_services' => $request->id_services,
                'id_entities' => $request->id_entities,
                'data_activities' => $request->data_activities,
                'note' => $request->note,
                'invoice_references' => $request->invoice_references ?? null,
                'imponibile' => $request->imponibile ?? null,
                'costi_mat' => $request->costi_mat ?? null,
                'created_by' => Auth::guard('admin')->id(),
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            // Create staff links
            if ($request->has('staff') && is_array($request->staff)) {
                foreach ($request->staff as $staffItem) {
                    if (!empty($staffItem['id_staff'])) {
                        ActivityStaffLink::create([
                            'id_activities' => $activity->id,
                            'id_staff' => $staffItem['id_staff'],
                            'n_ore' => $staffItem['n_ore'] ?? 0,
                            'spese' => $staffItem['spese'] ?? 0,
                            'note' => $staffItem['note'] ?? null,
                            'data_att' => $request->data_activities,
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.activities.index')
                ->with('success', 'Attività creata con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Preserve form data with names
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

    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $activity = Activity::with('staffDetails')->findOrFail($id);
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
        
        return view('admin.activities.edit', compact('activity', 'costCenters', 'services', 'entities', 'staffList'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_activities')) {
            return redirect()->route('admin.activities.index')
                ->with('error', 'Permessi insufficienti');
        }
        
        $request->validate([
            'data_activities' => 'nullable|date',
            'id_cost_centers' => 'required|exists:cost_centers,id',
            'id_services' => 'required|exists:services,id',
            'id_entities' => 'required|exists:entities,id_cliente',
            'note' => 'nullable|string',
            'staff' => 'array',
            'staff.*.id_staff' => 'required|exists:staff,id_personale',
            'staff.*.n_ore' => 'nullable|numeric',
            'staff.*.spese' => 'nullable|numeric',
            'staff.*.note' => 'nullable|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            $activity = Activity::findOrFail($id);
            
            // Update activity
            $activity->update([
                'id_cost_centers' => $request->id_cost_centers,
                'id_services' => $request->id_services,
                'id_entities' => $request->id_entities,
                'data_activities' => $request->data_activities,
                'note' => $request->note,
                'invoice_references' => $request->invoice_references ?? null,
                'imponibile' => $request->imponibile ?? null,
                'costi_mat' => $request->costi_mat ?? null,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            // Delete existing staff links
            ActivityStaffLink::where('id_activities', $id)->delete();
            
            // Create new staff links
            if ($request->has('staff') && is_array($request->staff)) {
                foreach ($request->staff as $staffItem) {
                    if (!empty($staffItem['id_staff'])) {
                        ActivityStaffLink::create([
                            'id_activities' => $activity->id,
                            'id_staff' => $staffItem['id_staff'],
                            'n_ore' => $staffItem['n_ore'] ?? 0,
                            'spese' => $staffItem['spese'] ?? 0,
                            'note' => $staffItem['note'] ?? null,
                            'data_att' => $request->data_activities,
                        ]);
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->route('admin.activities.index')
                ->with('success', 'Attività aggiornata con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
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
}