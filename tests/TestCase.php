<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    protected function seedTestCatalog(): void
    {
        $this->seed([
            CatalogSeeder::class,
            PromotionSeeder::class,
        ]);
    }

    protected function createCustomer(): User
    {
        $customer = User::create([
            'name' => 'Test Customer',
            'email' => 'customer@brewandgo.test',
            'phone' => '09181234567',
            'address' => '123 Test Street, Quezon City',
            'password' => Hash::make('Customer@12345'),
            'is_admin' => false,
            'is_active' => true,
        ]);

        return $customer;
    }
}
