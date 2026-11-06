<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->json('old_values')->nullable()->after('context');
            $table->json('new_values')->nullable()->after('old_values');
            $table->string('auditable_type')->nullable()->after('new_values');
            $table->unsignedBigInteger('auditable_id')->nullable()->after('auditable_type');

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['auditable_type', 'auditable_id']);
            $table->dropColumn(['old_values', 'new_values', 'auditable_type', 'auditable_id']);
        });
    }
};
