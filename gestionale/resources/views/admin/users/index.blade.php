@extends('admin.layouts.app')

@section('title', 'Gestione Utenti')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-users mr-2 text-emerald-600"></i> Gestione Utenti
        </h1>
        @if(auth()->guard('admin')->user()->hasPermission('create_users'))
            <a href="{{ route('admin.users.create') }}" 
               class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-5 py-2.5 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                <i class="fas fa-plus mr-2"></i> Nuovo Utente
            </a>
        @endif
    </div>

    <!-- Messaggi flash -->
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
        </div>
    @endif

    <!-- Componente Livewire -->
    @livewire('admin.user-table')
</div>
@endsection