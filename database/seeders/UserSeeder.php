<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@brewandgo.test'],
            [
                'name' => 'Brew & Go Admin',
                'phone' => '09171234567',
                'address' => 'Brew & Go HQ',
                'password' => Hash::make('Admin@12345'),
                'is_admin' => true,
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
