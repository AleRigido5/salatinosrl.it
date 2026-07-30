<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entity;
use App\Models\InvoiceSent;
use App\Models\InvoiceReceived;
use App\Models\AccountingEntry;
use App\Models\Ownership;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AccountStatementController extends Controller
{
    /**
     * Display the account statement for a specific entity
     */
    public function index(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $entity = Entity::findOrFail($id);

        $dateFrom      = $request->get('date_from', date('Y-m-d', strtotime('-12 months')));
        $dateTo        = $request->get('date_to', date('Y-m-d'));
        $statementType = $request->get('type', 'all');

        return view('admin.entities.account-statement', compact(
            'entity', 'dateFrom', 'dateTo', 'statementType', 'id'
        ));
    }

    // ==================== HELPER PRIVATO ====================

    /**
     * Costruisce l'elenco completo dei movimenti (fatture + pagamenti da accounting_entries)
     * per una entità, applicando gli stessi filtri usati dalla vista Livewire.
     */
    private function buildTransactions(Entity $entity, Request $request): array
    {
        $transactions = [];
        $dateFrom    = $request->date_from;
        $dateTo      = $request->date_to;
        $ownershipId = $request->ownership_id;
        $typeInvoice = $request->type_invoice;
        $status      = $request->status;
        $search      = $request->search;

        // ==================== CLIENTE: fatture emesse a lui ====================
        if (in_array($entity->entity_type, ['cliente', 'entrambi'])) {
            $sent = InvoiceSent::where('id_entities', $entity->id_cliente)
                ->when($dateFrom && $dateTo, fn($q) => $q->whereBetween('data_invoice', [$dateFrom, $dateTo]))
                ->when($ownershipId, fn($q) => $q->where('id_ownership', $ownershipId))
                ->when($typeInvoice, fn($q) => $q->where('type_invoice', $typeInvoice))
                ->when($status,      fn($q) => $q->where('status', $status))
                ->when($search,      fn($q) => $q->where('n_invoice', 'like', '%' . $search . '%'))
                ->with('ownership')
                ->get();

            foreach ($sent as $inv) {
                $isNC = in_array($inv->type_invoice, ['TD04', 'TD08']);
                $transactions[] = [
                    'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                    'descrizione' => $isNC ? 'Nota di Credito emessa' : 'Fattura di Vendita',
                    'data'        => $inv->data_invoice,
                    'n_fattura'   => $inv->n_invoice,
                    'dare'        => $isNC ? 0 : $inv->importo_totale,
                    'avere'       => $isNC ? $inv->importo_totale : 0,
                    'saldo'       => 0,
                ];
            }
        }

        // ==================== FORNITORE: fatture ricevute da lui ====================
        if (in_array($entity->entity_type, ['fornitore', 'entrambi'])) {
            $received = InvoiceReceived::where('id_entities', $entity->id_cliente)
                ->when($dateFrom && $dateTo, fn($q) => $q->whereBetween('data_invoice', [$dateFrom, $dateTo]))
                ->when($ownershipId, fn($q) => $q->where('id_ownership', $ownershipId))
                ->when($typeInvoice, fn($q) => $q->where('type_invoice', $typeInvoice))
                ->when($status,      fn($q) => $q->where('status', $status))
                ->when($search,      fn($q) => $q->where('n_invoice', 'like', '%' . $search . '%'))
                ->with('ownership')
                ->get();

            foreach ($received as $inv) {
                $isNC = in_array($inv->type_invoice, ['TD04', 'TD08']);
                $transactions[] = [
                    'proprieta'   => $inv->ownership->RagAbbrev ?? $inv->ownership->Rag_Soc_intest ?? '-',
                    'descrizione' => $isNC ? 'Nota di Credito ricevuta' : 'Fattura di Acquisto',
                    'data'        => $inv->data_invoice,
                    'n_fattura'   => $inv->n_invoice,
                    'dare'        => $isNC ? $inv->importo_totale : 0,
                    'avere'       => $isNC ? 0 : $inv->importo_totale,
                    'saldo'       => 0,
                ];
            }
        }

        // ==================== MOVIMENTI DI CASSA/BANCA (accounting_entries) ====================
        $entryRows = $this->buildAccountingEntryRows($entity, $dateFrom, $dateTo, $ownershipId, $typeInvoice, $search);
        $transactions = array_merge($transactions, $entryRows);

        usort($transactions, fn($a, $b) => strcmp(
            is_string($a['data']) ? $a['data'] : $a['data']->format('Y-m-d'),
            is_string($b['data']) ? $b['data'] : $b['data']->format('Y-m-d')
        ));

        $saldo = 0;
        foreach ($transactions as &$row) {
            $saldo += ($row['dare'] - $row['avere']);
            $row['saldo'] = $saldo;
        }

        return $transactions;
    }

    /**
     * Costruisce le righe dei movimenti di cassa/banca a partire da accounting_entries,
     * collegate all'entità tramite:
     * accounting_entries -> installment_transactions -> invoice_payments -> payable (InvoiceSent | InvoiceReceived)
     */
    private function buildAccountingEntryRows(
        Entity $entity,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $ownershipId,
        ?string $typeInvoice,
        ?string $search
    ): array {
        $entityId = $entity->id_cliente;

        $entries = AccountingEntry::whereHas('installmentTransactions.invoicePayment', function ($q) use ($entityId, $ownershipId, $typeInvoice, $search) {
                $q->where(function ($sub) use ($entityId, $ownershipId, $typeInvoice, $search) {
                    $sub->where('payable_type', InvoiceReceived::class)
                        ->whereHas('payable', function ($q2) use ($entityId, $ownershipId, $typeInvoice, $search) {
                            $q2->where('id_entities', $entityId);
                            if ($ownershipId) {
                                $q2->where('id_ownership', $ownershipId);
                            }
                            if ($typeInvoice) {
                                $q2->where('type_invoice', $typeInvoice);
                            }
                            if ($search) {
                                $q2->where('n_invoice', 'like', '%' . $search . '%');
                            }
                        });
                })->orWhere(function ($sub) use ($entityId, $ownershipId, $typeInvoice, $search) {
                    $sub->where('payable_type', InvoiceSent::class)
                        ->whereHas('payable', function ($q2) use ($entityId, $ownershipId, $typeInvoice, $search) {
                            $q2->where('id_entities', $entityId);
                            if ($ownershipId) {
                                $q2->where('id_ownership', $ownershipId);
                            }
                            if ($typeInvoice) {
                                $q2->where('type_invoice', $typeInvoice);
                            }
                            if ($search) {
                                $q2->where('n_invoice', 'like', '%' . $search . '%');
                            }
                        });
                });
            })
            ->when($dateFrom && $dateTo, fn($q) => $q->whereBetween('entry_date', [$dateFrom, $dateTo]))
            ->with([
                'paymentMethod',
                'bankAccount',
                'installmentTransactions.invoicePayment.payable.ownership',
            ])
            ->get();

        $rows = [];
        foreach ($entries as $entry) {
            $proprieta = '-';
            $firstInstallment = $entry->installmentTransactions->first();
            if ($firstInstallment && $firstInstallment->invoicePayment && $firstInstallment->invoicePayment->payable) {
                $payable = $firstInstallment->invoicePayment->payable;
                if ($payable->ownership) {
                    $proprieta = $payable->ownership->RagAbbrev ?? $payable->ownership->Rag_Soc_intest ?? '-';
                }
            }

            $methodLabel = $entry->paymentMethod->name ?? 'Non specificato';
            $bankLabel = $entry->bankAccount
                ? trim($entry->bankAccount->name . ' ' . $entry->bankAccount->n_conto)
                : null;

            $isUscita = $entry->type === 'uscita';
            $label = $isUscita ? 'Pagamento effettuato' : 'Incasso ricevuto';

            $descrizione = $label . ': ' . $methodLabel . ($bankLabel ? ' (' . $bankLabel . ')' : '');

            $rows[] = [
                'proprieta'   => $proprieta,
                'descrizione' => $descrizione,
                'data'        => $entry->entry_date,
                'n_fattura'   => '-',
                'dare'        => $isUscita ? floatval($entry->amount) : 0,
                'avere'       => $isUscita ? 0 : floatval($entry->amount),
                'saldo'       => 0,
            ];
        }

        return $rows;
    }

    // ==================== EXPORT PDF ====================

    public function exportPdf(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403);
        }

        $entity = Entity::findOrFail($id);
        $transactions = $this->buildTransactions($entity, $request);

        $totalDare    = array_sum(array_column($transactions, 'dare'));
        $totalAvere   = array_sum(array_column($transactions, 'avere'));
        $finalBalance = $totalDare - $totalAvere;

        $dateFrom      = $request->date_from ?? '-';
        $dateTo        = $request->date_to   ?? '-';
        $ownershipName = $request->ownership_id
            ? (Ownership::find($request->ownership_id)?->RagAbbrev ?? '-')
            : null;

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; margin: 15px; }
            .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #84cc16; padding-bottom: 8px; }
            .header h1 { margin: 0; font-size: 15px; color: #333; }
            .header h2 { margin: 4px 0 0; font-size: 11px; color: #555; font-weight: normal; }
            .header p  { margin: 3px 0 0; color: #777; font-size: 8px; }
            .filters { background: #f3f4f6; padding: 5px 8px; margin-bottom: 10px; font-size: 8px; border-radius: 3px; }
            table { width: 100%; border-collapse: collapse; }
            th { background-color: #84cc16; color: white; padding: 5px 6px; font-size: 8px; text-align: left; }
            td { border: 1px solid #e5e7eb; padding: 4px 6px; font-size: 8px; }
            tr:nth-child(even) { background-color: #f9fafb; }
            .text-right { text-align: right; }
            .dare       { color: #dc2626; font-weight: bold; }
            .avere      { color: #16a34a; font-weight: bold; }
            .saldo-pos  { color: #16a34a; font-weight: bold; }
            .saldo-neg  { color: #dc2626; font-weight: bold; }
            .tfoot-row td { background: #f3f4f6; font-weight: bold; border-top: 2px solid #84cc16; }
            .footer { margin-top: 12px; text-align: right; font-size: 7px; color: #999; border-top: 1px solid #ddd; padding-top: 6px; }
        </style>
        </head><body>

        <div class="header">
            <h1>Estratto Conto</h1>
            <h2>' . e($entity->full_name) . '</h2>
            <p>Periodo: ' . $dateFrom . ' → ' . $dateTo .
            ($ownershipName ? ' &nbsp;|&nbsp; Proprietà: ' . e($ownershipName) : '') . '</p>
        </div>';

        $activeFilters = [];
        if ($request->type_invoice) $activeFilters[] = 'Tipo: ' . $request->type_invoice;
        if ($request->status)       $activeFilters[] = 'Stato: ' . $request->status;
        if ($request->search)       $activeFilters[] = 'N. Fattura: "' . $request->search . '"';
        if (!empty($activeFilters)) {
            $html .= '<div class="filters">Filtri: ' . implode(' &nbsp;|&nbsp; ', $activeFilters) . '</div>';
        }

        $html .= '<table>
            <thead>
                <tr>
                    <th>Proprietà</th>
                    <th>Descrizione</th>
                    <th>Data</th>
                    <th>N. Fattura</th>
                    <th class="text-right">DARE (€)</th>
                    <th class="text-right">AVERE (€)</th>
                    <th class="text-right">SALDO (€)</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($transactions as $t) {
            $data       = is_string($t['data']) ? Carbon::parse($t['data'])->format('d/m/Y') : $t['data']->format('d/m/Y');
            $saldoClass = $t['saldo'] > 0 ? 'saldo-pos' : ($t['saldo'] < 0 ? 'saldo-neg' : '');

            $html .= '<tr>
                <td>' . e($t['proprieta']) . '</td>
                <td>' . e($t['descrizione']) . '</td>
                <td>' . $data . '</td>
                <td>' . e($t['n_fattura']) . '</td>
                <td class="text-right">' . ($t['dare']  > 0 ? '<span class="dare">'  . number_format($t['dare'],  2, ',', '.') . '</span>' : '<span style="color:#ccc">-</span>') . '</td>
                <td class="text-right">' . ($t['avere'] > 0 ? '<span class="avere">' . number_format($t['avere'], 2, ',', '.') . '</span>' : '<span style="color:#ccc">-</span>') . '</td>
                <td class="text-right"><span class="' . $saldoClass . '">' . number_format($t['saldo'], 2, ',', '.') . '</span></td>
            </tr>';
        }

        $balanceColor = $finalBalance > 0 ? '#16a34a' : ($finalBalance < 0 ? '#dc2626' : '#374151');

        $html .= '</tbody>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="4" class="text-right">TOTALI:</td>
                    <td class="text-right dare">'  . number_format($totalDare,    2, ',', '.') . '</td>
                    <td class="text-right avere">' . number_format($totalAvere,   2, ',', '.') . '</td>
                    <td class="text-right" style="color:' . $balanceColor . '; font-weight:bold;">' . number_format($finalBalance, 2, ',', '.') . '</td>
                </tr>
            </tfoot>
        </table>
        <div class="footer">
            Totale movimenti: ' . count($transactions) . ' — Generato il ' . Carbon::now()->format('d/m/Y H:i') . '
        </div>
        </body></html>';

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'landscape');
        return $pdf->download('estratto_conto_' . str_replace(' ', '_', $entity->full_name) . '_' . date('Y-m-d') . '.pdf');
    }

    // ==================== EXPORT EXCEL ====================

    public function exportExcel(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_entities')) {
            abort(403);
        }

        $entity = Entity::findOrFail($id);
        $transactions = $this->buildTransactions($entity, $request);

        $totalDare    = array_sum(array_column($transactions, 'dare'));
        $totalAvere   = array_sum(array_column($transactions, 'avere'));
        $finalBalance = $totalDare - $totalAvere;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estratto Conto');

        // Titolo
        $sheet->setCellValue('A1', 'Estratto Conto — ' . $entity->full_name);
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => '374151']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(22);

        // Periodo
        $sheet->setCellValue('A2', 'Periodo: ' . ($request->date_from ?? '-') . ' → ' . ($request->date_to ?? '-'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['size' => 9, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Intestazioni colonne (riga 4)
        $headers = ['Proprietà', 'Descrizione', 'Data', 'N. Fattura', 'DARE (€)', 'AVERE (€)', 'SALDO (€)'];
        foreach ($headers as $i => $header) {
            $sheet->setCellValue(chr(65 + $i) . '4', $header);
        }
        $sheet->getStyle('A4:G4')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '84cc16']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']]],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(18);

        // Dati
        $row = 5;
        foreach ($transactions as $t) {
            $data = is_string($t['data']) ? Carbon::parse($t['data'])->format('d/m/Y') : $t['data']->format('d/m/Y');

            $sheet->setCellValue('A' . $row, $t['proprieta']);
            $sheet->setCellValue('B' . $row, $t['descrizione']);
            $sheet->setCellValue('C' . $row, $data);
            $sheet->setCellValue('D' . $row, $t['n_fattura']);
            $sheet->setCellValue('E' . $row, $t['dare']  > 0 ? $t['dare']  : '');
            $sheet->setCellValue('F' . $row, $t['avere'] > 0 ? $t['avere'] : '');
            $sheet->setCellValue('G' . $row, $t['saldo']);

            if ($t['dare']  > 0) { $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('DC2626'); $sheet->getStyle('E' . $row)->getFont()->setBold(true); }
            if ($t['avere'] > 0) { $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('16A34A'); $sheet->getStyle('F' . $row)->getFont()->setBold(true); }

            $saldoColor = $t['saldo'] > 0 ? '16A34A' : ($t['saldo'] < 0 ? 'DC2626' : '374151');
            $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB($saldoColor);
            $sheet->getStyle('G' . $row)->getFont()->setBold(true);

            $sheet->getStyle('E' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

            if ($row % 2 === 0) {
                $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }
            $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);

            $row++;
        }

        // Riga totali
        $sheet->setCellValue('D' . $row, 'TOTALI:');
        $sheet->setCellValue('E' . $row, $totalDare);
        $sheet->setCellValue('F' . $row, $totalAvere);
        $sheet->setCellValue('G' . $row, $finalBalance);
        $sheet->getStyle('D' . $row . ':G' . $row)->applyFromArray([
            'font'    => ['bold' => true, 'size' => 10],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FDF4']],
            'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '84cc16']]],
        ]);
        $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB('DC2626');
        $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('16A34A');
        $balanceColor = $finalBalance > 0 ? '16A34A' : ($finalBalance < 0 ? 'DC2626' : '374151');
        $sheet->getStyle('G' . $row)->getFont()->getColor()->setRGB($balanceColor);
        $sheet->getStyle('E' . $row . ':G' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->setAutoFilter('A4:G' . ($row - 1));
        $sheet->freezePane('A5');

        $filename = 'estratto_conto_' . str_replace(' ', '_', $entity->full_name) . '_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}