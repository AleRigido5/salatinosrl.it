@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="p-6">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Ciao, {{ $admin->name }}! 👋</h1>
        <p class="text-gray-600 mt-1">Ecco cosa sta succedendo oggi nel tuo pannello di controllo.</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Admin</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_admins']) }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-users text-xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Admin Attivi</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['active_admins']) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-check-circle text-xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Utenti</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_users']) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-user-friends text-xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 mb-1">Totale Ruoli</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($stats['total_roles']) }}</p>
                </div>
                <div class="p-3 bg-yellow-100 rounded-full">
                    <i class="fas fa-tags text-xl text-yellow-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Admins -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-history mr-2 text-gray-500"></i> Ultimi Amministratori Registrati
            </h3>
        </div>
        <div class="p-6">
            <div class="space-y-4">
                @forelse($recentAdmins as $adminItem)
                <div class="flex items-center justify-between py-2 hover:bg-gray-50 px-2 rounded-lg transition">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-800">{{ $adminItem->name }}</p>
                            <p class="text-sm text-gray-500">{{ $adminItem->email }}</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-2 py-1 text-xs rounded-full 
                            @if($adminItem->role && $adminItem->role->slug == 'super_admin') bg-red-100 text-red-800
                            @elseif($adminItem->role && $adminItem->role->slug == 'admin') bg-blue-100 text-blue-800
                            @elseif($adminItem->role && $adminItem->role->slug == 'editor') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            <i class="fas 
                                @if($adminItem->role && $adminItem->role->slug == 'super_admin') fa-crown
                                @elseif($adminItem->role && $adminItem->role->slug == 'admin') fa-user-shield
                                @elseif($adminItem->role && $adminItem->role->slug == 'editor') fa-edit
                                @else fa-eye
                                @endif mr-1"></i>
                            {{ $adminItem->role->name ?? 'Nessun ruolo' }}
                        </span>
                        <span class="text-sm text-gray-400">{{ $adminItem->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                @empty
                <p class="text-gray-500 text-center py-4">Nessun amministratore trovato</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection