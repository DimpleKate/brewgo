<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('table_number', 20)->nullable();
            $table->timestamp('pickup_at')->nullable();
            $table->timestamp('preparation_start_at')->nullable();
            $table->timestamp('pickup_deadline_at')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 30)->default('unpaid');
            $table->decimal('required_payment_amount', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_reason', 255)->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('payment_status');
            $table->index('pickup_deadline_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['pickup_deadline_at']);
            $table->dropColumn([
                'table_number',
                'pickup_at',
                'preparation_start_at',
                'pickup_deadline_at',
                'payment_method',
                'payment_status',
                'required_payment_amount',
                'amount_paid',
                'paid_at',
                'overdue_at',
                'cancelled_at',
                'cancelled_reason',
                'cancelled_by',
            ]);
        });
    }
};
