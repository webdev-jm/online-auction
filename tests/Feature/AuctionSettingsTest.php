<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\AuctionSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuctionSettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_defaults_come_from_config_when_nothing_is_saved(): void
    {
        Setting::query()->delete();
        $settings = app(AuctionSettings::class);
        $settings->flush();

        $this->assertSame('sealed', $settings->biddingMode());
        $this->assertSame(2, $settings->get('payment_window_days'));
        $this->assertSame('IT Department', $settings->get('pickup_location'));
        $this->assertTrue($settings->get('auto_forfeit'));
    }

    public function test_saved_values_are_cast_to_their_types(): void
    {
        $settings = app(AuctionSettings::class);
        $settings->save(['payment_window_days' => '7', 'auto_forfeit' => false, 'max_bid' => '', 'unknown_key' => 'x']);

        $this->assertSame(7, $settings->get('payment_window_days'));
        $this->assertFalse($settings->get('auto_forfeit'));
        $this->assertNull($settings->get('max_bid'));
        $this->assertDatabaseMissing('settings', ['key' => 'unknown_key']);
    }

    public function test_terms_fill_in_live_values_and_strip_html(): void
    {
        $this->setAuctionSettings([
            'pickup_location' => 'Admin Office',
            'terms_content' => "## Pickup\n\n- Collect at {pickup_location}.\n\n<script>alert(1)</script>",
        ]);

        $html = app(AuctionSettings::class)->termsHtml();

        $this->assertStringContainsString('Collect at Admin Office.', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_the_settings_page_needs_the_settings_permission(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.auction'))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->get(route('settings.auction'))
            ->assertOk()
            ->assertSee('Auction settings')
            ->assertSee('Default bidding mode');
    }

    public function test_an_admin_can_save_settings_and_unticked_switches_turn_off(): void
    {
        $this->setAuctionSettings(['auto_forfeit' => true]);

        $this->actingAs($this->admin())
            ->post(route('settings.auction.update'), $this->validPayload(['bidding_mode' => 'open', 'payment_window_days' => 3]))
            ->assertRedirect(route('settings.auction'))
            ->assertSessionHas('message_success');

        $settings = app(AuctionSettings::class);
        $settings->flush();
        $this->assertSame('open', $settings->biddingMode());
        $this->assertSame(3, $settings->get('payment_window_days'));
        $this->assertFalse($settings->get('auto_forfeit'));
        $this->assertTrue($settings->get('notify_bid_placed'));
    }

    public function test_invalid_settings_are_rejected_with_readable_messages(): void
    {
        $this->actingAs($this->admin())
            ->post(route('settings.auction.update'), $this->validPayload([
                'bidding_mode' => 'auctioneer',
                'auction_code_prefix' => 'AU CT!',
                'payment_window_days' => 99,
            ]))
            ->assertSessionHasErrors([
                'bidding_mode' => 'Choose sealed or open bidding.',
                'auction_code_prefix' => 'Use only letters, numbers, dashes and underscores in the code prefix.',
                'payment_window_days' => 'The payment window can be at most 60 days.',
            ]);
    }

    public function test_publishing_new_terms_asks_bidders_to_accept_again(): void
    {
        $bidder = $this->bidder();
        $this->assertTrue($bidder->hasAcceptedCurrentTerms());
        $version = app(AuctionSettings::class)->get('terms_version');

        $this->actingAs($this->admin())
            ->post(route('settings.auction.update'), $this->validPayload(['publish_new_terms' => 1]))
            ->assertRedirect();

        app(AuctionSettings::class)->flush();
        $this->assertSame($version + 1, app(AuctionSettings::class)->get('terms_version'));
        $this->assertFalse($bidder->fresh()->hasAcceptedCurrentTerms());
    }

    public function test_saving_without_publishing_keeps_the_terms_version(): void
    {
        $version = app(AuctionSettings::class)->get('terms_version');

        $this->actingAs($this->admin())->post(route('settings.auction.update'), $this->validPayload());

        app(AuctionSettings::class)->flush();
        $this->assertSame($version, app(AuctionSettings::class)->get('terms_version'));
    }

    private function admin(): User
    {
        return $this->grant(User::factory()->create(), 'settings manage');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'bidding_mode' => 'sealed',
            'max_bids_per_user' => 1,
            'min_increment' => 100,
            'max_bid' => '',
            'anti_sniping_minutes' => 5,
            'auction_code_prefix' => 'AUCT-',
            'auction_code_digits' => 3,
            'payment_window_days' => 2,
            'payment_methods' => 'Cash',
            'pickup_location' => 'IT Department',
            'ending_soon_hours' => 24,
            'terms_content' => '## Terms',
            'notify_bid_placed' => 1,
            ...$overrides,
        ];
    }
}
