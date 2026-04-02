<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_roles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $roles = Role::withCount('administrators')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_roles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $permissions = Permission::orderBy('group')->orderBy('sort_order')->get();
        $permissionsByGroup = $permissions->groupBy('group');
        
        return view('admin.roles.create', compact('permissionsByGroup'));
    }

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_roles')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles',
            'slug' => 'required|string|max:255|unique:roles',
            'level' => 'required|integer|min:1|max:100',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'level' => $request->level,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Ruolo creato con successo!');
    }

    public function edit(Role $role)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_roles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        // Super admin non può essere modificato
        if ($role->slug === 'super_admin' && !Auth::guard('admin')->user()->isSuperAdmin()) {
            abort(403, 'Non puoi modificare il ruolo Super Admin.');
        }
        
        $permissions = Permission::orderBy('group')->orderBy('sort_order')->get();
        $permissionsByGroup = $permissions->groupBy('group');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissionsByGroup', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_roles')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        // Super admin non può essere modificato
        if ($role->slug === 'super_admin' && !Auth::guard('admin')->user()->isSuperAdmin()) {
            return back()->with('error', 'Non puoi modificare il ruolo Super Admin.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'slug' => 'required|string|max:255|unique:roles,slug,' . $role->id,
            'level' => 'required|integer|min:1|max:100',
            'permissions' => 'nullable|array',
        ]);

        $role->update([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'level' => $request->level,
            'is_default' => $request->boolean('is_default'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return redirect()->route('admin.roles.index')->with('success', 'Ruolo aggiornato con successo!');
    }

    public function destroy(Role $role)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_roles')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        // Impedisci eliminazione ruoli di sistema
        if (in_array($role->slug, ['super_admin', 'admin', 'editor', 'viewer'])) {
            return back()->with('error', 'Non puoi eliminare i ruoli di sistema.');
        }
        
        if ($role->administrators()->count() > 0) {
            return back()->with('error', 'Non puoi eliminare un ruolo che ha amministratori associati.');
        }
        
        $role->delete();
        return redirect()->route('admin.roles.index')->with('success', 'Ruolo eliminato con successo!');
    }
}