<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Administrator; // Assicurati di usare il modello corretto per la tua tabella admins
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Assicuriamoci che esistano i ruoli base (necessari per la relazione)
        $roleSuperAdmin = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin']);
        $roleAdmin = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $roleEditor = Role::firstOrCreate(['slug' => 'editor'], ['name' => 'Editor']);

        // 2. Creazione AMMINISTRATORI (per la tabella administrators che stiamo filtrando)
        $admins = [
            [
                'name' => 'Marco Rossi',
                'email' => 'marco.rossi@example.com',
                'role_id' => $roleSuperAdmin->id,
                'is_active' => true,
            ],
            [
                'name' => 'Giulia Bianchi',
                'email' => 'giulia.bianchi@example.com',
                'role_id' => $roleAdmin->id,
                'is_active' => true,
            ],
            [
                'name' => 'Alessandro Verdi',
                'email' => 'a.verdi@admin.it',
                'role_id' => $roleEditor->id,
                'is_active' => false, // Utente non attivo per testare il filtro stato
            ],
            [
                'name' => 'Elena Neri',
                'email' => 'elena.neri@tech.com',
                'role_id' => $roleAdmin->id,
                'is_active' => true,
            ],
            [
                'name' => 'Luca Romano',
                'email' => 'l.romano@agency.com',
                'role_id' => $roleEditor->id,
                'is_active' => true,
            ],
        ];

        foreach ($admins as $adminData) {
            Administrator::updateOrCreate(
                ['email' => $adminData['email']],
                [
                    'name' => $adminData['name'],
                    'password' => Hash::make('password'),
                    'role_id' => $adminData['role_id'],
                    'is_active' => $adminData['is_active'],
                    'created_at' => now(),
                ]
            );
        }

        // 3. Creazione UTENTI NORMALI (tabella users)
        $users = [
            [
                'name' => 'Mario Rossi',
                'email' => 'user@example.com',
                'city' => 'Roma'
            ],
            [
                'name' => 'Francesca Esposito',
                'email' => 'f.esposito@gmail.com',
                'city' => 'Napoli'
            ],
            [
                'name' => 'Roberto Galli',
                'email' => 'r.galli@libero.it',
                'city' => 'Milano'
            ]
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'role' => 'user',
                    'is_active' => true,
                    'phone' => '+39 333 ' . rand(1000000, 9999999),
                    'permissions' => json_encode([]),
                    'metadata' => json_encode(['city' => $userData['city']]),
                ]
            );
        }
    }
}