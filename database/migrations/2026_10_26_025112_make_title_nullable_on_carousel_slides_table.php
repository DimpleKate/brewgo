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
        Schema::table('carousel_slides', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('carousel_slides')
            ->whereNull('title')
            ->update(['title' => 'Brew & Go moment']);

        Schema::table('carousel_slides', function (Blueprint $table) {
            $table->string('title', 120)->nullable(false)->change();
        });
    }
};
