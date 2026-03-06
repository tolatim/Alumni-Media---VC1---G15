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
        $admin = User::firstOrCreate(
            ['email' => 'admin@alumni.com'],
            [
                'first_name' => 'super',
                'last_name' => 'admin',
                'password' => Hash::make('Admin123!'),
                'role_id' => 1,
            ]
        );
    }
}
