<?php
// app/Http/Controllers/Admin/InvoiceSentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use App\Models\InvoiceSent;
use App\Models\Ownership;
use App\Models\Entity;
use App\Models\CostCenter;
use App\Models\VatRate;

class InvoiceSentController extends Controller
{
    public function index()
    {
        // Metodo alternativo senza Gate
        if (!Auth::guard('admin')->user()->hasPermission('view_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per visualizzare le fatture di vendita.');
        }
        return view('admin.invoice-sent.index');
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per creare fatture di vendita.');
        }
        return view('admin.invoice-sent.create');
    }

    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per modificare fatture di vendita.');
        }
        return view('admin.invoice-sent.edit', ['id' => $id]);
    }

    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per visualizzare i dettagli della fattura.');
        }
        return view('admin.invoice-sent.show', ['id' => $id]);
    }

    public function statistics()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_invoices_sent')) {
            abort(403, 'Non hai i permessi necessari per visualizzare le statistiche.');
        }
        return view('admin.invoice-sent.statistics');
    }

    /**
     * Anteprima PDF di una singola fattura nel browser
     */
    public function previewPdf($id)
    {
        $invoice = InvoiceSent::with([
            'ownership',
            'entity.addresses',
            'rows.costCenter',
            'rows.vehicle',
            'payments'
        ])->findOrFail($id);

        // Recupera il conto bancario con il campo emittente
        $bankAccount = \Illuminate\Support\Facades\DB::table('bank_accounts')
            ->where('id_ownership', $invoice->id_ownership)
            ->where('default_invoice', 1)
            ->where('valid', 1)
            ->first();

        // Se il bankAccount esiste e ha il campo emittente, usalo
        // altrimenti usa il campo name come fallback
        if ($bankAccount) {
            $bankName = $bankAccount->emittente ?? $bankAccount->name;
        }

        $data = [
            'invoice' => $invoice,
            'typeDocuments' => config('gestionale.tipo_documento', []),
            'statuses' => config('gestionale.invoice_status', []),
            'vatRates' => VatRate::all()->keyBy('id'),
            'bankAccount' => $bankAccount,  // Include emittente
        ];

        $pdf = Pdf::loadView('admin.invoice-sent.invoice-sent-pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('fattura_' . str_replace(['/', '\\'], '-', $invoice->n_invoice) . '.pdf');
    }

    /**
     * Esporta le fatture in PDF con i filtri applicati (lista)
     */
    public function exportPdf(Request $request)
    {
        $query = InvoiceSent::query()
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
        if ($request->customer_id) {
            $query->where('id_entities', $request->customer_id);
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
        
        return $pdf->download('fatture_vendita_' . date('Y-m-d_His') . '.pdf');
    }
    
    /**
     * Genera l'HTML per il PDF della lista fatture
     */
    private function generatePdfHtml($invoices, $request)
    {
        $totalAmount = $invoices->sum('importo_totale');
        $typeDocuments = config('gestionale.tipo_documento', []);
        $statuses = config('gestionale.invoice_status', []);
        
        // Ottieni nomi filtri
        $ownershipName = $request->ownership_id ? Ownership::find($request->ownership_id)?->RagAbbrev : null;
        $customerName = $request->customer_id ? Entity::find($request->customer_id)?->ragione_sociale : null;
        $costCenterName = $request->cost_center_id ? CostCenter::find($request->cost_center_id)?->Nome : null;
        
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Fatture di Vendita</title>
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
                .badge-approved { background: #d9f99d; color: #4d7c0f; }
                .badge-paid { background: #dcfce7; color: #166534; }
                .badge-cancelled { background: #fee2e2; color: #991b1b; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Fatture di Vendita</h1>
                <p>Data esportazione: ' . now()->format('d/m/Y H:i:s') . '</p>
            </div>
            
            <div class="filters">
                <table width="100%">
                    <tr>
                        <td width="25%"><strong>Filtri applicati:</strong></td>
                        <td width="75%">';
        
        if ($request->date_from || $request->date_to) {
            $html .= 'Periodo: ' . ($request->date_from ?: 'da inizio') . ' → ' . ($request->date_to ?: 'ad oggi') . '<br>';
        }
        if ($ownershipName) {
            $html .= 'Proprietà: ' . $ownershipName . '<br>';
        }
        if ($customerName) {
            $html .= 'Cliente: ' . $customerName . '<br>';
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
        if (!$request->date_from && !$ownershipName && !$customerName && !$costCenterName && !$request->status && !$request->type_invoice && !$request->search) {
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
                        <th>Cliente</th>
                        <th>Stato</th>
                        <th class="text-right">Importo (€)</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($invoices as $invoice) {
            $badgeClass = match($invoice->status) {
                'issued' => 'badge-issued',
                'approved' => 'badge-approved',
                'paid' => 'badge-paid',
                'cancelled' => 'badge-cancelled',
                default => 'badge-issued'
            };
            $statusLabel = $statuses[$invoice->status]['label'] ?? $invoice->status;
            
            $html .= '<tr>
                        <td class="text-center">' . $invoice->id . '</td>
                        <td>' . e($invoice->n_invoice) . '</td>
                        <td class="text-center">' . $invoice->data_invoice->format('d/m/Y') . '</td>
                        <td class="text-center">' . e($typeDocuments[$invoice->type_invoice] ?? $invoice->type_invoice) . '</td>
                        <td>' . e($invoice->ownership->RagAbbrev ?? $invoice->ownership_name) . '</td>
                        <td>' . e($invoice->customer_name) . '</td>
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
                <p>Documento generato automaticamente dal gestionale</p>
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
        $query = InvoiceSent::query()
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
        if ($request->customer_id) {
            $query->where('id_entities', $request->customer_id);
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
        $headers = ['ID', 'N. Fattura', 'Data Fattura', 'Tipo Documento', 'Proprietà', 'Cliente', 'Stato', 'Importo Totale (€)'];
        
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
        $typeDocuments = config('gestionale.tipo_documento', []);
        $statuses = config('gestionale.invoice_status', []);
        
        foreach ($invoices as $invoice) {
            $sheet->setCellValue('A' . $row, $invoice->id);
            $sheet->setCellValue('B' . $row, $invoice->n_invoice);
            $sheet->setCellValue('C' . $row, $invoice->data_invoice->format('d/m/Y'));
            $sheet->setCellValue('D' . $row, $typeDocuments[$invoice->type_invoice] ?? $invoice->type_invoice);
            $sheet->setCellValue('E' . $row, $invoice->ownership->RagAbbrev ?? $invoice->ownership_name);
            $sheet->setCellValue('F' . $row, $invoice->customer_name);
            $sheet->setCellValue('G' . $row, $statuses[$invoice->status]['label'] ?? $invoice->status);
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
        $filename = 'fatture_vendita_' . date('Y-m-d_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}