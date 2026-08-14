<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTaskTag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTaskController extends Controller
{
    /**
     * Pagina "In Evidenza" — shell che estende il layout admin ed embedda
     * il componente Livewire admin.admin-tasks-table.
     */
    public function index(): View
    {
        return view('admin.admin-tasks.index');
    }

    /**
     * Autocomplete parole chiave (tag) esistenti, per suggerire mentre si
     * digita nel form del task (evita duplicati tipo "multe"/"Multe").
     */
    public function searchTags(Request $request)
    {
        $search = $request->get('q', '');
        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $results = AdminTaskTag::where('name', 'like', '%' . $search . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($results);
    }
}