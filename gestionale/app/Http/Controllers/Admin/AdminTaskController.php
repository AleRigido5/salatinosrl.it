<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTaskTag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTaskController extends Controller
{
    public function index(): View
    {
        return view('admin.admin-tasks.index');
    }

    public function searchTags(Request $request)
    {
        $search = $request->get('q', '');
        if (strlen($search) < 1) {
            return response()->json([]);
        }

        $results = AdminTaskTag::where('name', 'like', '%' . $search . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name']);

        return response()->json($results);
    }
}