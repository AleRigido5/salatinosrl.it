<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use App\Models\InvoiceReceived;
use App\Models\Ownership;
use App\Models\Entity;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoicePaymentController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.invoice-payments.index');
    }

    /**
     * Esporta le scadenze pagamenti in PDF
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        try {
            set_time_limit(300);
            
            // Recupera i dati con tutti i filtri
            $payments = $this->getExportData($request);
            
            // Se il parametro per_page è specificato, usalo per limitare i risultati
            $perPage = $request->input('per_page', 100);
            if ($perPage != 'all' && $perPage != '10000') {
                $payments = $payments->take((int)$perPage);
            }
            
            $html = $this->buildPdfHtml($payments, $request);
            
            $pdf = Pdf::loadHTML($html)
                ->setPaper('A4', 'landscape')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => false,
                    'enable_css' => true,
                    'dpi' => 72,
                ]);
            
            return $pdf->download("scadenze_pagamenti_" . Carbon::now()->format('Y-m-d') . ".pdf");
            
        } catch (\Exception $e) {
            Log::error('Errore esportazione PDF: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Errore durante l\'esportazione PDF: ' . $e->getMessage());
        }
    }

    /**
     * Esporta le scadenze pagamenti in Excel
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        try {
            set_time_limit(300);
            
            $payments = $this->getExportData($request);
            
            // Se il parametro per_page è specificato, usalo per limitare i risultati
            $perPage = $request->input('per_page', 100);
            if ($perPage != 'all' && $perPage != '10000') {
                $payments = $payments->take((int)$perPage);
            }
            
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Scadenze Pagamenti');

            // INTESTAZIONI
            $headers = [
                'A1' => 'Proprietà',
                'B1' => 'Fornitore',
                'C1' => 'Data Scadenza',
                'D1' => 'N. Fattura',
                'E1' => 'Importo (€)',
                'F1' => 'Residuo (€)',
                'G1' => 'Modalità Pagamento',
                'H1' => 'Stato',
                'I1' => 'Note'
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }

            // Stile intestazioni
            $headerStyle = $sheet->getStyle('A1:I1');
            $headerStyle->getFont()->setBold(true)->setColor(new Color(Color::COLOR_WHITE));
            $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('84cc16'));
            $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('DDDDDD'));

            // DATI
            $row = 2;
            $totalAmount = 0;
            $totalResidual = 0;

            foreach ($payments as $payment) {
                $invoice = $payment->payable;
                if (!$invoice) continue;
                
                $isCreditNote = $invoice && method_exists($invoice, 'isCreditNote') && $invoice->isCreditNote();
                
                $residual = $payment->residual_amount;
                if ($residual <= 0 && $payment->amount > 0) {
                    $residual = $payment->amount - $payment->paid_amount;
                    if ($residual < 0) $residual = 0;
                }
                
                $displayAmount = $isCreditNote ? -$payment->amount : $payment->amount;
                $displayResidual = $isCreditNote ? -$residual : $residual;
                
                $isClosedByNC = $payment->status === 'closed_credit_note' || 
                               ($payment->status === 'paid' && $invoice && 
                                method_exists($invoice, 'isClosedByCreditNote') && $invoice->isClosedByCreditNote());
                
                $statusConfig = $this->getStatusConfig($payment, $isClosedByNC);

                $totalAmount += $payment->amount;
                $totalResidual += $residual;

                $sheet->setCellValue('A' . $row, $invoice->ownership->RagAbbrev ?? $invoice->ownership_name ?? '-');
                $sheet->setCellValue('B' . $row, $invoice->entity->ragione_sociale ?? $invoice->supplier_name ?? '-');
                $sheet->setCellValue('C' . $row, $payment->due_date ? $payment->due_date->format('d/m/Y') : '-');
                $sheet->setCellValue('D' . $row, ($invoice->n_invoice ?? '-') . ($isCreditNote ? ' (NC)' : ''));
                $sheet->setCellValue('E' . $row, $displayAmount);
                $sheet->setCellValue('F' . $row, $displayResidual);
                $sheet->setCellValue('G' . $row, $payment->payment_method ?? '-');
                $sheet->setCellValue('H' . $row, $statusConfig['label']);
                $sheet->setCellValue('I' . $row, $isClosedByNC ? 'Chiusa con NC' : ($isCreditNote ? 'Nota di Credito' : ''));

                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00 €');
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00 €');

                if ($displayAmount < 0) {
                    $sheet->getStyle('E' . $row)->getFont()->setColor(new Color('7B1FA2'));
                }
                if ($displayResidual < 0) {
                    $sheet->getStyle('F' . $row)->getFont()->setColor(new Color('7B1FA2'));
                }

                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':I' . $row)->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->setStartColor(new Color('F9FAFB'));
                }

                $sheet->getStyle('A' . $row . ':I' . $row)->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN)
                    ->setColor(new Color('E5E7EB'));

                $row++;
            }

            // Riga totali
            $sheet->setCellValue('A' . $row, 'TOTALI');
            $sheet->setCellValue('E' . $row, $totalAmount);
            $sheet->setCellValue('F' . $row, $totalResidual);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('#,##0.00 €');
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0.00 €');
            
            $totalStyle = $sheet->getStyle('A' . $row . ':I' . $row);
            $totalStyle->getFont()->setBold(true);
            $totalStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('F0FDF4'));
            $totalStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('DDDDDD'));
            $sheet->mergeCells('A' . $row . ':D' . $row);

            $row++;
            $sheet->setCellValue('A' . $row, 'Totale record mostrati: ' . $payments->count());
            $footerStyle = $sheet->getStyle('A' . $row . ':I' . $row);
            $footerStyle->getFont()->setBold(true);
            $footerStyle->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor(new Color('F3F4F6'));
            $sheet->mergeCells('A' . $row . ':I' . $row);

            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $sheet->getRowDimension(1)->setRowHeight(25);
            $sheet->setAutoFilter('A1:I' . ($row - 2));
            $sheet->freezePane('A2');

            $filename = "scadenze_pagamenti_" . Carbon::now()->format('Y-m-d') . ".xlsx";

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
            
        } catch (\Exception $e) {
            Log::error('Errore esportazione Excel: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Errore durante l\'esportazione Excel: ' . $e->getMessage());
        }
    }

    /**
     * Recupera i dati per l'esportazione con tutti i filtri
     */
    private function getExportData(Request $request)
    {
        $query = InvoicePayment::query()
            ->with(['payable' => function($q) {
                $q->with(['ownership' => function($oq) {
                    $oq->select('id_proprieta', 'RagAbbrev');
                }, 'entity' => function($eq) {
                    $eq->select('id_cliente', 'ragione_sociale');
                }]);
            }])
            ->select([
                'id',
                'payable_id',
                'payable_type',
                'amount',
                'paid_amount',
                'residual_amount',
                'due_date',
                'payment_method',
                'status',
                'paid_at'
            ]);

        // Filtro Proprietà
        if ($request->filled('ownership_id')) {
            $query->whereHas('payable', fn($q) => $q->where('id_ownership', $request->ownership_id));
        }

        // Filtro Fornitore
        if ($request->filled('supplier_id')) {
            $query->whereHas('payable', fn($q) => $q->where('id_entities', $request->supplier_id));
        }

        // Filtro N. Fattura
        if ($request->filled('invoice_search')) {
            $query->whereHas('payable', fn($q) => $q->where('n_invoice', 'like', '%' . $request->invoice_search . '%'));
        }

        // Filtro Stato
        if ($request->filled('status')) {
            if ($request->status === 'closed_credit_note') {
                $query->where(function($sq) {
                    $sq->where('invoice_payments.status', 'closed_credit_note')
                      ->orWhereRaw("
                          invoice_payments.status = 'paid' 
                          AND invoice_payments.payable_type = 'App\\Models\\InvoiceReceived' 
                          AND EXISTS (
                              SELECT 1 FROM invoices_received ir 
                              WHERE ir.id = invoice_payments.payable_id 
                              AND ir.deleted_at IS NULL
                              AND (
                                  EXISTS (
                                      SELECT 1 FROM credit_note_invoice_relations r
                                      INNER JOIN invoices_received nc ON nc.id = r.credit_note_id
                                      WHERE r.invoice_id = ir.id 
                                      AND nc.type_invoice = 'TD04'
                                      AND nc.deleted_at IS NULL
                                  )
                                  OR 
                                  EXISTS (
                                      SELECT 1 FROM credit_note_invoice_relations r
                                      WHERE r.credit_note_id = ir.id
                                  )
                                  OR
                                  EXISTS (
                                      SELECT 1 FROM invoices_received nc 
                                      WHERE nc.closes_invoice_id = ir.id 
                                      AND nc.type_invoice = 'TD04'
                                      AND nc.deleted_at IS NULL
                                  )
                              )
                          )
                      ");
                });
            } else {
                $query->where('invoice_payments.status', $request->status);
            }
        }

        // Filtri Data
        if ($request->filled('date_from')) {
            $query->whereDate('due_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('due_date', '<=', $request->date_to);
        }

        // Ordinamento (stesso del Livewire)
        $sortField = $request->input('sort_field', 'due_date');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        return $query->get();
    }

    /**
     * Configurazione stati
     */
    private function getStatusConfig($payment, $isClosedByNC): array
    {
        $statuses = [
            'issued' => ['label' => 'In attesa', 'badge_class' => 'bg-yellow-100 text-yellow-800'],
            'partially_paid' => ['label' => 'Pagato parzialmente', 'badge_class' => 'bg-blue-100 text-blue-800'],
            'paid' => ['label' => 'Pagato', 'badge_class' => 'bg-green-100 text-green-800'],
            'closed_credit_note' => ['label' => 'Saldato con NC', 'badge_class' => 'bg-purple-100 text-purple-800'],
        ];
        
        if ($isClosedByNC && $payment->status !== 'closed_credit_note') {
            return $statuses['closed_credit_note'];
        }
        
        return $statuses[$payment->status] ?? ['label' => $payment->status, 'badge_class' => 'bg-gray-100'];
    }

    /**
     * Costruisce l'HTML per il PDF
     */
    private function buildPdfHtml($payments, Request $request): string
    {
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->format('d/m/Y') : 'Tutti';
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->format('d/m/Y') : 'Tutti';

        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; margin: 10px; }
                .header { text-align: center; margin-bottom: 10px; border-bottom: 2px solid #84cc16; padding-bottom: 6px; }
                .header h1 { margin: 0; font-size: 14px; color: #333; }
                .header p { margin: 2px 0 0; color: #666; font-size: 8px; }
                .filters-info { background-color: #f3f4f6; padding: 4px; margin-bottom: 8px; font-size: 7px; border-radius: 3px; }
                .filters-info span { color: #059669; font-weight: bold; }
                table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8px; }
                th { background-color: #84cc16; color: white; padding: 4px 5px; text-align: left; font-size: 8px; }
                td { border: 1px solid #ddd; padding: 3px 5px; font-size: 8px; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 10px; text-align: right; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
                .badge { background-color: #84cc16; color: white; padding: 1px 4px; border-radius: 3px; font-size: 6px; display: inline-block; }
                .text-right { text-align: right; }
                .text-purple { color: #7B1FA2; }
                .bg-red-50 { background-color: #fee2e2; }
                .bg-purple-50 { background-color: #f3e8ff; }
                .warning { background-color: #fef3c7; padding: 4px; margin-bottom: 8px; border: 1px solid #f59e0b; border-radius: 4px; font-size: 8px; color: #92400e; }
            </style>
        </head>
        <body>';

        $html .= '<div class="header">
            <h1>📋 Scadenze Pagamenti</h1>
            <p>Periodo: ' . $dateFrom . ' → ' . $dateTo . ' | Generato il ' . Carbon::now()->format('d/m/Y H:i') . '</p>
            <p style="font-size: 8px; color: #666;">Record mostrati: ' . $payments->count() . '</p>
        </div>';

        $activeFilters = [];
        if ($request->filled('ownership_id')) {
            $ownership = Ownership::find($request->ownership_id);
            if ($ownership) $activeFilters[] = 'Proprietà: ' . $ownership->RagAbbrev;
        }
        if ($request->filled('supplier_id')) {
            $supplier = Entity::find($request->supplier_id);
            if ($supplier) $activeFilters[] = 'Fornitore: ' . $supplier->ragione_sociale;
        }
        if ($request->filled('invoice_search')) {
            $activeFilters[] = 'N. Fattura: ' . $request->invoice_search;
        }
        if ($request->filled('status')) {
            $statusMap = [
                'issued' => 'In attesa',
                'partially_paid' => 'Pagato parzialmente',
                'paid' => 'Pagato',
                'closed_credit_note' => 'Saldato con NC'
            ];
            $activeFilters[] = 'Stato: ' . ($statusMap[$request->status] ?? $request->status);
        }
        
        if (!empty($activeFilters)) {
            $html .= '<div class="filters-info">Filtri attivi: <span>' . implode(' | ', $activeFilters) . '</span></div>';
        }

        $html .= '<table>
            <thead>
                <tr>
                    <th>Proprietà</th>
                    <th>Fornitore</th>
                    <th>Data Scadenza</th>
                    <th>N. Fattura</th>
                    <th class="text-right">Importo</th>
                    <th class="text-right">Residuo</th>
                    <th>Modalità</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody>';

        $totalAmount = 0;
        $totalResidual = 0;

        foreach ($payments as $payment) {
            $invoice = $payment->payable;
            if (!$invoice) continue;
            
            $isCreditNote = $invoice && method_exists($invoice, 'isCreditNote') && $invoice->isCreditNote();
            
            $residual = $payment->residual_amount;
            if ($residual <= 0 && $payment->amount > 0) {
                $residual = $payment->amount - $payment->paid_amount;
                if ($residual < 0) $residual = 0;
            }
            
            $displayAmount = $isCreditNote ? -$payment->amount : $payment->amount;
            $displayResidual = $isCreditNote ? -$residual : $residual;
            
            $isClosedByNC = $payment->status === 'closed_credit_note' || 
                           ($payment->status === 'paid' && $invoice && 
                            method_exists($invoice, 'isClosedByCreditNote') && $invoice->isClosedByCreditNote());
            
            $statusConfig = $this->getStatusConfig($payment, $isClosedByNC);
            
            $totalAmount += $payment->amount;
            $totalResidual += $residual;

            $rowClass = '';
            if ($payment->due_date && $payment->due_date->isPast() && $payment->status !== 'paid' && $payment->status !== 'closed_credit_note') {
                $rowClass = ' class="bg-red-50"';
            } elseif ($isCreditNote) {
                $rowClass = ' class="bg-purple-50"';
            }

            $html .= '<tr' . $rowClass . '>
                <td>' . e($invoice->ownership->RagAbbrev ?? $invoice->ownership_name ?? '-') . '</td>
                <td>' . e($invoice->entity->ragione_sociale ?? $invoice->supplier_name ?? '-') . '</td>
                <td>' . ($payment->due_date ? $payment->due_date->format('d/m/Y') : '-') . '</td>
                <td>' . e($invoice->n_invoice ?? '-') . ($isCreditNote ? ' <span class="badge">NC</span>' : '') . '</td>
                <td class="text-right' . ($isCreditNote ? ' text-purple' : '') . '">' . number_format($displayAmount, 2, ',', '.') . ' €</td>
                <td class="text-right' . ($isCreditNote ? ' text-purple' : '') . '">' . number_format($displayResidual, 2, ',', '.') . ' €</td>
                <td>' . e($payment->payment_method ?? '-') . '</td>
                <td><span style="background-color: ' . $this->getBadgeColor($statusConfig['badge_class']) . '; padding: 2px 5px; border-radius: 3px; font-size: 7px;">' . $statusConfig['label'] . '</span></td>
            </tr>';
        }

        $html .= '</tbody>
            <tfoot>
                <tr style="background-color: #f0fdf4; font-weight: bold; border-top: 2px solid #84cc16;">
                    <td colspan="4" class="text-right">TOTALI</td>
                    <td class="text-right">' . number_format($totalAmount, 2, ',', '.') . ' €</td>
                    <td class="text-right">' . number_format($totalResidual, 2, ',', '.') . ' €</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            </table>
            <div class="footer">
                Generato il ' . Carbon::now()->format('d/m/Y H:i:s') . '
            </div>
            </body></html>';

        return $html;
    }

    /**
     * Mappa classe CSS a colore per PDF
     */
    private function getBadgeColor($badgeClass): string
    {
        $map = [
            'bg-yellow-100' => '#fef3c7',
            'bg-blue-100' => '#dbeafe',
            'bg-green-100' => '#d1fae5',
            'bg-purple-100' => '#f3e8ff',
            'bg-gray-100' => '#f3f4f6',
            'bg-red-100' => '#fee2e2',
        ];
        
        foreach ($map as $class => $color) {
            if (strpos($badgeClass, $class) !== false) {
                return $color;
            }
        }
        
        return '#f3f4f6';
    }
}