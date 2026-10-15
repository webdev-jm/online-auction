<?php

namespace App\Providers;

use App\Services\AuctionSettings;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\DarkModeWasToggled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AuctionSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        Event::listen(function (DarkModeWasToggled $event): void {
            auth()->user()?->update(['dark_mode' => $event->darkMode->isEnabled()]);
        });

        Event::listen(function (Login $event): void {
            session(['adminlte_dark_mode' => (bool) $event->user->dark_mode]);
        });
    }
}
