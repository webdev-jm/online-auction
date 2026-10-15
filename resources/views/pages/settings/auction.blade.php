@extends('layouts.app')

@section('subtitle', 'Auction settings')

@php
    $value = fn (string $key) => old($key, $settings[$key]);
    $checked = fn (string $key) => (bool) (old('_token') ? old($key) : $settings[$key]);
@endphp

@section('content_body')
<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <span class="oa-eyebrow">Settings</span>
            <h2>Auction settings</h2>
            <p>These rules apply to every auction. Some can be changed on a single auction when you create or edit it.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="oa-callout is-bad mb-3">
            <strong>Some settings need fixing.</strong> The fields with problems are marked below.
        </div>
    @endif

    <form method="POST" action="{{ route('settings.auction.update') }}" novalidate>
        @csrf

        <div class="oa-settings">
            <nav aria-label="Settings sections">
                <a href="#bidding">Bidding</a>
                <a href="#new-auctions">New auctions</a>
                <a href="#after">After the auction</a>
                <a href="#notifications">Notifications</a>
                <a href="#terms">Terms and conditions</a>
            </nav>

            <div>
                {{-- Bidding --}}
                <section class="card" id="bidding">
                    <div class="card-header"><h3 class="card-title">Bidding</h3></div>
                    <div class="card-body">
                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Default bidding mode</div>
                                <div class="oa-setting-help">Sealed: bidders only see their own bid. Open: everyone sees the highest bid and must beat it.</div>
                            </div>
                            <div>
                                <select name="bidding_mode" class="custom-select custom-select-sm @error('bidding_mode') is-invalid @enderror">
                                    <option value="sealed" @selected($value('bidding_mode') === 'sealed')>Sealed</option>
                                    <option value="open" @selected($value('bidding_mode') === 'open')>Open</option>
                                </select>
                                @error('bidding_mode')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Bids per person</div>
                                <div class="oa-setting-help">Pre-filled on new auctions. Leave empty for no limit.</div>
                            </div>
                            <div>
                                <input type="number" min="1" name="max_bids_per_user" value="{{ $value('max_bids_per_user') }}"
                                       class="form-control form-control-sm @error('max_bids_per_user') is-invalid @enderror">
                                @error('max_bids_per_user')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Minimum increment</div>
                                <div class="oa-setting-help">Open bidding only. Each new bid must beat the highest bid by at least this much.</div>
                            </div>
                            <div>
                                <input type="number" min="0" step="0.01" name="min_increment" value="{{ $value('min_increment') }}"
                                       class="form-control form-control-sm @error('min_increment') is-invalid @enderror">
                                @error('min_increment')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Maximum bid</div>
                                <div class="oa-setting-help">Optional cap on any single bid. Leave empty for no cap.</div>
                            </div>
                            <div>
                                <input type="number" min="0" step="0.01" name="max_bid" value="{{ $value('max_bid') }}"
                                       class="form-control form-control-sm @error('max_bid') is-invalid @enderror">
                                @error('max_bid')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Extend on late bids</div>
                                <div class="oa-setting-help">A bid in the closing minutes pushes the end time out, so others can answer it.</div>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="anti_sniping_enabled" name="anti_sniping_enabled" value="1" @checked($checked('anti_sniping_enabled'))>
                                <label class="custom-control-label" for="anti_sniping_enabled">On</label>
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Extension length (minutes)</div>
                                <div class="oa-setting-help">Both the closing window that triggers an extension and how far the end moves.</div>
                            </div>
                            <div>
                                <input type="number" min="1" max="120" name="anti_sniping_minutes" value="{{ $value('anti_sniping_minutes') }}"
                                       class="form-control form-control-sm @error('anti_sniping_minutes') is-invalid @enderror">
                                @error('anti_sniping_minutes')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- New auctions --}}
                <section class="card" id="new-auctions">
                    <div class="card-header"><h3 class="card-title">New auctions</h3></div>
                    <div class="card-body">
                        @foreach([
                            'default_show_bidders' => ['Show bidders', 'Bidders can open the list of everyone who bid.'],
                            'default_show_leading_bidder' => ['Show leading bidder', 'Bidders see the name of the current leader.'],
                            'default_show_last_place_bidder' => ['Show lowest bidder', 'Bidders see the name of the lowest bidder.'],
                            'default_restrict_to_company_only' => ['Company only', 'Only employees of the auction\'s company can bid.'],
                        ] as $key => [$label, $help])
                            <div class="oa-setting">
                                <div>
                                    <div class="oa-setting-label">{{ $label }}</div>
                                    <div class="oa-setting-help">{{ $help }} Pre-ticked on new auctions.</div>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="{{ $key }}" name="{{ $key }}" value="1" @checked($checked($key))>
                                    <label class="custom-control-label" for="{{ $key }}">On</label>
                                </div>
                            </div>
                        @endforeach

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Auction code</div>
                                <div class="oa-setting-help">
                                    Prefix and number of digits. The next code looks like
                                    <strong class="oa-num">{{ $settings['auction_code_prefix'] }}{{ str_pad('1', (int) $settings['auction_code_digits'], '0', STR_PAD_LEFT) }}</strong>.
                                    Existing codes don't change.
                                </div>
                            </div>
                            <div class="d-flex" style="gap: .5rem">
                                <input type="text" name="auction_code_prefix" value="{{ $value('auction_code_prefix') }}" aria-label="Code prefix"
                                       class="form-control form-control-sm @error('auction_code_prefix') is-invalid @enderror">
                                <input type="number" min="1" max="8" name="auction_code_digits" value="{{ $value('auction_code_digits') }}" aria-label="Digits"
                                       class="form-control form-control-sm @error('auction_code_digits') is-invalid @enderror" style="max-width: 80px">
                            </div>
                        </div>
                        @error('auction_code_prefix')<div class="text-danger small text-right">{{ $message }}</div>@enderror
                        @error('auction_code_digits')<div class="text-danger small text-right">{{ $message }}</div>@enderror
                    </div>
                </section>

                {{-- After the auction --}}
                <section class="card" id="after">
                    <div class="card-header"><h3 class="card-title">After the auction</h3></div>
                    <div class="card-body">
                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Payment window (days)</div>
                                <div class="oa-setting-help">How long a winner has to pay after the auction closes.</div>
                            </div>
                            <div>
                                <input type="number" min="0" max="60" name="payment_window_days" value="{{ $value('payment_window_days') }}"
                                       class="form-control form-control-sm @error('payment_window_days') is-invalid @enderror">
                                @error('payment_window_days')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Payment methods</div>
                                <div class="oa-setting-help">Separate several with commas, for example "Cash, Salary deduction".</div>
                            </div>
                            <div>
                                <input type="text" name="payment_methods" value="{{ $value('payment_methods') }}"
                                       class="form-control form-control-sm @error('payment_methods') is-invalid @enderror">
                                @error('payment_methods')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Pickup location</div>
                                <div class="oa-setting-help">Where winners collect their item after paying.</div>
                            </div>
                            <div>
                                <input type="text" name="pickup_location" value="{{ $value('pickup_location') }}"
                                       class="form-control form-control-sm @error('pickup_location') is-invalid @enderror">
                                @error('pickup_location')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Forfeit unpaid wins</div>
                                <div class="oa-setting-help">When the payment window passes, the win is forfeited and the item goes to the next highest bidder.</div>
                            </div>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="auto_forfeit" name="auto_forfeit" value="1" @checked($checked('auto_forfeit'))>
                                <label class="custom-control-label" for="auto_forfeit">On</label>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Notifications --}}
                <section class="card" id="notifications">
                    <div class="card-header"><h3 class="card-title">Notifications</h3></div>
                    <div class="card-body">
                        @foreach([
                            'notify_bid_placed' => ['Bid confirmation', 'Email bidders when their bid is placed.'],
                            'notify_outbid' => ['Outbid', 'Open bidding only. Email the previous leader when someone bids higher.'],
                            'notify_winner_automatically' => ['Winner', 'Email the winner as soon as the auction closes. When off, send it from the auction page.'],
                            'notify_payment_reminder' => ['Payment reminder', 'Email winners who haven\'t paid when a day is left.'],
                            'notify_ending_soon' => ['Ending soon', 'Email everyone who bid on an auction shortly before it closes.'],
                        ] as $key => [$label, $help])
                            <div class="oa-setting">
                                <div>
                                    <div class="oa-setting-label">{{ $label }}</div>
                                    <div class="oa-setting-help">{{ $help }}</div>
                                </div>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="{{ $key }}" name="{{ $key }}" value="1" @checked($checked($key))>
                                    <label class="custom-control-label" for="{{ $key }}">On</label>
                                </div>
                            </div>
                        @endforeach

                        <div class="oa-setting">
                            <div>
                                <div class="oa-setting-label">Ending-soon notice (hours before closing)</div>
                                <div class="oa-setting-help">Used when the ending-soon notice is on.</div>
                            </div>
                            <div>
                                <input type="number" min="1" max="168" name="ending_soon_hours" value="{{ $value('ending_soon_hours') }}"
                                       class="form-control form-control-sm @error('ending_soon_hours') is-invalid @enderror">
                                @error('ending_soon_hours')<span class="text-danger small">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Terms --}}
                <section class="card" id="terms">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Terms and conditions</h3>
                        <span class="oa-pill">Version {{ $settings['terms_version'] }}</span>
                    </div>
                    <div class="card-body">
                        <p class="oa-hint mb-2">
                            Written in Markdown: start a heading with <code>##</code> and a list item with <code>-</code>.
                            These placeholders fill in live values:
                            <code>{payment_methods}</code> <code>{payment_window_days}</code> <code>{pickup_location}</code> <code>{max_bids_per_user}</code>.
                        </p>
                        <textarea name="terms_content" rows="16" class="form-control @error('terms_content') is-invalid @enderror"
                                  style="font-family: ui-monospace, Consolas, monospace; font-size: .85rem">{{ $value('terms_content') }}</textarea>
                        @error('terms_content')<span class="text-danger small">{{ $message }}</span>@enderror

                        <div class="custom-control custom-checkbox mt-3">
                            <input type="checkbox" class="custom-control-input" id="publish_new_terms" name="publish_new_terms" value="1" @checked(old('publish_new_terms'))>
                            <label class="custom-control-label" for="publish_new_terms">
                                Publish as version {{ $settings['terms_version'] + 1 }} and ask every bidder to accept it again
                            </label>
                        </div>
                        <p class="oa-hint mt-1 mb-0">Leave this unticked for small fixes like typos.</p>
                    </div>
                </section>

                <div class="oa-sticky-save">
                    <a href="{{ route('settings.auction') }}" class="btn btn-outline-secondary">Discard changes</a>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save settings</button>
                </div>
            </div>
        </div>
    </form>
</div>
@stop
