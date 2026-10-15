<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Bidding;
use App\Models\Company;
use App\Notifications\BidNotification;
use App\Notifications\OutbidNotification;
use App\Services\BiddingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BiddingRulesTest extends TestCase
{
    use DatabaseTransactions;

    private BiddingService $bidding;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->bidding = app(BiddingService::class);
    }

    public function test_a_valid_bid_on_an_open_auction_is_saved(): void
    {
        $auction = Auction::factory()->create(['min_bid' => 1000]);
        $user = $this->bidder();

        $bid = $this->bidding->place($auction, $user, 1500);

        $this->assertDatabaseHas('biddings', ['id' => $bid->id, 'auction_id' => $auction->id, 'user_id' => $user->id, 'bid_amount' => 1500]);
        Notification::assertSentTo($user, BidNotification::class);
    }

    public function test_bids_on_a_closed_auction_are_rejected_on_the_server(): void
    {
        $auction = Auction::factory()->ended()->create();

        $this->assertBidRejected($auction, 5000, 'closed');
    }

    public function test_bids_before_the_auction_opens_are_rejected(): void
    {
        $auction = Auction::factory()->upcoming()->create();

        $this->assertBidRejected($auction, 5000, 'opens');
    }

    public function test_company_only_auctions_reject_other_companies(): void
    {
        $auction = Auction::factory()->create(['restrict_to_company_only' => true]);
        $outsider = $this->bidder(['company_id' => Company::factory()->create()->id]);

        $this->assertBidRejected($auction, 5000, 'only open to', $outsider);
    }

    public function test_company_only_auctions_accept_their_own_company(): void
    {
        $auction = Auction::factory()->create(['restrict_to_company_only' => true]);
        $insider = $this->bidder(['company_id' => $auction->company_id]);

        $this->bidding->place($auction, $insider, 5000);

        $this->assertSame(1, $auction->biddings()->count());
    }

    public function test_the_bid_limit_is_enforced(): void
    {
        $auction = Auction::factory()->create(['bid_limit' => 1]);
        $user = $this->bidder();
        $this->bidding->place($auction, $user, 1200);

        $this->assertBidRejected($auction, 1500, 'already placed your bid', $user);
    }

    public function test_bids_below_the_minimum_are_rejected(): void
    {
        $auction = Auction::factory()->create(['min_bid' => 1000]);

        $this->assertBidRejected($auction, 999.99, 'at least 1,000.00');
    }

    public function test_bids_above_the_maximum_are_rejected(): void
    {
        $auction = Auction::factory()->create(['min_bid' => 1000, 'max_bid' => 3000]);

        $this->assertBidRejected($auction, 3000.01, 'cannot be more than 3,000.00');
    }

    public function test_non_numeric_amounts_are_rejected(): void
    {
        $auction = Auction::factory()->create();

        $this->assertBidRejected($auction, 'abc', 'greater than zero');
    }

    public function test_open_bidding_requires_beating_the_highest_bid_by_the_increment(): void
    {
        $auction = Auction::factory()->openBidding(250)->create(['min_bid' => 1000]);
        $this->bidding->place($auction, $this->bidder(), 2000);

        $this->assertBidRejected($auction, 2249, 'Bid at least 2,250.00');

        $this->bidding->place($auction, $this->bidder(), 2250);
        $this->assertSame(2, $auction->biddings()->count());
    }

    public function test_a_second_sealed_bid_must_be_higher_than_your_first(): void
    {
        $auction = Auction::factory()->create(['bid_limit' => 3]);
        $user = $this->bidder();
        $this->bidding->place($auction, $user, 2000);

        $this->assertBidRejected($auction, 2000, 'higher than your previous bid', $user);
    }

    public function test_users_who_have_not_accepted_the_current_terms_cannot_bid(): void
    {
        $auction = Auction::factory()->create();
        $user = $this->bidder(['accepted_terms_version' => 1]);
        $this->setAuctionSettings(['terms_version' => 2]);

        $this->assertBidRejected($auction, 5000, 'terms and conditions', $user->fresh());
    }

    public function test_users_without_bidding_access_cannot_bid(): void
    {
        $auction = Auction::factory()->create();
        $user = \App\Models\User::factory()->create(['accepts_terms_and_conditions' => 1, 'accepted_terms_version' => 1]);

        $this->assertBidRejected($auction, 5000, 'access to bidding', $user);
    }

    public function test_a_late_bid_extends_the_auction_when_anti_sniping_is_on(): void
    {
        $this->setAuctionSettings(['anti_sniping_enabled' => true, 'anti_sniping_minutes' => 5]);
        $auction = Auction::factory()->closingAt(now()->addMinutes(2))->create();

        $this->bidding->place($auction, $this->bidder(), 5000);

        $auction->refresh();
        $this->assertSame(1, $auction->extension_count);
        $this->assertTrue($auction->endsAt()->gte(now()->addMinutes(5)->subSeconds(2)));
    }

    public function test_a_late_bid_does_not_extend_when_anti_sniping_is_off(): void
    {
        $auction = Auction::factory()->closingAt(now()->addMinutes(2))->create();
        $originalEnd = $auction->endsAt();

        $this->bidding->place($auction, $this->bidder(), 5000);

        $this->assertTrue($auction->fresh()->endsAt()->eq($originalEnd));
        $this->assertSame(0, $auction->fresh()->extension_count);
    }

    public function test_the_previous_leader_is_told_they_were_outbid_in_open_bidding(): void
    {
        $auction = Auction::factory()->openBidding(100)->create();
        $first = $this->bidder();
        $second = $this->bidder();

        $this->bidding->place($auction, $first, 1500);
        $this->bidding->place($auction, $second, 1600);

        Notification::assertSentTo($first, OutbidNotification::class);
        Notification::assertNotSentTo($second, OutbidNotification::class);
    }

    public function test_sealed_bidding_never_sends_outbid_notices(): void
    {
        $auction = Auction::factory()->create();
        $first = $this->bidder();

        $this->bidding->place($auction, $first, 1500);
        $this->bidding->place($auction, $this->bidder(), 1600);

        Notification::assertNotSentTo($first, OutbidNotification::class);
    }

    public function test_bid_confirmations_can_be_turned_off(): void
    {
        $this->setAuctionSettings(['notify_bid_placed' => false]);
        $user = $this->bidder();

        $this->bidding->place(Auction::factory()->create(), $user, 1500);

        Notification::assertNotSentTo($user, BidNotification::class);
    }

    public function test_the_minimum_acceptable_amount_reflects_the_mode(): void
    {
        $user = $this->bidder();
        $open = Auction::factory()->openBidding(100)->create(['min_bid' => 1000]);
        Bidding::factory()->create(['auction_id' => $open->id, 'bid_amount' => 1800]);

        $this->assertSame(1900.0, $this->bidding->minimumAcceptable($open, $user));
        $this->assertSame(1000.0, $this->bidding->minimumAcceptable(Auction::factory()->create(['min_bid' => 1000]), $user));
    }

    private function assertBidRejected(Auction $auction, mixed $amount, string $expectedMessage, $user = null): void
    {
        try {
            $this->bidding->place($auction, $user ?? $this->bidder(), $amount);
            $this->fail('The bid should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString($expectedMessage, $exception->errors()['bid_amount'][0]);
        }
    }
}
