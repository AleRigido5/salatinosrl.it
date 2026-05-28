<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostCenterController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_cost_centers')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.cost_centers.index');
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_cost_centers')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.cost_centers.create');
    }

    /**
     * Cerca riferimenti per autocomplete
     */
    public function searchReferences(Request $request)
    {
        $type = $request->get('type');
        $search = $request->get('q');
        
        if (!$type || strlen($search) < 2) {
            return response()->json([]);
        }
        
        if ($type === 'ownership') {
            $results = Ownership::where('RagSocialePr', 'like', "%{$search}%")
                ->orWhere('RagAbbrev', 'like', "%{$search}%")
                ->orWhere('Rag_Soc_intest', 'like', "%{$search}%")
                ->limit(10)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id_proprieta,  // CHIAVE CORRETTA
                        'name' => $item->nome,  // Usa l'accessor che hai già definito
                        'details' => $item->PivaPr ? "P.IVA: {$item->PivaPr}" : null
                    ];
                });
        } elseif ($type === 'entities') {
            $results = Entity::where('ragione_sociale', 'like', "%{$search}%")
                ->orWhere('nome', 'like', "%{$search}%")
                ->orWhere('cognome', 'like', "%{$search}%")
                ->where('valid', 1)
                ->limit(10)
                ->get()
                ->map(function($item) {
                    $name = $item->ragione_sociale ?: ($item->nome . ' ' . $item->cognome);
                    return [
                        'id' => $item->id_cliente,  // CHIAVE CORRETTA
                        'name' => $name,
                        'details' => $item->partita_iva ? "P.IVA: {$item->partita_iva}" : ($item->codice_fiscale ? "CF: {$item->codice_fiscale}" : null)
                    ];
                });
        } else {
            return response()->json([]);
        }
        
        return response()->json($results);
    }

    /**
     * Get the client associated with a cost center
     */
    public function getClientByCostCenter(Request $request)
    {
        $costCenterId = $request->get('id');
        
        if (!$costCenterId) {
            return response()->json(null);
        }
        
        $costCenter = CostCenter::find($costCenterId);
        
        if (!$costCenter) {
            return response()->json(null);
        }
        
        // Recupera l'entità associata (id_references punta a id_cliente quando table_references = 'entities')
        if ($costCenter->table_references === 'entities') {
            $client = Entity::find($costCenter->id_references);
            if ($client) {
                return response()->json([
                    'id_cliente' => $client->id_cliente,
                    'ragione_sociale' => $client->ragione_sociale,
                    'nome' => $client->nome,
                    'cognome' => $client->cognome,
                    'partita_iva' => $client->partita_iva,
                    'entity_type' => $client->entity_type,
                ]);
            }
        }
        
        return response()->json(null);
    }

    /**
     * API search for cost centers (AJAX)
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }
        
        $results = CostCenter::where('valid', 1)
            ->where('Nome', 'like', '%' . $query . '%')
            ->orderBy('Nome')
            ->limit(10)
            ->get(['id', 'Nome', 'Localita']);
        
        return response()->json($results);
    }

    /**
     * Ottieni un riferimento per ID
     */
    public function getReferenceById($type, $id)
    {
        if ($type === 'ownership') {
            $item = Ownership::find($id);
            if ($item) {
                return response()->json([
                    'id' => $item->id_proprieta,  // CHIAVE CORRETTA
                    'name' => $item->nome
                ]);
            }
        } elseif ($type === 'entities') {
            $item = Entity::find($id);
            if ($item) {
                $name = $item->ragione_sociale ?: ($item->nome . ' ' . $item->cognome);
                return response()->json([
                    'id' => $item->id_cliente,  // CHIAVE CORRETTA
                    'name' => $name
                ]);
            }
        }
        
        return response()->json(null, 404);
    }

    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_cost_centers')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'id_references' => 'required|integer',
            'table_references' => 'required|in:ownership,entities',
            'nome' => 'nullable|string|max:255',
            'contrada' => 'nullable|string|max:50',
            'localita' => 'nullable|string|max:50',
            'foglio' => 'nullable|string|max:20',
            'particella' => 'nullable|string|max:20',
            'superficie' => 'nullable|numeric|min:0',
            'coltura' => 'nullable|string|max:30',
            'costo_h' => 'nullable|numeric|min:0',
            'num_h' => 'nullable|integer|min:0|max:24',
            'competenza' => 'nullable|string|max:50',
            'note' => 'nullable|string',
            'valid' => 'nullable|boolean',
        ]);

        $costCenter = CostCenter::create([
            'id_references'   => $request->id_references,
            'table_references'=> $request->table_references,
            'Nome'            => $request->nome,
            'Contrada'        => $request->contrada,
            'Localita'        => $request->localita,
            'Foglio'          => $request->foglio,
            'Particella'      => $request->particella,
            'Superficie'      => $request->superficie ?? 0,
            'Coltura'         => $request->coltura,
            'Note'            => $request->note,
            'valid'           => $request->boolean('valid'),
            'CostoH'          => $request->costo_h ?? 7.00,
            'NumH'            => $request->num_h ?? 8,
            'Competenza'      => $request->competenza ?? 'Solare',
            'created_by'      => Auth::guard('admin')->id(),
            'updated_by'      => Auth::guard('admin')->id()
        ]);

        return redirect()->route('admin.cost_centers.index')
            ->with('success', "Centro di Costo '{$costCenter->Nome}' creato con successo!");
    }

    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_cost_centers')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $costCenter = CostCenter::findOrFail($id);
        
        return view('admin.cost_centers.edit', compact('costCenter'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_cost_centers')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $costCenter = CostCenter::findOrFail($id);
        
        $request->validate([
            'id_references' => 'required|integer',
            'table_references' => 'required|in:ownership,entities',
            'nome' => 'nullable|string|max:255',
            'contrada' => 'nullable|string|max:50',
            'localita' => 'nullable|string|max:50',
            'foglio' => 'nullable|string|max:20',
            'particella' => 'nullable|string|max:20',
            'superficie' => 'nullable|numeric|min:0',
            'coltura' => 'nullable|string|max:30',
            'costo_h' => 'nullable|numeric|min:0',
            'num_h' => 'nullable|integer|min:0|max:24',
            'competenza' => 'nullable|string|max:50',
            'note' => 'nullable|string',
            'valid' => 'nullable|boolean',
        ]);

        $costCenter->update([
            'id_references'   => $request->id_references,
            'table_references'=> $request->table_references,
            'Nome'            => $request->nome,
            'Contrada'        => $request->contrada,
            'Localita'        => $request->localita,
            'Foglio'          => $request->foglio,
            'Particella'      => $request->particella,
            'Superficie'      => $request->superficie ?? 0,
            'Coltura'         => $request->coltura,
            'Note'            => $request->note,
            'valid'           => $request->boolean('valid'),
            'CostoH'          => $request->costo_h ?? 7.00,
            'NumH'            => $request->num_h ?? 8,
            'Competenza'      => $request->competenza ?? 'Solare',
            'updated_by'      => Auth::guard('admin')->id()
        ]);

        return redirect()->route('admin.cost_centers.index')
            ->with('success', "Centro di Costo '{$costCenter->Nome}' aggiornato con successo!");
    }

    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_cost_centers')) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $costCenter = CostCenter::findOrFail($id);
        $name = $costCenter->nome;
        $costCenter->delete();
        
        return redirect()->route('admin.cost_centers.index')
            ->with('success', "Centro di Costo '{$name}' eliminato con successo!");
    }

    public function toggleStatus($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_cost_centers')) {
            return response()->json(['error' => 'Permessi insufficienti'], 403);
        }
        
        $costCenter = CostCenter::findOrFail($id);
        $costCenter->update([
            'valid' => !$costCenter->valid,
            'updated_by' => Auth::guard('admin')->id()
        ]);
        
        $status = $costCenter->valid ? 'attivato' : 'disattivato';
        return response()->json(['success' => "Centro di Costo {$status} con successo!"]);
    }
}