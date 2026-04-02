<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\User;
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
            'total_users' => User::count(),
            'total_roles' => Role::count(),
        ];
        
        $recentAdmins = Administrator::with('role')->latest()->take(5)->get();
        
        return view('admin.dashboard.index', compact('admin', 'stats', 'recentAdmins'));
    }
}