@extends('adminlte::auth.login')

@section('auth_footer')
    @parent

    <p class="my-0">
        <a href="{{ route('hub.login') }}">
            {{ __('Login with Hub') }}
        </a>
    </p>
@stop
