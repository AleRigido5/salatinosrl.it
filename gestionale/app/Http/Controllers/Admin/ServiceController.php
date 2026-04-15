<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Setting;
use App\Models\UnitaMisura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        return view('admin.services.index');
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $categories = Setting::where('tabella_riferimento', 'services')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        $unitaMisura = UnitaMisura::where('valid', 1)->orderBy('nome')->get();

        return view('admin.services.create', compact('categories', 'unitaMisura'));
    }

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $request->validate([
            'Titolo' => 'required|string|max:255',
            'id_categories' => 'nullable|exists:settings,id',
            'Descrizione' => 'nullable|string',
            'Descr_fattura' => 'nullable|string',
            'Prezzo_un' => 'nullable|numeric|min:0',
            'UnitaMisura_id_unita' => 'nullable|exists:unita_misura,id_um',
            'Stato' => 'boolean'
        ]);

        $adminId = Auth::guard('admin')->id();

        $service = Service::create([
            'Titolo' => $request->Titolo,
            'id_categories' => $request->id_categories,
            'Descrizione' => $request->Descrizione,
            'Descr_fattura' => $request->Descr_fattura,
            'Prezzo_un' => $request->Prezzo_un,
            'UnitaMisura_id_unita' => $request->UnitaMisura_id_unita,
            'Stato' => $request->boolean('Stato', true),
            'created_by' => $adminId,   // <-- AGGIUNTO
            'updated_by' => $adminId    // <-- AGGIUNTO
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Servizio creato con successo!');
    }

    public function show(Service $service)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        return view('admin.services.show', compact('service'));
    }

    public function edit(Service $service)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $categories = Setting::where('tabella_riferimento', 'services')
            ->where('valid', 1)
            ->orderBy('ordinamento')
            ->get();

        $unitaMisura = UnitaMisura::where('valid', 1)->orderBy('nome')->get();

        return view('admin.services.edit', compact('service', 'categories', 'unitaMisura'));
    }

    public function update(Request $request, Service $service)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $request->validate([
            'Titolo' => 'required|string|max:255',
            'id_categories' => 'nullable|exists:settings,id',
            'Descrizione' => 'nullable|string',
            'Descr_fattura' => 'nullable|string',
            'Prezzo_un' => 'nullable|numeric|min:0',
            'UnitaMisura_id_unita' => 'nullable|exists:unita_misura,id_um',
            'Stato' => 'boolean'
        ]);

        $service->update([
            'Titolo' => $request->Titolo,
            'id_categories' => $request->id_categories,
            'Descrizione' => $request->Descrizione,
            'Descr_fattura' => $request->Descr_fattura,
            'Prezzo_un' => $request->Prezzo_un,
            'UnitaMisura_id_unita' => $request->UnitaMisura_id_unita,
            'Stato' => $request->boolean('Stato', true),
            'updated_by' => Auth::guard('admin')->id(),  // <-- AGGIUNTO
            'updated_at' => now()                         // <-- AGGIUNTO
        ]);

        return redirect()->route('admin.services.index')
            ->with('success', 'Servizio aggiornato con successo!');
    }

    public function destroy(Service $service)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_services')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $service->delete();

        return redirect()->route('admin.services.index')
            ->with('success', 'Servizio eliminato con successo!');
    }

    public function toggleStatus(Service $service)
    {
        $service->update([
            'Stato' => !$service->Stato,
            'updated_by' => Auth::guard('admin')->id(),  // <-- AGGIUNTO
            'updated_at' => now()                         // <-- AGGIUNTO
        ]);
        $status = $service->Stato ? 'attivato' : 'disattivato';
        return back()->with('success', "Servizio {$status} con successo!");
    }

    public function search(Request $request)
    {
        $search = $request->get('q', '');
        
        $services = Service::where('Titolo', 'like', "%{$search}%")
            ->orWhere('Descrizione', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'Titolo', 'Prezzo_un']);
        
        return response()->json($services);
    }
}