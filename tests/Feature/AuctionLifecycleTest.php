<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Models\Bidding;
use App\Notifications\AuctionEndingSoonNotification;
use App\Notifications\AuctionWinnerNotification;
use App\Notifications\PaymentReminderNotification;
use App\Services\AuctionLifecycle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuctionLifecycleTest extends TestCase
{
    use DatabaseTransactions;

    private AuctionLifecycle $lifecycle;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->lifecycle = app(AuctionLifecycle::class);
    }

    public function test_statuses_follow_the_clock(): void
    {
        $shouldBeOpen = Auction::factory()->create(['status' => Auction::STATUS_UPCOMING]);
        $shouldBeEnded = Auction::factory()->ended()->create(['status' => Auction::STATUS_OPEN]);
        $shouldBeUpcoming = Auction::factory()->upcoming()->create(['status' => null]);

        $this->lifecycle->syncStatuses();

        $this->assertSame(Auction::STATUS_OPEN, $shouldBeOpen->fresh()->status);
        $this->assertSame(Auction::STATUS_ENDED, $shouldBeEnded->fresh()->status);
        $this->assertSame(Auction::STATUS_UPCOMING, $shouldBeUpcoming->fresh()->status);
    }

    public function test_the_highest_bid_wins_and_the_earliest_wins_a_tie(): void
    {
        $auction = Auction::factory()->ended()->create();
        $early = Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 3000, 'created_at' => now()->subHours(5)]);
        Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 3000, 'created_at' => now()->subHours(3)]);
        Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 2500]);

        $this->lifecycle->sync();

        $winner = $auction->fresh()->auction_winner;
        $this->assertSame($early->id, $winner->bidding_id);
        $this->assertSame(AuctionWinner::STATUS_AWAITING_PAYMENT, $winner->status);
        $this->assertTrue($winner->payment_due_at->between(now()->addDays(2)->subMinute(), now()->addDays(2)->addMinute()));
    }

    public function test_the_payment_window_comes_from_settings(): void
    {
        $this->setAuctionSettings(['payment_window_days' => 5]);
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id]);

        $winner = $this->lifecycle->selectWinner($auction);

        $this->assertTrue($winner->payment_due_at->isSameDay(now()->addDays(5)));
    }

    public function test_an_auction_without_bids_gets_no_winner(): void
    {
        $auction = Auction::factory()->ended()->create();

        $this->lifecycle->sync();

        $this->assertNull($auction->fresh()->auction_winner);
    }

    public function test_open_auctions_never_get_a_winner(): void
    {
        $auction = Auction::factory()->create();
        Bidding::factory()->create(['auction_id' => $auction->id]);

        $this->assertNull($this->lifecycle->selectWinner($auction));
    }

    public function test_winners_are_picked_only_once(): void
    {
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id]);

        $this->lifecycle->sync();
        $this->lifecycle->sync();

        $this->assertSame(1, $auction->winners()->count());
    }

    public function test_forfeiting_offers_the_item_to_the_next_highest_bidder(): void
    {
        $auction = Auction::factory()->ended()->create();
        $first = Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 5000]);
        $sameUserLower = Bidding::factory()->create(['auction_id' => $auction->id, 'user_id' => $first->user_id, 'bid_amount' => 4500]);
        $second = Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 4000]);

        $winner = $this->lifecycle->selectWinner($auction);
        $next = $this->lifecycle->forfeit($winner);

        $this->assertSame(AuctionWinner::STATUS_FORFEITED, $winner->fresh()->status);
        $this->assertNotNull($winner->fresh()->forfeited_at);
        $this->assertSame($second->id, $next->bidding_id);
        $this->assertNotSame($sameUserLower->id, $next->bidding_id);
        $this->assertSame($next->id, $auction->fresh()->auction_winner->id);
    }

    public function test_forfeiting_the_last_bidder_leaves_the_auction_unsold(): void
    {
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id]);
        $winner = $this->lifecycle->selectWinner($auction);

        $next = $this->lifecycle->forfeit($winner);

        $this->assertNull($next);
        $this->assertSame(AuctionWinner::STATUS_FORFEITED, $auction->fresh()->auction_winner->status);
    }

    public function test_overdue_winners_are_forfeited_automatically(): void
    {
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 5000]);
        $runnerUp = Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 4000]);
        $winner = $this->lifecycle->selectWinner($auction);
        $winner->update(['payment_due_at' => now()->subMinute()]);

        $this->assertSame(1, $this->lifecycle->forfeitOverdueWinners());

        $this->assertSame($runnerUp->id, $auction->fresh()->auction_winner->bidding_id);
    }

    public function test_overdue_winners_stay_when_auto_forfeit_is_off(): void
    {
        $this->setAuctionSettings(['auto_forfeit' => false]);
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id]);
        $winner = $this->lifecycle->selectWinner($auction);
        $winner->update(['payment_due_at' => now()->subMinute()]);

        $this->assertSame(0, $this->lifecycle->forfeitOverdueWinners());
        $this->assertSame(AuctionWinner::STATUS_AWAITING_PAYMENT, $winner->fresh()->status);
    }

    public function test_winners_without_a_due_date_are_never_auto_forfeited(): void
    {
        $auction = Auction::factory()->ended()->create();
        $bid = Bidding::factory()->create(['auction_id' => $auction->id]);
        $legacy = AuctionWinner::create(['auction_id' => $auction->id, 'bidding_id' => $bid->id]);

        $this->lifecycle->forfeitOverdueWinners();

        $this->assertSame(AuctionWinner::STATUS_AWAITING_PAYMENT, $legacy->fresh()->status);
    }

    public function test_winners_are_notified_automatically_when_enabled(): void
    {
        $this->setAuctionSettings(['notify_winner_automatically' => true]);
        $auction = Auction::factory()->ended()->create();
        $bid = Bidding::factory()->create(['auction_id' => $auction->id]);

        $winner = $this->lifecycle->selectWinner($auction);

        Notification::assertSentTo($bid->user, AuctionWinnerNotification::class);
        $this->assertNotNull($winner->fresh()->notified_at);
    }

    public function test_winners_are_not_notified_automatically_by_default(): void
    {
        $auction = Auction::factory()->ended()->create();
        $bid = Bidding::factory()->create(['auction_id' => $auction->id]);

        $this->lifecycle->selectWinner($auction);

        Notification::assertNotSentTo($bid->user, AuctionWinnerNotification::class);
    }

    public function test_payment_reminders_go_out_once_within_a_day_of_the_due_date(): void
    {
        $auction = Auction::factory()->ended()->create();
        $bid = Bidding::factory()->create(['auction_id' => $auction->id]);
        $winner = $this->lifecycle->selectWinner($auction);
        $winner->update(['payment_due_at' => now()->addHours(10)]);

        $this->assertSame(1, $this->lifecycle->sendPaymentReminders());
        $this->assertSame(0, $this->lifecycle->sendPaymentReminders());

        Notification::assertSentToTimes($bid->user, PaymentReminderNotification::class, 1);
    }

    public function test_ending_soon_notices_go_to_bidders_once(): void
    {
        $this->setAuctionSettings(['notify_ending_soon' => true, 'ending_soon_hours' => 6]);
        $auction = Auction::factory()->closingAt(now()->addHours(3))->create();
        $later = Auction::factory()->closingAt(now()->addDays(3))->create();
        $bid = Bidding::factory()->create(['auction_id' => $auction->id]);
        Bidding::factory()->create(['auction_id' => $later->id, 'user_id' => $bid->user_id]);

        $this->lifecycle->sendEndingSoonNotices();
        $this->lifecycle->sendEndingSoonNotices();

        Notification::assertSentToTimes($bid->user, AuctionEndingSoonNotification::class, 1);
        $this->assertNotNull($auction->fresh()->ending_soon_notified_at);
        $this->assertNull($later->fresh()->ending_soon_notified_at);
    }

    public function test_marking_paid_and_released_records_who_and_when(): void
    {
        $handler = $this->bidder();
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id]);
        $winner = $this->lifecycle->selectWinner($auction);

        $this->lifecycle->markPaid($winner, $handler, 'Cash');
        $this->assertSame(AuctionWinner::STATUS_PAID, $winner->fresh()->status);
        $this->assertSame('Cash', $winner->fresh()->payment_method);

        $this->lifecycle->markReleased($winner, $handler);
        $this->assertSame(AuctionWinner::STATUS_RELEASED, $winner->fresh()->status);
        $this->assertSame($handler->id, $winner->fresh()->handled_by);
        $this->assertNotNull($winner->fresh()->released_at);
    }

    public function test_the_sync_command_runs(): void
    {
        $auction = Auction::factory()->ended()->create(['status' => Auction::STATUS_OPEN]);

        $this->artisan('auctions:sync')->assertSuccessful();

        $this->assertSame(Auction::STATUS_ENDED, $auction->fresh()->status);
    }
}
