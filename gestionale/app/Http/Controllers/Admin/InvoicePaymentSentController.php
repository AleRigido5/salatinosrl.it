<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class InvoicePaymentSentController extends Controller
{
    public function index()
    {
        return view('admin.invoice-payments-sent.index');
    }
}