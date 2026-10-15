<?php

namespace App\Console\Commands;

use App\Services\AuctionLifecycle;
use Illuminate\Console\Command;

class SyncAuctions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auctions:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Open and close auctions, pick winners, send reminders and forfeit unpaid wins';

    /**
     * Execute the console command.
     */
    public function handle(AuctionLifecycle $lifecycle): int
    {
        $summary = $lifecycle->sync();

        $this->components->info(sprintf(
            '%d status changes, %d winners picked, %d ending-soon notices, %d payment reminders, %d forfeited.',
            $summary['statuses'],
            $summary['winners'],
            $summary['ending_soon'],
            $summary['reminders'],
            $summary['forfeited'],
        ));

        return self::SUCCESS;
    }
}
