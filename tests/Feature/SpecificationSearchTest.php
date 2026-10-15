<?php

namespace Tests\Feature;

use App\Livewire\TermsAgreement;
use App\Models\Auction;
use App\Models\Item;
use App\Models\ItemSpecification;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class SpecificationSearchTest extends TestCase
{
    use DatabaseTransactions;

    private Auction $match;

    private Auction $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->match = Auction::factory()->create();
        $this->other = Auction::factory()->create();

        ItemSpecification::create(['item_id' => $this->match->item_id, 'specification' => 'Processor', 'value' => 'Core i7-1165G7']);
        ItemSpecification::create(['item_id' => $this->match->item_id, 'specification' => 'Battery health', 'value' => 'Good']);
        ItemSpecification::create(['item_id' => $this->other->item_id, 'specification' => 'Processor', 'value' => 'Ryzen 5']);
    }

    public function test_bidders_can_find_auctions_by_specification_value(): void
    {
        Livewire::actingAs($this->bidder())
            ->test(TermsAgreement::class)
            ->set('search', 'i7-1165')
            ->assertSee($this->match->auction_code)
            ->assertDontSee($this->other->auction_code);
    }

    public function test_bidders_can_find_auctions_by_specification_name(): void
    {
        Livewire::actingAs($this->bidder())
            ->test(TermsAgreement::class)
            ->set('search', 'Battery health')
            ->assertSee($this->match->auction_code)
            ->assertDontSee($this->other->auction_code);
    }

    public function test_the_admin_auction_list_searches_specifications(): void
    {
        $this->actingAs($this->grant(User::factory()->create(), 'auction access'))
            ->get(route('auction.index', ['search' => 'i7-1165']))
            ->assertOk()
            ->assertSee($this->match->auction_code)
            ->assertDontSee($this->other->auction_code);
    }

    public function test_the_item_list_searches_specification_names_and_values(): void
    {
        $user = $this->grant(User::factory()->create(), 'item access');
        $matchItem = Item::find($this->match->item_id);
        $otherItem = Item::find($this->other->item_id);

        foreach (['Battery health', 'i7-1165'] as $term) {
            $this->actingAs($user)
                ->get(route('item.index', ['search' => $term]))
                ->assertOk()
                ->assertSee($matchItem->item_number)
                ->assertDontSee($otherItem->item_number);
        }
    }
}
