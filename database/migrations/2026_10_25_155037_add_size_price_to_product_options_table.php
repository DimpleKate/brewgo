<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->nullable()->after('price_adjustment');
        });

        DB::table('product_options')
            ->where('group_name', 'size')
            ->orderBy('id')
            ->chunkById(100, function ($options) {
                foreach ($options as $option) {
                    $basePrice = (float) DB::table('products')
                        ->where('id', $option->product_id)
                        ->value('base_price');

                    DB::table('product_options')
                        ->where('id', $option->id)
                        ->update(['price' => round($basePrice + (float) $option->price_adjustment, 2)]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_options', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
