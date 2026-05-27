<?php
// app/Http/Controllers/Admin/InvoiceSentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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
}