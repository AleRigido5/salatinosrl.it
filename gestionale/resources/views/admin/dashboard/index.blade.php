@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">
            Ciao, {{ $admin->name }}!
        </h1>
        <p class="text-gray-600 mt-1">Ecco cosa sta succedendo oggi nel tuo pannello di controllo.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Admin</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_admins']) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-chart-line mr-1"></i> +12%
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-emerald-400 to-green-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-users text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Admin Attivi</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active_admins']) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-green-600 bg-green-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-check-circle mr-1"></i> Attivi
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-user-check text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-lime-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Clienti</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_entities'] ?? 0) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-lime-600 bg-lime-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-users mr-1"></i> Registrati
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-lime-400 to-green-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-building text-white text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 p-6 border-l-4 border-teal-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Ruoli</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_roles']) }}</p>
                    <div class="flex items-center mt-2">
                        <span class="text-xs text-teal-600 bg-teal-100 px-2 py-0.5 rounded-full">
                            <i class="fas fa-tags mr-1"></i> Configurati
                        </span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-teal-400 to-emerald-500 rounded-xl flex items-center justify-center shadow-md">
                    <i class="fas fa-shield-alt text-white text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Entities -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-emerald-100">
            <h3 class="text-lg font-semibold text-emerald-800">
                <i class="fas fa-history mr-2"></i> Ultimi Clienti / Fornitori Registrati
            </h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($recentEntities ?? [] as $entityItem)
                <div class="flex items-center justify-between py-3 hover:bg-emerald-50/30 px-3 rounded-lg transition-all duration-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-md">
                            <i class="fas {{ $entityItem->entity_type == 'cliente' ? 'fa-user-tie' : 'fa-truck' }} text-white"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800">{{ $entityItem->full_name }}</p>
                            <p class="text-sm text-gray-500">
                                <i class="fas fa-envelope mr-1 text-gray-400"></i> {{ $entityItem->email ?? 'Nessuna email' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full 
                            @if($entityItem->entity_type == 'cliente') bg-emerald-100 text-emerald-700
                            @elseif($entityItem->entity_type == 'fornitore') bg-blue-100 text-blue-700
                            @else bg-purple-100 text-purple-700
                            @endif">
                            <i class="fas 
                                @if($entityItem->entity_type == 'cliente') fa-user-tie
                                @elseif($entityItem->entity_type == 'fornitore') fa-truck
                                @else fa-handshake
                                @endif mr-1"></i>
                            {{ $entityTypes[$entityItem->entity_type] ?? $entityItem->entity_type }}
                        </span>
                        <span class="text-sm text-gray-400">
                            <i class="far fa-clock mr-1"></i> {{ $entityItem->created_at ? $entityItem->created_at->diffForHumans() : ($entityItem->data_inserimento ? date('d/m/Y', strtotime($entityItem->data_inserimento)) : '-') }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-4xl text-gray-300 mb-2"></i>
                    <p class="text-gray-500">Nessun cliente/fornitore trovato</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection