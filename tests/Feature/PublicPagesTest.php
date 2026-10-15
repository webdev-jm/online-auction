<?php

namespace Tests\Feature;

use App\Models\Auction;
use App\Models\Item;
use App\Models\ItemPicture;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_the_welcome_page_lists_open_auctions_even_when_an_item_has_no_photo(): void
    {
        $withoutPhoto = Auction::factory()->create(['item_id' => Item::factory()->create(['name' => 'Photo-less Monitor'])->id]);
        $withPhoto = Auction::factory()->create();
        ItemPicture::factory()->create(['item_id' => $withPhoto->item_id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Photo-less Monitor')
            ->assertSee($withPhoto->item->name)
            ->assertSee('oa-placeholder-img', false);
    }

    public function test_the_welcome_page_separates_upcoming_auctions(): void
    {
        $upcoming = Auction::factory()->upcoming()->create();

        $this->get('/')
            ->assertOk()
            ->assertSee('Starting soon')
            ->assertSee($upcoming->auction_code);
    }

    public function test_the_login_page_has_the_animated_scene_and_google_sign_in(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="oa-scene"', false)
            ->assertSee('Continue with Google')
            ->assertSee('Keep me signed in');
    }

    public function test_every_account_screen_renders_with_the_shared_layout(): void
    {
        foreach (['register', 'password.request'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('id="oa-scene"', false);
        }

        $this->get(route('password.reset', ['token' => 'abc']))->assertOk()->assertSee('Set a new password');
    }

    public function test_registering_gives_the_user_role_so_they_can_bid(): void
    {
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $this->post(route('register'), [
            'name' => 'New Bidder',
            'email' => 'new.bidder.'.uniqid().'@example.com',
            'password' => 'a-strong-password',
            'password_confirmation' => 'a-strong-password',
        ])->assertRedirect();

        $this->assertTrue(User::query()->where('name', 'New Bidder')->latest('id')->first()->hasRole('user'));
    }

    public function test_signing_in_restores_the_saved_dark_mode_choice(): void
    {
        $user = User::factory()->create(['dark_mode' => true]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'p4ssw0rd'])
            ->assertRedirect('/home');

        $this->assertTrue(session('adminlte_dark_mode'));
    }
}
