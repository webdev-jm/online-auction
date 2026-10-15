<div class="oa-bid-panel" @if($status !== \App\Models\Auction::STATUS_ENDED) wire:poll.15s.visible @endif>
    {{-- Clock --}}
    <div class="oa-bid-clock">
        @if($status === \App\Models\Auction::STATUS_OPEN)
            <span>Closes in</span>
            <strong class="oa-countdown" data-countdown="{{ $auction->endsAt()->toIso8601String() }}" data-countdown-done="Closing…">{{ $auction->endsAt()->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true, parts: 2) }}</strong>
        @elseif($status === \App\Models\Auction::STATUS_UPCOMING)
            <span>Opens in</span>
            <strong class="oa-countdown" data-countdown="{{ $auction->startsAt()->toIso8601String() }}" data-countdown-done="Opening…" data-countdown-urgent="0">{{ $auction->startsAt()->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true, parts: 2) }}</strong>
        @else
            <span>Closed</span>
            <strong>{{ $auction->endsAt()->format('M j, Y g:i A') }}</strong>
        @endif
    </div>

    @if($auction->extension_count > 0 && $status !== \App\Models\Auction::STATUS_ENDED)
        <div class="oa-callout is-info py-2">
            <i class="fas fa-clock mr-1"></i> Extended {{ $auction->extension_count }} {{ Str::plural('time', $auction->extension_count) }} because of late bids.
        </div>
    @endif

    {{-- Key figures --}}
    <div class="oa-bid-figures">
        <div>
            <span>Minimum bid</span>
            <strong>{{ number_format((float) $auction->min_bid, 2) }}</strong>
        </div>
        @if($isOpenMode)
            <div>
                <span>Highest bid</span>
                <strong>{{ $highestBid ? number_format((float) $highestBid->bid_amount, 2) : 'No bids yet' }}</strong>
            </div>
        @else
            <div>
                <span>Your bid</span>
                <strong>{{ $myBids->isNotEmpty() ? number_format((float) $myBids->max('bid_amount'), 2) : 'None yet' }}</strong>
            </div>
        @endif
    </div>

    {{-- Who is leading, when this auction allows it --}}
    @if($canSeeLeader && $highestBid)
        <div class="d-flex justify-content-between align-items-center small">
            <span class="text-muted">Leading bidder</span>
            <strong>{{ $highestBid->user?->name }}</strong>
        </div>
    @endif
    @if($lowestBidder)
        <div class="d-flex justify-content-between align-items-center small">
            <span class="text-muted">Lowest bidder</span>
            <strong>{{ $lowestBidder->user?->name }}</strong>
        </div>
    @endif

    {{-- Result after closing --}}
    @if($status === \App\Models\Auction::STATUS_ENDED)
        @if($iWon)
            <div class="oa-callout is-ok">
                <strong><i class="fas fa-trophy mr-1"></i> You won this auction.</strong><br>
                @if($winner->status === \App\Models\AuctionWinner::STATUS_AWAITING_PAYMENT)
                    Pay {{ number_format((float) $winner->bidding->bid_amount, 2) }}
                    @if($winner->payment_due_at) by {{ $winner->payment_due_at->format('M j, Y g:i A') }} @endif
                    ({{ $paymentMethods }}). After payment, pick up the item at {{ $pickupLocation }}.
                @elseif($winner->status === \App\Models\AuctionWinner::STATUS_PAID)
                    Payment received. Pick up the item at {{ $pickupLocation }}.
                @else
                    The item has been released to you.
                @endif
            </div>
        @elseif($myBids->isNotEmpty())
            <div class="oa-callout">This auction has closed and your bid did not win.</div>
        @else
            <div class="oa-callout">This auction has closed.</div>
        @endif
    @elseif($myBids->isNotEmpty())
        @if($isOpenMode && $highestBid)
            @if($highestBid->user_id === auth()->id())
                <div class="oa-callout is-ok"><strong>You're the highest bidder</strong> at {{ number_format((float) $highestBid->bid_amount, 2) }}.</div>
            @else
                <div class="oa-callout is-bad"><strong>You've been outbid.</strong> Your best bid is {{ number_format((float) $myBids->max('bid_amount'), 2) }}.</div>
            @endif
        @else
            <div class="oa-callout is-info">
                <strong>Your bid is in.</strong> Bids stay sealed until the auction closes, and the result is announced then.
            </div>
        @endif
    @endif

    {{-- Bid form --}}
    @if($status !== \App\Models\Auction::STATUS_ENDED)
        @if($denialReason)
            @if($status === \App\Models\Auction::STATUS_OPEN && $myBids->isEmpty())
                <div class="oa-callout is-warn">{{ $denialReason }}</div>
            @elseif($status !== \App\Models\Auction::STATUS_OPEN)
                <div class="oa-callout">{{ $denialReason }}</div>
            @endif
        @elseif($confirming)
            <div class="oa-callout is-warn">
                <strong>Confirm your bid of {{ number_format((float) $bid_amount, 2) }}</strong> on {{ $auction->auction_code }}.
                Bids cannot be withdrawn once placed.
            </div>
            <div class="d-flex" style="gap: .5rem">
                <button type="button" class="btn btn-outline-secondary flex-fill" wire:click="cancelReview">Change amount</button>
                <button type="button" class="btn btn-accent flex-fill" wire:click="placeBid" wire:loading.attr="disabled" wire:target="placeBid">
                    <span wire:loading.remove wire:target="placeBid"><i class="fas fa-gavel mr-1"></i> Confirm bid</span>
                    <span wire:loading wire:target="placeBid"><i class="fas fa-spinner fa-spin mr-1"></i> Placing bid…</span>
                </button>
            </div>
        @else
            <form wire:submit="review" class="d-grid" style="display: grid; gap: .5rem" novalidate>
                <label for="bid_amount" class="mb-0">Your bid</label>
                <input type="number" id="bid_amount" step="0.01" min="{{ $minimumAcceptable }}" inputmode="decimal"
                       class="form-control oa-bid-input @error('bid_amount') is-invalid @enderror"
                       placeholder="{{ number_format($minimumAcceptable, 2, '.', '') }}"
                       wire:model="bid_amount" aria-describedby="bid-help">
                @error('bid_amount')
                    <span class="text-danger small font-weight-bold">{{ $message }}</span>
                @else
                    <span class="oa-hint" id="bid-help">Enter at least {{ number_format($minimumAcceptable, 2) }}{{ $maxBid !== null ? ' and no more than '.number_format($maxBid, 2) : '' }}.</span>
                @enderror
                <button type="submit" class="btn btn-accent btn-lg btn-block" wire:loading.attr="disabled" wire:target="review">
                    <i class="fas fa-gavel mr-1"></i> Review bid
                </button>
            </form>
        @endif
    @endif

    {{-- This auction's rules --}}
    <div>
        <span class="oa-eyebrow mb-2">This auction's rules</span>
        <ul class="oa-rules">
            <li><i class="fas fa-{{ $isOpenMode ? 'eye' : 'envelope' }}"></i>
                <span>{{ $isOpenMode ? 'Open bidding: everyone sees the highest bid.' : 'Sealed bidding: bids stay hidden until closing.' }}</span></li>
            @if($isOpenMode && $increment > 0)
                <li><i class="fas fa-arrow-up"></i><span>Each bid must beat the highest by at least {{ number_format($increment, 2) }}.</span></li>
            @endif
            <li><i class="fas fa-hashtag"></i>
                <span>
                    @if($auction->bid_limit)
                        {{ $auction->bid_limit == 1 ? 'One bid per person.' : 'Up to '.$auction->bid_limit.' bids per person.' }}
                        @if($myBids->isNotEmpty()) You have used {{ $myBids->count() }}. @endif
                    @else
                        No limit on the number of bids.
                    @endif
                </span></li>
            @if($maxBid !== null)
                <li><i class="fas fa-ban"></i><span>Bids cannot exceed {{ number_format($maxBid, 2) }}.</span></li>
            @endif
            @if($antiSniping)
                <li><i class="fas fa-stopwatch"></i><span>A bid in the last {{ $antiSniping }} minutes extends closing by {{ $antiSniping }} minutes.</span></li>
            @endif
            <li><i class="fas fa-equals"></i><span>On a tie, the earliest bid wins.</span></li>
            <li><i class="fas fa-lock"></i><span>Bids cannot be withdrawn.</span></li>
        </ul>
        <a href="#" class="d-inline-block mt-2 small font-weight-bold" data-drawer-open="mechanics-drawer">
            <i class="fas fa-book-open mr-1"></i> Read the full auction mechanics
        </a>
    </div>

    @if($myBids->count() > 1)
        <details>
            <summary class="small font-weight-bold">Your bid history ({{ $myBids->count() }})</summary>
            <ul class="list-unstyled small mt-2 mb-0">
                @foreach($myBids as $myBid)
                    <li class="d-flex justify-content-between" wire:key="my-bid-{{ $myBid->id }}">
                        <span class="text-muted">{{ $myBid->created_at->format('M j, g:i A') }}</span>
                        <strong class="oa-num">{{ number_format((float) $myBid->bid_amount, 2) }}</strong>
                    </li>
                @endforeach
            </ul>
        </details>
    @endif
</div>
