<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CostCenterDetailController extends Controller
{
    public function index($costCenterId)
    {
        return view('admin.cost_centers.detail', ['costCenterId' => $costCenterId]);
    }
}