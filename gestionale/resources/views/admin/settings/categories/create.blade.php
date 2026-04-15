{{-- create.blade.php --}}
@extends('admin.layouts.app')

@section('title', 'Nuova Categoria')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2 text-lime-600"></i> Nuova Categoria
        </h1>
        <a href="{{ route('admin.settings.categories.index') }}" 
           class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> Torna indietro
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.settings.categories.store') }}">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titolo <span class="text-red-500">*</span></label>
                    <input type="text" name="titolo" value="{{ old('titolo') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @error('titolo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descrizione</label>
                    <textarea name="descrizione" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">{{ old('descrizione') }}</textarea>
                    @error('descrizione') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tabella di Riferimento</label>
                    <input type="text" name="tabella_riferimento" value="{{ old('tabella_riferimento') }}"
                           placeholder="es: contacts, staff_expiry, vehicle_documents"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    <p class="text-xs text-gray-500 mt-1">Nome della tabella associata a questa categoria</p>
                    @error('tabella_riferimento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ordinamento</label>
                    <input type="number" name="ordinamento" value="{{ old('ordinamento', 0) }}"
                           class="w-32 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-lime-500">
                    @error('ordinamento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6 pt-4 border-t">
                <a href="{{ route('admin.settings.categories.index') }}" 
                   class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md transition-colors">
                    Annulla
                </a>
                <button type="submit" 
                        class="px-4 py-2 bg-lime-600 hover:bg-lime-700 text-white rounded-md transition-colors">
                    <i class="fas fa-save mr-2"></i> Crea Categoria
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
