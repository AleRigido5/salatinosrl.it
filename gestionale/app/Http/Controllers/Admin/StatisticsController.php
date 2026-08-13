<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StatisticsController extends Controller
{
    /**
     * Pagina "Statistiche" — shell che estende il layout admin ed embedda
     * il componente Livewire admin.statistics-general (tab Generali /
     * Acquisti-Vendite).
     */
    public function index(): View
    {
        return view('admin.statistics.index');
    }
}