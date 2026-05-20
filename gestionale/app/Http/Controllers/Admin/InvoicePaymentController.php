<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InvoicePayment;
use App\Models\Ownership;
use App\Models\Entity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoicePaymentController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('view_purchases')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        return view('admin.invoice-payments.index');
    }
}