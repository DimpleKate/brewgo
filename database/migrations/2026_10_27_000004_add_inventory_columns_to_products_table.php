<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock_quantity')->nullable()->after('is_active');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_quantity');
            $table->boolean('is_sold_out')->default(false)->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'low_stock_threshold', 'is_sold_out']);
        });
    }
};
