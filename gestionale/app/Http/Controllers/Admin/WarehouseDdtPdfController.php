<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarehouseDdt;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class WarehouseDdtPdfController extends Controller
{
    public function show(int $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_warehouse')) {
            abort(403, 'Non hai i permessi necessari.');
        }

        $ddt = WarehouseDdt::with(['entity.addresses', 'ownership', 'rows.product'])->findOrFail($id);

        $pdf = Pdf::loadView('admin.warehouse.ddt-pdf', [
            'ddt' => $ddt,
        ])->setPaper('a4', 'portrait');

        $filename = 'DDT_' . str_replace(['/', '\\', ' '], '-', $ddt->ddt_number) . '.pdf';

        return $pdf->stream($filename);
    }
}