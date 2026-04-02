<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        // Utente normale
        User::updateOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Utente Normale',
                'password' => Hash::make('password'),
                'role' => 'user',
                'is_active' => true,
                'phone' => '+39 333 0000000',
                'permissions' => json_encode([]),
                'metadata' => json_encode(['city' => 'Roma']),
            ]
        );
    }
}