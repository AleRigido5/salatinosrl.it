<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Administrator;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class AdministratorsTableSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('slug', 'super_admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $editorRole = Role::where('slug', 'editor')->first();
        $viewerRole = Role::where('slug', 'viewer')->first();

        // Super Admin
        Administrator::updateOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('SAdmin123!'),
                'role_id' => $superAdminRole->id,
                'is_active' => true,
                'phone' => '+39 333 1111111',
            ]
        );

        // Admin
        Administrator::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Admin123!'),
                'role_id' => $adminRole->id,
                'is_active' => true,
                'phone' => '+39 333 2222222',
            ]
        );

        // Editor
        Administrator::updateOrCreate(
            ['email' => 'editor@gmail.com'],
            [
                'name' => 'Editor User',
                'password' => Hash::make('Editor123!'),
                'role_id' => $editorRole->id,
                'is_active' => true,
                'phone' => '+39 333 3333333',
            ]
        );

        // Viewer
        Administrator::updateOrCreate(
            ['email' => 'viewer@gmail.com'],
            [
                'name' => 'Viewer User',
                'password' => Hash::make('Viewer123!'),
                'role_id' => $viewerRole->id,
                'is_active' => true,
                'phone' => '+39 333 4444444',
            ]
        );
    }
}