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
        Schema::table('auction_winners', function (Blueprint $table) {
            $table->string('status')->default('awaiting_payment')->after('bidding_id');
            $table->string('payment_method')->nullable()->after('status');
            $table->timestamp('payment_due_at')->nullable()->after('payment_method');
            $table->timestamp('notified_at')->nullable()->after('payment_due_at');
            $table->timestamp('reminder_sent_at')->nullable()->after('notified_at');
            $table->timestamp('paid_at')->nullable()->after('reminder_sent_at');
            $table->timestamp('released_at')->nullable()->after('paid_at');
            $table->timestamp('forfeited_at')->nullable()->after('released_at');
            $table->unsignedBigInteger('handled_by')->nullable()->after('forfeited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_winners', function (Blueprint $table) {
            $table->dropColumn([
                'status', 'payment_method', 'payment_due_at', 'notified_at', 'reminder_sent_at',
                'paid_at', 'released_at', 'forfeited_at', 'handled_by',
            ]);
        });
    }
};
