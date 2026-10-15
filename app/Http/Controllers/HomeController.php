<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Services\AuctionLifecycle;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * The bidder's home: terms, then auctions by status.
     */
    public function index(): View
    {
        return view('home');
    }

    public function welcome(AuctionLifecycle $lifecycle): View
    {
        $lifecycle->syncIfDue();

        $open = Auction::query()
            ->where('status', Auction::STATUS_OPEN)
            ->whereHas('item')
            ->with('item.pictures')
            ->orderBy('end')
            ->orderBy('end_time')
            ->limit(6)
            ->get();

        $upcoming = Auction::query()
            ->where('status', Auction::STATUS_UPCOMING)
            ->whereHas('item')
            ->with('item.pictures')
            ->orderBy('start')
            ->orderBy('start_time')
            ->limit(3)
            ->get();

        return view('welcome', [
            'open' => $open,
            'upcoming' => $upcoming,
        ]);
    }
}
