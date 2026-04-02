@extends('admin.layouts.app')

@section('title', 'Gestione Ruoli')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-shield-alt mr-2 text-emerald-600"></i> Gestione Ruoli
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_roles'))
        <a href="{{ route('admin.roles.create') }}" 
           class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-5 py-2.5 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
            <i class="fas fa-plus mr-2"></i> Nuovo Ruolo
        </a>
        @endif
    </div>

    <!-- Componente Livewire per la tabella ruoli -->
    @livewire('roles-table')
</div>
@endsection