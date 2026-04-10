<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StaffController extends Controller
{
    /**
     * Display a listing of the staff.
     */
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
            }
            abort(403, 'Non hai i permessi necessari per visualizzare il personale.');
        }
        
        if (request()->ajax()) {
            $staff = Staff::orderBy('CognomePers')->orderBy('NomePers')->get();
            return response()->json(['success' => true, 'data' => $staff]);
        }
        
        return view('admin.staff.index');
    }
    
    /**
     * Store a newly created staff in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $request->validate([
            'NomePers' => 'nullable|string|max:255',
            'CognomePers' => 'nullable|string|max:255',
            'Soprannome' => 'nullable|string|max:255',
            'IndirPers' => 'nullable|string|max:255',
            'CittaPers' => 'nullable|string|max:50',
            'ProvPers' => 'nullable|string|max:5',
            'CapPers' => 'nullable|string|max:10',
            'TelPers' => 'nullable|string|max:20',
            'CellPers' => 'nullable|string|max:20',
            'EmailPers' => 'nullable|email|max:255',
            'CodFiscPers' => 'nullable|string|max:20',
            'DataNascPers' => 'nullable|date',
            'LuogoNasc' => 'nullable|string|max:50',
        ]);
        
        try {
            $staff = Staff::create([
                'NomePers' => $request->NomePers,
                'CognomePers' => $request->CognomePers,
                'Soprannome' => $request->Soprannome,
                'IndirPers' => $request->IndirPers,
                'CittaPers' => $request->CittaPers,
                'ProvPers' => $request->ProvPers,
                'CapPers' => $request->CapPers,
                'TelPers' => $request->TelPers,
                'CellPers' => $request->CellPers,
                'EmailPers' => $request->EmailPers,
                'CodFiscPers' => $request->CodFiscPers,
                'DataNascPers' => $request->DataNascPers,
                'LuogoNasc' => $request->LuogoNasc,
                'valid' => true
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Personale aggiunto con successo!',
                'data' => $staff
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Display the specified staff.
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $staff = Staff::findOrFail($id);
        
        return response()->json(['success' => true, 'data' => $staff]);
    }
    
    /**
     * Update the specified staff in storage.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $staff = Staff::findOrFail($id);
        
        $request->validate([
            'NomePers' => 'nullable|string|max:255',
            'CognomePers' => 'nullable|string|max:255',
            'Soprannome' => 'nullable|string|max:255',
            'IndirPers' => 'nullable|string|max:255',
            'CittaPers' => 'nullable|string|max:50',
            'ProvPers' => 'nullable|string|max:5',
            'CapPers' => 'nullable|string|max:10',
            'TelPers' => 'nullable|string|max:20',
            'CellPers' => 'nullable|string|max:20',
            'EmailPers' => 'nullable|email|max:255',
            'CodFiscPers' => 'nullable|string|max:20',
            'DataNascPers' => 'nullable|date',
            'LuogoNasc' => 'nullable|string|max:50',
        ]);
        
        try {
            $staff->update([
                'NomePers' => $request->NomePers,
                'CognomePers' => $request->CognomePers,
                'Soprannome' => $request->Soprannome,
                'IndirPers' => $request->IndirPers,
                'CittaPers' => $request->CittaPers,
                'ProvPers' => $request->ProvPers,
                'CapPers' => $request->CapPers,
                'TelPers' => $request->TelPers,
                'CellPers' => $request->CellPers,
                'EmailPers' => $request->EmailPers,
                'CodFiscPers' => $request->CodFiscPers,
                'DataNascPers' => $request->DataNascPers,
                'LuogoNasc' => $request->LuogoNasc,
                'valid' => $request->boolean('valid', $staff->valid)
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Personale aggiornato con successo!',
                'data' => $staff
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Remove the specified staff from storage.
     */
    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        try {
            $staff = Staff::findOrFail($id);
            $name = $staff->full_name;
            $staff->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Personale '{$name}' eliminato con successo!"
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Toggle staff status (active/inactive).
     */
    public function toggleStatus($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_staff')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        try {
            $staff = Staff::findOrFail($id);
            $staff->update(['valid' => !$staff->valid]);
            
            $status = $staff->valid ? 'attivato' : 'disattivato';
            
            return response()->json([
                'success' => true,
                'message' => "Personale {$status} con successo!",
                'data' => $staff
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il cambio di stato: ' . $e->getMessage()
            ], 500);
        }
    }
}