<?php

namespace Tests;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        Promotion::updateOrCreate(
            ['code' => 'GOLDEN10'],
            [
                'title' => 'Golden Hour',
                'description' => 'Take 10% off your order when you spend at least ₱300.',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'minimum_spend' => 300,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(30),
                'is_active' => true,
                'image' => 'images/promotions/golden.jpg',
            ]
        );

        Promotion::updateOrCreate(
            ['code' => 'PAIR50'],
            [
                'title' => 'Pickle Pair',
                'description' => '₱50 off when you order two or more items worth ₱400.',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'minimum_spend' => 400,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(30),
                'is_active' => true,
                'image' => 'images/promotions/pair.jpg',
            ]
        );

        Promotion::updateOrCreate(
            ['code' => 'FEAST15'],
            [
                'title' => 'Weekend Feast',
                'description' => '15% off food and drink bundles over ₱600.',
                'discount_type' => 'percent',
                'discount_value' => 15,
                'minimum_spend' => 600,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(45),
                'is_active' => true,
                'image' => 'images/promotions/feast.jpg',
            ]
        );
    }
}
