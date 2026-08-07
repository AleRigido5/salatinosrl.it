<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class SubActivitiesController extends Controller
{
    /**
     * Mostra la pagina "Sotto-attività (Lat/Long) per Cliente".
     * Il contenuto/logica di ricerca è gestito interamente dal componente
     * Livewire App\Livewire\Admin\SubActivitiesReport incluso nella view.
     */
    public function index()
    {
        return view('admin.activities.sub-activities');
    }
}