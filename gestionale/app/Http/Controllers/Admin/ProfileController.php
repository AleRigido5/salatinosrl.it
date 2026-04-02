<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Mostra il profilo dell'admin
     */
    public function edit()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile.edit', compact('admin'));
    }

    /**
     * Aggiorna il profilo dell'admin
     */
    public function update(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:administrators,email,' . $admin->id,
            'phone' => 'nullable|string|max:20',
            'current_password' => 'nullable|required_with:password',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Verifica password corrente se si vuole cambiare password
        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $admin->password)) {
                return back()->withErrors(['current_password' => 'La password corrente non è corretta.']);
            }
            $admin->password = Hash::make($request->password);
        }

        $admin->name = $request->name;
        $admin->email = $request->email;
        $admin->phone = $request->phone;
        $admin->save();

        return redirect()->route('admin.profile.edit')->with('success', 'Profilo aggiornato con successo!');
    }

    /**
     * Mostra le impostazioni
     */
    public function settings()
    {
        $admin = Auth::guard('admin')->user();
        return view('admin.profile.settings', compact('admin'));
    }

    /**
     * Aggiorna le impostazioni
     */
    public function updateSettings(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        $request->validate([
            'language' => 'nullable|string|in:it,en',
            'timezone' => 'nullable|string|timezone',
            'notifications' => 'nullable|boolean',
        ]);

        // Salva impostazioni (puoi salvarle in una colonna settings JSON o in una tabella separata)
        $settings = $admin->settings ?? [];
        $settings['language'] = $request->language ?? 'it';
        $settings['timezone'] = $request->timezone ?? 'Europe/Rome';
        $settings['notifications'] = $request->boolean('notifications');
        
        // Se hai aggiunto una colonna settings nel database
        // $admin->settings = json_encode($settings);
        // $admin->save();

        return redirect()->route('admin.profile.settings')->with('success', 'Impostazioni aggiornate con successo!');
    }
}