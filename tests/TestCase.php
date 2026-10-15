<?php

namespace Tests;

use App\Models\User;
use App\Services\AuctionSettings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * A user who may bid and has accepted the current terms.
     *
     * @param  array<string, mixed>  $attributes
     */
    protected function bidder(array $attributes = []): User
    {
        $user = User::factory()->create([
            'accepts_terms_and_conditions' => 1,
            'accepted_terms_version' => app(AuctionSettings::class)->get('terms_version'),
            ...$attributes,
        ]);

        return $this->grant($user, 'bidding access');
    }

    protected function grant(User $user, string ...$permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['module' => 'Tests', 'description' => $permission]
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        return $user->fresh();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function setAuctionSettings(array $values): void
    {
        app(AuctionSettings::class)->save($values);
    }
}
