<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GestionaleController extends Controller
{
    /**
     * Mostra la dashboard del gestionale
     */
    public function dashboard()
    {
        return view('gestionale.dashboard');
    }
}