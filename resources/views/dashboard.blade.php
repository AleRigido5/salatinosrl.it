<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Green System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gradient-to-br from-green-50 via-lime-50 to-green-100">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-8">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-700 to-lime-600">
                        Dashboard
                    </h1>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg hover:from-red-600 hover:to-red-700 transition">
                            Logout
                        </button>
                    </form>
                </div>
                
                <div class="bg-green-50 rounded-lg p-6 border border-green-200">
                    <p class="text-green-800">Benvenuto! Hai effettuato l'accesso con successo.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>