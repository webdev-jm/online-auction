@extends('layouts.auth')

@section('title', 'Set a new password')

@section('card')
    <h2>Set a new password</h2>
    <p class="oa-sub">Choose a password you don't use anywhere else.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="oa-field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" autocomplete="email" required
                   class="@error('email') is-invalid @enderror">
            @error('email')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="oa-field">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" required autofocus
                   class="@error('password') is-invalid @enderror">
            @error('password')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="oa-field">
            <label for="password-confirm">Confirm new password</label>
            <input id="password-confirm" type="password" name="password_confirmation" autocomplete="new-password" required>
        </div>

        <button type="submit" class="oa-btn oa-btn-primary">Save new password</button>
    </form>
@endsection
