@extends('admin.layouts.app')

@section('title', 'Dettagli Amministratore')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-user-circle mr-2 text-emerald-600"></i> Dettagli Amministratore
        </h1>
        <div>
            @if(auth()->guard('admin')->user()->hasPermission('edit_administrators') && $administrator->id != auth()->guard('admin')->id())
            <a href="{{ route('admin.administrators.edit', $administrator) }}" 
               class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-4 py-2 rounded-lg transition-all duration-200 shadow-md mr-2">
                <i class="fas fa-edit mr-2"></i> Modifica
            </a>
            @endif
            <a href="{{ route('admin.administrators.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg transition-all duration-200">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
        <div class="bg-gradient-to-r from-emerald-50 to-green-50 p-6 border-b border-emerald-100">
            <div class="flex items-center space-x-5">
                <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-user-shield text-5xl text-white"></i>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">{{ $administrator->name }}</h2>
                    <p class="text-gray-600 text-lg mt-1">
                        <i class="fas fa-envelope mr-1 text-emerald-500"></i> {{ $administrator->email }}
                    </p>
                    <div class="mt-3 flex space-x-2">
                        <span class="inline-flex items-center px-3 py-1 text-sm rounded-full 
                            @if($administrator->role && $administrator->role->slug == 'super_admin') bg-red-100 text-red-700
                            @elseif($administrator->role && $administrator->role->slug == 'admin') bg-emerald-100 text-emerald-700
                            @elseif($administrator->role && $administrator->role->slug == 'editor') bg-green-100 text-green-700
                            @else bg-gray-100 text-gray-700
                            @endif">
                            <i class="fas 
                                @if($administrator->role && $administrator->role->slug == 'super_admin') fa-crown
                                @elseif($administrator->role && $administrator->role->slug == 'admin') fa-user-shield
                                @elseif($administrator->role && $administrator->role->slug == 'editor') fa-edit
                                @else fa-eye
                                @endif mr-1"></i>
                            {{ $administrator->role->name ?? 'Nessun ruolo' }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 text-sm rounded-full {{ $administrator->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            <i class="fas {{ $administrator->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                            {{ $administrator->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-5 text-gray-800">
                <i class="fas fa-info-circle mr-2 text-emerald-500"></i> Informazioni
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="bg-gradient-to-r from-emerald-50 to-transparent rounded-lg p-4 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-phone mr-1"></i> Telefono
                    </p>
                    <p class="font-medium text-gray-800 mt-1">{{ $administrator->phone ?? 'Non specificato' }}</p>
                </div>
                <div class="bg-gradient-to-r from-green-50 to-transparent rounded-lg p-4 border-l-4 border-green-500">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-chart-line mr-1"></i> Livello Ruolo
                    </p>
                    <p class="font-medium text-gray-800 mt-1">{{ $administrator->role->level ?? '-' }}</p>
                </div>
                <div class="bg-gradient-to-r from-lime-50 to-transparent rounded-lg p-4 border-l-4 border-lime-500">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-calendar-alt mr-1"></i> Registrato il
                    </p>
                    <p class="font-medium text-gray-800 mt-1">{{ $administrator->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="bg-gradient-to-r from-teal-50 to-transparent rounded-lg p-4 border-l-4 border-teal-500">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i> Ultimo accesso
                    </p>
                    <p class="font-medium text-gray-800 mt-1">{{ $administrator->last_login_at ? $administrator->last_login_at->format('d/m/Y H:i') : 'Mai' }}</p>
                </div>
                <div class="bg-gradient-to-r from-amber-50 to-transparent rounded-lg p-4 border-l-4 border-amber-500">
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-network-wired mr-1"></i> Ultimo IP
                    </p>
                    <p class="font-medium text-gray-800 mt-1">{{ $administrator->last_login_ip ?? '-' }}</p>
                </div>
            </div>
            
            @if($administrator->role)
            <h3 class="text-lg font-semibold mt-6 mb-4 text-gray-800">
                <i class="fas fa-key mr-2 text-emerald-500"></i> Permessi del Ruolo
            </h3>
            <div class="bg-gradient-to-r from-gray-50 to-emerald-50 rounded-lg p-5 border border-emerald-200">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($administrator->role->permissions as $perm)
                        <span class="inline-flex items-center text-sm text-gray-700">
                            <i class="fas fa-check-circle text-emerald-500 mr-2"></i> {{ $perm->name }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection