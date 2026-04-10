@extends('admin.layouts.app')

@section('title', 'Modifica Categoria - ' . $category->titolo)

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.settings.categories.show', $category->slug) }}" 
               class="text-gray-500 hover:text-gray-700 transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-edit mr-2 text-lime-600"></i> Modifica Categoria
            </h1>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.settings.categories.update', $category->id) }}">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titolo <span class="text-red-500">*</span></label>
                    <input type="text" name="titolo" value="{{ old('titolo', $category->titolo) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @error('titolo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                    <textarea name="descrizione" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">{{ old('descrizione', $category->descrizione) }}</textarea>
                    @error('descrizione') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tabella di Riferimento</label>
                    <input type="text" name="tabella_riferimento" value="{{ old('tabella_riferimento', $category->tabella_riferimento) }}"
                           placeholder="es: contacts, staff_expiry, vehicle_documents"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <p class="text-xs text-gray-500 mt-1">Nome della tabella associata a questa categoria</p>
                    @error('tabella_riferimento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Icona</label>
                    <input type="text" name="icona" value="{{ old('icona', $category->icona) }}"
                           placeholder="fas fa-cog"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <p class="text-xs text-gray-500 mt-1">Classe FontAwesome (es: fas fa-address-book)</p>
                    @error('icona') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordinamento</label>
                    <input type="number" name="ordinamento" value="{{ old('ordinamento', $category->ordinamento) }}"
                           class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @error('ordinamento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="inline-flex items-center">
                        <input type="checkbox" name="valid" value="1" 
                               {{ old('valid', $category->valid) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-lime-600 shadow-sm focus:ring-lime-500">
                        <span class="ml-2 text-sm text-gray-700">Categoria attiva</span>
                    </label>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <a href="{{ route('admin.settings.categories.show', $category->slug) }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Aggiorna Categoria
                </button>
            </div>
        </form>
    </div>
</div>
@endsection