@extends('layouts.auth')

@section('title', 'Reset password')

@section('card')
    <h2>Reset your password</h2>
    <p class="oa-sub">Enter your email and we'll send you a link to set a new password.</p>

    @if(session('status'))
        <div class="oa-alert is-ok" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="oa-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                   class="@error('email') is-invalid @enderror">
            @error('email')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="oa-btn oa-btn-primary">Send reset link</button>
    </form>

    <p class="oa-foot"><a href="{{ route('login') }}">Back to sign in</a></p>
@endsection
