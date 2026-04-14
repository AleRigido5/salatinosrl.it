<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Login - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-green-50 to-emerald-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <!-- Header con gradiente -->
            <div class="bg-gradient-to-r from-emerald-600 to-green-600 px-8 py-6 text-center">
                <div class="mx-auto w-16 h-16 bg-white/20 rounded-full flex items-center justify-center mb-3 backdrop-blur-sm">
                    <i class="fas fa-lock text-2xl text-white"></i>
                </div>
                <h2 class="text-2xl font-bold text-white">Area Admin</h2>
                <p class="text-emerald-100 text-sm mt-1">Pannello di controllo</p>
            </div>
            
            <div class="p-8">
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-r">
                        <div class="text-red-700 text-sm">
                            @foreach ($errors->all() as $error)
                                <p><i class="fas fa-exclamation-circle mr-2"></i> {{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-r">
                        <div class="text-red-700 text-sm">
                            <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}">
                    @csrf
                    <div class="mb-5">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-1 text-emerald-500"></i> Email
                        </label>
                        <div class="relative">
                            <i class="fas fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition"
                                required autofocus>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-key mr-1 text-emerald-500"></i> Password
                        </label>
                        <div class="relative">
                            <i class="fas fa-key absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            <input type="password" name="password" id="password" 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition"
                                required>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-8">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500">
                            <span class="ml-2 text-sm text-gray-600">Ricordami</span>
                        </label>
                    </div>

                    <button type="submit" 
                        class="w-full bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] shadow-md hover:shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i> Accedi
                    </button>
                </form>
                
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <p class="text-center text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i> Area riservata agli amministratori
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>