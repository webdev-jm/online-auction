@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'New auction')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    {{ html()->form('POST', route('auction.store'))->open() }}
        <div class="oa-page-head">
            <div>
                <a href="{{ route('auction.index') }}" class="small font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> Auctions</a>
                <h2 class="mt-2">New auction</h2>
                <p>Bidders see it as soon as it's saved. Bidding opens at the start time.</p>
            </div>
            <div class="oa-toolbar">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Create auction</button>
            </div>
        </div>

        @include('pages.auctions._form')
    {{ html()->form()->close() }}
</div>
@stop
