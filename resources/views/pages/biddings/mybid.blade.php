@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'My bids')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <span class="oa-eyebrow">Bidding</span>
            <h2>My bids</h2>
            <p>Every bid you have placed, newest first.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($biddings->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-hand-holding-usd"></i>
                    <strong>You haven't placed any bids yet</strong>
                    <span>Open auctions are listed on the <a href="{{ route('home') }}">Browse</a> page.</span>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Auction</th>
                            <th>Item</th>
                            <th class="text-right">Your bid</th>
                            <th>Placed</th>
                            <th>Result</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($biddings as $bidding)
                            @php
                                $auction = $bidding->auction;
                                $winner = $auction?->auction_winner;
                                $auctionStatus = $auction?->currentStatus();
                            @endphp
                            <tr>
                                <td class="oa-num font-weight-bold">{{ $auction?->auction_code }}</td>
                                <td>{{ $auction?->item?->name }} <span class="text-muted small d-block">{{ $auction?->item?->item_number }}</span></td>
                                <td class="text-right oa-num font-weight-bold">{{ number_format((float) $bidding->bid_amount, 2) }}</td>
                                <td class="text-nowrap">{{ $bidding->created_at->format('M j, Y g:i A') }}</td>
                                <td>
                                    @if($auctionStatus !== \App\Models\Auction::STATUS_ENDED)
                                        <span class="oa-pill is-{{ strtolower((string) $auctionStatus) }}">{{ ucfirst(strtolower((string) $auctionStatus)) }}</span>
                                    @elseif($winner && $winner->bidding_id === $bidding->id && $winner->status !== \App\Models\AuctionWinner::STATUS_FORFEITED)
                                        <span class="oa-pill is-leading">Won · {{ $winner->statusLabel() }}</span>
                                    @else
                                        <span class="oa-pill">Not won</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if($auction)
                                        <a href="{{ route('bidding.index', encrypt($bidding->auction_id)) }}" class="btn btn-outline-secondary btn-xs">View</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($biddings->hasPages())
            <div class="card-footer">
                {{ $biddings->links() }}
            </div>
        @endif
    </div>
</div>
@stop
