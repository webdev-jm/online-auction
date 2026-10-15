@props([
    'auction',
    'href' => null,
    'myBid' => null,
    'state' => null,
])

@php
    $status = $auction->currentStatus();
    $picture = $auction->coverPicture();
    $statusLabels = [
        \App\Models\Auction::STATUS_OPEN => 'Open',
        \App\Models\Auction::STATUS_UPCOMING => 'Upcoming',
        \App\Models\Auction::STATUS_ENDED => 'Ended',
    ];
    $stateLabels = [
        'won' => ['Won', 'is-leading'],
        'leading' => ['Leading', 'is-leading'],
        'outbid' => ['Outbid', 'is-outbid'],
        'bid' => ['You bid', 'is-bid'],
    ];
@endphp

<article {{ $attributes->class('oa-auction-card') }}>
    <div class="oa-auction-media">
        @if($picture)
            <img src="{{ $picture->url('large') }}" alt="{{ $auction->item?->name }}" loading="lazy">
        @else
            <div class="oa-placeholder-img" aria-hidden="true"><i class="fas fa-box-open"></i></div>
        @endif
        <span class="oa-pill is-{{ strtolower($status) }}">{{ $statusLabels[$status] ?? $status }}</span>
    </div>

    <div class="oa-auction-body">
        <div class="d-flex justify-content-between align-items-center" style="gap: .5rem">
            <span class="oa-auction-code">{{ $auction->auction_code }}</span>
            @if($state && isset($stateLabels[$state]))
                <span class="oa-pill {{ $stateLabels[$state][1] }}">{{ $stateLabels[$state][0] }}</span>
            @endif
        </div>

        <h3 class="oa-auction-title">{{ $auction->item?->name }}</h3>
        @if($auction->item?->brand)
            <span class="text-muted small">{{ $auction->item->brand }} · {{ $auction->item->item_number }}</span>
        @endif

        <dl class="oa-auction-meta">
            <div>
                <dt>{{ $myBid !== null ? 'Your bid' : 'Minimum bid' }}</dt>
                <dd>{{ number_format((float) ($myBid ?? $auction->min_bid), 2) }}</dd>
            </div>
            <div>
                @if($status === \App\Models\Auction::STATUS_OPEN)
                    <dt>Closes in</dt>
                    <dd><span class="oa-countdown" data-countdown="{{ $auction->endsAt()->toIso8601String() }}" data-countdown-done="Closing…">{{ $auction->endsAt()->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}</span></dd>
                @elseif($status === \App\Models\Auction::STATUS_UPCOMING)
                    <dt>Opens in</dt>
                    <dd><span class="oa-countdown" data-countdown="{{ $auction->startsAt()->toIso8601String() }}" data-countdown-done="Opening…" data-countdown-urgent="0">{{ $auction->startsAt()->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}</span></dd>
                @else
                    <dt>Closed</dt>
                    <dd>{{ $auction->endsAt()->format('M j, g:i A') }}</dd>
                @endif
            </div>
        </dl>

        @if($href)
            <div class="oa-auction-foot">
                @if($status === \App\Models\Auction::STATUS_OPEN)
                    <a href="{{ $href }}" class="btn btn-accent btn-sm btn-block"><i class="fas fa-gavel mr-1"></i> {{ $myBid !== null ? 'View my bid' : 'Place a bid' }}</a>
                @else
                    <a href="{{ $href }}" class="btn btn-outline-secondary btn-sm btn-block">View details</a>
                @endif
            </div>
        @endif
    </div>
</article>
