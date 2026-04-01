<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Dati statistici di esempio
        $totalUsers = User::count();
        
        $stats = [
            'total_users' => $totalUsers,
            'revenue' => 45678,
            'orders' => 892,
            'visitors' => 5678,
        ];
        
        $recentUsers = User::latest()->take(5)->get();
        
        return view('dashboard.index', compact('user', 'stats', 'recentUsers'));
    }
}