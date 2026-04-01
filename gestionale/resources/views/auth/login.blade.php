@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6-4h12a2 2 0 002-2v-2a2 2 0 00-2-2H6a2 2 0 00-2 2v2a2 2 0 002 2zm10 4v2a2 2 0 01-2 2H8a2 2 0 01-2-2v-2m12-4h-4"></path>
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-gray-900">Benvenuto</h2>
            <p class="text-gray-600 mt-2">Accedi al tuo account</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded-r">
                <div class="text-red-700 text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded-r">
                <div class="text-green-700 text-sm">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-5">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Indirizzo Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition outline-none"
                    required autofocus>
            </div>

            <div class="mb-5">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input type="password" name="password" id="password" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition outline-none"
                    required>
            </div>

            <div class="flex items-center justify-between mb-6">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-600">Ricordami</span>
                </label>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-700">Password dimenticata?</a>
            </div>

            <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 transform hover:scale-[1.02]">
                Accedi
            </button>
        </form>

        <div class="mt-6 pt-6 border-t border-gray-200">
            <p class="text-center text-sm text-gray-600">
                Credenziali di test:<br>
                <span class="font-mono text-xs">admin@example.com / password</span>
            </p>
        </div>
    </div>
</div>
@endsection