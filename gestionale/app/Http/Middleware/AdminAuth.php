<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAuth
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $admin = Auth::guard('admin')->user();

        // Verifica se l'account è attivo
        if (!$admin->is_active) {
            Auth::guard('admin')->logout();
            return redirect()->route('admin.login')->with('error', 'Il tuo account è stato disabilitato.');
        }

        // Verifica i ruoli se specificati
        if (!empty($roles)) {
            $hasRole = false;
            foreach ($roles as $role) {
                if ($admin->hasRole($role)) {
                    $hasRole = true;
                    break;
                }
            }
            
            if (!$hasRole) {
                abort(403, 'Accesso negato. Non hai i permessi necessari.');
            }
        }

        return $next($request);
    }
}