@extends('layouts.auth')

@section('title', 'Verify your email')

@section('card')
    <h2>Verify your email</h2>
    <p class="oa-sub">We sent a verification link to your email. Open it to finish setting up your account.</p>

    @if(session('resent'))
        <div class="oa-alert is-ok" role="status">A new verification link has been sent.</div>
    @endif

    <form method="POST" action="{{ route('verification.resend') }}">
        @csrf
        <button type="submit" class="oa-btn oa-btn-primary">Send the link again</button>
    </form>
@endsection
