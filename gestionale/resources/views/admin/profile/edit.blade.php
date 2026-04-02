@extends('admin.layouts.app')

@section('title', 'Il mio profilo')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">
            <i class="fas fa-user-circle mr-2"></i> Il mio profilo
        </h1>
        <a href="{{ route('admin.dashboard') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-1"></i> Torna alla dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Profilo -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6 text-center">
                <div class="w-32 h-32 bg-blue-500 rounded-full flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4">
                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                </div>
                <h2 class="text-xl font-bold text-gray-800">{{ $admin->name }}</h2>
                <p class="text-gray-500 text-sm">{{ $admin->role->name ?? 'Nessun ruolo' }}</p>
                <p class="text-gray-500 text-sm mt-1">{{ $admin->email }}</p>
                
                <div class="mt-4 pt-4 border-t">
                    <div class="text-sm text-gray-600">
                        <p><i class="fas fa-calendar-alt mr-2"></i> Registrato: {{ $admin->created_at->format('d/m/Y') }}</p>
                        <p class="mt-1"><i class="fas fa-clock mr-2"></i> Ultimo accesso: {{ $admin->last_login_at ? $admin->last_login_at->format('d/m/Y H:i') : 'Mai' }}</p>
                        <p class="mt-1"><i class="fas fa-network-wired mr-2"></i> Ultimo IP: {{ $admin->last_login_ip ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modifica Profilo -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">
                    <i class="fas fa-edit mr-2 text-blue-500"></i> Modifica informazioni
                </h3>
                
                <form method="POST" action="{{ route('admin.profile.update') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-1"></i> Nome
                            </label>
                            <input type="text" name="name" value="{{ old('name', $admin->name) }}" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-envelope mr-1"></i> Email
                            </label>
                            <input type="email" name="email" value="{{ old('email', $admin->email) }}" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-phone mr-1"></i> Telefono
                            </label>
                            <input type="text" name="phone" value="{{ old('phone', $admin->phone) }}"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold mb-4">
                            <i class="fas fa-key mr-2 text-yellow-500"></i> Cambia password
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-lock mr-1"></i> Password corrente
                                </label>
                                <input type="password" name="current_password"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div></div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key mr-1"></i> Nuova password
                                </label>
                                <input type="password" name="password"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                                @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-key mr-1"></i> Conferma nuova password
                                </label>
                                <input type="password" name="password_confirmation"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
                            <i class="fas fa-save mr-2"></i> Aggiorna profilo
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection