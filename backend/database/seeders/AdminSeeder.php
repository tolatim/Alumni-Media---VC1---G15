<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin (role_id = 1)
        $admin = User::firstOrCreate(
            ['email' => 'admin@alumni.com'],
            [
                'first_name' => 'super',
                'last_name' => 'admin',
                'password' => Hash::make('Admin123!'),
                'role_id' => 1,
            ]
        );

        // User 1
        $user1 = User::firstOrCreate(
            ['email' => 'user1@alumni.com'],
            [
                'first_name' => 'john',
                'last_name' => 'doe',
                'password' => Hash::make('Admin123!'),
                'role_id' => 2,
            ]
        );

        // User 2
        $user2 = User::firstOrCreate(
            ['email' => 'user2@alumni.com'],
            [
                'first_name' => 'jane',
                'last_name' => 'smith',
                'password' => Hash::make('Admin123!'),
                'role_id' => 2,
            ]
        );

        // User 3
        $user3 = User::firstOrCreate(
            ['email' => 'user3@alumni.com'],
            [
                'first_name' => 'david',
                'last_name' => 'lee',
                'password' => Hash::make('Admin123!'),
                'role_id' => 2,
            ]
        );
    }
}
