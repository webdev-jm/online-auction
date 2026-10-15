@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', $auction->auction_code)

{{-- Content body: main page content --}}
@section('content_body')
@php
    $status = $auction->currentStatus();
    $picture = $auction->coverPicture();
@endphp
<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <a href="{{ route('auction.index') }}" class="small font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> Auctions</a>
            <h2 class="mt-2">{{ $auction->auction_code }} · {{ $auction->item?->name }}</h2>
            <p>
                <span class="oa-pill is-{{ strtolower($status) }}">{{ ucfirst(strtolower($status)) }}</span>
                @if($status === \App\Models\Auction::STATUS_OPEN)
                    · closes in <span class="oa-countdown" data-countdown="{{ $auction->endsAt()->toIso8601String() }}" data-countdown-done="now">{{ $auction->endsAt()->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}</span>
                @elseif($status === \App\Models\Auction::STATUS_UPCOMING)
                    · opens {{ $auction->startsAt()->format('M j, g:i A') }}
                @endif
            </p>
        </div>
        <div class="oa-toolbar">
            @can('auction edit')
                @if($status !== \App\Models\Auction::STATUS_ENDED)
                    <a href="{{ route('auction.edit', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-pen mr-1"></i> Edit</a>
                @endif
            @endcan
            @can('auction print')
                <a href="{{ route('auction.print', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-sm" target="_blank"><i class="fas fa-file-pdf mr-1"></i> PDF</a>
            @endcan
            @can('auction export')
                <a href="{{ route('auction.export', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-file-excel mr-1"></i> Excel</a>
            @endcan
            <a href="{{ route('bidding.index', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-eye mr-1"></i> Bidder view</a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                @if($picture)
                    <img src="{{ $picture->url('large') }}" alt="{{ $auction->item?->name }}" class="card-img-top" style="aspect-ratio: 4 / 3; object-fit: cover">
                @endif
                <div class="card-body">
                    <dl class="oa-specs">
                        <dt>Item</dt>
                        <dd>[{{ $auction->item?->item_number }}] {{ $auction->item?->name }}</dd>
                        <dt>Company</dt>
                        <dd>{{ $auction->company?->name ?? '-' }}{{ $auction->restrict_to_company_only ? ' (only)' : '' }}</dd>
                        <dt>Opens</dt>
                        <dd>{{ $auction->startsAt()->format('M j, Y g:i A') }}</dd>
                        <dt>Closes</dt>
                        <dd>
                            {{ $auction->endsAt()->format('M j, Y g:i A') }}
                            @if($auction->extension_count) <span class="text-muted">(extended {{ $auction->extension_count }}×)</span> @endif
                        </dd>
                        <dt>Mode</dt>
                        <dd>{{ ucfirst($auction->biddingMode()) }}{{ $auction->bidding_mode ? '' : ' (default)' }}</dd>
                        <dt>Minimum bid</dt>
                        <dd class="oa-num">{{ number_format((float) $auction->min_bid, 2) }}</dd>
                        @if($auction->isOpenBidding())
                            <dt>Increment</dt>
                            <dd class="oa-num">{{ number_format($auction->minIncrement(), 2) }}</dd>
                        @endif
                        @if($auction->maxBid() !== null)
                            <dt>Maximum bid</dt>
                            <dd class="oa-num">{{ number_format($auction->maxBid(), 2) }}</dd>
                        @endif
                        <dt>Bids per person</dt>
                        <dd>{{ $auction->bid_limit ?: 'No limit' }}</dd>
                    </dl>
                </div>
            </div>

            <livewire:auctions.winner-panel :auction="$auction"/>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Bids</h3>
                    <span class="text-muted small">{{ $biddings->count() }} {{ Str::plural('bid', $biddings->count()) }} from {{ $biddings->unique('user_id')->count() }} {{ Str::plural('bidder', $biddings->unique('user_id')->count()) }}</span>
                </div>
                <div class="card-body p-0 table-responsive">
                    @if($biddings->isEmpty())
                        <div class="oa-empty">
                            <i class="fas fa-gavel"></i>
                            <strong>No bids yet</strong>
                        </div>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Bidder</th>
                                    <th class="text-right">Bid</th>
                                    <th>Placed</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($biddings as $bidding)
                                    @php($won = $auction->winners->firstWhere('bidding_id', $bidding->id))
                                    <tr>
                                        <td class="text-muted oa-num">{{ $loop->iteration }}</td>
                                        <td class="font-weight-bold">{{ $bidding->user?->name ?? 'Deleted user' }}</td>
                                        <td class="text-right oa-num font-weight-bold">{{ number_format((float) $bidding->bid_amount, 2) }}</td>
                                        <td class="text-nowrap">{{ $bidding->created_at->format('M j, Y g:i:s A') }}</td>
                                        <td class="text-right">
                                            @if($won)
                                                <span class="oa-pill is-{{ $won->status }}">{{ $won->status === \App\Models\AuctionWinner::STATUS_FORFEITED ? 'Forfeited' : 'Winner' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@stop
