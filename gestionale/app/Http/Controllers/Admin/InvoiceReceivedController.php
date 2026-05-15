<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class InvoiceReceivedController extends Controller
{
    public function index()
    {
        return view('admin.invoices-received.index');
    }

    public function create()
    {
        $ownerships = Ownership::where('valid', 1)->get();
        $tipoDocumento = config('gestionale.tipo_documento', []);
        
        return view('admin.invoices-received.create', compact('ownerships', 'tipoDocumento'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_ownership' => 'nullable|exists:ownership,id_proprieta',
            'id_entities' => 'nullable|exists:entities,id_cliente',
            'type_invoice' => 'required|string|max:10',
            'n_invoice' => 'required|string|max:50',
            'data_invoice' => 'required|date',
            'importo_totale' => 'nullable|numeric|min:0',
            'causale' => 'nullable|string',
            'divisa' => 'required|string|size:3',
            'status' => 'required|in:bozza,inviata,scartata,consegnata',
            'sdi_id' => 'nullable|string|max:100',
            'rows' => 'nullable|array',
            'rows.*.id_cost_center' => 'nullable|exists:cost_centers,id',
            'rows.*.description' => 'required|string',
            'rows.*.quantity' => 'required|numeric|min:0',
            'rows.*.unit_price' => 'required|numeric|min:0',
            'rows.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            DB::beginTransaction();

            $validated['data_ownership'] = $this->buildOwnershipData($validated['id_ownership'] ?? null);
            $validated['data_entities'] = $this->buildEntityData($validated['id_entities'] ?? null);

            $invoice = InvoiceReceived::create($validated);

            if (!empty($validated['rows'])) {
                foreach ($validated['rows'] as $row) {
                    $row['document_id'] = $invoice->id;
                    $row['document_type'] = 'invoice_received';
                    InvoiceRow::create($row);
                }
                $invoice->importo_totale = $invoice->refresh()->rows->sum('total');
                $invoice->save();
            }

            DB::commit();
            return redirect()->route('admin.invoices-received.index')->with('success', 'Fattura creata con successo.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore creazione fattura: ' . $e->getMessage());
            return back()->with('error', 'Errore: ' . $e->getMessage())->withInput();
        }
    }

    public function show(InvoiceReceived $invoice)
    {
        $invoice->load(['rows.costCenter', 'ownership', 'entity']);
        return view('admin.invoices-received.show', compact('invoice'));
    }

    public function xmlImport()
    {
        Log::info('xmlImport - Auth check: ' . (Auth::guard('admin')->check() ? 'true' : 'false'));
        Log::info('xmlImport - User ID: ' . (Auth::guard('admin')->id() ?? 'null'));
        return view('admin.invoices-received.xml-import');
    }

    public function exportPdf()
    {
        $invoices = InvoiceReceived::with(['ownership', 'entity'])->orderBy('data_invoice', 'desc')->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices-received.pdf-export', compact('invoices'));
        return $pdf->download('fatture_acquisto_' . date('Y-m-d_His') . '.pdf');
    }

    private function buildOwnershipData($ownershipId)
    {
        if (!$ownershipId) return null;
        $ownership = Ownership::find($ownershipId);
        if (!$ownership) return null;

        return [
            'Denominazione' => $ownership->Rag_Soc_intest ?: $ownership->RagSocialePr,
            'Indirizzo' => $ownership->IndirizzoPr,
            'CAP' => $ownership->CapPr,
            'Comune' => $ownership->LocalitPr,
            'Provincia' => $ownership->ProvinciaPr,
            'Nazione' => 'IT',
            'CodiceDestinatario' => $ownership->codice_SDI,
            'PECDestinatario' => $ownership->PEC,
            'PartitaIVA' => $ownership->PivaPr,
            'CodiceFiscale' => $ownership->CodFiscPr
        ];
    }

    private function buildEntityData($entityId)
    {
        if (!$entityId) return null;
        $entity = Entity::find($entityId);
        if (!$entity) return null;

        return [
            'Denominazione' => $entity->ragione_sociale ?: ($entity->nome . ' ' . $entity->cognome),
            'Nome' => $entity->nome,
            'Cognome' => $entity->cognome,
            'Indirizzo' => $entity->indirizzo ?? '',
            'CAP' => $entity->cap ?? '',
            'Comune' => $entity->comune ?? '',
            'Provincia' => $entity->provincia ?? '',
            'Nazione' => 'IT',
            'CodiceFiscale' => $entity->codice_fiscale,
            'PartitaIVA' => $entity->partita_iva,
            'RegimeFiscale' => 'RF01',
            'Telefono' => $entity->telefono ?? '',
            'Email' => $entity->email ?? '',
            'PEC' => $entity->pec ?? ''
        ];
    }
}