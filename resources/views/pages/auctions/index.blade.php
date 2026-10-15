@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Auctions')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <span class="oa-eyebrow">Manage</span>
            <h2>Auctions</h2>
            <p>Every auction, newest first.</p>
        </div>
        <div class="oa-toolbar">
            {{ html()->form('GET', route('auction.index'))->class('d-flex')->open() }}
                @if($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <div class="input-group input-group-sm" style="width: min(260px, 100%)">
                    <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span></div>
                    {{ html()->search('search', $search)->class('form-control')->placeholder('Code, item, brand, specs or date')->attribute('aria-label', 'Search auctions') }}
                </div>
            {{ html()->form()->close() }}
            @can('auction create')
                <a href="{{ route('auction.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New auction</a>
            @endcan
        </div>
    </div>

    <nav class="oa-tabs mb-3" aria-label="Filter by status">
        @foreach(['' => 'All', 'OPEN' => 'Open', 'UPCOMING' => 'Upcoming', 'ENDED' => 'Ended'] as $value => $label)
            <a href="{{ route('auction.index', array_filter(['status' => $value, 'search' => $search])) }}"
               class="{{ ($status ?? '') === $value ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($auctions->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-gavel"></i>
                    @if($search || $status)
                        <strong>No auctions match these filters</strong>
                        <a href="{{ route('auction.index') }}">Clear filters</a>
                    @else
                        <strong>No auctions yet</strong>
                        @can('auction create')
                            <a href="{{ route('auction.create') }}">Create the first auction</a>
                        @endcan
                    @endif
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Item</th>
                            <th>Opens</th>
                            <th>Closes</th>
                            <th class="text-right">Min bid</th>
                            <th class="text-right">Bids</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auctions as $auction)
                            @php($currentStatus = $auction->currentStatus())
                            <tr>
                                <td class="oa-num font-weight-bold">{{ $auction->auction_code }}</td>
                                <td>
                                    {{ $auction->item?->name }}
                                    <span class="d-block text-muted small">{{ $auction->item?->item_number }}</span>
                                </td>
                                <td class="text-nowrap">{{ $auction->startsAt()->format('M j, Y g:i A') }}</td>
                                <td class="text-nowrap">{{ $auction->endsAt()->format('M j, Y g:i A') }}</td>
                                <td class="text-right oa-num">{{ number_format((float) $auction->min_bid, 2) }}</td>
                                <td class="text-right oa-num">{{ $auction->biddings_count }}</td>
                                <td>
                                    @if($auction->auction_winner && $auction->auction_winner->status !== \App\Models\AuctionWinner::STATUS_FORFEITED)
                                        <span class="oa-pill is-{{ $auction->auction_winner->status }}">{{ $auction->auction_winner->statusLabel() }}</span>
                                    @else
                                        <span class="oa-pill is-{{ strtolower($currentStatus) }}">{{ ucfirst(strtolower($currentStatus)) }}</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('auction.show', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-xs">View</a>
                                    @if($currentStatus !== \App\Models\Auction::STATUS_ENDED)
                                        @can('auction edit')
                                            <a href="{{ route('auction.edit', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-xs">Edit</a>
                                        @endcan
                                        @can('auction delete')
                                            <a href="#" class="btn btn-outline-secondary btn-xs text-danger btn-delete" data-id="{{ encrypt($auction->id) }}" aria-label="Delete {{ $auction->auction_code }}">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($auctions->hasPages())
            <div class="card-footer">
                {{ $auctions->links() }}
            </div>
        @endif
    </div>
</div>
@stop

{{-- Push extra scripts --}}
@push('js')
    <script>
        $(function() {
            $('body').on('click', '.btn-delete', function(e) {
                e.preventDefault();
                var id = $(this).data('id');
                Livewire.dispatch('setDeleteModel', {type: 'Auction', model_id: id});
                $('#modal-delete').modal('show');
            });
        });
    </script>
@endpush
