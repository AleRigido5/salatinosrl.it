<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\SettingCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('access_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $categoryId = $request->get('category');
        
        if ($categoryId) {
            $category = SettingCategory::find($categoryId);
            $settings = Setting::where('category_id', $categoryId)->orderBy('ordinamento')->get();
        } else {
            $category = null;
            $settings = Setting::orderBy('ordinamento')->get();
        }
        
        $categories = SettingCategory::ordered()->active()->get();
        
        return view('admin.settings.index', compact('settings', 'categories', 'category'));
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
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
            }
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        
        $request->validate([
            'valore' => 'nullable|string|max:255',
            'descrizione' => 'nullable|string',
            'ordinamento' => 'nullable|integer',
            'valid' => 'nullable|boolean'
        ]);
        
        $setting->update([
            'valore' => $request->valore,
            'descrizione' => $request->descrizione,
            'ordinamento' => $request->ordinamento ?: 0,
            'valid' => $request->boolean('valid')
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Impostazione aggiornata con successo!',
                'data' => $setting
            ]);
        }
        
        // Redirect per richieste normali
        if ($setting->category) {
            return redirect()->route('admin.settings.categories.show', $setting->category->slug)
                ->with('success', 'Impostazione aggiornata con successo!');
        }
        
        return redirect()->route('admin.settings.categories.index')
            ->with('success', 'Impostazione aggiornata con successo!');
    }
}