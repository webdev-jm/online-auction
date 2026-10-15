{{--
    Shared by create and edit.
    Expects: $items, $companies, $defaults, and either $auction (edit) or $auction_code (create).
--}}
@php
    $editing = isset($auction);
    $model = $auction ?? null;
    $field = fn (string $key, $fallback = null) => old($key, $editing ? $model->{$key} : $fallback);
    $switch = function (string $key) use ($editing, $model, $defaults) {
        if (old('_token')) {
            return (bool) old($key);
        }

        return (bool) ($editing ? $model->{$key} : ($defaults[$key] ?? false));
    };
    $limitOn = old('_token') ? (bool) old('user_bidding_limit') : ($editing ? ! empty($model->bid_limit) : ! empty($defaults['bid_limit']));
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Item and schedule</h3>
                <span class="oa-pill oa-num">{{ $editing ? $auction->auction_code : $auction_code }}</span>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-6">
                        {{ html()->label('Item', 'item_id') }}
                        {{ html()->select('item_id', $items, old('item_id', $selected_item ?? ''))->class(['custom-select', 'custom-select-sm', 'is-invalid' => $errors->has('item_id')]) }}
                        @error('item_id')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-md-6">
                        {{ html()->label('Company', 'company_id') }}
                        {{ html()->select('company_id', $companies, old('company_id', $selected_company ?? ''))->class(['custom-select', 'custom-select-sm', 'is-invalid' => $errors->has('company_id')]) }}
                        @error('company_id')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-6 col-md-3">
                        {{ html()->label('Start date', 'start') }}
                        {{ html()->date('start', $field('start', date('Y-m-d')))->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('start')]) }}
                        @error('start')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-6 col-md-3">
                        {{ html()->label('Start time', 'start_time') }}
                        {{ html()->time('start_time', $field('start_time', date('H:i')))->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('start_time')]) }}
                        @error('start_time')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-6 col-md-3">
                        {{ html()->label('End date', 'end') }}
                        {{ html()->date('end', $field('end', date('Y-m-d', strtotime('+7 days'))))->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('end')]) }}
                        @error('end')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-6 col-md-3">
                        {{ html()->label('End time', 'end_time') }}
                        {{ html()->time('end_time', $field('end_time', '17:00'))->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('end_time')]) }}
                        @error('end_time')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Bidding rules</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        {{ html()->label('Minimum bid', 'min_bid') }}
                        {{ html()->number('min_bid', $field('min_bid'), 0, null, '0.01')->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('min_bid')]) }}
                        @error('min_bid')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-md-4">
                        {{ html()->label('Bidding mode', 'bidding_mode') }}
                        {{ html()->select('bidding_mode', ['' => 'Use the default ('.ucfirst($defaults['bidding_mode']).')', 'sealed' => 'Sealed', 'open' => 'Open'], $field('bidding_mode', ''))->class(['custom-select', 'custom-select-sm', 'is-invalid' => $errors->has('bidding_mode')]) }}
                        @error('bidding_mode')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-md-4">
                        {{ html()->label('Minimum increment', 'min_increment') }}
                        {{ html()->number('min_increment', $field('min_increment'), 0, null, '0.01')->placeholder('Default: '.number_format((float) $defaults['min_increment'], 2))->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('min_increment')]) }}
                        <small class="form-text">Open bidding only.</small>
                        @error('min_increment')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        {{ html()->label('Maximum bid', 'max_bid') }}
                        {{ html()->number('max_bid', $field('max_bid'), 0, null, '0.01')->placeholder($defaults['max_bid'] ? 'Default: '.number_format((float) $defaults['max_bid'], 2) : 'No cap')->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('max_bid')]) }}
                        @error('max_bid')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group col-md-8">
                        <label for="bid_limit">Bids per person</label>
                        <div class="d-flex align-items-center" style="gap: .75rem">
                            <div class="custom-control custom-switch">
                                {{ html()->checkbox('user_bidding_limit', $limitOn, 1)->class('custom-control-input') }}
                                {{ html()->label('Limit', 'user_bidding_limit')->class('custom-control-label') }}
                            </div>
                            {{ html()->number('bid_limit', $field('bid_limit', $defaults['bid_limit']), 1)->class(['form-control', 'form-control-sm', 'is-invalid' => $errors->has('bid_limit')])->style('max-width: 110px') }}
                        </div>
                        @error('bid_limit')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Visibility</h3>
            </div>
            <div class="card-body">
                @foreach([
                    'show_bidders' => ['Show bidders', 'Bidders can see who else bid.'],
                    'show_leading_bidder' => ['Show leading bidder', 'Bidders see the current leader\'s name.'],
                    'show_last_place_bidder' => ['Show lowest bidder', 'Bidders see the lowest bidder\'s name.'],
                    'restrict_to_company_only' => ['Company only', 'Only the selected company\'s employees can bid.'],
                ] as $key => [$label, $help])
                    <div class="custom-control custom-switch mb-3">
                        {{ html()->checkbox($key, $switch($key), 1)->class('custom-control-input') }}
                        <label class="custom-control-label" for="{{ $key }}">{{ $label }}</label>
                        <small class="form-text mt-0">{{ $help }}</small>
                    </div>
                @endforeach
                @unless($editing)
                    <p class="oa-hint mb-0">Pre-set from <a href="{{ Route::has('settings.auction') && auth()->user()->can('settings manage') ? route('settings.auction') : '#' }}">Auction settings</a>.</p>
                @endunless
            </div>
        </div>
    </div>
</div>
