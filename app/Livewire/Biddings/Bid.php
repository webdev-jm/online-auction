<?php

namespace App\Livewire\Biddings;

use App\Models\Auction;
use App\Models\AuctionWinner;
use App\Models\Bidding;
use App\Services\AuctionSettings;
use App\Services\BiddingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Bid extends Component
{
    public Auction $auction;

    public $bid_amount = '';

    public bool $confirming = false;

    public function mount(Auction $auction): void
    {
        $this->auction = $auction;
    }

    /**
     * First step: check the amount and show the confirmation.
     */
    public function review(BiddingService $bidding): void
    {
        $this->resetErrorBag();
        $user = auth()->user();
        $error = $bidding->denialReason($this->auction, $user)
            ?? $bidding->amountError($this->auction, $user, $this->bid_amount);

        if ($error !== null) {
            $this->addError('bid_amount', $error);
            $this->confirming = false;

            return;
        }

        $this->bid_amount = number_format((float) $this->bid_amount, 2, '.', '');
        $this->confirming = true;
    }

    public function cancelReview(): void
    {
        $this->confirming = false;
    }

    /**
     * Second step: save the bid. Every rule is checked again on the server inside BiddingService.
     */
    public function placeBid(BiddingService $bidding): void
    {
        $this->confirming = false;

        $placed = $bidding->place($this->auction, auth()->user(), $this->bid_amount);

        $this->auction->refresh();
        $this->bid_amount = '';

        $this->dispatch('toast', type: 'success', message: 'Your bid of '.number_format((float) $placed->bid_amount, 2).' was placed.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $bidding = app(BiddingService::class);
        $settings = app(AuctionSettings::class);

        $this->auction->refresh();
        $status = $this->auction->currentStatus();
        $isOpenMode = $this->auction->isOpenBidding();

        $myBids = Bidding::query()
            ->where('auction_id', $this->auction->id)
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $highestBid = $this->auction->highestBid();
        $canSeeLeader = $user->can('bidding leader') || $this->auction->show_leading_bidder;

        $lowestBidder = null;
        if ($this->auction->show_last_place_bidder) {
            $lowestBidder = Bidding::query()
                ->selectRaw('MAX(bid_amount) as bid_amount, user_id')
                ->where('auction_id', $this->auction->id)
                ->groupBy('user_id')
                ->orderBy('bid_amount')
                ->with('user')
                ->first();
        }

        $winner = $status === Auction::STATUS_ENDED ? $this->auction->auction_winner : null;

        return view('livewire.biddings.bid', [
            'status' => $status,
            'isOpenMode' => $isOpenMode,
            'denialReason' => $bidding->denialReason($this->auction, $user),
            'minimumAcceptable' => $bidding->minimumAcceptable($this->auction, $user),
            'myBids' => $myBids,
            'highestBid' => $highestBid,
            'canSeeLeader' => $canSeeLeader,
            'lowestBidder' => $lowestBidder,
            'winner' => $winner,
            'iWon' => $winner !== null
                && $winner->bidding?->user_id === $user->id
                && $winner->status !== AuctionWinner::STATUS_FORFEITED,
            'maxBid' => $this->auction->maxBid(),
            'increment' => $this->auction->minIncrement(),
            'antiSniping' => $settings->get('anti_sniping_enabled') ? (int) $settings->get('anti_sniping_minutes') : null,
            'paymentMethods' => $settings->get('payment_methods'),
            'pickupLocation' => $settings->get('pickup_location'),
        ]);
    }
}
