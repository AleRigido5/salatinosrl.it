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
        <a href="{{ route('admin.services.create') }}" class="bg-lime-600 hover:bg-lime-700 text-white px-4 py-2 rounded-lg transition-colors">
            <i class="fas fa-plus mr-2"></i> Nuovo Servizio
        </a>
        @endif
    </div>

    @livewire('admin.services-table')
</div>
@endsection