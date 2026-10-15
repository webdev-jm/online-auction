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
        if (Schema::hasTable('auction_winners')) {
            return;
        }

        Schema::create('auction_winners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auction_id')->nullable();
            $table->unsignedBigInteger('bidding_id')->nullable();
            $table->timestamps();

            $table->foreign('auction_id')
                ->references('id')->on('auctions')
                ->onDelete('cascade');

            $table->foreign('bidding_id')
                ->references('id')->on('biddings')
                ->onDelete('cascade');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_winners');
    }
};
