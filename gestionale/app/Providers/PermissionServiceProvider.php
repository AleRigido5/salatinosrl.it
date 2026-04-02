<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Direttiva Blade @canpermission
        Blade::directive('canpermission', function ($expression) {
            return "<?php if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->hasPermission({$expression})): ?>";
        });
        
        Blade::directive('endcanpermission', function () {
            return "<?php endif; ?>";
        });
        
        // Direttiva Blade @cannotpermission
        Blade::directive('cannotpermission', function ($expression) {
            return "<?php if (Auth::guard('admin')->check() && !Auth::guard('admin')->user()->hasPermission({$expression})): ?>";
        });
        
        Blade::directive('endcannotpermission', function () {
            return "<?php endif; ?>";
        });
    }
}