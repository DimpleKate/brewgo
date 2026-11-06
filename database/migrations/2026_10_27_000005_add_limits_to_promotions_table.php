<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedInteger('usage_limit')->nullable()->after('minimum_spend');
            $table->unsignedInteger('per_customer_limit')->nullable()->after('usage_limit');
            $table->unsignedInteger('used_count')->default(0)->after('per_customer_limit');
            $table->string('applies_to', 20)->default('all')->after('used_count');
            $table->foreignId('category_id')->nullable()->after('applies_to')->constrained()->nullOnDelete();
            $table->decimal('max_discount', 10, 2)->nullable()->after('category_id');
        });

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('discount_amount', 10, 2);
            $table->timestamps();

            $table->unique(['promotion_id', 'order_id']);
            $table->index(['promotion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_redemptions');

        Schema::table('promotions', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'usage_limit',
                'per_customer_limit',
                'used_count',
                'applies_to',
                'category_id',
                'max_discount',
            ]);
        });
    }
};
