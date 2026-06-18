<?php
// app/Http/Controllers/Admin/StaffCalendarController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expiration;
use App\Models\Staff;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffCalendarController extends Controller
{
    public function exportPdf(Request $request)
    {
        $query = Expiration::query()
            ->with(['staff', 'setting', 'ownershipLegacy'])
            ->where('table_references', 'staff')
            ->whereNotNull('data_fine');

        if ($request->date_from) {
            $query->whereDate('data_fine', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('data_fine', '<=', $request->date_to);
        }
        if ($request->staff_id) {
            $query->where('id_references', $request->staff_id);
        }
        if ($request->type_ids) {
            $typeIds = explode(',', $request->type_ids);
            $query->whereIn('id_settings', $typeIds);
        }

        $expirations = $query->orderBy('data_fine', 'asc')->get();

        $staffName = '';
        if ($request->staff_id) {
            $staff = Staff::find($request->staff_id);
            $staffName = $staff ? "_{$staff->CognomePers}_{$staff->NomePers}" : '';
        }

        $html = $this->generatePdfHtml($expirations, $request);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('scadenze_personale' . $staffName . '_' . date('Y-m-d') . '.pdf');
    }

    private function generatePdfHtml($expirations, $request)
    {
        $typeLabels = Setting::whereIn('id', $expirations->pluck('id_settings')->unique())
            ->pluck('valore', 'id');

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Scadenze Personale</title>
            <style>
                body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; margin: 20px; }
                .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #84cc16; padding-bottom: 10px; }
                .header h1 { margin: 0; font-size: 18px; color: #333; }
                .filters { background: #f7fafc; padding: 10px; margin-bottom: 20px; border-radius: 5px; font-size: 9px; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #f1f5f9; padding: 8px; text-align: center; border: 1px solid #cbd5e1; font-size: 9px; }
                td { padding: 6px; border: 1px solid #e2e8f0; font-size: 9px; }
                .text-center { text-align: center; }
                .footer { margin-top: 20px; text-align: right; font-size: 9px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
                .badge-expired { color: #dc2626; font-weight: bold; }
                .badge-warning { color: #d97706; font-weight: bold; }
                .badge-valid { color: #059669; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Scadenze Personale</h1>
                <p>Data esportazione: ' . now()->format('d/m/Y H:i:s') . '</p>
            </div>
            <div class="filters">
                <strong>Filtri:</strong> ';

        if ($request->date_from || $request->date_to) {
            $html .= 'Periodo: ' . ($request->date_from ?: 'inizio') . ' → ' . ($request->date_to ?: 'oggi') . ' &nbsp;|&nbsp; ';
        }
        if ($request->staff_id) {
            $staff = Staff::find($request->staff_id);
            $html .= 'Personale: ' . ($staff ? $staff->NomePers . ' ' . $staff->CognomePers : 'Selezionato') . ' &nbsp;|&nbsp; ';
        }
        if (!$request->date_from && !$request->date_to && !$request->staff_id && !$request->type_ids) {
            $html .= 'Tutte le scadenze';
        }

        $html .= '
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Data Scadenza</th>
                        <th>Personale</th>
                        <th>Società</th>
                        <th>Tipologia</th>
                        <th>Titolo</th>
                        <th>Note</th>
                        <th>Stato</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($expirations as $exp) {
            $carbonDate = Carbon::parse($exp->data_fine);
            $now = Carbon::now();

            if ($carbonDate->isPast()) {
                $status = '<span class="badge-expired">SCADUTA</span>';
            } elseif ($now->diffInDays($carbonDate) <= 30) {
                $status = '<span class="badge-warning">In scadenza</span>';
            } else {
                $status = '<span class="badge-valid">Valida</span>';
            }

            $typeLabel   = $typeLabels[$exp->id_settings] ?? ($exp->setting->valore ?? 'Scadenza');
            $societaNome = $exp->ownershipLegacy ? e($exp->ownershipLegacy->RagAbbrev) : '-';

            $html .= '<tr>
                <td>' . $carbonDate->format('d/m/Y') . '</td>
                <td>' . ($exp->staff ? e($exp->staff->NomePers . ' ' . $exp->staff->CognomePers) : '-') . '</td>
                <td>' . $societaNome . '</td>
                <td>' . e($typeLabel) . '</td>
                <td>' . e($exp->titolo ?? '-') . '</td>
                <td>' . e($exp->note ?? '-') . '</td>
                <td class="text-center">' . $status . '</td>
            </tr>';
        }

        $html .= '
                </tbody>
            </table>
            <div class="footer">
                <p>Documento generato automaticamente dal gestionale</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    public function exportExcel(Request $request)
    {
        $query = Expiration::query()
            ->with(['staff', 'setting', 'ownershipLegacy'])
            ->where('table_references', 'staff')
            ->whereNotNull('data_fine');

        if ($request->date_from) {
            $query->whereDate('data_fine', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('data_fine', '<=', $request->date_to);
        }
        if ($request->staff_id) {
            $query->where('id_references', $request->staff_id);
        }
        if ($request->type_ids) {
            $typeIds = explode(',', $request->type_ids);
            $query->whereIn('id_settings', $typeIds);
        }

        $expirations = $query->orderBy('data_fine', 'asc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Scadenze Personale');

        $headers = ['Data Scadenza', 'Personale', 'Società', 'Tipologia', 'Titolo', 'Note', 'Stato'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . '1', $header);
        }

        $headerStyle = [
            'font'      => ['bold' => true, 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E5E7EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

        $row = 2;
        foreach ($expirations as $exp) {
            $carbonDate = Carbon::parse($exp->data_fine);
            $now        = Carbon::now();

            if ($carbonDate->isPast()) {
                $status = 'SCADUTA';
            } elseif ($now->diffInDays($carbonDate) <= 30) {
                $status = 'In scadenza';
            } else {
                $status = 'Valida';
            }

            $sheet->setCellValue('A' . $row, $carbonDate->format('d/m/Y'));
            $sheet->setCellValue('B' . $row, $exp->staff
                ? $exp->staff->NomePers . ' ' . $exp->staff->CognomePers
                : '-');
            $sheet->setCellValue('C' . $row, $exp->ownershipLegacy
                ? $exp->ownershipLegacy->RagAbbrev
                : '-');
            $sheet->setCellValue('D' . $row, $exp->setting->valore ?? 'Scadenza');
            $sheet->setCellValue('E' . $row, $exp->titolo ?? '-');
            $sheet->setCellValue('F' . $row, $exp->note ?? '-');
            $sheet->setCellValue('G' . $row, $status);

            $row++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->setAutoFilter('A1:G' . ($row - 1));
        $sheet->freezePane('A2');

        $writer   = new Xlsx($spreadsheet);
        $filename = 'scadenze_personale_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}