<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_users')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $query = User::query();
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }
        
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }
        
        $users = $query->latest()->paginate(15);
        $roles = User::getRoles(); // Ora questo metodo esiste
        
        return view('admin.users.index', compact('users', 'roles'));
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_users')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $roles = User::getRoles();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_users')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,premium,vip,moderator',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'is_active' => $request->boolean('is_active'),
            'phone' => $request->phone,
            'permissions' => json_encode([]),
            'metadata' => json_encode([]),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Utente creato con successo!');
    }

    public function show(User $user)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_users')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $roles = User::getRoles();
        return view('admin.users.show', compact('user', 'roles'));
    }

    public function edit(User $user)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_users')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $roles = User::getRoles();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_users')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:user,premium,vip,moderator',
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'is_active' => $request->boolean('is_active'),
            'phone' => $request->phone,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Utente aggiornato con successo!');
    }

    public function destroy(User $user)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_users')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Utente eliminato con successo!');
    }

    public function toggleStatus(User $user)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_users')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'attivato' : 'disattivato';
        return back()->with('success', "Utente {$status} con successo!");
    }
}