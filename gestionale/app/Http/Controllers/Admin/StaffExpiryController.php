<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffExpiry;
use App\Models\Ownership;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffExpiryController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff_expiry')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        return view('admin.staff-expiry.index');
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_staff_expiry')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $tipologie = Setting::where('tabella_riferimento', 'staff_expiry')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        return view('admin.staff-expiry.create', compact('ownerships', 'tipologie'));
    }

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_staff_expiry')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $request->validate([
            'titolo' => 'required|string|max:255',
            'tipologia_id' => 'required|exists:settings,id',
            'data_inizio' => 'required|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'id_proprieta' => 'nullable|exists:ownership,id_proprieta',
            'qualifica' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'valid' => 'boolean'
        ]);

        $tipologia = Setting::find($request->tipologia_id);

        StaffExpiry::create([
            'titolo' => $request->titolo,
            'tipologia_id' => $request->tipologia_id,
            'tipologia' => $tipologia->valore,
            'data_inizio' => $request->data_inizio,
            'data_fine' => $request->data_fine,
            'id_proprieta' => $request->id_proprieta,
            'qualifica' => $request->qualifica,
            'note' => $request->note,
            'valid' => $request->boolean('valid', true)
        ]);

        return redirect()->route('admin.staff-expiry.index')
            ->with('success', 'Scadenza creata con successo!');
    }

    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff_expiry')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staffExpiry = StaffExpiry::findOrFail($id);
        $ownerships = Ownership::orderBy('RagSocialePr')->get();
        $tipologie = Setting::where('tabella_riferimento', 'staff_expiry')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        return view('admin.staff-expiry.edit', compact('staffExpiry', 'ownerships', 'tipologie'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff_expiry')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staffExpiry = StaffExpiry::findOrFail($id);

        $request->validate([
            'titolo' => 'required|string|max:255',
            'tipologia_id' => 'required|exists:settings,id',
            'data_inizio' => 'required|date',
            'data_fine' => 'nullable|date|after_or_equal:data_inizio',
            'id_proprieta' => 'nullable|exists:ownership,id_proprieta',
            'qualifica' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'valid' => 'boolean'
        ]);

        $tipologia = Setting::find($request->tipologia_id);

        $staffExpiry->update([
            'titolo' => $request->titolo,
            'tipologia_id' => $request->tipologia_id,
            'tipologia' => $tipologia->valore,
            'data_inizio' => $request->data_inizio,
            'data_fine' => $request->data_fine,
            'id_proprieta' => $request->id_proprieta,
            'qualifica' => $request->qualifica,
            'note' => $request->note,
            'valid' => $request->boolean('valid', true)
        ]);

        return redirect()->route('admin.staff-expiry.index')
            ->with('success', 'Scadenza aggiornata con successo!');
    }

    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_staff_expiry')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $staffExpiry = StaffExpiry::findOrFail($id);
        $staffExpiry->delete();

        return redirect()->route('admin.staff-expiry.index')
            ->with('success', 'Scadenza eliminata con successo!');
    }

    public function toggleStatus($id)
    {
        $staffExpiry = StaffExpiry::findOrFail($id);
        $staffExpiry->update(['valid' => !$staffExpiry->valid]);
        
        $status = $staffExpiry->valid ? 'attivata' : 'disattivata';
        return back()->with('success', "Scadenza {$status} con successo!");
    }
}