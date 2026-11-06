<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['Maria Santos', 'maria@brewandgo.test', '09181234567', '12 Mabini Street, Quezon City'],
            ['Juan Dela Cruz', 'juan@brewandgo.test', '09182345678', '45 Katipunan Avenue, Quezon City'],
            ['Ana Reyes', 'ana@brewandgo.test', '09183456789', '8 Tomas Morato, Quezon City'],
        ];

        foreach ($customers as [$name, $email, $phone, $address]) {
            User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'phone' => $phone,
                    'address' => $address,
                    'password' => Hash::make('Customer@12345'),
                    'is_admin' => false,
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
