@extends('admin.layouts.app')

@section('title', 'Nuovo Servizio')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-plus mr-2 text-lime-600"></i> Nuovo Servizio
            </h1>
            <p class="text-gray-600 mt-1">Inserisci un nuovo servizio nel catalogo</p>
        </div>
        <div class="relative group">
            <a href="{{ route('admin.services.index') }}" 
            class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-lg transition-colors flex items-center">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Torna alla lista servizi
                <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
        <form method="POST" action="{{ route('admin.services.store') }}">
            @csrf
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Titolo <span class="text-red-500">*</span></label>
                        <input type="text" name="Titolo" value="{{ old('Titolo') }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                        @error('Titolo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Categoria</label>
                        <select name="id_categories" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            <option value="">Seleziona categoria</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('id_categories') == $category->id ? 'selected' : '' }}>
                                {{ $category->valore }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prezzo</label>
                        <input type="number" step="0.001" name="Prezzo_un" value="{{ old('Prezzo_un') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unità di Misura</label>
                        <select name="UnitaMisura_id_unita" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                            <option value="">Seleziona unità</option>
                            @foreach($unitaMisura as $um)
                            <option value="{{ $um->id_um }}" {{ old('UnitaMisura_id_unita') == $um->id_um ? 'selected' : '' }}>
                                {{ $um->nome }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                        <textarea name="Descrizione" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">{{ old('Descrizione') }}</textarea>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione per Fattura</label>
                        <textarea name="Descr_fattura" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">{{ old('Descr_fattura') }}</textarea>
                    </div>
                    
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="Stato" value="1" {{ old('Stato', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-lime-600 focus:ring-lime-500">
                            <span class="ml-2 text-sm text-gray-700">Attivo</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 p-6 border-t">
                <a href="{{ route('admin.services.index') }}" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md">
                    Annulla
                </a>
                <button type="submit" class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md">
                    <i class="fas fa-save mr-2"></i> Salva
                </button>
            </div>
        </form>
    </div>
</div>
@endsection