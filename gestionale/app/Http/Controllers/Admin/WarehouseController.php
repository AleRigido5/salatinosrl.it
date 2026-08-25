<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function productsIndex(): View
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_warehouse')) {
            abort(403, 'Non hai i permessi necessari per visualizzare il magazzino.');
        }

        return view('admin.warehouse.products.index');
    }

    public function movementsIndex(): View
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_warehouse')) {
            abort(403, 'Non hai i permessi necessari per visualizzare le movimentazioni.');
        }

        return view('admin.warehouse.movements.index');
    }

    public function ddtPurchasesIndex(): View
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_warehouse')) {
            abort(403, 'Non hai i permessi necessari per visualizzare i DDT di acquisto.');
        }

        return view('admin.warehouse.ddt-purchases.index');
    }

    public function ddtSalesIndex(): View
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_warehouse')) {
            abort(403, 'Non hai i permessi necessari per visualizzare i DDT di vendita.');
        }

        return view('admin.warehouse.ddt-sales.index');
    }
}