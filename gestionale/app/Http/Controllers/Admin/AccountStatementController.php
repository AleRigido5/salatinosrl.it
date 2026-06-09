<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountStatementController extends Controller
{
    /**
     * Display the account statement for a specific entity
     */
    public function index(Request $request, $id)
    {
        // Verifica permessi
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        // Carica l'entità
        $entity = Entity::findOrFail($id);
        
        // Parametri filtro
        $dateFrom = $request->get('date_from', date('Y-m-d', strtotime('-12 months')));
        $dateTo = $request->get('date_to', date('Y-m-d'));
        $statementType = $request->get('type', 'all');
        
        // Passa i dati alla view che chiamerà il Livewire
        return view('admin.entities.account-statement', compact(
            'entity',
            'dateFrom',
            'dateTo',
            'statementType',
            'id'
        ));
    }
}