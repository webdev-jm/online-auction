@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', $auction->auction_code)

{{-- Content body: main page content --}}
@section('content_body')
@php
    $pictures = $item->pictures;
    $firstPicture = $pictures->first();
    $status = $auction->currentStatus();
@endphp

<div class="pt-3">
    <div class="oa-page-head">
        <div>
            <a href="{{ route('home') }}" class="small font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> All auctions</a>
            <h2 class="mt-2">{{ $item->name }}</h2>
            <p>
                <span class="oa-num">{{ $auction->auction_code }}</span>
                · <span class="oa-pill is-{{ strtolower($status) }}">{{ ucfirst(strtolower($status)) }}</span>
            </p>
        </div>
        @if(auth()->user()->can('bidding list') || $auction->show_bidders)
            <div class="oa-toolbar">
                <a href="{{ route('bidding.list', encrypt($auction->id)) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-list mr-1"></i> Bidders
                </a>
            </div>
        @endif
    </div>

    <div class="row">
        <div class="col-lg-7 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="oa-gallery">
                        <div class="oa-gallery-main">
                            @if($firstPicture)
                                <img src="{{ $firstPicture->url('large') }}" alt="{{ $firstPicture->title }}">
                            @else
                                <div class="oa-placeholder-img" aria-hidden="true"><i class="fas fa-box-open"></i></div>
                            @endif
                        </div>
                        @if($pictures->count() > 1)
                            <div class="oa-gallery-thumbs" aria-label="More photos">
                                @foreach($pictures as $picture)
                                    <button type="button" class="{{ $loop->first ? 'is-active' : '' }}"
                                            data-gallery-thumb="{{ $picture->url('large') }}" data-gallery-alt="{{ $picture->title }}"
                                            aria-label="Show photo {{ $loop->iteration }}">
                                        <img src="{{ $picture->url('small') }}" alt="">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Item details</h3>
                </div>
                <div class="card-body">
                    <dl class="oa-specs">
                        <dt>Item number</dt>
                        <dd class="oa-num">{{ $item->item_number }}</dd>
                        @if($item->brand)
                            <dt>Brand</dt>
                            <dd>{{ $item->brand }}</dd>
                        @endif
                        <dt>Bidding opens</dt>
                        <dd>{{ $auction->startsAt()->format('M j, Y g:i A') }}</dd>
                        <dt>Bidding closes</dt>
                        <dd>{{ $auction->endsAt()->format('M j, Y g:i A') }}</dd>
                        @foreach($item->specifications as $specification)
                            @if(filled($specification->value))
                                <dt>{{ $specification->specification }}</dt>
                                <dd>{{ $specification->value }}</dd>
                            @endif
                        @endforeach
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-3">
            <livewire:biddings.bid :auction="$auction"/>
        </div>
    </div>
</div>

<div class="oa-drawer" id="mechanics-drawer" hidden>
    <div class="oa-drawer-panel" role="dialog" aria-modal="true" aria-labelledby="mechanics-title">
        <div class="oa-drawer-head">
            <h3 id="mechanics-title" class="mb-0">Auction mechanics</h3>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-drawer-close>Close</button>
        </div>
        <div class="oa-terms">{!! $termsHtml !!}</div>
    </div>
</div>
@stop
