@extends('layouts.app')

{{-- Customize layout sections --}}
@section('subtitle', 'System logs')

{{-- Content body: main page content --}}
@section('content_body')
<div class="pt-3">
    <x-page-head eyebrow="Settings" title="System logs" description="What people did in the app, newest first.">
        {{ html()->form('GET', route('system-logs'))->open() }}
            <div class="input-group input-group-sm" style="width: min(280px, 100%)">
                <div class="input-group-prepend"><span class="input-group-text bg-transparent"><i class="fas fa-search"></i></span></div>
                {{ html()->search('search', $search)->class('form-control')->placeholder('Description, user or value')->attribute('aria-label', 'Search logs') }}
            </div>
        {{ html()->form()->close() }}
    </x-page-head>

    <div class="card">
        <div class="card-body p-0 table-responsive">
            @if($activities->isEmpty())
                <div class="oa-empty">
                    <i class="fas fa-stream"></i>
                    <strong>{{ $search ? 'No log entries match "'.$search.'"' : 'Nothing logged yet' }}</strong>
                </div>
            @else
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>What happened</th>
                            <th>User</th>
                            <th>Changes</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td><span class="oa-pill">{{ $activity->log_name }}</span></td>
                                <td>{{ $activity->description }}</td>
                                <td class="text-nowrap">{{ $activity->causer?->name ?? 'System' }}</td>
                                <td class="small">
                                    @if($activity->log_name == 'updated' && !empty($updates[$activity->id]))
                                        <ul class="list-unstyled mb-0">
                                            @foreach($updates[$activity->id] as $column => $data)
                                                <li><b>{{ $column }}:</b> {{ is_scalar($data['old']) || $data['old'] === null ? $data['old'] : json_encode($data['old']) }}
                                                    <span class="text-muted">→</span> {{ is_scalar($data['new']) || $data['new'] === null ? $data['new'] : json_encode($data['new']) }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </td>
                                <td class="text-nowrap" title="{{ $activity->created_at }}">{{ $activity->created_at->format('M j, Y g:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @if($activities->hasPages())
            <div class="card-footer">{{ $activities->links() }}</div>
        @endif
    </div>
</div>
@stop
