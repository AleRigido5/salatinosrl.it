<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoiceReceived;
use App\Models\InvoiceRow;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
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

    public function edit($id)
    {
        return view('admin.invoices-received.edit', ['id' => $id]);
    }

    public function update(Request $request, $id)
    {
        // Questo metodo non viene più utilizzato perché usiamo Livewire
        // Ma lo manteniamo per compatibilità
        return redirect()->route('admin.invoices-received.index');
    }

    public function xmlImport()
    {
        Log::info('xmlImport - Auth check: ' . (Auth::guard('admin')->check() ? 'true' : 'false'));
        Log::info('xmlImport - User ID: ' . (Auth::guard('admin')->id() ?? 'null'));
        return view('admin.invoices-received.xml-import');
    }

    public function statistics()
    {
        return view('admin.invoices-received.statistics');
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

    /**
     * Esporta le fatture in PDF con i filtri applicati
     */
    public function exportPdf(Request $request)
    {
        $query = InvoiceReceived::query()
            ->with(['ownership', 'entity']);
        
        // Applica filtri
        if ($request->date_from) {
            $query->whereDate('data_invoice', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('data_invoice', '<=', $request->date_to);
        }
        if ($request->ownership_id) {
            $query->where('id_ownership', $request->ownership_id);
        }
        if ($request->supplier_id) {
            $query->where('id_entities', $request->supplier_id);
        }
        if ($request->cost_center_id) {
            $query->whereHas('rows', fn($q) => $q->where('id_cost_center', $request->cost_center_id));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type_invoice) {
            $query->where('type_invoice', $request->type_invoice);
        }
        if ($request->search) {
            $query->where('n_invoice', 'like', '%' . $request->search . '%');
        }
        
        $invoices = $query->orderBy('data_invoice', 'desc')->get();
        
        // Genera HTML per il PDF
        $html = $this->generatePdfHtml($invoices, $request);
        
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        
        return $pdf->download('fatture_acquisto_' . date('Y-m-d_His') . '.pdf');
    }
    
    /**
     * Genera l'HTML per il PDF
     */
    private function generatePdfHtml($invoices, $request)
    {
        $totalAmount = $invoices->sum('importo_totale');
        $typeDocuments = config('gestionale.tipo_documento', []);
        $statuses = config('gestionale.invoice_status', []);
        
        // Ottieni nomi filtri
        $ownershipName = $request->ownership_id ? Ownership::find($request->ownership_id)?->RagAbbrev : null;
        $supplierName = $request->supplier_id ? Entity::find($request->supplier_id)?->ragione_sociale : null;
        $costCenterName = $request->cost_center_id ? CostCenter::find($request->cost_center_id)?->Nome : null;
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Fatture di Acquisto</title>
            <style>
                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 10px;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #84cc16;
                    padding-bottom: 10px;
                }
                .header h1 {
                    color: #4a5568;
                    margin: 0;
                    font-size: 18px;
                }
                .header p {
                    color: #718096;
                    margin: 5px 0 0;
                    font-size: 10px;
                }
                .filters {
                    background: #f7fafc;
                    padding: 10px;
                    margin-bottom: 20px;
                    border-radius: 5px;
                    font-size: 9px;
                }
                .filters td {
                    padding: 3px 5px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                }
                th {
                    background: #f1f5f9;
                    padding: 8px 6px;
                    text-align: center;
                    font-weight: bold;
                    border: 1px solid #cbd5e1;
                    font-size: 9px;
                }
                td {
                    padding: 6px;
                    border: 1px solid #e2e8f0;
                    font-size: 9px;
                }
                .text-right {
                    text-align: right;
                }
                .text-center {
                    text-align: center;
                }
                .footer {
                    margin-top: 20px;
                    text-align: right;
                    font-size: 9px;
                    border-top: 1px solid #e2e8f0;
                    padding-top: 10px;
                }
                .badge {
                    display: inline-block;
                    padding: 2px 6px;
                    border-radius: 10px;
                    font-size: 8px;
                }
                .badge-issued { background: #fef3c7; color: #92400e; }
                .badge-viewed { background: #dbeafe; color: #1e40af; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Fatture di Acquisto</h1>
                <p>Data esportazione: ' . now()->format('d/m/Y H:i:s') . '</p>
            </div>
            
            <div class="filters">
                <table>
                    <tr>
                        <td width="25%"><strong>Filtri applicati:</strong></td>
                        <td width="75%">';
        
        if ($request->date_from || $request->date_to) {
            $html .= 'Periodo: ' . ($request->date_from ?: 'da inizio') . ' → ' . ($request->date_to ?: 'ad oggi') . '<br>';
        }
        if ($ownershipName) {
            $html .= 'Proprietà: ' . $ownershipName . '<br>';
        }
        if ($supplierName) {
            $html .= 'Fornitore: ' . $supplierName . '<br>';
        }
        if ($costCenterName) {
            $html .= 'Centro di Costo: ' . $costCenterName . '<br>';
        }
        if ($request->status) {
            $html .= 'Stato: ' . ($statuses[$request->status]['label'] ?? $request->status) . '<br>';
        }
        if ($request->type_invoice) {
            $html .= 'Tipo Documento: ' . ($typeDocuments[$request->type_invoice] ?? $request->type_invoice) . '<br>';
        }
        if ($request->search) {
            $html .= 'Ricerca: "' . $request->search . '"<br>';
        }
        if (!$request->date_from && !$ownershipName && !$supplierName && !$costCenterName && !$request->status && !$request->type_invoice && !$request->search) {
            $html .= 'Nessun filtro applicato';
        }
        
        $html .= '</td>
                    </tr>
                </table>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>N. Fattura</th>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Proprietà</th>
                        <th>Fornitore</th>
                        <th>Stato</th>
                        <th class="text-right">Importo (€)</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($invoices as $invoice) {
            $badgeClass = $invoice->status === 'issued' ? 'badge-issued' : 'badge-viewed';
            $statusLabel = $invoice->status === 'issued' ? 'Emessa' : 'Visionata';
            
            $html .= '<tr>
                        <td class="text-center">' . $invoice->id . '</td>
                        <td>' . e($invoice->n_invoice) . '</td>
                        <td class="text-center">' . $invoice->data_invoice->format('d/m/Y') . '</td>
                        <td class="text-center">' . e($invoice->type_invoice_label) . '</td>
                        <td>' . e($invoice->ownership_name) . '</td>
                        <td>' . e($invoice->supplier_name) . '</td>
                        <td class="text-center"><span class="badge ' . $badgeClass . '">' . $statusLabel . '</span></td>
                        <td class="text-right">' . number_format($invoice->importo_totale, 2, ',', '.') . ' €</td>
                    </tr>';
        }
        
        $html .= '</tbody>
                <tfoot>
                    <tr>
                        <th colspan="7" class="text-right">TOTALE GENERALE:</th>
                        <th class="text-right">' . number_format($totalAmount, 2, ',', '.') . ' €</th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="footer">
                <p>Documento generato automaticamente dal gestionale Gruppo Salatino</p>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Esporta le fatture in Excel con i filtri applicati
     */
    public function exportExcel(Request $request)
    {
        $query = InvoiceReceived::query()
            ->with(['ownership', 'entity']);
        
        // Applica filtri
        if ($request->date_from) {
            $query->whereDate('data_invoice', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('data_invoice', '<=', $request->date_to);
        }
        if ($request->ownership_id) {
            $query->where('id_ownership', $request->ownership_id);
        }
        if ($request->supplier_id) {
            $query->where('id_entities', $request->supplier_id);
        }
        if ($request->cost_center_id) {
            $query->whereHas('rows', fn($q) => $q->where('id_cost_center', $request->cost_center_id));
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->type_invoice) {
            $query->where('type_invoice', $request->type_invoice);
        }
        if ($request->search) {
            $query->where('n_invoice', 'like', '%' . $request->search . '%');
        }
        
        $invoices = $query->orderBy('data_invoice', 'desc')->get();
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Intestazioni
        $headers = ['ID', 'N. Fattura', 'Data Fattura', 'Tipo Documento', 'Proprietà', 'Fornitore', 'Stato', 'Importo Totale (€)'];
        
        foreach ($headers as $col => $header) {
            $cell = chr(65 + $col) . '1';
            $sheet->setCellValue($cell, $header);
        }
        
        // Stile intestazioni
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);
        
        // Dati
        $row = 2;
        $totalAmount = 0;
        
        foreach ($invoices as $invoice) {
            $sheet->setCellValue('A' . $row, $invoice->id);
            $sheet->setCellValue('B' . $row, $invoice->n_invoice);
            $sheet->setCellValue('C' . $row, $invoice->data_invoice->format('d/m/Y'));
            $sheet->setCellValue('D' . $row, $invoice->type_invoice_label);
            $sheet->setCellValue('E' . $row, $invoice->ownership_name);
            $sheet->setCellValue('F' . $row, $invoice->supplier_name);
            $sheet->setCellValue('G' . $row, $invoice->status_label);
            $sheet->setCellValue('H' . $row, $invoice->importo_totale);
            
            $totalAmount += $invoice->importo_totale;
            $row++;
        }
        
        // Riga totale
        $sheet->setCellValue('G' . $row, 'TOTALE:');
        $sheet->setCellValue('H' . $row, $totalAmount);
        $sheet->getStyle('G' . $row . ':H' . $row)->getFont()->setBold(true);
        
        // Formatta colonne
        $sheet->getStyle('H2:H' . ($row - 1))->getNumberFormat()->setFormatCode('#,##0.00 €');
        $sheet->getStyle('H' . $row)->getNumberFormat()->setFormatCode('#,##0.00 €');
        
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $sheet->setAutoFilter('A1:H' . ($row - 1));
        
        $writer = new Xlsx($spreadsheet);
        $filename = 'fatture_acquisto_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}