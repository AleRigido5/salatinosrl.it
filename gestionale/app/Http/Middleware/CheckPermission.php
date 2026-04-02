<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        $admin = Auth::guard('admin')->user();
        
        if (!$admin || !$admin->hasPermission($permission)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi necessari per eseguire questa azione.'
                ], 403);
            }
            
            if ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('delete')) {
                return back()->with('error', 'Non hai i permessi necessari per eseguire questa azione.');
            }
            
            abort(403, 'Non hai i permessi necessari per accedere a questa pagina.');
        }
        
        return $next($request);
    }
}