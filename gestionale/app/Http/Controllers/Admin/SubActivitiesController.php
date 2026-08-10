<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityCoordinate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SubActivitiesController extends Controller
{
    /**
     * Mostra la pagina "Sotto-attività (Lat/Long) per Cliente".
     * Il contenuto/logica di ricerca è gestito interamente dal componente
     * Livewire App\Livewire\Admin\SubActivitiesReport incluso nella view.
     */
    public function index()
    {
        return view('admin.activities.sub-activities');
    }

    /**
     * Esporta in PDF i risultati correnti (stessi filtri passati via query string
     * dal componente Livewire tramite getExportPdfUrl()).
     */
    public function exportPdf(Request $request)
    {
        $data = $this->buildGroups($request);

        $html = $this->renderHtml($data);

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream('sotto-attivita.pdf');
    }

    /**
     * Esporta in Excel (xlsx) i risultati correnti — un foglio per ogni
     * combinazione Cliente/Centro di Costo/Servizio, usando PhpSpreadsheet
     * direttamente (nessuna classe Export separata).
     */
    public function exportExcel(Request $request)
    {
        $data = $this->buildGroups($request);

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($data['groups'] as $index => $group) {
            $sheet = $spreadsheet->createSheet();

            $title = preg_replace('/[\\\\\/\?\*\[\]:]/', '', $group['service_name'] ?: 'Gruppo');
            $title = mb_substr($title, 0, 25);
            $sheet->setTitle(($index + 1) . '. ' . $title);

            $sheet->setCellValue('A1', $group['entity_name'] . ' - ' . $group['service_name']);
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = ['Data', 'Coord. GPS', 'Coord. GPS', 'Note', 'ha'];
            $sheet->fromArray($headers, null, 'A2');
            $sheet->getStyle('A2:E2')->getFont()->setBold(true);
            $sheet->getStyle('A2:E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $rowNum = 3;
            $lastRowDate = null;

            foreach ($group['rows'] as $row) {
                $currentRowDate = optional($row->activity)->data_activities
                    ? $row->activity->data_activities->format('Y-m-d')
                    : null;

                if ($lastRowDate !== null && $lastRowDate !== $currentRowDate) {
                    $rowNum++;
                }

                $sheet->fromArray([
                    optional($row->activity)->data_activities?->format('d/m/Y') ?? '-',
                    $row->Lat_inizio ?: '-',
                    $row->Lat_fine ?: '-',
                    $row->NoteAtt ?: '-',
                    $row->ha ? floatval(str_replace(',', '.', $row->ha)) : null,
                ], null, 'A' . $rowNum);

                $rowNum++;
                $lastRowDate = $currentRowDate;
            }

            $year = Carbon::parse($data['dateFrom'])->format('Y');
            $sheet->fromArray([
                'TOTALE ' . mb_strtoupper($group['service_name']) . ' ' . $year,
                '', '', '',
                $group['total_ha'],
            ], null, 'A' . $rowNum);
            $sheet->getStyle('A' . $rowNum . ':E' . $rowNum)->getFont()->setBold(true);
            $sheet->getStyle('E' . $rowNum)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            foreach (['A' => 14, 'B' => 22, 'C' => 22, 'D' => 40, 'E' => 10] as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'sotto-attivita.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Ricostruisce esattamente la stessa query/raggruppamento usati dal
     * componente Livewire App\Livewire\Admin\SubActivitiesReport::applyFilters(),
     * leggendo i filtri dalla query string invece che dalle proprietà del componente.
     */
    private function buildGroups(Request $request): array
    {
        $entityFilter = $request->get('entityFilter');
        $costCenterFilter = $request->get('costCenterFilter');
        $serviceFilter = $request->get('serviceFilter');
        $positionFilter = $request->get('positionFilter');
        $search = $request->get('search');
        $dateFrom = $request->get('date_from') ?: $request->get('dateFrom');
        $dateTo = $request->get('date_to') ?: $request->get('dateTo');

        if (!$dateFrom || !$dateTo) {
            $now = Carbon::now();
            $dateFrom = $dateFrom ?: $now->copy()->startOfYear()->format('Y-m-d');
            $dateTo = $dateTo ?: $now->copy()->endOfYear()->format('Y-m-d');
        }

        if (empty($entityFilter)) {
            abort(422, 'Seleziona almeno un cliente/fornitore prima di esportare.');
        }

        $coordinates = ActivityCoordinate::query()
            ->whereHas('activity', function ($q) use ($entityFilter, $dateFrom, $dateTo, $costCenterFilter, $serviceFilter, $positionFilter) {
                $q->where('id_entities', $entityFilter);

                if ($dateFrom && $dateTo) {
                    $q->whereBetween('data_activities', [$dateFrom, $dateTo]);
                } elseif ($dateFrom) {
                    $q->whereDate('data_activities', '>=', $dateFrom);
                } elseif ($dateTo) {
                    $q->whereDate('data_activities', '<=', $dateTo);
                }

                if ($costCenterFilter) {
                    $q->where('id_cost_centers', $costCenterFilter);
                }

                if ($serviceFilter) {
                    $q->where('id_services', $serviceFilter);
                }

                if ($positionFilter === 'aperte') {
                    $q->where(function ($qq) {
                        $qq->whereNull('activities.invoice_references')
                           ->orWhere('activities.invoice_references', '');
                    })->whereExists(function ($qq) {
                        $qq->select(DB::raw(1))
                           ->from('cost_centers')
                           ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                           ->where('cost_centers.table_references', 'entities');
                    });
                } elseif ($positionFilter === 'interne') {
                    $q->whereNotExists(function ($qq) {
                        $qq->select(DB::raw(1))
                           ->from('cost_centers')
                           ->whereColumn('cost_centers.id', 'activities.id_cost_centers')
                           ->where('cost_centers.table_references', 'entities');
                    });
                }
            })
            ->when($search, function ($q) use ($search) {
                $term = '%' . $search . '%';
                $q->where(function ($qq) use ($term) {
                    $qq->where('NoteAtt', 'like', $term)
                       ->orWhere('Lat_inizio', 'like', $term)
                       ->orWhere('Lat_fine', 'like', $term);
                });
            })
            ->with(['activity.entity', 'activity.costCenter', 'activity.service'])
            ->get()
            ->filter(fn ($coord) => $coord->activity !== null)
            ->sortBy([
                fn ($a, $b) => $a->activity->data_activities <=> $b->activity->data_activities,
                fn ($a, $b) => $a->id_att_LatLong <=> $b->id_att_LatLong,
            ])
            ->values();

        $grouped = $coordinates->groupBy(function ($coord) {
            return $coord->activity->id_entities . '|' . $coord->activity->id_cost_centers . '|' . $coord->activity->id_services;
        });

        $groups = $grouped->map(function ($items) {
            $first = $items->first()->activity;

            $totalHa = $items->sum(function ($coord) {
                if (!$coord->ha) return 0;
                return floatval(str_replace(',', '.', $coord->ha));
            });

            return [
                'entity_name' => optional($first->entity)->ragione_sociale
                    ?: trim(optional($first->entity)->nome . ' ' . optional($first->entity)->cognome),
                'cost_center_name' => optional($first->costCenter)->Nome ?? '-',
                'service_name' => optional($first->service)->Titolo ?? '-',
                'rows' => $items,
                'total_ha' => $totalHa,
            ];
        })->values()->all();

        $grandTotalHa = collect($groups)->sum('total_ha');

        return [
            'groups' => $groups,
            'grandTotalHa' => $grandTotalHa,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * Genera l'HTML del PDF direttamente come stringa (nessuna view separata).
     */
    private function renderHtml(array $data): string
    {
        $groupsHtml = '';

        foreach ($data['groups'] as $group) {
            $rowsHtml = '';
            $lastRowDate = null;

            foreach ($group['rows'] as $row) {
                $currentRowDate = optional($row->activity)->data_activities
                    ? $row->activity->data_activities->format('Y-m-d')
                    : null;

                if ($lastRowDate !== null && $lastRowDate !== $currentRowDate) {
                    $rowsHtml .= '<tr class="spacer"><td colspan="5"></td></tr>';
                }

                $data_fmt = optional($row->activity)->data_activities?->format('d/m/Y') ?? '-';
                $latInizio = e($row->Lat_inizio ?: '-');
                $latFine = e($row->Lat_fine ?: '-');
                $note = e($row->NoteAtt ?: '-');
                $ha = $row->ha ? number_format(floatval(str_replace(',', '.', $row->ha)), 2, ',', '.') : '';

                $rowsHtml .= "<tr><td>{$data_fmt}</td><td>{$latInizio}</td><td>{$latFine}</td><td>{$note}</td><td class=\"text-right\">{$ha}</td></tr>";

                $lastRowDate = $currentRowDate;
            }

            $year = Carbon::parse($data['dateFrom'])->format('Y');
            $groupTitle = e($group['entity_name'] . ' - ' . $group['service_name']);
            $totalLabel = e('TOTALE ' . mb_strtoupper($group['service_name']) . ' ' . $year);
            $totalHa = number_format($group['total_ha'], 2, ',', '.');

            $groupsHtml .= <<<HTML
                <table>
                    <tr><td colspan="5" class="group-title">{$groupTitle}</td></tr>
                    <thead>
                        <tr>
                            <th style="width:12%;">Data</th>
                            <th style="width:24%;">Coord. GPS</th>
                            <th style="width:24%;">Coord. GPS</th>
                            <th>Note</th>
                            <th style="width:8%;" class="text-right">ha</th>
                        </tr>
                    </thead>
                    <tbody>{$rowsHtml}</tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4" class="text-right">{$totalLabel}</td>
                            <td class="text-right">{$totalHa}</td>
                        </tr>
                    </tfoot>
                </table>
            HTML;
        }

        $grandTotalHtml = '';
        if (count($data['groups']) > 1) {
            $grandTotalHa = number_format($data['grandTotalHa'], 2, ',', '.');
            $grandTotalHtml = "<div class=\"grand-total\">Totale complessivo: {$grandTotalHa} ha</div>";
        }

        $periodo = Carbon::parse($data['dateFrom'])->format('d/m/Y') . ' - ' . Carbon::parse($data['dateTo'])->format('d/m/Y');

        return <<<HTML
            <!DOCTYPE html>
            <html lang="it">
            <head>
                <meta charset="UTF-8">
                <title>Sotto-attività (Lat/Long)</title>
                <style>
                    body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #1f2937; }
                    h1 { font-size: 14px; text-align: center; margin: 0 0 16px 0; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
                    thead th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 5px 6px; text-align: left; font-size: 9px; text-transform: uppercase; }
                    tbody td { border: 1px solid #e5e7eb; padding: 4px 6px; font-size: 9.5px; }
                    .group-title { text-align: center; font-weight: bold; font-size: 12px; background: #f9fafb; border: 1px solid #d1d5db; padding: 6px; }
                    .spacer td { border: none; padding: 3px; }
                    .text-right { text-align: right; }
                    .total-row td { font-weight: bold; background: #ecfccb; border-top: 2px solid #84cc16; }
                    .grand-total { margin-top: 10px; text-align: right; font-size: 12px; font-weight: bold; background: #1f2937; color: #fff; padding: 8px 12px; }
                </style>
            </head>
            <body>
                <h1>Sotto-attività (Lat/Long) — {$periodo}</h1>
                {$groupsHtml}
                {$grandTotalHtml}
            </body>
            </html>
        HTML;
    }
}