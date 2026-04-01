<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class GestionaleAuthController extends Controller
{
    /**
     * Gestisce il tentativo di login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'L\'email è obbligatoria.',
            'email.email' => 'Inserisci un indirizzo email valido.',
            'password.required' => 'La password è obbligatoria.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            return redirect()->intended(route('gestionale.dashboard'))
                ->with('success', 'Benvenuto ' . (Auth::user()->name ?? Auth::user()->email) . '!');
        }

        throw ValidationException::withMessages([
            'email' => 'Le credenziali fornite non sono corrette.',
        ]);
    }

    /**
     * Gestisce il logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/')->with('status', 'Sei stato disconnesso con successo.');
    }
}