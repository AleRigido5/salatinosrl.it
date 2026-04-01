<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Gestionale Salatino Srl') }} - Login</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS per estendere Tailwind -->
    <style>
        /* Personalizzazioni del tema */
        .btn-primary {
            @apply bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 px-6 rounded-xl 
                   hover:from-indigo-700 hover:to-purple-700 transform hover:scale-[1.02] transition-all duration-200 
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
        }
        
        .input-field {
            @apply w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 
                   focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-20 transition-all duration-200 
                   outline-none text-gray-700;
        }
        
        .card {
            @apply bg-white rounded-2xl shadow-xl overflow-hidden;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .animate-slide-up {
            animation: slideUp 0.5s ease-out;
        }
        
        /* Configurazione Tailwind personalizzata */
        .bg-gradient-custom {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #a855f7 100%);
        }
    </style>
</head>
<body class="font-sans antialiased bg-gradient-to-br from-indigo-600 via-purple-700 to-pink-600 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-md animate-fade-in">
            <!-- Logo e Titolo -->
            <div class="text-center mb-8 animate-slide-up">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-sm rounded-2xl mb-4 shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2 tracking-tight">Salatino Srl</h1>
                <p class="text-white/80 text-sm">Accedi al gestionale aziendale</p>
            </div>

            <!-- Card Login -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden animate-slide-up" style="animation-delay: 0.1s">
                <div class="p-8">
                    <!-- Errori di validazione -->
                    @if ($errors->any())
                        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-red-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        <p>{{ $error }}</p>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Messaggi di sessione -->
                    @if (session('status'))
                        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 rounded-lg">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-green-700 text-sm">{{ session('status') }}</span>
                            </div>
                        </div>
                    @endif

                    <!-- Form di Login -->
                    <form method="POST" action="{{ route('gestionale.login') }}">
                        @csrf
                        
                        <!-- Campo Email -->
                        <div class="mb-5">
                            <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                                Email
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email') }}" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-20 transition-all duration-200 outline-none text-gray-700 @error('email') border-red-500 @enderror"
                                   placeholder="nome@salatinosrl.it"
                                   required 
                                   autofocus>
                        </div>

                        <!-- Campo Password -->
                        <div class="mb-5">
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                Password
                            </label>
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-20 transition-all duration-200 outline-none text-gray-700 @error('password') border-red-500 @enderror"
                                   placeholder="••••••••"
                                   required>
                        </div>

                        <!-- Remember me e Forgot password -->
                        <div class="mb-6 flex items-center justify-between">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" 
                                       name="remember" 
                                       class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <span class="ml-2 text-sm text-gray-600">Ricordami</span>
                            </label>
                            <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700 hover:underline transition-colors">
                                Password dimenticata?
                            </a>
                        </div>

                        <!-- Bottone Login -->
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold py-3 px-6 rounded-xl hover:from-indigo-700 hover:to-purple-700 transform hover:scale-[1.02] transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 shadow-md">
                            <span class="flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                </svg>
                                Accedi
                            </span>
                        </button>
                    </form>

                    <!-- Info aggiuntive -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-center space-x-2 text-xs text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span>Area riservata ai dipendenti</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-white/50 text-xs">
                © {{ date('Y') }} Salatino Srl. Tutti i diritti riservati.
            </div>
        </div>
    </div>
</body>
</html>