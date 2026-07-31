<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use App\Models\InvoiceSent;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class InvoicePaymentSentController extends Controller
{
    /**
     * Etichette stato, allineate con InvoicePaymentsSentTable::$statuses
     * (qui servono solo le label testuali, niente classi badge).
     */
    protected array $statusLabels = [
        'issued' => 'In attesa',
        'partially_paid' => 'Pagato parzialmente',
        'paid' => 'Pagato',
        'overdue' => 'Scaduto',
        'closed_credit_note' => 'Saldato con NC',
    ];

    public function index()
    {
        return view('admin.invoice-payments-sent.index');
    }

    /**
     * Applica agli scopes della query gli stessi filtri disponibili
     * nella tabella Livewire (InvoicePaymentsSentTable::getPaymentsProperty).
     */
    protected function buildFilteredQuery(Request $request): Builder
    {
        $query = InvoicePayment::where('payable_type', InvoiceSent::class)
            ->with(['payable.entity', 'payable.ownership'])
            ->join('invoices_sent', 'invoice_payments.payable_id', '=', 'invoices_sent.id')
            ->join('entities', 'invoices_sent.id_entities', '=', 'entities.id_cliente')
            ->select('invoice_payments.*')
            // Solo le scadenze con un residuo ancora aperto, come nella tabella a schermo
            ->where(function ($q) {
                $q->where('invoice_payments.residual_amount', '>', 0.01)
                  ->orWhereRaw('invoice_payments.amount - invoice_payments.paid_amount > 0.01');
            });

        if ($request->filled('ownership_id')) {
            $query->where('invoices_sent.id_ownership', $request->ownership_id);
        }

        if ($request->filled('client_id')) {
            $query->where('invoices_sent.id_entities', $request->client_id);
        }

        if ($request->filled('status') && $request->status !== 'paid') {
            $query->where('invoice_payments.status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_payments.due_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_payments.due_date', '<=', $request->date_to);
        }

        if ($request->filled('invoice_number')) {
            $query->where('invoices_sent.n_invoice', 'like', '%' . $request->invoice_number . '%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoices_sent.n_invoice', 'like', '%' . $search . '%')
                  ->orWhere('entities.ragione_sociale', 'like', '%' . $search . '%')
                  ->orWhere('entities.nome', 'like', '%' . $search . '%')
                  ->orWhere('entities.cognome', 'like', '%' . $search . '%');
            });
        }

        return $query->orderBy('invoice_payments.due_date', 'asc');
    }

    /**
     * Determina lo stato "effettivo" della scadenza, con la stessa logica
     * usata nella vista Blade della tabella (residuo/pagamenti parziali).
     */
    protected function resolveStatus(InvoicePayment $payment, float $residual): string
    {
        $status = $payment->status;

        if ($status !== 'closed_credit_note') {
            if ($residual <= 0.01) {
                $status = 'paid';
            } elseif ($payment->paid_amount > 0) {
                $status = 'partially_paid';
            }
        }

        return $status;
    }

    public function exportPdf(Request $request)
    {
        $payments = $this->buildFilteredQuery($request)->get();

        $html = '<!DOCTYPE html><html><head><meta charset="utf-8">
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; margin: 15px; }
            .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #84cc16; padding-bottom: 8px; }
            .header h1 { margin: 0; font-size: 14px; color: #333; }
            .header p { margin: 3px 0 0; color: #666; font-size: 9px; }
            .filters-info { background-color: #f3f4f6; padding: 5px; margin-bottom: 10px; font-size: 8px; border-radius: 4px; }
            .filters-info span { color: #059669; font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th { background-color: #84cc16; color: white; padding: 6px; text-align: left; font-size: 9px; }
            td { border: 1px solid #ddd; padding: 4px 6px; font-size: 8px; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .text-right { text-align: right; }
            .overdue { color: #dc2626; font-weight: bold; }
            .credit-note { color: #7c3aed; }
            .footer { margin-top: 15px; text-align: right; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 8px; }
        </style>
        </head><body>';

        $html .= '<div class="header">
            <h1>Scadenze Vendite</h1>
            <p>Generato il ' . Carbon::now()->format('d/m/Y H:i') . '</p>
        </div>';

        $activeFilters = [];
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $activeFilters[] = 'Periodo: ' . ($request->date_from ?: '...') . ' - ' . ($request->date_to ?: '...');
        }
        if ($request->filled('status')) $activeFilters[] = 'Stato: ' . ($this->statusLabels[$request->status] ?? $request->status);
        if ($request->filled('invoice_number')) $activeFilters[] = 'N. Fattura: ' . $request->invoice_number;

        if (!empty($activeFilters)) {
            $html .= '<div class="filters-info">Filtri attivi: <span>' . implode(' | ', $activeFilters) . '</span></div>';
        }

        $html .= '<table>
            <thead>
                <tr>
                    <th>Proprietà</th>
                    <th>Cliente</th>
                    <th>Data Scadenza</th>
                    <th>N. Fattura</th>
                    <th class="text-right">Importo</th>
                    <th class="text-right">Residuo</th>
                    <th>Modalità Pagamento</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody>';

        $totalAmount = 0;
        $totalResidual = 0;

        foreach ($payments as $payment) {
            $invoice = $payment->payable;
            if (!$invoice) {
                continue;
            }

            $isCreditNote = method_exists($invoice, 'isCreditNote') && $invoice->isCreditNote();
            $isOverdue = $payment->due_date && $payment->due_date->isPast() && $payment->residual_amount > 0 && $payment->status !== 'closed_credit_note';
            $residual = $payment->residual_amount > 0 ? $payment->residual_amount : ($payment->amount - $payment->paid_amount);
            $displayAmount = $isCreditNote ? -$payment->amount : $payment->amount;
            $displayResidual = $isCreditNote ? -max(0, $residual) : max(0, $residual);

            $totalAmount += $displayAmount;
            $totalResidual += $displayResidual;

            $currentStatus = $this->resolveStatus($payment, $residual);
            $statusLabel = $this->statusLabels[$currentStatus] ?? $currentStatus;

            $ownershipName = $invoice->ownership->RagAbbrev ?? $invoice->ownership->Rag_Soc_intest ?? '-';
            $clientName = $invoice->entity->ragione_sociale ?? trim(($invoice->entity->nome ?? '') . ' ' . ($invoice->entity->cognome ?? '')) ?: '-';
            $rowClass = $isOverdue ? ' class="overdue"' : ($isCreditNote ? ' class="credit-note"' : '');

            $html .= '<tr>
                <td>' . e($ownershipName) . '</td>
                <td>' . e($clientName) . '</td>
                <td' . $rowClass . '>' . ($payment->due_date ? e($payment->due_date->format('d/m/Y')) : '-') . ($isOverdue ? ' &#9888;' : '') . '</td>
                <td>' . e($invoice->n_invoice ?? '-') . ($isCreditNote ? ' (NC)' : '') . '</td>
                <td class="text-right">' . number_format($displayAmount, 2, ',', '.') . ' &euro;</td>
                <td class="text-right">' . number_format($displayResidual, 2, ',', '.') . ' &euro;</td>
                <td>' . e($payment->payment_method ?? '-') . '</td>
                <td>' . e($statusLabel) . '</td>
            </tr>';
        }

        $html .= '</tbody>
            </table>
            <div class="footer">
                Totale scadenze: ' . $payments->count() . ' &mdash; Importo: ' . number_format($totalAmount, 2, ',', '.') . ' &euro; &mdash; Residuo: ' . number_format($totalResidual, 2, ',', '.') . ' &euro;
            </div>
            </body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'landscape');

        return $pdf->download('scadenze_vendite_' . Carbon::now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $payments = $this->buildFilteredQuery($request)->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Scadenze Vendite');

        $headers = [
            'A1' => 'Proprietà',
            'B1' => 'Cliente',
            'C1' => 'Data Scadenza',
            'D1' => 'N. Fattura',
            'E1' => 'Importo',
            'F1' => 'Residuo',
            'G1' => 'Modalità Pagamento',
            'H1' => 'Stato',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '84cc16']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        $row = 2;
        $totalAmount = 0;
        $totalResidual = 0;

        foreach ($payments as $payment) {
            $invoice = $payment->payable;
            if (!$invoice) {
                continue;
            }

            $isCreditNote = method_exists($invoice, 'isCreditNote') && $invoice->isCreditNote();
            $residual = $payment->residual_amount > 0 ? $payment->residual_amount : ($payment->amount - $payment->paid_amount);
            $displayAmount = $isCreditNote ? -$payment->amount : $payment->amount;
            $displayResidual = $isCreditNote ? -max(0, $residual) : max(0, $residual);

            $totalAmount += $displayAmount;
            $totalResidual += $displayResidual;

            $currentStatus = $this->resolveStatus($payment, $residual);
            $statusLabel = $this->statusLabels[$currentStatus] ?? $currentStatus;

            $ownershipName = $invoice->ownership->RagAbbrev ?? $invoice->ownership->Rag_Soc_intest ?? '-';
            $clientName = $invoice->entity->ragione_sociale ?? trim(($invoice->entity->nome ?? '') . ' ' . ($invoice->entity->cognome ?? '')) ?: '-';
            $invoiceNumber = ($invoice->n_invoice ?? '-') . ($isCreditNote ? ' (NC)' : '');

            $sheet->setCellValue('A' . $row, $ownershipName);
            $sheet->setCellValue('B' . $row, $clientName);
            $sheet->setCellValue('C' . $row, $payment->due_date ? $payment->due_date->format('d/m/Y') : '-');
            $sheet->setCellValue('D' . $row, $invoiceNumber);
            $sheet->setCellValue('E' . $row, $displayAmount);
            $sheet->setCellValue('F' . $row, $displayResidual);
            $sheet->setCellValue('G' . $row, $payment->payment_method ?? '-');
            $sheet->setCellValue('H' . $row, $statusLabel);

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }

            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);

            $row++;
        }

        // Riga totali
        $sheet->setCellValue('A' . $row, 'Totale scadenze: ' . $payments->count());
        $sheet->setCellValue('E' . $row, $totalAmount);
        $sheet->setCellValue('F' . $row, $totalResidual);
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ]);

        $sheet->getStyle('E2:F' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(25);
        $sheet->setAutoFilter('A1:H' . ($row - 1));
        $sheet->freezePane('A2');

        $filename = 'scadenze_vendite_' . Carbon::now()->format('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}