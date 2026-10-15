@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', 'Browse auctions')

{{-- Content body: main page content --}}

@section('content_body')
    <div class="pt-3">
        <livewire:terms-agreement/>
    </div>
@stop
