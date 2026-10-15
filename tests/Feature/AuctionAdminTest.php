<?php

namespace Tests\Feature;

use App\Livewire\Auctions\WinnerPanel;
use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Models\Bidding;
use App\Models\Company;
use App\Models\Item;
use App\Models\User;
use App\Notifications\AuctionWinnerNotification;
use App\Services\AuctionLifecycle;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AuctionAdminTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_new_auctions_use_the_configured_code_format_and_defaults(): void
    {
        $this->setAuctionSettings([
            'auction_code_prefix' => 'ZZQ-',
            'auction_code_digits' => 5,
            'default_show_bidders' => true,
        ]);

        $response = $this->actingAs($this->manager())
            ->get(route('auction.create'))
            ->assertOk()
            ->assertSee('ZZQ-00001');

        $this->assertMatchesRegularExpression('/<input[^>]*name="show_bidders"[^>]*checked/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression('/<input[^>]*name="show_leading_bidder"[^>]*checked/', $response->getContent());
    }

    public function test_codes_continue_from_the_highest_number_including_deleted_auctions(): void
    {
        $this->setAuctionSettings(['auction_code_prefix' => 'ZZR-', 'auction_code_digits' => 3]);
        Auction::factory()->create(['auction_code' => 'ZZR-009']);
        Auction::factory()->create(['auction_code' => 'ZZR-010'])->delete();

        $this->actingAs($this->manager())
            ->get(route('auction.create'))
            ->assertSee('ZZR-011');
    }

    public function test_an_auction_saves_its_own_bidding_rules(): void
    {
        $item = Item::factory()->create();
        $company = Company::factory()->create();

        $this->actingAs($this->manager())
            ->post(route('auction.store'), [
                'item_id' => encrypt($item->id),
                'company_id' => encrypt($company->id),
                'start' => now()->toDateString(),
                'start_time' => '08:00',
                'end' => now()->addDays(3)->toDateString(),
                'end_time' => '17:00',
                'min_bid' => 1000,
                'bidding_mode' => 'open',
                'min_increment' => 50,
                'max_bid' => 9000,
                'user_bidding_limit' => 1,
                'bid_limit' => 3,
                'show_leading_bidder' => 1,
            ])
            ->assertRedirect(route('auction.index'));

        $auction = Auction::query()->where('item_id', $item->id)->firstOrFail();
        $this->assertSame('open', $auction->bidding_mode);
        $this->assertEquals(50, $auction->min_increment);
        $this->assertEquals(9000, $auction->max_bid);
        $this->assertSame(3, (int) $auction->bid_limit);
        $this->assertTrue((bool) $auction->show_leading_bidder);
        $this->assertFalse((bool) $auction->show_bidders);
    }

    public function test_a_maximum_bid_below_the_minimum_is_rejected(): void
    {
        $this->actingAs($this->manager())
            ->post(route('auction.store'), [
                'item_id' => encrypt(Item::factory()->create()->id),
                'company_id' => encrypt(Company::factory()->create()->id),
                'start' => now()->toDateString(),
                'start_time' => '08:00',
                'end' => now()->addDay()->toDateString(),
                'end_time' => '17:00',
                'min_bid' => 1000,
                'max_bid' => 500,
            ])
            ->assertSessionHasErrors(['max_bid' => 'The maximum bid must be at least the minimum bid.']);
    }

    public function test_the_edit_form_preselects_the_item_and_company(): void
    {
        $auction = Auction::factory()->create();

        $response = $this->actingAs($this->manager())->get(route('auction.edit', encrypt($auction->id)))->assertOk();

        $this->assertMatchesRegularExpression('/<option value="[^"]+" selected="selected">\['.preg_quote($auction->item->item_number, '/').'\]/', $response->getContent());
    }

    public function test_the_auction_list_filters_by_status(): void
    {
        $open = Auction::factory()->create();
        $ended = Auction::factory()->ended()->create();

        $this->actingAs($this->manager())
            ->get(route('auction.index', ['status' => 'ENDED', 'search' => $ended->auction_code]))
            ->assertOk()
            ->assertSee($ended->auction_code)
            ->assertDontSee($open->auction_code);
    }

    public function test_the_auction_page_shows_bids_and_the_winner_panel(): void
    {
        $auction = Auction::factory()->ended()->create();
        $bid = Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 4321]);
        app(AuctionLifecycle::class)->selectWinner($auction);

        $this->actingAs($this->manager())
            ->get(route('auction.show', encrypt($auction->id)))
            ->assertOk()
            ->assertSee('4,321.00')
            ->assertSee($bid->user->name)
            ->assertSee('Awaiting payment');
    }

    public function test_winner_staff_can_record_payment_and_release(): void
    {
        [$auction, $winner] = $this->auctionWithWinner();
        $staff = $this->grant($this->manager(), 'auction winner manage');

        Livewire::actingAs($staff)
            ->test(WinnerPanel::class, ['auction' => $auction])
            ->set('payment_method', 'Cash')
            ->call('markPaid')
            ->assertDispatched('toast')
            ->call('markReleased');

        $this->assertSame(AuctionWinner::STATUS_RELEASED, $winner->fresh()->status);
        $this->assertSame('Cash', $winner->fresh()->payment_method);
    }

    public function test_winner_staff_can_forfeit_to_the_next_bidder(): void
    {
        [$auction, $winner, $runnerUp] = $this->auctionWithWinner();

        Livewire::actingAs($this->grant($this->manager(), 'auction winner manage'))
            ->test(WinnerPanel::class, ['auction' => $auction])
            ->call('forfeit');

        $this->assertSame(AuctionWinner::STATUS_FORFEITED, $winner->fresh()->status);
        $this->assertSame($runnerUp->id, $auction->fresh()->auction_winner->bidding_id);
    }

    public function test_winner_actions_need_the_winner_permission(): void
    {
        [$auction, $winner] = $this->auctionWithWinner();

        Livewire::actingAs($this->manager())
            ->test(WinnerPanel::class, ['auction' => $auction])
            ->call('markPaid')
            ->assertForbidden();

        $this->assertSame(AuctionWinner::STATUS_AWAITING_PAYMENT, $winner->fresh()->status);
    }

    public function test_the_send_notification_button_emails_the_winner(): void
    {
        [$auction, $winner] = $this->auctionWithWinner();
        $staff = $this->grant($this->manager(), 'auction print');

        $this->actingAs($staff)
            ->get(route('auction.notification', encrypt($auction->id)))
            ->assertSessionHas('message_success');

        Notification::assertSentTo($winner->bidding->user, AuctionWinnerNotification::class);
        $this->assertNotNull($winner->fresh()->notified_at);
    }

    public function test_the_dashboard_counts_what_needs_attention(): void
    {
        Auction::factory()->closingAt(now()->addHours(2))->create();
        [, $winner] = $this->auctionWithWinner();
        $winner->update(['payment_due_at' => now()->subHour()]);

        $this->actingAs($this->manager())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Closing within 24h')
            ->assertSee('Winners awaiting payment')
            ->assertSee($winner->auction->auction_code);
    }

    public function test_the_dashboard_needs_auction_access(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertForbidden();
    }

    private function manager(): User
    {
        return $this->grant(User::factory()->create(), 'auction access', 'auction create', 'auction edit');
    }

    /**
     * @return array{0: Auction, 1: AuctionWinner, 2: Bidding}
     */
    private function auctionWithWinner(): array
    {
        $auction = Auction::factory()->ended()->create();
        Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 5000]);
        $runnerUp = Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 4000]);
        $winner = app(AuctionLifecycle::class)->selectWinner($auction);

        return [$auction, $winner, $runnerUp];
    }
}
