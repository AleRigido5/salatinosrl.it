<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Administrator;
use App\Models\Role;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TrashController extends Controller
{
    /**
     * Mostra il cestino per tipo
     */
    public function index($type)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        // Verifica permessi in base al tipo
        if (!$this->checkPermission($type, $currentAdmin)) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $items = $this->getTrashedItems($type, $currentAdmin);
        $stats = $this->getTrashStats();
        
        return view('admin.trash.index', compact('type', 'items', 'stats'));
    }
    
    /**
     * Ripristina un elemento dal cestino
     */
    public function restore($type, $id)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$this->checkPermission($type, $currentAdmin)) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $model = $this->getModel($type);
        
        // Per le entità, usa id_cliente come chiave primaria
        if ($type === 'entities') {
            $item = $model::withTrashed()->where('id_cliente', $id)->first();
        } else {
            $item = $model::withTrashed()->find($id);
        }
        
        if (!$item) {
            return back()->with('error', 'Elemento non trovato.');
        }
        
        // Verifiche aggiuntive per amministratori
        if ($type === 'administrators' && $item->id === $currentAdmin->id) {
            return back()->with('error', 'Non puoi ripristinare il tuo account!');
        }
        
        $item->restore();
        
        return back()->with('success', $this->getItemDisplayName($item) . ' ripristinato con successo!');
    }
    
    /**
     * Ripristina più elementi
     */
    public function bulkRestore(Request $request, $type)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$this->checkPermission($type, $currentAdmin)) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Nessun elemento selezionato']);
        }
        
        $model = $this->getModel($type);
        $restoredCount = 0;
        
        foreach ($ids as $id) {
            if ($type === 'entities') {
                $item = $model::withTrashed()->where('id_cliente', $id)->first();
            } else {
                $item = $model::withTrashed()->find($id);
            }
            
            if ($item && !($type === 'administrators' && $item->id === $currentAdmin->id)) {
                $item->restore();
                $restoredCount++;
            }
        }
        
        return response()->json([
            'success' => true, 
            'message' => "{$restoredCount} elementi ripristinati con successo!",
            'count' => $restoredCount
        ]);
    }
    
    /**
     * Elimina definitivamente un elemento
     */
    public function forceDelete($type, $id)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$this->checkPermission($type, $currentAdmin)) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $model = $this->getModel($type);
        
        // Per le entità, usa id_cliente come chiave primaria
        if ($type === 'entities') {
            $item = $model::withTrashed()->where('id_cliente', $id)->first();
        } else {
            $item = $model::withTrashed()->find($id);
        }
        
        if (!$item) {
            return back()->with('error', 'Elemento non trovato.');
        }
        
        // Verifiche aggiuntive per amministratori
        if ($type === 'administrators' && $item->id === $currentAdmin->id) {
            return back()->with('error', 'Non puoi eliminare permanentemente il tuo account!');
        }
        
        $itemName = $this->getItemDisplayName($item);
        $item->forceDelete();
        
        return back()->with('success', "{$itemName} è stato eliminato permanentemente!");
    }
    
    /**
     * Elimina definitivamente più elementi
     */
    public function bulkForceDelete(Request $request, $type)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$this->checkPermission($type, $currentAdmin)) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => 'Nessun elemento selezionato']);
        }
        
        $model = $this->getModel($type);
        $deletedCount = 0;
        
        foreach ($ids as $id) {
            if ($type === 'entities') {
                $item = $model::withTrashed()->where('id_cliente', $id)->first();
            } else {
                $item = $model::withTrashed()->find($id);
            }
            
            if ($item && !($type === 'administrators' && $item->id === $currentAdmin->id)) {
                $item->forceDelete();
                $deletedCount++;
            }
        }
        
        return response()->json([
            'success' => true, 
            'message' => "{$deletedCount} elementi eliminati permanentemente!",
            'count' => $deletedCount
        ]);
    }
    
    /**
     * Svuota completamente il cestino
     */
    public function empty($type)
    {
        $currentAdmin = Auth::guard('admin')->user();
        
        if (!$this->checkPermission($type, $currentAdmin)) {
            return back()->with('error', 'Non hai i permessi necessari.');
        }
        
        $model = $this->getModel($type);
        $items = $model::onlyTrashed();
        
        // Verifiche aggiuntive per amministratori
        if ($type === 'administrators') {
            $items->where('id', '!=', $currentAdmin->id);
        }
        
        $count = $items->count();
        
        if ($type === 'administrators' && $currentAdmin->isSuperAdmin()) {
            $items->forceDelete();
        } elseif ($type !== 'administrators') {
            $items->forceDelete();
        } else {
            return back()->with('error', 'Non puoi svuotare il cestino degli amministratori.');
        }
        
        return back()->with('success', "{$count} elementi sono stati eliminati permanentemente!");
    }
    
    /**
     * Ottiene gli elementi nel cestino
     */
    private function getTrashedItems($type, $currentAdmin)
    {
        $model = $this->getModel($type);
        $query = $model::onlyTrashed();
        
        // Filtra per amministratori
        if ($type === 'administrators' && !$currentAdmin->isSuperAdmin()) {
            $query->whereHas('role', function($q) use ($currentAdmin) {
                $q->where('level', '>', $currentAdmin->role->level);
            });
        }
        
        return $query->orderBy('deleted_at', 'desc')->paginate(20);
    }
    
    /**
     * Ottiene le statistiche del cestino
     */
    private function getTrashStats()
    {
        return [
            'administrators' => Administrator::onlyTrashed()->count(),
            'roles' => Role::onlyTrashed()->count(),
            'entities' => Entity::onlyTrashed()->count(),
        ];
    }
    
    /**
     * Ottiene il modello corretto
     */
    private function getModel($type)
    {
        return match($type) {
            'administrators' => Administrator::class,
            'roles' => Role::class,
            'entities' => Entity::class,
            default => abort(404, 'Tipo non valido'),
        };
    }
    
    /**
     * Verifica i permessi
     */
    private function checkPermission($type, $currentAdmin)
    {
        return match($type) {
            'administrators' => $currentAdmin->hasPermission('view_administrators'),
            'roles' => $currentAdmin->hasPermission('view_roles'),
            'entities' => $currentAdmin->hasPermission('view_entities'),
            default => false,
        };
    }
    
    /**
     * Ottiene il nome visualizzabile di un elemento
     */
    private function getItemDisplayName($item)
    {
        if (isset($item->name) && $item->name) {
            return $item->name;
        }
        
        if (isset($item->ragione_sociale) && $item->ragione_sociale) {
            return $item->ragione_sociale;
        }
        
        if (isset($item->nome) && isset($item->cognome)) {
            return trim($item->nome . ' ' . $item->cognome);
        }
        
        if (isset($item->first_name) && isset($item->last_name)) {
            return trim($item->first_name . ' ' . $item->last_name);
        }
        
        if (isset($item->email) && $item->email) {
            return $item->email;
        }
        
        if (isset($item->slug) && $item->slug) {
            return $item->name . ' (' . $item->slug . ')';
        }
        
        return 'Elemento #' . $item->id;
    }
}