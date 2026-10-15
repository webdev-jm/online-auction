@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Items')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <x-page-head eyebrow="Manage" title="Items" description="Everything that can be put up for auction.">
        {{ html()->form('GET', route('item.index'))->open() }}
            <div class="input-group input-group-sm" style="width: min(260px, 100%)">
                <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span></div>
                {{ html()->search('search', $search)->class('form-control')->placeholder('Number, name, brand or specs')->attribute('aria-label', 'Search items') }}
            </div>
        {{ html()->form()->close() }}
        @can('item upload')
            <a href="{{ route('item.upload') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-upload mr-1"></i> Upload</a>
        @endcan
        @can('item create')
            <a href="{{ route('item.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New item</a>
        @endcan
    </x-page-head>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($items->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-laptop"></i>
                    @if($search)
                        <strong>No items match "{{ $search }}"</strong>
                        <a href="{{ route('item.index') }}">Clear search</a>
                    @else
                        <strong>No items yet</strong>
                    @endif
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 56px"></th>
                            <th>Item number</th>
                            <th>Name</th>
                            <th>Brand</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            @php
                                $thumb = $item->pictures->first();
                                $sold = $item->auctions->contains(fn ($auction) => $auction->auction_winner && $auction->auction_winner->status !== \App\Models\AuctionWinner::STATUS_FORFEITED);
                            @endphp
                            <tr>
                                <td>
                                    @if($thumb)
                                        <img src="{{ $thumb->url('small') }}" alt="" width="40" height="40" style="border-radius: 6px; object-fit: cover" loading="lazy">
                                    @endif
                                </td>
                                <td class="oa-num font-weight-bold">{{ $item->item_number }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->brand }}</td>
                                <td>
                                    @if($sold)
                                        <span class="oa-pill is-sold">Sold</span>
                                    @else
                                        <span class="oa-pill is-open">Available</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('item.show', encrypt($item->id)) }}" class="btn btn-outline-secondary btn-xs">View</a>
                                    @can('item edit')
                                        <a href="{{ route('item.edit', encrypt($item->id)) }}" class="btn btn-outline-secondary btn-xs">Edit</a>
                                    @endcan
                                    @can('item delete')
                                        <a href="#" class="btn btn-outline-secondary btn-xs text-danger btn-delete" data-id="{{ encrypt($item->id) }}" aria-label="Delete {{ $item->name }}"><i class="fas fa-trash-alt"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($items->hasPages())
            <div class="card-footer">{{ $items->links() }}</div>
        @endif
    </div>
</div>
@stop

@include('partials.delete-script', ['type' => 'Item'])
