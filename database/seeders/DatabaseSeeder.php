<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        if (app()->environment(['local', 'development'])) {
            $this->call([
                DemoCatalogSeeder::class,
                DemoPromotionSeeder::class,
                DemoCustomerSeeder::class,
            ]);
        }
    }
}
