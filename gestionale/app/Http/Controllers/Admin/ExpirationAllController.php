<?php
// app/Http/Controllers/Admin/ExpirationAllController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpirationAllController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_expiration')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.expiration-all.index');
    }
}