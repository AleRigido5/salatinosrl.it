<?php
// app/Http/Controllers/Admin/VehiclesController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vehicles;
use App\Models\Ownership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehiclesController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.vehicles.index');
    }
    
    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $ownerships = Ownership::all();
        return view('admin.vehicles.create', compact('ownerships'));
    }
    
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'targa' => 'required|string|max:20|unique:vehicles,targa',
            'marca' => 'nullable|string|max:255',
            'modello' => 'nullable|string|max:255',
            'tipologia' => 'required|string|max:50',
            'immatricolazione' => 'nullable|date',
            'valid' => 'boolean',
            'id_ownership' => 'required|exists:ownership,id_proprieta',
            'note' => 'nullable|string',
        ]);
        
        try {
            $adminId = Auth::guard('admin')->id();
            
            $vehicle = Vehicles::create([
                'targa' => strtoupper($request->targa),
                'marca' => $request->marca,
                'modello' => $request->modello,
                'tipologia' => $request->tipologia,
                'immatricolazione' => $request->immatricolazione,
                'valid' => $request->valid ?? 1,
                'id_ownership' => $request->id_ownership,
                'note' => $request->note,
                'created_by' => $adminId,
                'updated_by' => $adminId
            ]);
            
            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Mezzo creato con successo!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Errore durante la creazione: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $vehicle = Vehicles::with(['createdBy', 'updatedBy', 'ownership'])->findOrFail($id);
        
        return view('admin.vehicles.show', compact('vehicle'));
    }
    
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $vehicle = Vehicles::with('ownership')->findOrFail($id);
        $ownerships = Ownership::all();
        
        return view('admin.vehicles.edit', compact('vehicle', 'ownerships'));
    }
    
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $vehicle = Vehicles::findOrFail($id);
        
        $request->validate([
            'targa' => 'required|string|max:20|unique:vehicles,targa,' . $id,
            'marca' => 'nullable|string|max:255',
            'modello' => 'nullable|string|max:255',
            'tipologia' => 'required|string|max:50',
            'immatricolazione' => 'nullable|date',
            'valid' => 'boolean',
            'id_ownership' => 'required|exists:ownership,id_proprieta',
            'note' => 'nullable|string',
        ]);
        
        try {
            $vehicle->update([
                'targa' => strtoupper($request->targa),
                'marca' => $request->marca,
                'modello' => $request->modello,
                'tipologia' => $request->tipologia,
                'immatricolazione' => $request->immatricolazione,
                'valid' => $request->valid ?? 0,
                'id_ownership' => $request->id_ownership,
                'note' => $request->note,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            return redirect()->route('admin.vehicles.index')
                ->with('success', 'Mezzo aggiornato con successo!');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        try {
            $vehicle = Vehicles::findOrFail($id);
            $vehicleName = $vehicle->full_name ?? $vehicle->targa;
            $vehicle->delete();
            
            return redirect()->route('admin.vehicles.index')
                ->with('success', "Mezzo '{$vehicleName}' eliminato con successo!");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    public function toggleStatus($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        try {
            $vehicle = Vehicles::findOrFail($id);
            $newStatus = $vehicle->valid == 1 ? 0 : 1;
            
            $vehicle->update([
                'valid' => $newStatus,
                'updated_by' => Auth::guard('admin')->id(),
            ]);
            
            $statusText = $newStatus == 1 ? 'attivato' : 'disattivato';
            
            return redirect()->route('admin.vehicles.index')
                ->with('success', "Mezzo '{$vehicle->targa}' {$statusText} con successo!");
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Errore durante il cambio di stato: ' . $e->getMessage());
        }
    }
    
    public function export()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $vehicles = Vehicles::with('ownership')->orderBy('targa')->get();
        
        $filename = 'vehicles_' . date('Y-m-d_His') . '.csv';
        $handle = fopen('php://temp', 'w+');
        
        // Intestazioni
        fputcsv($handle, ['ID', 'Targa', 'Marca', 'Modello', 'Anno', 'Tipologia', 'Proprietà', 'Stato', 'Note', 'Creato il']);
        
        foreach ($vehicles as $vehicle) {
            // Calcola l'anno dalla data di immatricolazione
            $anno = '-';
            if ($vehicle->immatricolazione && $vehicle->immatricolazione != '0000-00-00') {
                try {
                    $date = date_create($vehicle->immatricolazione);
                    if ($date && $date->format('Y') > 1900 && $date->format('Y') <= date('Y') + 1) {
                        $anno = $date->format('Y');
                    }
                } catch (\Exception $e) {
                    $anno = '-';
                }
            }
            
            // Nome proprietà
            $proprietaNome = 'Non specificata';
            if ($vehicle->ownership) {
                $proprietaNome = $vehicle->ownership->RagAbbrev 
                    ?: $vehicle->ownership->Rag_Soc_intest 
                    ?: 'Proprietà ' . $vehicle->ownership->id_proprieta;
            }
            
            fputcsv($handle, [
                $vehicle->id,
                $vehicle->targa ?? '-',
                $vehicle->marca ?? '-',
                $vehicle->modello ?? '-',
                $anno,
                $vehicle->tipologia ?? '-',
                $proprietaNome,
                $vehicle->valid == 1 ? 'Attivo' : 'Disattivo',
                $vehicle->note ?? '',
                $vehicle->created_at ? $vehicle->created_at->format('d/m/Y H:i') : '-'
            ]);
        }
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
    
    public function exportPdf()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $vehicles = Vehicles::with('ownership')->orderBy('targa')->get();
        
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('admin.vehicles.pdf', compact('vehicles'));
        
        return $pdf->download('vehicles_' . date('Y-m-d_His') . '.pdf');
    }
    
    // API per ricerche AJAX
    public function search(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $term = $request->get('q', '');
        
        if (strlen($term) < 2) {
            return response()->json([]);
        }
        
        $vehicles = Vehicles::with('ownership')
            ->where(function($q) use ($term) {
                $q->where('marca', 'like', '%' . $term . '%')
                  ->orWhere('modello', 'like', '%' . $term . '%')
                  ->orWhere('targa', 'like', '%' . $term . '%');
            })
            ->where('valid', 1)
            ->orderBy('marca')
            ->limit(10)
            ->get()
            ->map(function($vehicle) {
                $proprietaNome = 'Non specificata';
                if ($vehicle->ownership) {
                    $proprietaNome = $vehicle->ownership->RagAbbrev 
                        ?: $vehicle->ownership->Rag_Soc_intest 
                        ?: 'Proprietà ' . $vehicle->ownership->id_proprieta;
                }
                
                return [
                    'id' => $vehicle->id,
                    'full_name' => $vehicle->full_name,
                    'targa' => $vehicle->targa,
                    'marca' => $vehicle->marca,
                    'modello' => $vehicle->modello,
                    'tipologia' => $vehicle->tipologia,
                    'proprieta' => $proprietaNome,
                ];
            });
        
        return response()->json($vehicles);
    }
    
    public function getVehicle($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $vehicle = Vehicles::with(['createdBy', 'updatedBy', 'ownership'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $vehicle
        ]);
    }
    
    public function getDocuments($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
        }
        
        $vehicle = Vehicles::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'message' => 'Utilizzare /admin/documents/vehicles/' . $id,
            'vehicle' => $vehicle->full_name
        ]);
    }
}