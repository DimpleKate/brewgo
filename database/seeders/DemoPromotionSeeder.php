<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Promotion;
use Illuminate\Database\Seeder;

class DemoPromotionSeeder extends Seeder
{
    public function run(): void
    {
        Promotion::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'title' => 'Welcome Offer',
                'description' => 'Take 10% off your first order when you spend at least ₱300.',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'minimum_spend' => 300,
                'usage_limit' => 500,
                'per_customer_limit' => 1,
                'applies_to' => Promotion::APPLIES_ALL,
                'max_discount' => 150,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(60),
                'is_active' => true,
                'image' => 'images/promotions/golden.jpg',
            ]
        );

        Promotion::updateOrCreate(
            ['code' => 'COFFEE50'],
            [
                'title' => 'Coffee Break',
                'description' => '₱50 off coffee orders over ₱400.',
                'discount_type' => 'fixed',
                'discount_value' => 50,
                'minimum_spend' => 400,
                'usage_limit' => 200,
                'per_customer_limit' => 2,
                'applies_to' => Promotion::APPLIES_CATEGORY,
                'category_id' => Category::where('slug', 'coffee')->value('id'),
                'max_discount' => null,
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
                'description' => '15% off food orders over ₱600.',
                'discount_type' => 'percent',
                'discount_value' => 15,
                'minimum_spend' => 600,
                'usage_limit' => null,
                'per_customer_limit' => null,
                'applies_to' => Promotion::APPLIES_CATEGORY,
                'category_id' => Category::where('slug', 'food')->value('id'),
                'max_discount' => 250,
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addDays(45),
                'is_active' => true,
                'image' => 'images/promotions/feast.jpg',
            ]
        );
    }
}
