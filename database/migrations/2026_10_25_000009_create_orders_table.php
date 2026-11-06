<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('service_type', 20);
            $table->unsignedSmallInteger('people_count')->nullable();
            $table->string('pickup_name')->nullable();
            $table->string('pickup_phone', 30)->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promotion_code', 40)->nullable();
            $table->string('status', 30)->default('Pending');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
