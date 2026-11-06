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
        if (Schema::hasColumn('audit_logs', 'actor_role')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('actor_role', 20)->nullable()->after('user_name')->index();
        });

        DB::table('audit_logs')
            ->whereNull('actor_role')
            ->update(['actor_role' => 'admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('audit_logs', 'actor_role')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('actor_role');
        });
    }
};
