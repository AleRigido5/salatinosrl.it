<?php
// app/Http/Controllers/Admin/VehicleCalendarController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expiration;
use App\Models\Vehicles;
use App\Models\Setting;
use App\Models\Ownership;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VehicleCalendarController extends Controller
{
    /**
     * Export vehicle expirations to PDF.
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        // Costruisci la query con i filtri
        $query = Expiration::query()
            ->with(['vehicles', 'setting'])
            ->where('table_references', 'vehicles')
            ->whereNotNull('data_fine');

        // Applica filtri dalla request
        if ($request->date_from) {
            $query->whereDate('data_fine', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('data_fine', '<=', $request->date_to);
        }
        if ($request->vehicle_id) {
            $query->where('id_references', $request->vehicle_id);
        }
        if ($request->status) {
            $now = Carbon::now();
            if ($request->status === 'expired') {
                $query->whereDate('data_fine', '<', $now);
            } elseif ($request->status === 'expiring') {
                $query->whereDate('data_fine', '>=', $now)
                    ->whereDate('data_fine', '<=', $now->addDays(30));
            } elseif ($request->status === 'valid') {
                $query->whereDate('data_fine', '>', $now->addDays(30));
            }
        }
        if ($request->type_id) {
            $query->where('id_settings', $request->type_id);
        }
        if ($request->ownership_id) {
            $query->whereHas('vehicles', function($q) use ($request) {
                $q->where('id_ownership', $request->ownership_id);
            });
        }

        $expirations = $query->orderBy('data_fine', 'asc')->get();

        // Genera HTML per il PDF
        $html = $this->generatePdfHtml($expirations, $request);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'scadenze_veicoli_' . date('Y-m-d_His') . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Export vehicle expirations to Excel.
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_vehicles')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        // Costruisci la query con i filtri
        $query = Expiration::query()
            ->with(['vehicles', 'setting'])
            ->where('table_references', 'vehicles')
            ->whereNotNull('data_fine');

        // Applica filtri dalla request
        if ($request->date_from) {
            $query->whereDate('data_fine', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('data_fine', '<=', $request->date_to);
        }
        if ($request->vehicle_id) {
            $query->where('id_references', $request->vehicle_id);
        }
        if ($request->status) {
            $now = Carbon::now();
            if ($request->status === 'expired') {
                $query->whereDate('data_fine', '<', $now);
            } elseif ($request->status === 'expiring') {
                $query->whereDate('data_fine', '>=', $now)
                    ->whereDate('data_fine', '<=', $now->addDays(30));
            } elseif ($request->status === 'valid') {
                $query->whereDate('data_fine', '>', $now->addDays(30));
            }
        }
        if ($request->type_id) {
            $query->where('id_settings', $request->type_id);
        }
        if ($request->ownership_id) {
            $query->whereHas('vehicles', function($q) use ($request) {
                $q->where('id_ownership', $request->ownership_id);
            });
        }

        $expirations = $query->orderBy('data_fine', 'asc')->get();

        // Crea il foglio Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Scadenze Veicoli');

        // Intestazioni
        $headers = ['ID', 'Data Scadenza', 'Veicolo', 'Targa', 'Tipologia', 'Titolo', 'Note', 'Stato', 'Proprietà'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }

        // Stile intestazioni
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Popola i dati
        $row = 2;
        $typeLabels = Setting::whereIn('id', $expirations->pluck('id_settings')->unique())->pluck('valore', 'id');
        $ownershipLabels = Ownership::whereIn('id_proprieta', Vehicles::whereIn('id', $expirations->pluck('id_references')->unique())->pluck('id_ownership')->unique())->pluck('RagAbbrev', 'id_proprieta');

        foreach ($expirations as $exp) {
            $vehicle = $exp->vehicles->first();
            $carbonDate = Carbon::parse($exp->data_fine);
            $now = Carbon::now();

            // Determina lo stato
            if ($carbonDate->isPast()) {
                $status = 'SCADUTA';
                $statusColor = 'FF0000';
            } elseif ($now->diffInDays($carbonDate) <= 30) {
                $status = 'In scadenza';
                $statusColor = 'FFA500';
            } else {
                $status = 'Valida';
                $statusColor = '008000';
            }

            $sheet->setCellValue('A' . $row, $exp->id);
            $sheet->setCellValue('B' . $row, $carbonDate->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $vehicle ? $vehicle->marca . ' ' . $vehicle->modello : '-');
            $sheet->setCellValue('D' . $row, $vehicle ? $vehicle->targa : '-');
            $sheet->setCellValue('E' . $row, $typeLabels[$exp->id_settings] ?? 'Scadenza');
            $sheet->setCellValue('F' . $row, $exp->titolo ?? '-');
            $sheet->setCellValue('G' . $row, $exp->note ?? '-');
            $sheet->setCellValue('H' . $row, $status);
            $sheet->setCellValue('I' . $row, $vehicle && $vehicle->id_ownership ? ($ownershipLabels[$vehicle->id_ownership] ?? '-') : '-');

            // Colora la cella dello stato
            $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB($statusColor);
            $sheet->getStyle('H' . $row)->getFont()->setBold(true);

            $row++;
        }

        // Stile per i dati
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheet->getStyle('A2:I' . ($row - 1))->applyFromArray($dataStyle);

        // Auto-size delle colonne
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Blocca la prima riga
        $sheet->freezePane('A2');

        // Aggiungi filtro
        $sheet->setAutoFilter('A1:I' . ($row - 1));

        // Crea il file e scarica
        $writer = new Xlsx($spreadsheet);
        $filename = 'scadenze_veicoli_' . date('Y-m-d_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: 0');
        header('Pragma: public');

        $writer->save('php://output');
        exit;
    }

    /**
     * Generate HTML for PDF export.
     */
    private function generatePdfHtml($expirations, $request)
    {
        $typeLabels = Setting::whereIn('id', $expirations->pluck('id_settings')->unique())->pluck('valore', 'id');
        $now = Carbon::now();

        // Ottieni nomi dei filtri per il report
        $vehicleName = $request->vehicle_id ? Vehicles::find($request->vehicle_id)?->targa : null;
        $typeName = $request->type_id ? Setting::find($request->type_id)?->valore : null;
        $ownershipName = $request->ownership_id ? Ownership::find($request->ownership_id)?->RagAbbrev : null;

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Scadenze Veicoli</title>
            <style>
                body {
                    font-family: "DejaVu Sans", sans-serif;
                    font-size: 10px;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #4CAF50;
                    padding-bottom: 10px;
                }
                .header h1 {
                    margin: 0;
                    font-size: 18px;
                    color: #333;
                }
                .header p {
                    margin: 5px 0 0;
                    color: #666;
                    font-size: 10px;
                }
                .filters {
                    background: #f7fafc;
                    padding: 10px;
                    margin-bottom: 20px;
                    border-radius: 5px;
                    font-size: 9px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 15px;
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
                .badge-expired {
                    color: #dc2626;
                    font-weight: bold;
                }
                .badge-warning {
                    color: #d97706;
                    font-weight: bold;
                }
                .badge-valid {
                    color: #059669;
                }
                .text-left {
                    text-align: left;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Scadenze Veicoli</h1>
                <p>Data esportazione: ' . Carbon::now()->format('d/m/Y H:i:s') . '</p>
            </div>';

        // Filtri applicati
        $html .= '<div class="filters">
            <strong>Filtri applicati:</strong><br>';
        
        if ($request->date_from || $request->date_to) {
            $html .= 'Periodo: ' . ($request->date_from ?: 'da inizio') . ' → ' . ($request->date_to ?: 'ad oggi') . '<br>';
        }
        if ($vehicleName) {
            $html .= 'Veicolo: ' . e($vehicleName) . '<br>';
        }
        if ($typeName) {
            $html .= 'Tipologia: ' . e($typeName) . '<br>';
        }
        if ($ownershipName) {
            $html .= 'Proprietà: ' . e($ownershipName) . '<br>';
        }
        if ($request->status) {
            $statusLabels = [
                'expired' => 'Scadute',
                'expiring' => 'In scadenza (30gg)',
                'valid' => 'Valide'
            ];
            $html .= 'Stato: ' . ($statusLabels[$request->status] ?? $request->status) . '<br>';
        }
        if (!$request->date_from && !$request->date_to && !$vehicleName && !$typeName && !$ownershipName && !$request->status) {
            $html .= 'Nessun filtro applicato';
        }

        $html .= '</div>';

        // Tabella
        $html .= '
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data Scadenza</th>
                        <th>Veicolo</th>
                        <th>Targa</th>
                        <th>Tipologia</th>
                        <th>Titolo</th>
                        <th>Note</th>
                        <th>Stato</th>
                        <th>Proprietà</th>
                    </tr>
                </thead>
                <tbody>';

        if ($expirations->isEmpty()) {
            $html .= '<tr><td colspan="9" class="text-center">Nessuna scadenza trovata</td></tr>';
        } else {
            foreach ($expirations as $exp) {
                $vehicle = $exp->vehicles->first();
                $carbonDate = Carbon::parse($exp->data_fine);
                
                if ($carbonDate->isPast()) {
                    $status = '<span class="badge-expired">SCADUTA</span>';
                } elseif ($now->diffInDays($carbonDate) <= 30) {
                    $status = '<span class="badge-warning">In scadenza</span>';
                } else {
                    $status = '<span class="badge-valid">Valida</span>';
                }

                $ownershipName = '';
                if ($vehicle && $vehicle->id_ownership) {
                    $ownership = Ownership::find($vehicle->id_ownership);
                    $ownershipName = $ownership ? $ownership->RagAbbrev : '-';
                }

                $html .= '<tr>
                    <td class="text-center">' . $exp->id . '</td>
                    <td class="text-center">' . $carbonDate->format('d/m/Y') . '</td>
                    <td class="text-left">' . ($vehicle ? e($vehicle->marca . ' ' . $vehicle->modello) : '-') . '</td>
                    <td class="text-center">' . ($vehicle ? e($vehicle->targa) : '-') . '</td>
                    <td class="text-center">' . e($typeLabels[$exp->id_settings] ?? 'Scadenza') . '</td>
                    <td class="text-left">' . e($exp->titolo ?? '-') . '</td>
                    <td class="text-left">' . e($exp->note ?? '-') . '</td>
                    <td class="text-center">' . $status . '</td>
                    <td class="text-center">' . e($ownershipName) . '</td>
                </tr>';
            }
        }

        $html .= '
                </tbody>
            </table>
            
            <div class="footer">
                <p>Documento generato automaticamente dal gestionale</p>
                <p>Totale scadenze: ' . $expirations->count() . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}