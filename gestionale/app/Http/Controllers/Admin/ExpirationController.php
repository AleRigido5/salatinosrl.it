<?php
// app/Http/Controllers/Admin/ExpirationController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expiration;
use App\Models\Entity;
use App\Models\Ownership;
use App\Models\Setting;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpirationController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $staffId = $request->get('staff_id');
        $staffName = null;
        
        if ($staffId) {
            $staff = Staff::find($staffId);
            $staffName = $staff ? $staff->full_name : null;
        }
        
        return view('admin.expiration.index', compact('staffId', 'staffName'));
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

        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $tipologie = Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        return view('admin.expiration.create', compact('ownerships', 'entities', 'tipologie', 'staff', 'staffId'));
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
            'table_references' => 'nullable|string|in:' . implode(',', [
                Expiration::TABLE_STAFF, 
                Expiration::TABLE_ENTITY, 
                Expiration::TABLE_OWNERSHIP
            ]),
            'id_references' => 'nullable|integer',
            'id_entities' => 'nullable|exists:entities,id_cliente',
            'id_ownership' => 'nullable|exists:ownership,id_proprieta',
            'subtitolo' => 'nullable|string|max:255',
            'note' => 'nullable|string',
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
        
        // Gestione polimorfica
        if ($request->table_references && $request->id_references) {
            $data['table_references'] = $request->table_references;
            $data['id_references'] = $request->id_references;
        } elseif ($request->id_entities) {
            $data['id_entities'] = $request->id_entities;
        } elseif ($request->id_ownership) {
            $data['id_ownership'] = $request->id_ownership;
        }

        $expiration = Expiration::create($data);

        // Redirect appropriato in base al contesto
        if ($request->table_references === Expiration::TABLE_STAFF && $request->id_references) {
            return redirect()->route('admin.expiration.index', ['staff_id' => $request->id_references])
                ->with('success', 'Scadenza creata con successo!');
        }

        return redirect()->route('admin.expiration.index')
            ->with('success', 'Scadenza creata con successo!');
    }

    public function edit($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::with(['ownership', 'entity', 'setting', 'createdBy', 'updatedBy'])->findOrFail($id);
        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $entities = Entity::orderBy('ragione_sociale')->get();
        $tipologie = Setting::where('tabella_riferimento', 'expiration')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();
        
        $staffId = $request->get('staff_id');

        return view('admin.expiration.edit', compact('expiration', 'ownerships', 'entities', 'tipologie', 'staffId'));
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
            'table_references' => 'nullable|string|in:' . implode(',', [
                Expiration::TABLE_STAFF, 
                Expiration::TABLE_ENTITY, 
                Expiration::TABLE_OWNERSHIP
            ]),
            'id_references' => 'nullable|integer',
            'id_entities' => 'nullable|exists:entities,id_cliente',
            'id_ownership' => 'nullable|exists:ownership,id_proprieta',
            'subtitolo' => 'nullable|string|max:255',
            'note' => 'nullable|string',
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
        
        // Gestione polimorfica
        if ($request->table_references && $request->id_references) {
            $data['table_references'] = $request->table_references;
            $data['id_references'] = $request->id_references;
            $data['id_entities'] = null;
            $data['id_ownership'] = null;
        } elseif ($request->id_entities) {
            $data['id_entities'] = $request->id_entities;
            $data['table_references'] = null;
            $data['id_references'] = null;
        } elseif ($request->id_ownership) {
            $data['id_ownership'] = $request->id_ownership;
            $data['table_references'] = null;
            $data['id_references'] = null;
        }

        $expiration->update($data);

        $redirectUrl = route('admin.expiration.index');
        if ($request->staff_id) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $request->staff_id]);
        } elseif ($expiration->table_references === Expiration::TABLE_STAFF && $expiration->id_references) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $expiration->id_references]);
        }

        return redirect($redirectUrl)->with('success', 'Scadenza aggiornata con successo!');
    }

    public function destroy($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::findOrFail($id);
        $expiration->delete();

        $redirectUrl = route('admin.expiration.index');
        if ($request->staff_id) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $request->staff_id]);
        } elseif ($expiration->table_references === Expiration::TABLE_STAFF && $expiration->id_references) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $expiration->id_references]);
        }

        return redirect($redirectUrl)->with('success', 'Scadenza eliminata con successo!');
    }

    public function restore($id, Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $expiration = Expiration::withTrashed()->findOrFail($id);
        $expiration->restore();

        $redirectUrl = route('admin.expiration.index');
        if ($request->staff_id) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $request->staff_id]);
        } elseif ($expiration->table_references === Expiration::TABLE_STAFF && $expiration->id_references) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $expiration->id_references]);
        }

        return redirect($redirectUrl)->with('success', 'Scadenza ripristinata con successo!');
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
        
        $redirectUrl = route('admin.expiration.index');
        if ($request->staff_id) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $request->staff_id]);
        } elseif ($expiration->table_references === Expiration::TABLE_STAFF && $expiration->id_references) {
            $redirectUrl = route('admin.expiration.index', ['staff_id' => $expiration->id_references]);
        }
        
        return redirect($redirectUrl)->with('success', "Scadenza {$status} con successo!");
    }
}