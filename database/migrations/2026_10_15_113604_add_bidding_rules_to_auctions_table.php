<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->string('bidding_mode')->nullable()->after('bid_limit');
            $table->decimal('min_increment', 10, 2)->nullable()->after('bidding_mode');
            $table->decimal('max_bid', 10, 2)->nullable()->after('min_increment');
            $table->unsignedInteger('extension_count')->default(0)->after('end_time');
            $table->timestamp('ending_soon_notified_at')->nullable()->after('extension_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table) {
            $table->dropColumn(['bidding_mode', 'min_increment', 'max_bid', 'extension_count', 'ending_soon_notified_at']);
        });
    }
};
