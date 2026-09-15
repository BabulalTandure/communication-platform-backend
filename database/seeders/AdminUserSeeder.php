<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Default Admin User
        User::firstOrCreate(
            ['username' => 'admin'],
            [
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'created_by' => null,
            ]
        );

        // Default Normal User
        User::firstOrCreate(
            ['username' => 'user1'],
            [
                'password' => Hash::make('password123'),
                'role' => 'user',
                'status' => 'active',
                'created_by' => 1,
            ]
        );
    }
}
