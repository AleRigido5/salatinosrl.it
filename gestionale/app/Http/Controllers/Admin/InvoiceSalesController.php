<?php
// app/Http/Controllers/Admin/InvoiceSalesController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class InvoiceSalesController extends Controller
{
    public function index()
    {
        // Metodo alternativo senza Gate
        if (!Auth::guard('admin')->user()->hasPermission('view_invoices_sales')) {
            abort(403, 'Non hai i permessi necessari per visualizzare le fatture di vendita.');
        }
        return view('admin.invoice-sales.index');
    }

    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('create_invoices_sales')) {
            abort(403, 'Non hai i permessi necessari per creare fatture di vendita.');
        }
        return view('admin.invoice-sales.create');
    }

    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_invoices_sales')) {
            abort(403, 'Non hai i permessi necessari per modificare fatture di vendita.');
        }
        return view('admin.invoice-sales.edit', ['id' => $id]);
    }

    public function show($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_invoices_sales')) {
            abort(403, 'Non hai i permessi necessari per visualizzare i dettagli della fattura.');
        }
        return view('admin.invoice-sales.show', ['id' => $id]);
    }
}