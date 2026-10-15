<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\Bidding;
use App\Models\User;
use App\Notifications\BidNotification;
use App\Notifications\OutbidNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BiddingService
{
    public function __construct(private AuctionSettings $settings)
    {
    }

    /**
     * Why this user cannot bid on this auction right now, or null when they can.
     */
    public function denialReason(Auction $auction, User $user): ?string
    {
        if (! $user->can('bidding access')) {
            return 'Your account does not have access to bidding.';
        }

        if (! $user->hasAcceptedCurrentTerms()) {
            return 'Accept the terms and conditions before bidding.';
        }

        $status = $auction->currentStatus();

        if ($status === Auction::STATUS_UPCOMING) {
            return 'Bidding opens '.$auction->startsAt()->format('M j, Y g:i A').'.';
        }

        if ($status === Auction::STATUS_ENDED) {
            return 'Bidding for this auction has closed.';
        }

        if (! $auction->isVisibleTo($user)) {
            return 'This auction is only open to '.($auction->company?->name ?? 'one company').' employees.';
        }

        if (! empty($auction->bid_limit) && $this->bidCount($auction, $user) >= (int) $auction->bid_limit) {
            return $auction->bid_limit == 1
                ? 'You have already placed your bid on this auction.'
                : 'You have used all '.$auction->bid_limit.' of your bids on this auction.';
        }

        return null;
    }

    /**
     * Why this amount is not accepted, or null when it is.
     */
    public function amountError(Auction $auction, User $user, mixed $amount): ?string
    {
        if (! is_numeric($amount) || (float) $amount <= 0) {
            return 'Enter a bid amount greater than zero.';
        }

        $amount = round((float) $amount, 2);

        if ($amount < (float) $auction->min_bid) {
            return 'Your bid must be at least '.number_format((float) $auction->min_bid, 2).'.';
        }

        $maxBid = $auction->maxBid();
        if ($maxBid !== null && $amount > $maxBid) {
            return 'Bids on this auction cannot be more than '.number_format($maxBid, 2).'.';
        }

        if ($auction->isOpenBidding()) {
            $highest = $auction->highestBid();
            if ($highest !== null) {
                $required = round((float) $highest->bid_amount + $auction->minIncrement(), 2);
                if ($amount < $required) {
                    return 'Bid at least '.number_format($required, 2).'. That is the current highest bid plus the minimum increment of '.number_format($auction->minIncrement(), 2).'.';
                }
            }
        }

        $ownHighest = $this->ownHighestAmount($auction, $user);
        if ($ownHighest !== null && $amount <= $ownHighest) {
            return 'Your new bid must be higher than your previous bid of '.number_format($ownHighest, 2).'.';
        }

        return null;
    }

    /**
     * The lowest amount this user may bid right now.
     */
    public function minimumAcceptable(Auction $auction, User $user): float
    {
        $minimum = (float) $auction->min_bid;

        if ($auction->isOpenBidding() && ($highest = $auction->highestBid()) !== null) {
            $minimum = max($minimum, (float) $highest->bid_amount + $auction->minIncrement());
        }

        $ownHighest = $this->ownHighestAmount($auction, $user);
        if ($ownHighest !== null) {
            $minimum = max($minimum, $ownHighest + 0.01);
        }

        return round($minimum, 2);
    }

    /**
     * Check every rule again under a row lock, save the bid, then extend and notify.
     *
     * @throws ValidationException
     */
    public function place(Auction $auction, User $user, mixed $amount): Bidding
    {
        [$bidding, $previousLeader, $auction] = DB::transaction(function () use ($auction, $user, $amount): array {
            $locked = Auction::query()->whereKey($auction->getKey())->lockForUpdate()->firstOrFail();

            $error = $this->denialReason($locked, $user) ?? $this->amountError($locked, $user, $amount);
            if ($error !== null) {
                throw ValidationException::withMessages(['bid_amount' => $error]);
            }

            $previousLeader = $locked->highestBid();

            $bidding = Bidding::query()->create([
                'auction_id' => $locked->id,
                'user_id' => $user->id,
                'bid_amount' => round((float) $amount, 2),
            ]);

            $this->extendIfSniped($locked);

            return [$bidding, $previousLeader, $locked];
        });

        activity('bid')
            ->causedBy($user)
            ->performedOn($bidding)
            ->log(':causer.name placed a '.number_format((float) $bidding->bid_amount, 2).' bid on '.$auction->auction_code);

        $this->notifyAfterBid($auction, $user, $bidding, $previousLeader);

        return $bidding;
    }

    public function bidCount(Auction $auction, User $user): int
    {
        return $auction->biddings()->where('user_id', $user->id)->count();
    }

    public function ownHighestAmount(Auction $auction, User $user): ?float
    {
        $amount = $auction->biddings()->where('user_id', $user->id)->max('bid_amount');

        return $amount === null ? null : (float) $amount;
    }

    /**
     * A bid in the closing minutes pushes the end time out so others can answer it.
     */
    private function extendIfSniped(Auction $auction): void
    {
        if (! $this->settings->get('anti_sniping_enabled')) {
            return;
        }

        $minutes = max(1, (int) $this->settings->get('anti_sniping_minutes'));
        $extendedEnd = now()->addMinutes($minutes);

        if ($auction->endsAt()->gte($extendedEnd)) {
            return;
        }

        $auction->update([
            'end' => $extendedEnd->toDateString(),
            'end_time' => $extendedEnd->format('H:i:s'),
            'extension_count' => $auction->extension_count + 1,
        ]);
    }

    private function notifyAfterBid(Auction $auction, User $user, Bidding $bidding, ?Bidding $previousLeader): void
    {
        if ($this->settings->get('notify_bid_placed')) {
            rescue(function () use ($user, $bidding): void {
                $user->notify(new BidNotification($bidding));
            }, report: false);
        }

        if (
            $this->settings->get('notify_outbid')
            && $auction->isOpenBidding()
            && $previousLeader !== null
            && $previousLeader->user_id !== $user->id
            && $previousLeader->user !== null
        ) {
            rescue(function () use ($previousLeader, $auction, $bidding): void {
                $previousLeader->user->notify(new OutbidNotification($auction, (float) $bidding->bid_amount));
            }, report: false);
        }
    }
}
