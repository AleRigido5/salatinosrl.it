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
        $selectedCategory = $request->get('category_id');
        $category = null;
        
        if ($selectedCategory) {
            $category = SettingCategory::find($selectedCategory);
        }
        
        return view('admin.settings.create', compact('categories', 'selectedCategory', 'category'));
    }
    
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
            }
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $request->validate([
            'valore' => 'required|string|max:255',
            'category_id' => 'nullable|exists:settings_categories,id',
            'descrizione' => 'nullable|string',
            'ordinamento' => 'nullable|integer',
            'valid' => 'nullable|boolean',
            'tabella_riferimento' => 'nullable|string|max:50'
        ]);
        
        // Recupera la categoria se presente
        $category = null;
        if ($request->category_id) {
            $category = SettingCategory::find($request->category_id);
        }
        
        // Determina tabella_riferimento: priorità al valore inviato, altrimenti dalla categoria
        $tabellaRiferimento = $request->tabella_riferimento;
        if (empty($tabellaRiferimento) && $category && $category->tabella_riferimento) {
            $tabellaRiferimento = $category->tabella_riferimento;
        }
        
        $setting = Setting::create([
            'valore' => $request->valore,
            'category_id' => $request->category_id,
            'descrizione' => $request->descrizione,
            'ordinamento' => $request->ordinamento ?: 0,
            'valid' => $request->boolean('valid', true),
            'tabella_riferimento' => $tabellaRiferimento,
            'created_by' => Auth::guard('admin')->id()
        ]);
        
        // Verifica se è una richiesta AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Impostazione creata con successo!',
                'data' => $setting
            ]);
        }
        
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
            'valid' => 'nullable|boolean',
            'tabella_riferimento' => 'nullable|string|max:50'
        ]);
        
        // Se non viene fornito tabella_riferimento, mantieni il valore esistente
        $tabellaRiferimento = $request->tabella_riferimento;
        if (empty($tabellaRiferimento) && empty($setting->tabella_riferimento) && $setting->category) {
            $tabellaRiferimento = $setting->category->tabella_riferimento;
        }
        
        $setting->update([
            'valore' => $request->valore,
            'descrizione' => $request->descrizione,
            'ordinamento' => (int)($request->ordinamento ?? 0),
            'valid' => (bool)($request->boolean('valid') || $request->has('valid')),
            'tabella_riferimento' => $tabellaRiferimento ?? $setting->tabella_riferimento,
            'updated_by' => Auth::guard('admin')->id()
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
    
    public function destroy(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Non hai i permessi necessari.'], 403);
            }
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $setting = Setting::findOrFail($id);
        $category = $setting->category;
        $setting->delete();
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Impostazione eliminata con successo!'
            ]);
        }
        
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
        $setting->update([
            'valid' => !$setting->valid,
            'updated_by' => Auth::guard('admin')->id()
        ]);
        
        $status = $setting->valid ? 'attivata' : 'disattivata';
        return back()->with('success', "Impostazione {$status} con successo!");
    }
}