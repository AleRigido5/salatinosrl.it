<?php
// app/Http/Controllers/Admin/ExpirationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expiration;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\Vehicles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpirationController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        // Supporto per staff_id (legacy)
        $staffId = $request->get('staff_id');
        $staffName = null;
        
        // Nuovi parametri unificati
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');
        $entityName = $request->get('entityName');
        
        // Se c'è staff_id, usa quello (retrocompatibilità)
        if ($staffId) {
            $staff = Staff::find($staffId);
            $staffName = $staff ? $staff->full_name : null;
            $entityId = $staffId;
            $entityType = 'staff';
            $entityName = $staffName;
        }
        
        // Se c'è entityId ma non entityName, cerca il nome
        if ($entityId && !$entityName) {
            if ($entityType === 'staff') {
                $staff = Staff::find($entityId);
                $entityName = $staff ? $staff->full_name : null;
                $staffName = $entityName;
                $staffId = $entityId;
            } elseif ($entityType === 'vehicle') {
                $vehicle = Vehicles::find($entityId);
                $entityName = $vehicle ? ($vehicle->full_name ?? $vehicle->targa) : null;
            }
        }
        
        return view('admin.expiration.index', compact('staffId', 'staffName', 'entityId', 'entityType', 'entityName'));
    }

    public function create(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staffId = $request->get('staff_id');
        $staff = null;
        if ($staffId) {
            $staff = Staff::find($staffId);
        }
        
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');
        $entity = null;
        
        if ($entityType === 'staff' && $entityId) {
            $entity = Staff::find($entityId);
        } elseif ($entityType === 'vehicle' && $entityId) {
            $entity = Vehicles::find($entityId);
        }

        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $vehicles = Vehicles::where('valid', 1)->orderBy('targa')->get();
        $tipologie = Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        return view('admin.expiration.create', compact('ownerships', 'entities', 'vehicles', 'tipologie', 'staff', 'staffId', 'entity', 'entityId', 'entityType'));
    }

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $request->validate([
            'titolo' => 'required|string|max:255',
            'id_settings' => 'required|exists:settings,id',
            'data_inizio' => 'required|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'subtitolo' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'id_entities' => 'nullable|exists:entities,id_cliente',
            'id_ownership' => 'nullable|exists:ownership,id_proprieta',
            'vehicle_ids' => 'nullable|array',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        $adminId = Auth::guard('admin')->id();

        $data = [
            'titolo' => $request->titolo,
            'id_settings' => $request->id_settings,
            'data_inizio' => $request->data_inizio,
            'data_fine' => $request->data_fine,
            'subtitolo' => $request->subtitolo,
            'note' => $request->note,
            'created_by' => $adminId,
            'updated_by' => $adminId
        ];
        
        if ($request->id_entities) {
            $data['id_entities'] = $request->id_entities;
        }
        
        if ($request->id_ownership) {
            $data['id_ownership'] = $request->id_ownership;
        }
        
        // Gestione per STAFF
        if ($request->staff_id) {
            $data['id_references'] = $request->staff_id;
            $data['table_references'] = Expiration::TABLE_STAFF;
            $expiration = Expiration::create($data);
            return redirect()->route('admin.expiration.index', ['staff_id' => $request->staff_id])
                ->with('success', 'Scadenza creata con successo!');
        }
        
        // Gestione per VEHICLE (singolo dalla tabella mezzi)
        if ($request->vehicle_id) {
            $data['table_references'] = Expiration::TABLE_VEHICLE;
            $expiration = Expiration::create($data);
            $expiration->vehicles()->attach($request->vehicle_id);
            return redirect()->route('admin.expiration.index', ['entityId' => $request->vehicle_id, 'entityType' => 'vehicle'])
                ->with('success', 'Scadenza creata con successo!');
        }
        
        // Gestione per VEHICOLI MULTIPLI (dal form)
        $expiration = Expiration::create($data);
        
        if ($request->has('vehicle_ids') && count($request->vehicle_ids) > 0) {
            $expiration->vehicles()->attach($request->vehicle_ids);
        }
        
        $redirectParams = [];
        if ($request->entityId && $request->entityType) {
            $redirectParams = ['entityId' => $request->entityId, 'entityType' => $request->entityType];
        }

        return redirect()->route('admin.expiration.index', $redirectParams)
            ->with('success', 'Scadenza creata con successo!');
    }

    public function edit($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::with(['setting', 'createdBy', 'updatedBy', 'vehicles'])->findOrFail($id);
        
        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $vehicles = Vehicles::where('valid', 1)->orderBy('targa')->get();
        $tipologie = Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
        
        $staffId = $request->get('staff_id');
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        return view('admin.expiration.edit', compact('expiration', 'ownerships', 'entities', 'vehicles', 'tipologie', 'staffId', 'entityId', 'entityType'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::findOrFail($id);

        $request->validate([
            'titolo' => 'required|string|max:255',
            'id_settings' => 'required|exists:settings,id',
            'data_inizio' => 'required|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'subtitolo' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'id_entities' => 'nullable|exists:entities,id_cliente',
            'id_ownership' => 'nullable|exists:ownership,id_proprieta',
            'vehicle_ids' => 'nullable|array',
            'vehicle_ids.*' => 'exists:vehicles,id',
        ]);

        $data = [
            'titolo' => $request->titolo,
            'id_settings' => $request->id_settings,
            'data_inizio' => $request->data_inizio,
            'data_fine' => $request->data_fine,
            'subtitolo' => $request->subtitolo,
            'note' => $request->note,
            'updated_by' => Auth::guard('admin')->id(),
            'updated_at' => now()
        ];
        
        if ($request->id_entities) {
            $data['id_entities'] = $request->id_entities;
        }
        
        if ($request->id_ownership) {
            $data['id_ownership'] = $request->id_ownership;
        }

        $expiration->update($data);
        
        // Aggiorna la relazione con i veicoli (many-to-many)
        if ($request->has('vehicle_ids')) {
            $expiration->vehicles()->sync($request->vehicle_ids);
        }

        // Recupera lo staff_id dal request
        $staffId = $request->get('staff_id');
        
        // Recupera entityId e entityType
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');
        
        // Se non arriva staff_id, cerca di recuperarlo dalla scadenza
        if (!$staffId && $expiration->table_references === Expiration::TABLE_STAFF) {
            $staffId = $expiration->id_references;
        }
        
        // Se non arriva entityId, cerca di recuperarlo per veicoli
        if (!$entityId && $expiration->table_references === Expiration::TABLE_VEHICLE) {
            $firstVehicle = $expiration->vehicles->first();
            if ($firstVehicle) {
                $entityId = $firstVehicle->id;
                $entityType = 'vehicle';
            }
        }
        
        // Redirect con parametri appropriati
        if ($staffId) {
            return redirect()->route('admin.expiration.index', ['staff_id' => $staffId])
                ->with('success', 'Scadenza aggiornata con successo!');
        }
        
        if ($entityId && $entityType) {
            return redirect()->route('admin.expiration.index', ['entityId' => $entityId, 'entityType' => $entityType])
                ->with('success', 'Scadenza aggiornata con successo!');
        }
        
        return redirect()->route('admin.expiration.index')
            ->with('success', 'Scadenza aggiornata con successo!');
    }

    public function destroy($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::findOrFail($id);
        $expiration->delete();
        
        $staffId = $request->get('staff_id');
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        if ($staffId) {
            return redirect()->route('admin.expiration.index', ['staff_id' => $staffId])
                ->with('success', 'Scadenza eliminata con successo!');
        }
        
        if ($entityId && $entityType) {
            return redirect()->route('admin.expiration.index', ['entityId' => $entityId, 'entityType' => $entityType])
                ->with('success', 'Scadenza eliminata con successo!');
        }

        return redirect()->route('admin.expiration.index')
            ->with('success', 'Scadenza eliminata con successo!');
    }

    public function restore($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::withTrashed()->findOrFail($id);
        $expiration->restore();
        
        $staffId = $request->get('staff_id');
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        if ($staffId) {
            return redirect()->route('admin.expiration.index', ['staff_id' => $staffId])
                ->with('success', 'Scadenza ripristinata con successo!');
        }
        
        if ($entityId && $entityType) {
            return redirect()->route('admin.expiration.index', ['entityId' => $entityId, 'entityType' => $entityType])
                ->with('success', 'Scadenza ripristinata con successo!');
        }

        return redirect()->route('admin.expiration.index')
            ->with('success', 'Scadenza ripristinata con successo!');
    }

    public function toggleStatus($id, Request $request)
    {
        $expiration = Expiration::withTrashed()->findOrFail($id);
        
        if ($expiration->trashed()) {
            $expiration->restore();
            $status = 'riattivata';
        } else {
            $expiration->delete();
            $status = 'disattivata';
        }
        
        $staffId = $request->get('staff_id');
        $entityId = $request->get('entityId');
        $entityType = $request->get('entityType');

        if ($staffId) {
            return redirect()->route('admin.expiration.index', ['staff_id' => $staffId])
                ->with('success', "Scadenza {$status} con successo!");
        }
        
        if ($entityId && $entityType) {
            return redirect()->route('admin.expiration.index', ['entityId' => $entityId, 'entityType' => $entityType])
                ->with('success', "Scadenza {$status} con successo!");
        }

        return redirect()->route('admin.expiration.index')
            ->with('success', "Scadenza {$status} con successo!");
    }
    
    // API per la ricerca veicoli
    public function searchVehicles(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_expiration')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $term = $request->get('q', '');
        
        if (strlen($term) < 2) {
            return response()->json([]);
        }
        
        $vehicles = Vehicles::where(function($q) use ($term) {
                $q->where('targa', 'like', '%' . $term . '%')
                  ->orWhere('marca', 'like', '%' . $term . '%')
                  ->orWhere('modello', 'like', '%' . $term . '%');
            })
            ->where('valid', 1)
            ->orderBy('targa')
            ->limit(10)
            ->get()
            ->map(function($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'full_name' => $vehicle->full_name ?? $vehicle->targa,
                    'targa' => $vehicle->targa,
                    'marca' => $vehicle->marca,
                    'modello' => $vehicle->modello,
                ];
            });
        
        return response()->json($vehicles);
    }
}