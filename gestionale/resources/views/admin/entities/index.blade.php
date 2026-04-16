@extends('admin.layouts.app')

@section('title', 'Gestione Clienti / Fornitori')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div class="flex space-x-3">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-building mr-2 text-lime-600"></i> Gestione Clienti / Fornitori
            </h1>
            
            {{-- @php
                $trashCount = \App\Models\Entity::onlyTrashed()->count();
            @endphp
            <a href="{{ route('admin.trash.index', 'entities') }}" 
               class="relative inline-flex items-center px-4 py-2 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-lg transition-all duration-200">
                <i class="fas fa-trash-alt mr-2"></i> Cestino
                @if($trashCount > 0)
                    <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                        {{ $trashCount }}
                    </span>
                @endif
            </a> --}}
        </div>
        
        @if(auth()->guard('admin')->user()->hasPermission('create_entities'))
        <div class="relative group">
            <button onclick="Livewire.dispatch('openCreateModal')"
                    class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-plus"></i>
            </button>
            <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Nuovo Cliente / Fornitore
                <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
        @endif
    </div>

    <!-- Componente Livewire -->
    @livewire('admin.entities-table')
</div>
@endsection