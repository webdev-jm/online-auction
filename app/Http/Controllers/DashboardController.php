<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Models\Bidding;
use App\Services\AuctionLifecycle;
use Illuminate\Contracts\View\View;
use Spatie\Activitylog\Models\Activity;

class DashboardController extends Controller
{
    public function index(AuctionLifecycle $lifecycle): View
    {
        $lifecycle->syncIfDue();

        $openAuctions = Auction::query()->where('status', Auction::STATUS_OPEN)->with('item')->withCount('biddings')->get();

        $endingSoon = $openAuctions
            ->filter(fn (Auction $auction) => $auction->endsAt()->lte(now()->addDay()))
            ->sortBy(fn (Auction $auction) => $auction->endsAt()->timestamp)
            ->values();

        $awaitingPayment = AuctionWinner::query()
            ->where('status', AuctionWinner::STATUS_AWAITING_PAYMENT)
            ->whereHas('auction')
            ->with(['auction.item', 'bidding.user'])
            ->orderByRaw('payment_due_at IS NULL')
            ->orderBy('payment_due_at')
            ->get();

        return view('pages.dashboard', [
            'stats' => [
                'open' => $openAuctions->count(),
                'upcoming' => Auction::query()->where('status', Auction::STATUS_UPCOMING)->count(),
                'bidsToday' => Bidding::query()->whereDate('created_at', today())->count(),
                'endingSoon' => $endingSoon->count(),
                'awaitingPayment' => $awaitingPayment->count(),
                'overdue' => $awaitingPayment->filter(fn (AuctionWinner $winner) => $winner->isOverdue())->count(),
                'awaitingRelease' => AuctionWinner::query()->where('status', AuctionWinner::STATUS_PAID)->count(),
            ],
            'endingSoon' => $endingSoon->take(6),
            'awaitingPayment' => $awaitingPayment->take(8),
            'activity' => Activity::query()->with('causer')->latest()->limit(8)->get(),
        ]);
    }
}
