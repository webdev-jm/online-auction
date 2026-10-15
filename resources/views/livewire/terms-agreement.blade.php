<div>
    @if($needsTerms)
        <div class="card">
            <div class="card-header">
                <span class="oa-eyebrow">Version {{ $termsVersion }}</span>
                <h3 class="card-title">Terms and conditions</h3>
            </div>
            <div class="card-body">
                @if($declined)
                    <div class="oa-callout is-warn mb-3">
                        <strong>You declined the terms.</strong>
                        To see auctions and place bids, read the terms again and accept them.
                    </div>
                @elseif($termsUpdated)
                    <div class="oa-callout is-info mb-3">
                        <strong>The terms have changed.</strong>
                        Please read and accept the new version to keep bidding.
                    </div>
                @endif

                @unless($declined)
                    <div class="oa-terms">{!! $termsHtml !!}</div>
                @endunless
            </div>
            <div class="card-footer d-flex justify-content-end" style="gap: .5rem">
                @if($declined)
                    <button type="button" class="btn btn-primary btn-sm" wire:click="readAgain">Read the terms</button>
                @else
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="decline">Decline</button>
                    <button type="button" class="btn btn-accent btn-sm" wire:click="accept">
                        <span wire:loading.remove wire:target="accept">I accept the terms</span>
                        <span wire:loading wire:target="accept"><i class="fas fa-spinner fa-spin mr-1"></i> Saving…</span>
                    </button>
                @endif
            </div>
        </div>
    @else
        <div wire:poll.30s>
            <div class="oa-page-head">
                <div>
                    <span class="oa-eyebrow">Auctions</span>
                    <h2>Find something to bid on</h2>
                    <p>Bids are binding once placed. Read the rules on each auction before you bid.</p>
                </div>
                <div class="oa-toolbar">
                    <div class="input-group input-group-sm" style="width: min(300px, 100%)">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="search" class="form-control" placeholder="Item, brand, code or spec"
                               aria-label="Search auctions" wire:model.live.debounce.400ms="search">
                    </div>
                </div>
            </div>

            <div class="oa-tabs mb-3" role="tablist">
                @foreach(['open' => 'Open now', 'upcoming' => 'Upcoming', 'ended' => 'Recently ended'] as $key => $label)
                    <button type="button" role="tab" wire:key="tab-{{ $key }}"
                            aria-selected="{{ $tab === $key ? 'true' : 'false' }}"
                            class="{{ $tab === $key ? 'is-active' : '' }}"
                            wire:click="setTab('{{ $key }}')">
                        {{ $label }}<span class="oa-count">{{ $counts[$key] }}</span>
                    </button>
                @endforeach
            </div>

            <div wire:loading.class="oa-dim" wire:target="setTab, search">
                @if($auctions->isEmpty())
                    <div class="card">
                        <div class="oa-empty">
                            <i class="fas fa-gavel"></i>
                            @if($search !== '')
                                <strong>No auctions match "{{ $search }}"</strong>
                                <span>Try another item name, brand, auction code or spec such as "16GB" or "i7".</span>
                            @elseif($tab === 'open')
                                <strong>No auctions are open right now</strong>
                                <span>Check the Upcoming tab to see what opens next.</span>
                            @elseif($tab === 'upcoming')
                                <strong>Nothing is scheduled yet</strong>
                                <span>New auctions appear here before bidding opens.</span>
                            @else
                                <strong>No auctions ended in the last two weeks</strong>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="oa-grid">
                        @foreach($auctions as $auction)
                            @php
                                $myBid = $myBids[$auction->id] ?? null;
                                $state = null;
                                if ($auction->auction_winner?->bidding?->user_id === auth()->id() && $auction->auction_winner->status !== \App\Models\AuctionWinner::STATUS_FORFEITED) {
                                    $state = 'won';
                                } elseif ($myBid !== null && $leaders->has($auction->id)) {
                                    $state = $leaders[$auction->id] === auth()->id() ? 'leading' : 'outbid';
                                } elseif ($myBid !== null) {
                                    $state = 'bid';
                                }
                            @endphp
                            <x-auction-card :auction="$auction" :my-bid="$myBid" :state="$state"
                                            :href="auth()->user()->can('bidding access') ? route('bidding.index', encrypt($auction->id)) : null"
                                            wire:key="auction-{{ $auction->id }}"/>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
