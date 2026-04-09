@extends('admin.layouts.app')

@section('title', 'Modifica Impostazione')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-yellow-600"></i> Modifica Impostazione
            </h1>
            <p class="text-gray-600 mt-1">Modifica il valore di: <strong>{{ $setting->nome }}</strong></p>
        </div>
        <a href="{{ route('admin.settings.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Torna alla lista
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 max-w-2xl">
        <!-- IMPORTANTE: usa $setting->id_settings o $setting->id -->
        <form method="POST" action="{{ route('admin.settings.update', $setting->id_settings ?? $setting->id) }}">
            @csrf
            @method('PUT')
            
            <div class="p-6">
                <div class="mb-6">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-database mr-1 text-emerald-500"></i> Tabella di Riferimento
                    </label>
                    <input type="text" 
                           value="{{ $setting->tabella_riferimento }}" 
                           disabled
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-value mr-1 text-emerald-500"></i> Valore
                    </label>
                    <input type="text" 
                           name="valore" 
                           value="{{ old('valore', $setting->valore) }}"
                           placeholder="Inserisci il valore"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    @error('valore') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    <p class="text-xs text-gray-500 mt-1">Slug: {{ $setting->slug }}</p>
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sort-numeric-down mr-1 text-emerald-500"></i> Ordinamento
                    </label>
                    <input type="number" 
                           value="{{ $setting->ordinamento }}" 
                           disabled
                           class="w-full px-4 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-500">
                </div>
                
                <div class="mb-6">
                    <label class="inline-flex items-center">
                        <input type="checkbox" 
                               name="valid" 
                               value="1" 
                               {{ $setting->valid ? 'checked' : '' }}
                               class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                        <span class="ml-2 text-sm text-gray-700">Attivo</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 px-6 py-4 bg-gray-50 border-t">
                <a href="{{ route('admin.settings.index') }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Aggiorna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection