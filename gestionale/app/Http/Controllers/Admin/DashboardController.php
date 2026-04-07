<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        
        $stats = [
            'total_admins' => Administrator::count(),
            'active_admins' => Administrator::where('is_active', true)->count(),
            'total_entities' => Entity::count(),
            'total_roles' => Role::count(),
        ];
        
        $recentAdmins = Administrator::with('role')->latest()->take(5)->get();
        $recentEntities = Entity::latest('created_at')->take(5)->get();
        $entityTypes = Entity::getEntityTypes();
        
        return view('admin.dashboard.index', compact('admin', 'stats', 'recentAdmins', 'recentEntities', 'entityTypes'));
    }
}