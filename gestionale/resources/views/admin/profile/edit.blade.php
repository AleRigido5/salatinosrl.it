@extends('admin.layouts.app')

@section('title', 'Il mio profilo')

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-user-circle mr-2 text-emerald-600"></i> Il mio profilo
        </h1>
        <a href="{{ route('admin.dashboard') }}" class="text-emerald-600 hover:text-emerald-700 transition-colors">
            <i class="fas fa-arrow-left mr-1"></i> Torna alla dashboard
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar Profilo -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
                <div class="bg-gradient-to-r from-emerald-50 to-green-50 p-6 text-center border-b border-emerald-100">
                    <div class="w-32 h-32 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center text-white text-4xl font-bold mx-auto mb-4 shadow-lg">
                        {{ strtoupper(substr($admin->name, 0, 1)) }}
                    </div>
                    <h2 class="text-xl font-bold text-gray-800">{{ $admin->name }}</h2>
                    <p class="text-emerald-600 text-sm mt-1">
                        <i class="fas fa-shield-alt mr-1"></i> {{ $admin->role->name ?? 'Nessun ruolo' }}
                    </p>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-envelope mr-1"></i> {{ $admin->email }}
                    </p>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3">
                        <div class="flex items-center text-sm text-gray-600">
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-alt text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Registrato il</p>
                                <p class="font-medium">{{ $admin->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-clock text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Ultimo accesso</p>
                                <p class="font-medium">{{ $admin->last_login_at ? $admin->last_login_at->format('d/m/Y H:i') : 'Mai' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-network-wired text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Ultimo IP</p>
                                <p class="font-medium">{{ $admin->last_login_ip ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600">
                            <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-tag text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400">Livello ruolo</p>
                                <p class="font-medium">{{ $admin->role->level ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Modifica Profilo -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-emerald-100">
                <div class="bg-gradient-to-r from-emerald-50 to-green-50 px-6 py-4 border-b border-emerald-100">
                    <h3 class="text-lg font-semibold text-emerald-800">
                        <i class="fas fa-edit mr-2"></i> Modifica informazioni
                    </h3>
                </div>
                
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.profile.update') }}">
                        @csrf
                        @method('PUT')
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-1 text-emerald-500"></i> Nome
                                </label>
                                <input type="text" name="name" value="{{ old('name', $admin->name) }}" required
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-envelope mr-1 text-emerald-500"></i> Email
                                </label>
                                <input type="email" name="email" value="{{ old('email', $admin->email) }}" required
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                                @error('email') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-phone mr-1 text-emerald-500"></i> Telefono
                                </label>
                                <input type="text" name="phone" value="{{ old('phone', $admin->phone) }}"
                                       class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition">
                                @error('phone') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        
                        <div class="mt-8">
                            <div class="bg-gradient-to-r from-amber-50 to-yellow-50 rounded-lg p-4 border border-amber-200">
                                <h3 class="text-lg font-semibold mb-4 text-amber-800">
                                    <i class="fas fa-key mr-2 text-amber-600"></i> Cambia password
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-lock mr-1 text-amber-500"></i> Password corrente
                                        </label>
                                        <input type="password" name="current_password"
                                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition">
                                        @error('current_password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    
                                    <div></div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-key mr-1 text-amber-500"></i> Nuova password
                                        </label>
                                        <input type="password" name="password"
                                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition">
                                        @error('password') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            <i class="fas fa-key mr-1 text-amber-500"></i> Conferma nuova password
                                        </label>
                                        <input type="password" name="password_confirmation"
                                               class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white px-6 py-2.5 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg">
                                <i class="fas fa-save mr-2"></i> Aggiorna profilo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection