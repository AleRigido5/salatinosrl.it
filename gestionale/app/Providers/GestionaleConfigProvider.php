<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class GestionaleConfigProvider extends ServiceProvider
{
    public function boot()
    {
        // Assicura che la configurazione sia sempre caricata
        if (!Config::has('gestionale.modalita_pagamento')) {
            $config = include config_path('gestionale.php');
            foreach ($config as $key => $value) {
                Config::set('gestionale.' . $key, $value);
            }
        }
    }
    
    public function register()
    {
        //
    }
}