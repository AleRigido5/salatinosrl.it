<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdministratorController extends Controller
{
    /**
     * Lista amministratori
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_administrators')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $query = Administrator::with('role');
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->filled('role')) {
            $query->where('role_id', $request->role);
        }
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $administrators = $query->latest()->paginate(15);
        $roles = Role::where('is_active', true)->get();
        
        return view('admin.administrators.index', compact('administrators', 'roles'));
    }

    /**
     * Form creazione amministratore
     */
    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_administrators')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $roles = Role::where('is_active', true)->get();
        return view('admin.administrators.create', compact('roles'));
    }

    /**
     * Salva nuovo amministratore
     */
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_administrators')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:administrators',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
        ]);

        // Verifica livello ruolo
        $currentAdmin = Auth::guard('admin')->user();
        $newRole = Role::find($request->role_id);
        
        if ($currentAdmin->role->level >= $newRole->level && !$currentAdmin->isSuperAdmin()) {
            return back()->with('error', 'Non puoi creare un amministratore con livello superiore o uguale al tuo.');
        }

        Administrator::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.administrators.index')->with('success', 'Amministratore creato con successo!');
    }

    /**
     * Dettagli amministratore
     */
    public function show(Administrator $administrator)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_administrators')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.administrators.show', compact('administrator'));
    }

    /**
     * Form modifica amministratore
     */
    public function edit(Administrator $administrator)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_administrators')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $currentAdmin = Auth::guard('admin')->user();
        
        // Non puoi modificare te stesso se non sei super admin
        if ($administrator->id === $currentAdmin->id && !$currentAdmin->isSuperAdmin()) {
            abort(403, 'Non puoi modificare il tuo account.');
        }
        
        // Non puoi modificare un admin con livello superiore
        if ($currentAdmin->role->level >= $administrator->role->level && !$currentAdmin->isSuperAdmin()) {
            abort(403, 'Non puoi modificare un amministratore con livello superiore o uguale al tuo.');
        }
        
        $roles = Role::where('is_active', true)->get();
        return view('admin.administrators.edit', compact('administrator', 'roles'));
    }

    /**
     * Aggiorna amministratore
     */
    public function update(Request $request, Administrator $administrator)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_administrators')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $currentAdmin = Auth::guard('admin')->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:administrators,email,' . $administrator->id,
            'role_id' => 'required|exists:roles,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $newRole = Role::find($request->role_id);
        
        // Verifica livelli
        if (!$currentAdmin->isSuperAdmin()) {
            if ($currentAdmin->role->level >= $newRole->level) {
                return back()->with('error', 'Non puoi assegnare un ruolo con livello superiore o uguale al tuo.');
            }
            
            if ($administrator->id !== $currentAdmin->id && $currentAdmin->role->level >= $administrator->role->level) {
                return back()->with('error', 'Non puoi modificare questo amministratore.');
            }
        }

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'phone' => $request->phone,
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'min:8|confirmed']);
            $data['password'] = Hash::make($request->password);
        }

        $administrator->update($data);

        return redirect()->route('admin.administrators.index')->with('success', 'Amministratore aggiornato con successo!');
    }

    /**
     * Elimina amministratore
     */
    public function destroy(Administrator $administrator)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_administrators')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $currentAdmin = Auth::guard('admin')->user();
        
        // Non puoi eliminare te stesso
        if ($administrator->id === $currentAdmin->id) {
            return back()->with('error', 'Non puoi eliminare il tuo account!');
        }
        
        // Non puoi eliminare super admin se non sei super admin
        if ($administrator->isSuperAdmin() && !$currentAdmin->isSuperAdmin()) {
            return back()->with('error', 'Non puoi eliminare un Super Amministratore!');
        }
        
        $administrator->delete();
        
        return redirect()->route('admin.administrators.index')->with('success', 'Amministratore eliminato con successo!');
    }

    /**
     * Cambia stato
     */
    public function toggleStatus(Administrator $administrator)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_administrators')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $currentAdmin = Auth::guard('admin')->user();
        
        if ($administrator->id === $currentAdmin->id) {
            return back()->with('error', 'Non puoi disattivare il tuo account!');
        }
        
        $administrator->update(['is_active' => !$administrator->is_active]);
        
        $status = $administrator->is_active ? 'attivato' : 'disattivato';
        return back()->with('success', "Amministratore {$status} con successo!");
    }
}