@extends('admin.layouts.app')

@section('title', 'Dettagli Utente')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-user-circle mr-2"></i> Dettagli Utente
        </h1>
        <div>
            @if(auth()->guard('admin')->user()->hasPermission('edit_users'))
            <a href="{{ route('admin.users.edit', $user) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg mr-2">
                <i class="fas fa-edit mr-2"></i> Modifica
            </a>
            @endif
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i> Torna indietro
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b">
            <div class="flex items-center space-x-4">
                <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-circle text-4xl text-blue-600"></i>
                </div>
                <div>
                    <h2 class="text-2xl font-bold">{{ $user->name }}</h2>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    <span class="inline-block px-2 py-1 text-xs rounded-full mt-2 
                        @if($user->role == 'moderator') bg-purple-100 text-purple-800
                        @elseif($user->role == 'vip') bg-yellow-100 text-yellow-800
                        @elseif($user->role == 'premium') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        <i class="fas 
                            @if($user->role == 'moderator') fa-gavel
                            @elseif($user->role == 'vip') fa-gem
                            @elseif($user->role == 'premium') fa-star
                            @else fa-user
                            @endif mr-1"></i>
                        {{ $roles[$user->role] ?? $user->role }}
                    </span>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">
                <i class="fas fa-info-circle mr-2 text-blue-500"></i> Informazioni
            </h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-phone mr-1"></i> Telefono
                    </p>
                    <p class="font-medium">{{ $user->phone ?? 'Non specificato' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-toggle-on mr-1"></i> Stato
                    </p>
                    <p class="font-medium">
                        <span class="px-2 py-1 text-xs rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            <i class="fas {{ $user->is_active ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                            {{ $user->is_active ? 'Attivo' : 'Disattivo' }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-calendar-alt mr-1"></i> Registrato il
                    </p>
                    <p class="font-medium">{{ $user->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-clock mr-1"></i> Ultimo accesso
                    </p>
                    <p class="font-medium">{{ $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Mai' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">
                        <i class="fas fa-network-wired mr-1"></i> Ultimo IP
                    </p>
                    <p class="font-medium">{{ $user->last_login_ip ?? '-' }}</p>
                </div>
            </div>
            
            @if($user->metadata && json_decode($user->metadata, true))
            <h3 class="text-lg font-semibold mt-6 mb-4">
                <i class="fas fa-database mr-2 text-purple-500"></i> Metadata
            </h3>
            <div class="bg-gray-50 rounded-lg p-4">
                <pre class="text-sm">{{ json_encode(json_decode($user->metadata, true), JSON_PRETTY_PRINT) }}</pre>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection