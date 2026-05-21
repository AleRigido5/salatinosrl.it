<?php
// app/Http/Controllers/Admin/AccountingEntryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountingEntry;
use Illuminate\Http\Request;

class AccountingEntryController extends Controller
{
    public function index()
    {
        return view('admin.accounting-entries.index');
    }
}