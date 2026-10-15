@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'Users')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <x-page-head eyebrow="Settings" title="Users" description="Everyone who can sign in, newest first.">
        @can('user create')
            <a href="{{ route('user.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New user</a>
        @endcan
    </x-page-head>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($users->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-users"></i>
                    <strong>No users yet</strong>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Roles</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="font-weight-bold">{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->company?->name ?? '-' }}</td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="oa-pill">{{ $role->name }}</span>
                                    @endforeach
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('user.show', encrypt($user->id)) }}" class="btn btn-outline-secondary btn-xs">View</a>
                                    @can('user edit')
                                        <a href="{{ route('user.edit', encrypt($user->id)) }}" class="btn btn-outline-secondary btn-xs">Edit</a>
                                    @endcan
                                    @can('user delete')
                                        <a href="#" class="btn btn-outline-secondary btn-xs text-danger btn-delete" data-id="{{ encrypt($user->id) }}" aria-label="Delete {{ $user->name }}"><i class="fas fa-trash-alt"></i></a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@stop

@include('partials.delete-script', ['type' => 'User'])
