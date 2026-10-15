<?php

namespace Tests\Feature;

use App\Livewire\Biddings\Bid;
use App\Livewire\TermsAgreement;
use App\Models\Auction;
use App\Models\Bidding;
use App\Models\Company;
use App\Models\Item;
use App\Models\User;
use App\Services\AuctionSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class BidderPagesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_bidders_see_the_terms_until_they_accept_the_current_version(): void
    {
        $user = $this->bidder(['accepts_terms_and_conditions' => 0, 'accepted_terms_version' => null]);

        Livewire::actingAs($user)
            ->test(TermsAgreement::class)
            ->assertSee('Terms and conditions')
            ->assertSee('I accept the terms')
            ->call('accept')
            ->assertSee('Find something to bid on');

        $this->assertSame(app(AuctionSettings::class)->get('terms_version'), $user->fresh()->accepted_terms_version);
    }

    public function test_declining_shows_how_to_come_back(): void
    {
        $user = $this->bidder(['accepts_terms_and_conditions' => 0, 'accepted_terms_version' => null]);

        Livewire::actingAs($user)
            ->test(TermsAgreement::class)
            ->call('decline')
            ->assertSee('You declined the terms.')
            ->call('readAgain')
            ->assertSee('I accept the terms');
    }

    public function test_searching_does_not_bring_back_old_auctions(): void
    {
        $item = Item::factory()->create(['name' => 'Searchable Laptop']);
        $current = Auction::factory()->create(['item_id' => $item->id]);
        $old = Auction::factory()->create([
            'item_id' => $item->id,
            'status' => Auction::STATUS_ENDED,
            'start' => now()->subMonths(3)->toDateString(),
            'end' => now()->subMonths(2)->toDateString(),
        ]);

        Livewire::actingAs($this->bidder())
            ->test(TermsAgreement::class)
            ->set('tab', 'ended')
            ->set('search', 'Searchable')
            ->assertDontSee($old->auction_code)
            ->set('tab', 'open')
            ->assertSee($current->auction_code);
    }

    public function test_company_only_auctions_are_hidden_from_other_companies(): void
    {
        $hidden = Auction::factory()->create(['restrict_to_company_only' => true]);
        $visible = Auction::factory()->create();

        Livewire::actingAs($this->bidder(['company_id' => Company::factory()->create()->id]))
            ->test(TermsAgreement::class)
            ->assertSee($visible->auction_code)
            ->assertDontSee($hidden->auction_code);
    }

    public function test_tabs_split_auctions_by_status(): void
    {
        $open = Auction::factory()->create();
        $upcoming = Auction::factory()->upcoming()->create();

        Livewire::actingAs($this->bidder())
            ->test(TermsAgreement::class)
            ->assertSee($open->auction_code)
            ->assertDontSee($upcoming->auction_code)
            ->call('setTab', 'upcoming')
            ->assertSee($upcoming->auction_code)
            ->assertDontSee($open->auction_code);
    }

    public function test_the_bidding_page_shows_item_details_and_the_mechanics(): void
    {
        $auction = Auction::factory()->create();

        $this->actingAs($this->bidder())
            ->get(route('bidding.index', encrypt($auction->id)))
            ->assertOk()
            ->assertSee($auction->item->name)
            ->assertSee('Review bid')
            ->assertSee('Auction mechanics')
            ->assertSee('Sealed bidding');
    }

    public function test_other_companies_cannot_open_a_company_only_auction(): void
    {
        $auction = Auction::factory()->create(['restrict_to_company_only' => true]);

        $this->actingAs($this->bidder(['company_id' => Company::factory()->create()->id]))
            ->get(route('bidding.index', encrypt($auction->id)))
            ->assertForbidden();
    }

    public function test_placing_a_bid_takes_a_review_then_a_confirmation(): void
    {
        $auction = Auction::factory()->create(['min_bid' => 1000]);
        $user = $this->bidder();

        Livewire::actingAs($user)
            ->test(Bid::class, ['auction' => $auction])
            ->set('bid_amount', '1500')
            ->call('review')
            ->assertSet('confirming', true)
            ->assertSee('Confirm your bid of 1,500.00')
            ->call('placeBid')
            ->assertHasNoErrors()
            ->assertSet('confirming', false)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('biddings', ['auction_id' => $auction->id, 'user_id' => $user->id, 'bid_amount' => 1500]);
    }

    public function test_review_rejects_a_low_bid_without_saving(): void
    {
        $auction = Auction::factory()->create(['min_bid' => 1000]);

        Livewire::actingAs($this->bidder())
            ->test(Bid::class, ['auction' => $auction])
            ->set('bid_amount', '500')
            ->call('review')
            ->assertHasErrors('bid_amount')
            ->assertSet('confirming', false);

        $this->assertSame(0, $auction->biddings()->count());
    }

    public function test_calling_place_bid_directly_on_a_closed_auction_is_refused(): void
    {
        $auction = Auction::factory()->ended()->create();

        Livewire::actingAs($this->bidder())
            ->test(Bid::class, ['auction' => $auction])
            ->set('bid_amount', '5000')
            ->call('placeBid')
            ->assertHasErrors('bid_amount');

        $this->assertSame(0, $auction->biddings()->count());
    }

    public function test_open_bidding_shows_the_highest_bid_and_who_leads(): void
    {
        $auction = Auction::factory()->openBidding(100)->create();
        $user = $this->bidder();
        Bidding::factory()->create(['auction_id' => $auction->id, 'user_id' => $user->id, 'bid_amount' => 2000]);
        Bidding::factory()->create(['auction_id' => $auction->id, 'bid_amount' => 2500]);

        Livewire::actingAs($user)
            ->test(Bid::class, ['auction' => $auction])
            ->assertSee('2,500.00')
            ->assertSee('outbid');
    }

    public function test_my_bids_lists_results(): void
    {
        $user = $this->bidder();
        $auction = Auction::factory()->create();
        Bidding::factory()->create(['auction_id' => $auction->id, 'user_id' => $user->id, 'bid_amount' => 1234]);

        $this->actingAs($user)
            ->get(route('bidding.mybid'))
            ->assertOk()
            ->assertSee($auction->auction_code)
            ->assertSee('1,234.00');
    }

    public function test_the_bidders_list_needs_permission_or_a_public_auction(): void
    {
        $private = Auction::factory()->create(['show_bidders' => false]);
        $public = Auction::factory()->create(['show_bidders' => true]);
        $user = $this->bidder();

        $this->actingAs($user)->get(route('bidding.list', encrypt($private->id)))->assertForbidden();
        $this->actingAs($user)->get(route('bidding.list', encrypt($public->id)))->assertOk();
    }

    public function test_users_without_bidding_access_cannot_reach_bidding_pages(): void
    {
        $auction = Auction::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('bidding.index', encrypt($auction->id)))
            ->assertForbidden();
    }
}
