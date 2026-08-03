<?php
// app/Http/Controllers/Admin/ExpirationVehicleController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expiration;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\Setting;
use App\Models\Vehicles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpirationVehicleController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $vehicleId = $request->get('vehicleId');
        $vehicleName = null;
        
        if ($vehicleId) {
            $vehicle = Vehicles::find($vehicleId);
            $vehicleName = $vehicle ? ($vehicle->full_name ?? $vehicle->targa) : null;
        }
        
        return view('admin.expiration-vehicle.index', compact('vehicleId', 'vehicleName'));
    }

    public function create(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $vehicleId = $request->get('vehicleId');
        $vehicle = null;
        if ($vehicleId) {
            $vehicle = Vehicles::find($vehicleId);
        }

        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $tipologie = Setting::where('tabella_riferimento', 'vehicles_expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        return view('admin.expiration-vehicle.create', compact('ownerships', 'entities', 'tipologie', 'vehicle', 'vehicleId'));
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
            'updated_by' => $adminId,
            'table_references' => Expiration::TABLE_VEHICLE,
        ];
        
        if ($request->id_entities) {
            $data['id_entities'] = $request->id_entities;
        }
        
        if ($request->id_ownership) {
            $data['id_ownership'] = $request->id_ownership;
        }

        $expiration = Expiration::create($data);
        $expiration->vehicles()->attach($request->vehicleId);

        return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $request->vehicleId])
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
        $tipologie = Setting::where('tabella_riferimento', 'vehicles_expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
        
        $vehicleId = $request->get('vehicleId');

        return view('admin.expiration-vehicle.edit', compact('expiration', 'ownerships', 'entities', 'tipologie', 'vehicleId'));
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

        $vehicleId = $request->get('vehicleId');
        
        if (!$vehicleId && $expiration->table_references === Expiration::TABLE_VEHICLE) {
            $firstVehicle = $expiration->vehicles->first();
            if ($firstVehicle) {
                $vehicleId = $firstVehicle->id;
            }
        }
        
        if ($vehicleId) {
            return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId])
                ->with('success', 'Scadenza aggiornata con successo!');
        }
        
        return redirect()->route('admin.expiration-vehicle.index')
            ->with('success', 'Scadenza aggiornata con successo!');
    }

    public function destroy($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::findOrFail($id);
        $expiration->delete();

        $vehicleId = $request->get('vehicleId');

        if ($vehicleId) {
            return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId])
                ->with('success', 'Scadenza eliminata con successo!');
        }

        return redirect()->route('admin.expiration-vehicle.index')
            ->with('success', 'Scadenza eliminata con successo!');
    }

    public function restore($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::withTrashed()->findOrFail($id);
        $expiration->restore();

        $vehicleId = $request->get('vehicleId');

        if ($vehicleId) {
            return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId])
                ->with('success', 'Scadenza ripristinata con successo!');
        }

        return redirect()->route('admin.expiration-vehicle.index')
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
        
        $vehicleId = $request->get('vehicleId');

        if ($vehicleId) {
            return redirect()->route('admin.expiration-vehicle.index', ['vehicleId' => $vehicleId])
                ->with('success', "Scadenza {$status} con successo!");
        }

        return redirect()->route('admin.expiration-vehicle.index')
            ->with('success', "Scadenza {$status} con successo!");
    }
}