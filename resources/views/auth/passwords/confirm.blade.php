@extends('layouts.auth')

@section('title', 'Confirm password')

@section('card')
    <h2>Confirm your password</h2>
    <p class="oa-sub">For your security, enter your password again to continue.</p>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <div class="oa-field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required autofocus
                   class="@error('password') is-invalid @enderror">
            @error('password')
                <span class="oa-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="oa-btn oa-btn-primary">Confirm</button>
    </form>

    @if(Route::has('password.request'))
        <p class="oa-foot"><a href="{{ route('password.request') }}">Forgot your password?</a></p>
    @endif
@endsection
