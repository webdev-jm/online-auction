@extends('layouts.auth')

@section('title', 'Create an account')

@section('card')
    <h2>Create an account</h2>
    <p class="oa-sub">Already have a company Google account? <a href="{{ route('google.login') }}">Continue with Google</a> instead.</p>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <div class="oa-field">
            <label for="name">Full name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus
                   class="@error('name') is-invalid @enderror">
            @error('name')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="oa-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required
                   class="@error('email') is-invalid @enderror">
            @error('email')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="oa-field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required
                   class="@error('password') is-invalid @enderror">
            @error('password')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="oa-field">
            <label for="password-confirm">Confirm password</label>
            <input id="password-confirm" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="oa-btn oa-btn-primary">Create account</button>
    </form>

    <p class="oa-foot">Have an account? <a href="{{ route('login') }}">Sign in</a></p>
@endsection
