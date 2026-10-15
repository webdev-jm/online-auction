@extends('layouts.app')

@section('subtitle', 'Dashboard')

@section('content_body')
<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <span class="oa-eyebrow">{{ now()->format('l, F j') }}</span>
            <h2>Dashboard</h2>
            <p>What needs attention across all auctions.</p>
        </div>
        <div class="oa-toolbar">
            @can('auction create')
                <a href="{{ route('auction.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New auction</a>
            @endcan
        </div>
    </div>

    <div class="oa-stats">
        <div class="oa-stat">
            <div class="oa-stat-label">Open now</div>
            <div class="oa-stat-value">{{ $stats['open'] }}</div>
            <div class="oa-stat-note">{{ $stats['upcoming'] }} upcoming</div>
        </div>
        <div class="oa-stat">
            <div class="oa-stat-label">Bids today</div>
            <div class="oa-stat-value">{{ $stats['bidsToday'] }}</div>
            <div class="oa-stat-note">since midnight</div>
        </div>
        <div class="oa-stat {{ $stats['endingSoon'] ? 'is-attention' : '' }}">
            <div class="oa-stat-label">Closing within 24h</div>
            <div class="oa-stat-value">{{ $stats['endingSoon'] }}</div>
            <div class="oa-stat-note">open auctions</div>
        </div>
        <div class="oa-stat {{ $stats['overdue'] ? 'is-attention' : '' }}">
            <div class="oa-stat-label">Awaiting payment</div>
            <div class="oa-stat-value">{{ $stats['awaitingPayment'] }}</div>
            <div class="oa-stat-note">{{ $stats['overdue'] }} overdue · {{ $stats['awaitingRelease'] }} paid, not released</div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Winners awaiting payment</h3></div>
                <div class="card-body p-0 table-responsive">
                    @if($awaitingPayment->isEmpty())
                        <div class="oa-empty">
                            <i class="fas fa-check-circle"></i>
                            <strong>All winners have paid</strong>
                        </div>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Auction</th>
                                    <th>Winner</th>
                                    <th class="text-right">Amount</th>
                                    <th>Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($awaitingPayment as $winner)
                                    <tr>
                                        <td><a href="{{ route('auction.show', encrypt($winner->auction_id)) }}" class="font-weight-bold oa-num">{{ $winner->auction->auction_code }}</a>
                                            <span class="d-block small text-muted">{{ $winner->auction->item?->name }}</span></td>
                                        <td>{{ $winner->bidding?->user?->name }}</td>
                                        <td class="text-right oa-num">{{ number_format((float) $winner->bidding?->bid_amount, 2) }}</td>
                                        <td class="text-nowrap">
                                            @if($winner->payment_due_at)
                                                <span class="{{ $winner->isOverdue() ? 'text-danger font-weight-bold' : '' }}">{{ $winner->payment_due_at->format('M j, g:i A') }}</span>
                                            @else
                                                <span class="text-muted">No due date</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">Closing within 24 hours</h3></div>
                <div class="card-body p-0 table-responsive">
                    @if($endingSoon->isEmpty())
                        <div class="oa-empty">
                            <i class="fas fa-clock"></i>
                            <strong>Nothing closes in the next 24 hours</strong>
                        </div>
                    @else
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Auction</th>
                                    <th class="text-right">Bids</th>
                                    <th>Closes in</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($endingSoon as $auction)
                                    <tr>
                                        <td><a href="{{ route('auction.show', encrypt($auction->id)) }}" class="font-weight-bold oa-num">{{ $auction->auction_code }}</a>
                                            <span class="d-block small text-muted">{{ $auction->item?->name }}</span></td>
                                        <td class="text-right oa-num">{{ $auction->biddings_count }}</td>
                                        <td><span class="oa-countdown" data-countdown="{{ $auction->endsAt()->toIso8601String() }}" data-countdown-done="Closing…">{{ $auction->endsAt()->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Recent activity</h3>
                    @can('system logs')
                        <a href="{{ route('system-logs') }}" class="small font-weight-bold">All logs</a>
                    @endcan
                </div>
                <div class="card-body">
                    @forelse($activity as $entry)
                        <div class="d-flex justify-content-between py-2 {{ $loop->first ? '' : 'border-top' }}" style="gap: 1rem; border-color: var(--oa-line) !important">
                            <span class="small">{{ $entry->description }}</span>
                            <span class="small text-muted text-nowrap" title="{{ $entry->created_at }}">{{ $entry->created_at->diffForHumans(short: true) }}</span>
                        </div>
                    @empty
                        <div class="oa-empty py-3"><strong>No activity yet</strong></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@stop
