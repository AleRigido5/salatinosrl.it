<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\VatRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InvoiceReceivedController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.invoices_received.index');
    }
    
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.invoices_received.create');
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $validated = $request->validate([
            'id_ownership' => 'required|exists:ownership,id_proprieta',
            'id_entities' => 'required|exists:entities,id_cliente',
            'type_invoice' => 'required|string',
            'n_invoice' => 'required|string|max:100',
            'data_invoice' => 'required|date',
            'importo_totale' => 'required|numeric|min:0',
            'causale' => 'nullable|string',
            'divisa' => 'required|string|size:3',
            'status' => 'required|string',
            'sdi_id' => 'nullable|string|max:100',
            'rows' => 'required|array|min:1',
            'rows.*.id_cost_center' => 'required|exists:cost_centers,id',
            'rows.*.description' => 'required|string',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.unit_price' => 'required|numeric|min:0',
            'rows.*.vat_rate_id' => 'required|exists:vat_rates,id',
            'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Recupera i dati dell'ownership per il JSON
            $ownership = Ownership::find($validated['id_ownership']);
            $dataOwnership = [
                'Denominazione' => $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? '',
                'indirizzo' => $ownership->IndirizzoProp ?? '',
                'CAP' => $ownership->cap ?? '',
                'Comune' => $ownership->ComuneProp ?? '',
                'Provincia' => $ownership->ProvProp ?? '',
                'Nazione' => 'IT',
                'CodiceDestinatario' => $ownership->CodiceDestinatario ?? '',
                'PECDestinatario' => $ownership->PECDestinatario ?? '',
            ];
            
            // Recupera i dati dell'entità per il JSON
            $entity = Entity::find($validated['id_entities']);
            $dataEntities = [
                'Denominazione' => $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome),
                'indirizzo' => $entity->indirizzo ?? '',
                'CAP' => $entity->cap ?? '',
                'Comune' => $entity->citta ?? '',
                'Provincia' => $entity->provincia ?? '',
                'Nazione' => 'IT',
                'CodiceFiscale' => $entity->codice_fiscale ?? $entity->partita_iva ?? '',
                'RegimeFiscale' => 'RF01',
                'Telefono' => $entity->telefono ?? '',
                'Email' => $entity->email ?? '',
            ];
            
            // Crea la fattura
            $invoice = InvoiceReceived::create([
                'id_ownership' => $validated['id_ownership'],
                'id_entities' => $validated['id_entities'],
                'data_ownership' => json_encode($dataOwnership),
                'data_entities' => json_encode($dataEntities),
                'type_invoice' => $validated['type_invoice'],
                'n_invoice' => $validated['n_invoice'],
                'data_invoice' => $validated['data_invoice'],
                'importo_totale' => $validated['importo_totale'],
                'causale' => $validated['causale'] ?? null,
                'divisa' => $validated['divisa'],
                'status' => $validated['status'],
                'sdi_id' => $validated['sdi_id'] ?? null,
            ]);
            
            // Crea le righe fattura
            foreach ($validated['rows'] as $row) {
                $total = ($row['quantity'] * $row['unit_price']) * (1 - ($row['discount_percentage'] ?? 0) / 100);
                
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => $row['id_cost_center'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'vat_rate_id' => $row['vat_rate_id'],
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'total' => $total,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('admin.invoices-received.index')
                ->with('success', 'Fattura creata con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione fattura: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()->withInput()
                ->with('error', 'Errore durante la creazione: ' . $e->getMessage());
        }
    }
    
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $invoice = InvoiceReceived::with(['ownership', 'entity', 'rows.costCenter', 'rows.vatRate'])->findOrFail($id);
        
        return view('admin.invoices_received.show', compact('invoice'));
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $invoice = InvoiceReceived::with(['ownership', 'entity', 'rows'])->findOrFail($id);
        
        return view('admin.invoices_received.edit', compact('invoice'));
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $invoice = InvoiceReceived::findOrFail($id);
        
        $validated = $request->validate([
            'id_ownership' => 'required|exists:ownership,id_proprieta',
            'id_entities' => 'required|exists:entities,id_cliente',
            'type_invoice' => 'required|string',
            'n_invoice' => 'required|string|max:100',
            'data_invoice' => 'required|date',
            'importo_totale' => 'required|numeric|min:0',
            'causale' => 'nullable|string',
            'divisa' => 'required|string|size:3',
            'status' => 'required|string',
            'sdi_id' => 'nullable|string|max:100',
            'rows' => 'required|array|min:1',
            'rows.*.id' => 'nullable|exists:invoice_row,id',
            'rows.*.id_cost_center' => 'required|exists:cost_centers,id',
            'rows.*.description' => 'required|string',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.unit_price' => 'required|numeric|min:0',
            'rows.*.vat_rate_id' => 'required|exists:vat_rates,id',
            'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Aggiorna i dati JSON dell'ownership
            $ownership = Ownership::find($validated['id_ownership']);
            $dataOwnership = [
                'Denominazione' => $ownership->RagAbbrev ?? $ownership->Rag_Soc_intest ?? '',
                'indirizzo' => $ownership->IndirizzoProp ?? '',
                'CAP' => $ownership->cap ?? '',
                'Comune' => $ownership->ComuneProp ?? '',
                'Provincia' => $ownership->ProvProp ?? '',
                'Nazione' => 'IT',
                'CodiceDestinatario' => $ownership->CodiceDestinatario ?? '',
                'PECDestinatario' => $ownership->PECDestinatario ?? '',
            ];
            
            // Aggiorna i dati JSON dell'entità
            $entity = Entity::find($validated['id_entities']);
            $dataEntities = [
                'Denominazione' => $entity->ragione_sociale ?? ($entity->nome . ' ' . $entity->cognome),
                'indirizzo' => $entity->indirizzo ?? '',
                'CAP' => $entity->cap ?? '',
                'Comune' => $entity->citta ?? '',
                'Provincia' => $entity->provincia ?? '',
                'Nazione' => 'IT',
                'CodiceFiscale' => $entity->codice_fiscale ?? $entity->partita_iva ?? '',
                'RegimeFiscale' => 'RF01',
                'Telefono' => $entity->telefono ?? '',
                'Email' => $entity->email ?? '',
            ];
            
            // Aggiorna la fattura
            $invoice->update([
                'id_ownership' => $validated['id_ownership'],
                'id_entities' => $validated['id_entities'],
                'data_ownership' => json_encode($dataOwnership),
                'data_entities' => json_encode($dataEntities),
                'type_invoice' => $validated['type_invoice'],
                'n_invoice' => $validated['n_invoice'],
                'data_invoice' => $validated['data_invoice'],
                'importo_totale' => $validated['importo_totale'],
                'causale' => $validated['causale'] ?? null,
                'divisa' => $validated['divisa'],
                'status' => $validated['status'],
                'sdi_id' => $validated['sdi_id'] ?? null,
            ]);
            
            // Elimina le righe esistenti
            $invoice->rows()->delete();
            
            // Ricrea le righe
            foreach ($validated['rows'] as $row) {
                $total = ($row['quantity'] * $row['unit_price']) * (1 - ($row['discount_percentage'] ?? 0) / 100);
                
                InvoiceRow::create([
                    'document_id' => $invoice->id,
                    'document_type' => 'invoice_received',
                    'id_cost_center' => $row['id_cost_center'],
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'vat_rate_id' => $row['vat_rate_id'],
                    'discount_percentage' => $row['discount_percentage'] ?? 0,
                    'total' => $total,
                ]);
            }
            
            DB::commit();
            
            return redirect()->route('admin.invoices-received.index')
                ->with('success', 'Fattura aggiornata con successo!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore aggiornamento fattura: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return back()->withInput()
                ->with('error', 'Errore durante l\'aggiornamento: ' . $e->getMessage());
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('delete_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        try {
            $invoice = InvoiceReceived::findOrFail($id);
            $invoice->rows()->delete();
            $invoice->delete();
            
            return redirect()->route('admin.invoices-received.index')
                ->with('success', 'Fattura eliminata con successo!');
                
        } catch (\Exception $e) {
            Log::error('Errore eliminazione fattura: ' . $e->getMessage());
            
            return back()->with('error', 'Errore durante l\'eliminazione: ' . $e->getMessage());
        }
    }
    
    /**
     * Update invoice status via AJAX.
     */
    public function updateStatus(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_purchases')) {
            return response()->json(['success' => false, 'message' => 'Permessi insufficienti'], 403);
        }
        
        try {
            $invoice = InvoiceReceived::findOrFail($id);
            $invoice->update(['status' => $request->status]);
            
            return response()->json([
                'success' => true,
                'message' => 'Stato aggiornato con successo!',
                'status_label' => $invoice->status_label,
                'status_badge_class' => $invoice->status_badge_class,
                'status_icon' => $invoice->status_icon
            ]);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento stato: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export invoices to PDF.
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $query = InvoiceReceived::with(['ownership', 'entity', 'rows.vatRate']);
        
        // Applica gli stessi filtri della lista
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('data_invoice', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('data_invoice', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('n_invoice', 'like', "%{$search}%")
                  ->orWhere('causale', 'like', "%{$search}%")
                  ->orWhere('sdi_id', 'like', "%{$search}%");
            });
        }
        
        $invoices = $query->orderBy('data_invoice', 'desc')->get();
        
        $totalAmount = $invoices->sum('importo_totale');
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices_received.export-pdf', compact('invoices', 'totalAmount', 'request'));
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('fatture_acquisto_' . Carbon::now()->format('Y-m-d_H-i') . '.pdf');
    }
    
    /**
     * Export invoices to Excel.
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $query = InvoiceReceived::with(['ownership', 'entity']);
        
        // Applica gli stessi filtri della lista
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('data_invoice', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('data_invoice', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('n_invoice', 'like', "%{$search}%")
                  ->orWhere('causale', 'like', "%{$search}%")
                  ->orWhere('sdi_id', 'like', "%{$search}%");
            });
        }
        
        $invoices = $query->orderBy('data_invoice', 'desc')->get();
        
        // Crea export Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Intestazioni
        $headers = ['Numero Fattura', 'Data', 'Fornitore', 'Tipo', 'Importo', 'Stato', 'SDI ID', 'Causale'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }
        
        // Dati
        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->setCellValue('A' . $row, $invoice->n_invoice);
            $sheet->setCellValue('B' . $row, Carbon::parse($invoice->data_invoice)->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $invoice->entity?->ragione_sociale ?: ($invoice->entity?->nome . ' ' . $invoice->entity?->cognome) ?? '-');
            $sheet->setCellValue('D' . $row, $invoice->type_label);
            $sheet->setCellValue('E' . $row, $invoice->importo_totale);
            $sheet->setCellValue('F' . $row, $invoice->status_label);
            $sheet->setCellValue('G' . $row, $invoice->sdi_id ?? '-');
            $sheet->setCellValue('H' . $row, $invoice->causale ?? '-');
            $row++;
        }
        
        // Formatta colonne
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getStyle('E2:E' . ($row - 1))->getNumberFormat()->setFormatCode('€ #,##0.00');
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'fatture_acquisto_' . Carbon::now()->format('Y-m-d_H-i') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }
}