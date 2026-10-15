@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Companies')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <x-page-head eyebrow="Settings" title="Companies" description="Used to limit an auction to one company's employees.">
        @can('company create')
            <a href="{{ route('company.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New company</a>
        @endcan
    </x-page-head>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($companies->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-building"></i>
                    <strong>No companies yet</strong>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($companies as $company)
                            <tr>
                                <td class="font-weight-bold">{{ $company->name }}</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('company.show', encrypt($company->id)) }}" class="btn btn-outline-secondary btn-xs">View</a>
                                    @can('company edit')
                                        <a href="{{ route('company.edit', encrypt($company->id)) }}" class="btn btn-outline-secondary btn-xs">Edit</a>
                                    @endcan
                                    @can('company delete')
                                        <a href="#" class="btn btn-outline-secondary btn-xs text-danger btn-delete" data-id="{{ encrypt($company->id) }}" aria-label="Delete {{ $company->name }}"><i class="fas fa-trash-alt"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if(method_exists($companies, 'hasPages') && $companies->hasPages())
            <div class="card-footer">{{ $companies->links() }}</div>
        @endif
    </div>
</div>
@stop

@include('partials.delete-script', ['type' => 'Company'])
