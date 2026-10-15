@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Bidders · '.$auction->auction_code)

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <a href="{{ route('bidding.index', encrypt($auction->id)) }}" class="small font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> Back to the auction</a>
            <h2 class="mt-2">Bidders</h2>
            <p>{{ $auction->auction_code }} · highest bid first, earliest first on a tie.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($bidders->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-users"></i>
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
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bidders as $bid)
                            <tr>
                                <td class="text-muted oa-num">{{ $bidders->firstItem() + $loop->index }}</td>
                                <td class="font-weight-bold">{{ $bid->user?->name }}</td>
                                <td class="text-right oa-num font-weight-bold">{{ number_format((float) $bid->bid_amount, 2) }}</td>
                                <td>{{ $bid->created_at->format('M j, Y g:i:s A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($bidders->hasPages())
            <div class="card-footer">
                {{ $bidders->links() }}
            </div>
        @endif
    </div>
</div>
@stop
