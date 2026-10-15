@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Edit '.$auction->auction_code)

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    {{ html()->form('POST', route('auction.update', encrypt($auction->id)))->open() }}
        <div class="oa-page-head">
            <div>
                <a href="{{ route('auction.show', encrypt($auction->id)) }}" class="small font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> {{ $auction->auction_code }}</a>
                <h2 class="mt-2">Edit auction</h2>
                <p>Changes apply right away, including to bids still to come.</p>
            </div>
            <div class="oa-toolbar">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Save changes</button>
            </div>
        </div>

        @include('pages.auctions._form')
    {{ html()->form()->close() }}
</div>
@stop
