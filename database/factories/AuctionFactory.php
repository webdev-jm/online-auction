<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\Company;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Auction>
 */
class AuctionFactory extends Factory
{
    /**
     * Define the model's default state: open now, closing tomorrow.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'item_id' => Item::factory(),
            'company_id' => Company::factory(),
            'auction_code' => 'TEST-'.fake()->unique()->numerify('######'),
            'status' => Auction::STATUS_OPEN,
            ...$this->window(now()->subHour(), now()->addDay()),
            'min_bid' => 1000,
            'bid_limit' => null,
            'bidding_mode' => Auction::MODE_SEALED,
            'min_increment' => null,
            'max_bid' => null,
            'show_bidders' => false,
            'show_leading_bidder' => false,
            'show_last_place_bidder' => false,
            'restrict_to_company_only' => false,
        ];
    }

    public function upcoming(): static
    {
        return $this->state(fn () => [
            'status' => Auction::STATUS_UPCOMING,
            ...$this->window(now()->addDay(), now()->addDays(2)),
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'status' => Auction::STATUS_ENDED,
            ...$this->window(now()->subDays(2), now()->subHour()),
        ]);
    }

    public function openBidding(float $increment = 100): static
    {
        return $this->state(fn () => [
            'bidding_mode' => Auction::MODE_OPEN,
            'min_increment' => $increment,
        ]);
    }

    public function closingAt(Carbon $end): static
    {
        return $this->state(fn () => $this->window(now()->subHour(), $end));
    }

    /**
     * @return array{start: string, start_time: string, end: string, end_time: string}
     */
    private function window(Carbon $start, Carbon $end): array
    {
        return [
            'start' => $start->toDateString(),
            'start_time' => $start->format('H:i:s'),
            'end' => $end->toDateString(),
            'end_time' => $end->format('H:i:s'),
        ];
    }
}
