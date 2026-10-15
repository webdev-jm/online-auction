<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Winner</h3>
        @if($winner)
            <span class="oa-pill is-{{ $winner->status }}">{{ $winner->statusLabel() }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($status !== \App\Models\Auction::STATUS_ENDED)
            <div class="oa-empty py-3">
                <i class="fas fa-hourglass-half"></i>
                <strong>Not decided yet</strong>
                <span>The winner is picked automatically when bidding closes {{ $auction->endsAt()->format('M j, g:i A') }}.</span>
            </div>
        @elseif(! $winner)
            <div class="oa-empty py-3">
                <i class="fas fa-box-open"></i>
                <strong>No winner</strong>
                <span>Nobody bid on this auction. You can auction the item again.</span>
            </div>
        @else
            <dl class="oa-specs mb-3">
                <dt>Bidder</dt>
                <dd class="font-weight-bold">{{ $winner->bidding?->user?->name ?? 'Deleted user' }}</dd>
                <dt>Winning bid</dt>
                <dd class="oa-num font-weight-bold">{{ number_format((float) $winner->bidding?->bid_amount, 2) }}</dd>
                @if($winner->payment_due_at && $winner->isAwaitingPayment())
                    <dt>Payment due</dt>
                    <dd class="{{ $winner->isOverdue() ? 'text-danger font-weight-bold' : '' }}">
                        {{ $winner->payment_due_at->format('M j, Y g:i A') }}
                        @if($winner->isOverdue()) · overdue @endif
                    </dd>
                @endif
                @if($winner->paid_at)
                    <dt>Paid</dt>
                    <dd>{{ $winner->paid_at->format('M j, Y g:i A') }}{{ $winner->payment_method ? ' · '.$winner->payment_method : '' }}</dd>
                @endif
                @if($winner->released_at)
                    <dt>Released</dt>
                    <dd>{{ $winner->released_at->format('M j, Y g:i A') }}</dd>
                @endif
                <dt>Notified</dt>
                <dd>{{ $winner->notified_at ? $winner->notified_at->format('M j, Y g:i A') : 'Not yet' }}</dd>
                @if($winner->handler)
                    <dt>Last handled by</dt>
                    <dd>{{ $winner->handler->name }}</dd>
                @endif
            </dl>

            @if($canManage && $winner->status !== \App\Models\AuctionWinner::STATUS_FORFEITED)
                <div style="display: grid; gap: .5rem">
                    @if($winner->status !== \App\Models\AuctionWinner::STATUS_RELEASED)
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="notifyWinner" wire:loading.attr="disabled" wire:target="notifyWinner">
                            <i class="fas fa-paper-plane mr-1"></i> {{ $winner->notified_at ? 'Send the winner email again' : 'Email the winner' }}
                        </button>
                    @endif

                    @if($winner->isAwaitingPayment())
                        <div class="d-flex" style="gap: .5rem">
                            <select class="custom-select custom-select-sm" wire:model="payment_method" aria-label="Payment method">
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}">{{ $method }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-primary btn-sm text-nowrap" wire:click="markPaid" wire:loading.attr="disabled" wire:target="markPaid">
                                Record payment
                            </button>
                        </div>
                        @error('payment_method')<span class="text-danger small">{{ $message }}</span>@enderror

                        @if($confirmingForfeit)
                            <div class="oa-callout is-bad">
                                Forfeit this win? The item will go to the next highest bidder, if there is one.
                                <div class="d-flex mt-2" style="gap: .5rem">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$set('confirmingForfeit', false)">Keep the winner</button>
                                    <button type="button" class="btn btn-danger btn-sm" wire:click="forfeit" wire:loading.attr="disabled" wire:target="forfeit">Forfeit</button>
                                </div>
                            </div>
                        @else
                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-left" wire:click="$set('confirmingForfeit', true)">
                                Forfeit and offer to the next bidder
                            </button>
                        @endif
                    @elseif($winner->status === \App\Models\AuctionWinner::STATUS_PAID)
                        <button type="button" class="btn btn-primary btn-sm" wire:click="markReleased" wire:loading.attr="disabled" wire:target="markReleased">
                            <i class="fas fa-box mr-1"></i> Mark item as released
                        </button>
                    @endif
                </div>
            @endif
        @endif

        @if($history->isNotEmpty())
            <details class="mt-3">
                <summary class="small font-weight-bold">Earlier winners ({{ $history->count() }})</summary>
                <ul class="list-unstyled small mt-2 mb-0">
                    @foreach($history as $previous)
                        <li class="d-flex justify-content-between" wire:key="previous-winner-{{ $previous->id }}">
                            <span>{{ $previous->bidding?->user?->name }} · {{ number_format((float) $previous->bidding?->bid_amount, 2) }}</span>
                            <span class="oa-pill is-{{ $previous->status }}">{{ $previous->statusLabel() }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
</div>
