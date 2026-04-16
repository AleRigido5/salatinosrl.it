<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SettingCategory;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class SettingCategoryController extends Controller
{
    public function index()
    {
        if (!Auth::guard('admin')->user()->hasPermission('access_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $categories = SettingCategory::ordered()->active()->get();
        
        return view('admin.settings.categories.index', compact('categories'));
    }
    
    public function show($slug)
    {
        if (!Auth::guard('admin')->user()->hasPermission('access_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $category = SettingCategory::where('slug', $slug)->firstOrFail();
        
        // Recupera le impostazioni della categoria
        $settings = Setting::where('category_id', $category->id)
            ->orderBy('ordinamento')
            ->get();
        
        return view('admin.settings.categories.show', compact('category', 'settings'));
    }
    
    public function create()
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        // Se è una richiesta AJAX, ritorna solo il form del modal
        if (request()->ajax()) {
            return view('admin.settings.categories.modal-form')->render();
        }
        
        return view('admin.settings.categories.create');
    }
    
    public function store(Request $request)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non hai i permessi necessari.'
                ], 403);
            }
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $validator = Validator::make($request->all(), [
            'titolo' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'tabella_riferimento' => 'nullable|string|max:100',
            'ordinamento' => 'nullable|integer'
        ]);
        
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        // Genera slug dal titolo
        $slug = Str::slug($request->titolo);
        
        // Verifica slug univoco
        $originalSlug = $slug;
        $counter = 1;
        while (SettingCategory::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $category = SettingCategory::create([
            'titolo' => $request->titolo,
            'slug' => $slug,
            'descrizione' => $request->descrizione,
            'tabella_riferimento' => $request->tabella_riferimento,
            'ordinamento' => $request->ordinamento ?: 0,
            'valid' => true,
            'created_by' => Auth::guard('admin')->id()
        ]);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoria creata con successo!',
                'category' => $category,
                'redirect_url' => route('admin.settings.categories.show', $category->slug)
            ]);
        }
        
        return redirect()->route('admin.settings.categories.show', $category->slug)
            ->with('success', 'Categoria creata con successo!');
    }
    
    public function edit($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $category = SettingCategory::findOrFail($id);
        
        return view('admin.settings.categories.edit', compact('category'));
    }
    
    public function update(Request $request, $id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $category = SettingCategory::findOrFail($id);
        
        $request->validate([
            'titolo' => 'required|string|max:255',
            'descrizione' => 'nullable|string',
            'tabella_riferimento' => 'nullable|string|max:100',
            'ordinamento' => 'nullable|integer',
            'valid' => 'nullable|boolean'
        ]);
        
        $category->update([
            'titolo' => $request->titolo,
            'slug' => Str::slug($request->titolo),
            'descrizione' => $request->descrizione,
            'tabella_riferimento' => $request->tabella_riferimento,
            'ordinamento' => $request->ordinamento ?: 0,
            'valid' => $request->boolean('valid'),
            'updated_by' => Auth::guard('admin')->id()
        ]);
        
        // Redirect alla pagina show della categoria (dettaglio con tabella)
        return redirect()->route('admin.settings.categories.show', $category->slug)
            ->with('success', 'Categoria aggiornata con successo!');
    }
    
    public function destroy($id)
    {
        if (!Auth::guard('admin')->user()->hasPermission('edit_settings')) {
            abort(403, 'Non hai i permessi necessari.');
        }
        
        $category = SettingCategory::findOrFail($id);
        
        // Sposta le impostazioni in una categoria "generica" o le dissocia
        Setting::where('category_id', $category->id)->update(['category_id' => null]);
        
        $category->delete();
        
        return redirect()->route('admin.settings.categories.index')
            ->with('success', 'Categoria eliminata con successo!');
    }
}