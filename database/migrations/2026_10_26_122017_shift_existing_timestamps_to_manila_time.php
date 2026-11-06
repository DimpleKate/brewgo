<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Timestamps written before the application switched from UTC to Asia/Manila
     * were stored as if the UTC wall-clock value were already local time.
     *
     * @var array<string, list<string>>
     */
    private array $instantColumns = [
        'addons' => ['created_at', 'updated_at'],
        'audit_logs' => ['created_at', 'updated_at'],
        'carousel_slides' => ['created_at', 'updated_at'],
        'cart_items' => ['created_at', 'updated_at'],
        'carts' => ['created_at', 'updated_at'],
        'categories' => ['created_at', 'updated_at'],
        'notifications' => ['read_at', 'created_at', 'updated_at'],
        'order_items' => ['created_at', 'updated_at'],
        'order_requests' => ['reviewed_at', 'created_at', 'updated_at'],
        'order_status_histories' => ['created_at', 'updated_at'],
        'order_waste_items' => ['created_at', 'updated_at'],
        'orders' => ['created_at', 'updated_at', 'preparation_start_at', 'paid_at', 'overdue_at', 'cancelled_at'],
        'password_reset_tokens' => ['created_at'],
        'payments' => ['verified_at', 'rejected_at', 'created_at', 'updated_at'],
        'product_options' => ['created_at', 'updated_at'],
        'products' => ['created_at', 'updated_at'],
        'promotions' => ['created_at', 'updated_at'],
        'receipts' => ['issued_at', 'created_at', 'updated_at'],
        'site_settings' => ['created_at', 'updated_at'],
        'users' => ['email_verified_at', 'created_at', 'updated_at'],
    ];

    public function up(): void
    {
        $this->shiftInstantTimestamps(8);
    }

    public function down(): void
    {
        $this->shiftInstantTimestamps(-8);
    }

    private function shiftInstantTimestamps(int $hours): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach ($this->instantColumns as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereNotNull($column)
                    ->update([
                        $column => DB::raw("DATE_ADD(`{$column}`, INTERVAL {$hours} HOUR)"),
                    ]);
            }
        }
    }
};
