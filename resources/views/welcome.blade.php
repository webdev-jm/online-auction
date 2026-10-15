@extends('layouts.app')

@section('subtitle', 'Welcome')

@section('content_body')
<div class="pt-3">
    <section class="oa-hero">
        <canvas id="oa-scene" aria-hidden="true"></canvas>
        <div class="oa-hero-inner">
            <span class="oa-eyebrow">Online Auction</span>
            <h1>Bid on company items. <em>Live.</em></h1>
            <p>Browse items released for auction, place your bid before the clock runs out, and get told the moment you win.</p>
            <div class="oa-toolbar mt-3">
                @auth
                    <a href="{{ route('home') }}" class="btn btn-accent"><i class="fas fa-gavel mr-1"></i> Go to auctions</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-accent"><i class="fas fa-sign-in-alt mr-1"></i> Sign in to bid</a>
                @endauth
            </div>
        </div>
    </section>

    <section class="mt-4">
        <div class="oa-page-head">
            <div>
                <h2>Open now</h2>
                <p>Closing soonest first.</p>
            </div>
        </div>

        @if($open->isEmpty())
            <div class="card">
                <div class="oa-empty">
                    <i class="fas fa-gavel"></i>
                    <strong>No auctions are open right now</strong>
                    <span>{{ $upcoming->isNotEmpty() ? 'See what opens next below.' : 'Check back soon.' }}</span>
                </div>
            </div>
        @else
            <div class="oa-grid">
                @foreach($open as $auction)
                    <x-auction-card :auction="$auction" :href="route('bidding.index', encrypt($auction->id))"/>
                @endforeach
            </div>
        @endif
    </section>

    @if($upcoming->isNotEmpty())
        <section class="mt-4 mb-4">
            <div class="oa-page-head">
                <div>
                    <h2>Starting soon</h2>
                    <p>Bidding opens at the time shown on each card.</p>
                </div>
            </div>
            <div class="oa-grid">
                @foreach($upcoming as $auction)
                    <x-auction-card :auction="$auction" :href="route('bidding.index', encrypt($auction->id))"/>
                @endforeach
            </div>
        </section>
    @endif
</div>
@stop

@push('css')
    @vite('resources/js/login-scene.js')
@endpush
