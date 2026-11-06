<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number', 30)->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_due', 10, 2)->default(0);
            $table->string('payment_method', 20);
            $table->json('snapshot')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->timestamps();

            $table->index(['order_id', 'issued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
