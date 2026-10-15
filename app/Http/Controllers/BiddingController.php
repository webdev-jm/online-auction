<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Auction;
use App\Models\Bidding;
use App\Services\AuctionLifecycle;
use App\Services\AuctionSettings;

class BiddingController extends Controller
{
    public function index($id, AuctionSettings $settings, AuctionLifecycle $lifecycle) {
        $auction = Auction::with(['item.pictures', 'item.specifications'])->findOrFail(decrypt($id));

        abort_unless($auction->isVisibleTo(auth()->user()) || auth()->user()->can('auction access'), 403);

        $lifecycle->syncIfDue();

        return view('pages.biddings.index')->with([
            'auction' => $auction,
            'item' => $auction->item,
            'termsHtml' => $settings->termsHtml(),
        ]);
    }

    public function list($id) {
        $auction = Auction::findOrFail(decrypt($id));

        abort_unless(auth()->user()->can('bidding list') || $auction->show_bidders, 403);

        $bidders = Bidding::with('user')
            ->orderBy('bid_amount', 'DESC')
            ->orderBy('created_at', 'ASC')
            ->where('auction_id', $auction->id)
            ->paginate(10)->appends(request()->query());

        return view('pages.biddings.list')->with([
            'auction' => $auction,
            'bidders' => $bidders
        ]);
    }

    public function my_bids() {
        $biddings = Bidding::with(['auction.item.pictures', 'auction.auction_winner'])
            ->orderBy('created_at', 'DESC')
            ->where('user_id', auth()->user()->id)
            ->paginate(10);

        return view('pages.biddings.mybid')->with([
            'biddings' => $biddings
        ]);
    }
}
