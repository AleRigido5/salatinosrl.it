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
    
    public function create(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $categories = SettingCategory::ordered()->get();
        $selectedCategory = $request->get('category');
        
        return view('admin.settings.create', compact('categories', 'selectedCategory'));
    }
    
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'valore' => 'required|string|max:255',
            'category_id' => 'nullable|exists:settings_categories,id',
            'tabella_riferimento' => 'nullable|string|max:50',
            'descrizione' => 'nullable|string',
            'ordinamento' => 'nullable|integer',
            'valid' => 'nullable|boolean'
        ]);
        
        $setting = Setting::create([
            'valore' => $request->valore,
            'category_id' => $request->category_id,
            'tabella_riferimento' => $request->tabella_riferimento,
            'descrizione' => $request->descrizione,
            'ordinamento' => $request->ordinamento ?: 0,
            'valid' => $request->boolean('valid', true)
        ]);
        
        if ($setting->category) {
            return redirect()->route('admin.settings.categories.show', $setting->category->slug)
                ->with('success', 'Impostazione creata con successo!');
        }
        
        return redirect()->route('admin.settings.index')
            ->with('success', 'Impostazione creata con successo!');
    }
    
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        $categories = SettingCategory::ordered()->get();
        
        return view('admin.settings.edit', compact('setting', 'categories'));
    }
    
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
            }
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        
        $request->validate([
            'valore' => 'nullable|string|max:255',
            'descrizione' => 'nullable|string|max:255',
            'ordinamento' => 'nullable|integer',
            'valid' => 'nullable|boolean'
        ]);
        
        $setting->update([
            'valore' => $request->valore,
            'descrizione' => $request->descrizione,
            'ordinamento' => (int)($request->ordinamento ?? 0),
            'valid' => (bool)($request->boolean('valid') || $request->has('valid'))
        ]);
        
        // Verifica se è una richiesta AJAX
        if ($request->ajax() || $request->wantsJson()) {
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
        
        return redirect()->route('admin.settings.index')
            ->with('success', 'Impostazione aggiornata con successo!');
    }
    
    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        $category = $setting->category;
        $setting->delete();
        
        if ($category) {
            return redirect()->route('admin.settings.categories.show', $category->slug)
                ->with('success', 'Impostazione eliminata con successo!');
        }
        
        return redirect()->route('admin.settings.index')
            ->with('success', 'Impostazione eliminata con successo!');
    }
    
    public function toggleStatus($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        $setting->update(['valid' => !$setting->valid]);
        
        $status = $setting->valid ? 'attivata' : 'disattivata';
        return back()->with('success', "Impostazione {$status} con successo!");
    }
}