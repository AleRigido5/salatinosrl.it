<?php
// app/Http/Controllers/Admin/ExpirationStaffController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expiration;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpirationStaffController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $staffId = $request->get('staffId');
        $staffName = null;
        
        if ($staffId) {
            $staff = Staff::find($staffId);
            $staffName = $staff ? $staff->full_name : null;
        }
        
        return view('admin.expiration-staff.index', compact('staffId', 'staffName'));
    }

    public function create(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staffId = $request->get('staffId');
        $staff = null;
        if ($staffId) {
            $staff = Staff::find($staffId);
        }

        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $tipologie = Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        return view('admin.expiration-staff.create', compact('ownerships', 'entities', 'tipologie', 'staff', 'staffId'));
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
            'id_references' => $request->staffId,
            'table_references' => Expiration::TABLE_STAFF,
        ];
        
        if ($request->id_entities) {
            $data['id_entities'] = $request->id_entities;
        }
        
        if ($request->id_ownership) {
            $data['id_ownership'] = $request->id_ownership;
        }

        $expiration = Expiration::create($data);

        return redirect()->route('admin.expiration-staff.index', ['staffId' => $request->staffId])
            ->with('success', 'Scadenza creata con successo!');
    }

    public function edit($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::with(['setting', 'createdBy', 'updatedBy'])->findOrFail($id);
        
        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $tipologie = Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
        
        $staffId = $request->get('staffId');

        return view('admin.expiration-staff.edit', compact('expiration', 'ownerships', 'entities', 'tipologie', 'staffId'));
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

        $staffId = $request->get('staffId');
        
        if (!$staffId && $expiration->table_references === Expiration::TABLE_STAFF) {
            $staffId = $expiration->id_references;
        }
        
        if ($staffId) {
            return redirect()->route('admin.expiration-staff.index', ['staffId' => $staffId])
                ->with('success', 'Scadenza aggiornata con successo!');
        }
        
        return redirect()->route('admin.expiration-staff.index')
            ->with('success', 'Scadenza aggiornata con successo!');
    }

    public function destroy($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::findOrFail($id);
        $expiration->delete();

        $staffId = $request->get('staffId');

        if ($staffId) {
            return redirect()->route('admin.expiration-staff.index', ['staffId' => $staffId])
                ->with('success', 'Scadenza eliminata con successo!');
        }

        return redirect()->route('admin.expiration-staff.index')
            ->with('success', 'Scadenza eliminata con successo!');
    }

    public function restore($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::withTrashed()->findOrFail($id);
        $expiration->restore();

        $staffId = $request->get('staffId');

        if ($staffId) {
            return redirect()->route('admin.expiration-staff.index', ['staffId' => $staffId])
                ->with('success', 'Scadenza ripristinata con successo!');
        }

        return redirect()->route('admin.expiration-staff.index')
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
        
        $staffId = $request->get('staffId');

        if ($staffId) {
            return redirect()->route('admin.expiration-staff.index', ['staffId' => $staffId])
                ->with('success', "Scadenza {$status} con successo!");
        }

        return redirect()->route('admin.expiration-staff.index')
            ->with('success', "Scadenza {$status} con successo!");
    }
}