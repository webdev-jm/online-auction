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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('accepted_terms_version')->nullable()->after('accepts_terms_and_conditions');
            $table->boolean('dark_mode')->default(false)->after('accepted_terms_version');
        });

        DB::table('users')
            ->where('accepts_terms_and_conditions', 1)
            ->update(['accepted_terms_version' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accepted_terms_version', 'dark_mode']);
        });
    }
};
