@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Roles')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <x-page-head eyebrow="Settings" title="Roles" description="Groups of permissions you can give to users.">
        {{ html()->form('GET', route('role.index'))->open() }}
            <div class="input-group input-group-sm" style="width: min(220px, 100%)">
                <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span></div>
                {{ html()->search('search', $search ?? '')->class('form-control')->placeholder('Role name')->attribute('aria-label', 'Search roles') }}
            </div>
        {{ html()->form()->close() }}
        @can('role create')
            <a href="{{ route('role.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New role</a>
        @endcan
    </x-page-head>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($roles->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-user-lock"></i>
                    <strong>No roles found</strong>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-right">Users</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($roles as $role)
                            <tr>
                                <td class="font-weight-bold">{{ $role->name }}</td>
                                <td class="text-right oa-num">{{ $role->users()->count() }}</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('role.show', encrypt($role->id)) }}" class="btn btn-outline-secondary btn-xs">View</a>
                                    @can('role edit')
                                        <a href="{{ route('role.edit', encrypt($role->id)) }}" class="btn btn-outline-secondary btn-xs">Edit</a>
                                    @endcan
                                    @can('role delete')
                                        <a href="#" class="btn btn-outline-secondary btn-xs text-danger btn-delete" data-id="{{ encrypt($role->id) }}" aria-label="Delete {{ $role->name }}"><i class="fas fa-trash-alt"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if(method_exists($roles, 'hasPages') && $roles->hasPages())
            <div class="card-footer">{{ $roles->links() }}</div>
        @endif
    </div>
</div>
@stop

@include('partials.delete-script', ['type' => 'Role'])
