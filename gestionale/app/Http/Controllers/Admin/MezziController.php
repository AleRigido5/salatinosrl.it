<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MezziController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.vehicles.index');
    }
    
    public function create()
    {
        return view('admin.vehicles.create');
    }
    
    public function store(Request $request)
    {
        // Implementare la logica di salvataggio
    }
    
    public function show($id)
    {
        return view('admin.vehicles.show');
    }
    
    public function edit($id)
    {
        return view('admin.vehicles.edit');
    }
    
    public function update(Request $request, $id)
    {
        // Implementare la logica di aggiornamento
    }
    
    public function destroy($id)
    {
        // Implementare la logica di eliminazione
    }
    
    public function toggleStatus($id)
    {
        // Implementare la logica di toggle stato
    }
    
    public function export(Request $request)
    {
        // Implementare export CSV
    }
    
    public function exportPdf(Request $request)
    {
        // Implementare export PDF
    }
    
    public function search(Request $request)
    {
        // API per ricerca
    }
    
    public function getDocuments($id)
    {
        // API per ottenere documenti
    }
}