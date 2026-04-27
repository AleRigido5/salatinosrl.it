@extends('admin.layouts.app')

@section('title', 'Gestione Servizi')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-concierge-bell mr-2 text-lime-600"></i> Gestione Servizi
            </h1>
            <p class="text-gray-600 mt-1">Gestisci il catalogo dei servizi offerti</p>
        </div>
        @if(auth()->guard('admin')->user()->hasPermission('create_services'))
        <div class="relative group">
            <a href="{{ route('admin.services.create') }}" 
               class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all duration-200">
                <i class="fas fa-plus"></i>
            </a>
            <div class="absolute bottom-full transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Nuovo Servizio
                <div class="absolute top-full transform -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-800"></div>
            </div>
        </div>
        @endif
    </div>

    @livewire('admin.services-table')
</div>
@endsection