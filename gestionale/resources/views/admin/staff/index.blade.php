@extends('admin.layouts.app')

@section('title', 'Gestione Personale')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-users mr-2 text-lime-600"></i> Gestione Personale
        </h1>
        
        @if(auth()->guard('admin')->user()->hasPermission('create_staff'))
        <button onclick="Livewire.dispatch('openCreateModal')"
                class="bg-gradient-to-r from-lime-500 to-lime-600 text-white px-5 py-2.5 rounded-lg">
            <i class="fas fa-plus mr-2"></i> Nuovo Personale
        </button>
        @endif
    </div>

    <!-- Componente Livewire -->
    @livewire('admin.staff-table')
</div>
@endsection