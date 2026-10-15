@php
    $liveCounts = rescue(function () {
        app(\App\Services\AuctionLifecycle::class)->syncIfDue();

        return [
            'open' => \App\Models\Auction::query()->where('status', \App\Models\Auction::STATUS_OPEN)->count(),
            'upcoming' => \App\Models\Auction::query()->where('status', \App\Models\Auction::STATUS_UPCOMING)->count(),
        ];
    }, ['open' => 0, 'upcoming' => 0], false);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="color-scheme" content="light dark">
    <title>@yield('title') | {{ config('adminlte.title') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">
    @vite(['resources/sass/auth.scss', 'resources/js/login-scene.js'])
</head>
<body class="oa-auth">
    <canvas id="oa-scene" aria-hidden="true"></canvas>

    <div class="oa-auth-shell">
        <section class="oa-auth-intro">
            <a class="oa-brand" href="{{ route('welcome') }}">
                <img src="{{ asset('assets/images/auction-logo.png') }}" alt="">
                Online Auction
            </a>

            <h1>Bid on company items. <em>Live.</em></h1>
            <p>Browse items released for auction, place your bid before the clock runs out, and get told the moment you win.</p>

            <div class="oa-live" aria-label="Auction activity">
                <span><i class="dot"></i><b>{{ $liveCounts['open'] }}</b> {{ Str::plural('auction', $liveCounts['open']) }} open now</span>
                <span><i class="dot is-soon"></i><b>{{ $liveCounts['upcoming'] }}</b> starting soon</span>
            </div>
        </section>

        <main class="oa-auth-card">
            @yield('card')
        </main>
    </div>

    <footer class="oa-legal">
        &copy; {{ date('Y') }} {{ config('app.company_name', config('app.name')) }}
    </footer>
</body>
</html>
