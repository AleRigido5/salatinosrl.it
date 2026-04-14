@extends('admin.layouts.app')

@section('title', 'Categorie Impostazioni')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-layer-group mr-2 text-emerald-600"></i> Categorie Impostazioni
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('edit_settings'))
        <a href="{{ route('admin.settings.categories.create') }}" 
           class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i> Nuova Categoria
        </a>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $category)
        <a href="{{ route('admin.settings.categories.show', $category->slug) }}" 
           class="group bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <i class="fas fa-chevron-right text-gray-400 group-hover:text-emerald-500 transition-colors"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-emerald-600 transition-colors">
                    {{ $category->titolo }}
                </h3>
                <p class="text-gray-500 text-sm mb-4">
                    {{ $category->descrizione ?: 'Nessuna descrizione' }}
                </p>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-400">
                        <i class="fas fa-database mr-1"></i> 
                        {{ $category->settings->count() }} impostazioni
                    </span>
                    @if($category->tabella_riferimento)
                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded">
                        {{ $category->tabella_riferimento }}
                    </span>
                    @endif
                </div>
            </div>
        </a>
        @endforeach
    </div>

    @if($categories->isEmpty())
    <div class="bg-white rounded-xl shadow-lg p-12 text-center">
        <i class="fas fa-folder-open text-gray-300 text-6xl mb-4"></i>
        <p class="text-gray-500">Nessuna categoria impostata</p>
        @if(auth()->guard('admin')->user()->hasPermission('edit_settings'))
        <a href="{{ route('admin.settings.categories.create') }}" class="mt-4 inline-block text-emerald-600 hover:text-emerald-700">
            <i class="fas fa-plus mr-1"></i> Crea la prima categoria
        </a>
        @endif
    </div>
    @endif
</div>
@endsection