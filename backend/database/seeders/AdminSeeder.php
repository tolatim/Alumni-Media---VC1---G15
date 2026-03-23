<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRoleId = Role::where('name', 'admin')->value('id');
        $alumniRoleId = Role::where('name', 'alumni')->value('id');

        $admin = User::firstOrCreate(
            ['email' => 'admin@alumni.com'],
            [
                'first_name' => 'super',
                'last_name' => 'admin',
                'password' => Hash::make('Admin123!'),
                'role_id' => $adminRoleId,
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'sothin3@gmail.com'],
            [
                'first_name' => 'Ke',
                'last_name' => 'Sothin',
                'password' => Hash::make('kesothin'),
                'role_id' => $alumniRoleId,
            ],
        );
    }
}
