<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('access_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $settings = Setting::orderBy('ordinamento')->get();
        
        return view('admin.settings.index', compact('settings'));
    }
    
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        
        return view('admin.settings.edit', compact('setting'));
    }
    
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        
        $request->validate([
            'valore' => 'nullable|string|max:255',
        ]);
        
        $setting->update([
            'valore' => $request->valore
        ]);
        
        return redirect()->route('admin.settings.index')
            ->with('success', 'Impostazione aggiornata con successo!');
    }
}