@extends('layouts.app')

@section('title', 'Active Sessions - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.activity-logs.index') }}">Activity Logs</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Active Sessions</span>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Active User Sessions</div>
                    <div class="dash-panel-sub">{{ $sessions->total() }} active sessions found</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Device</th>
                                <th>Browser</th>
                                <th>OS</th>
                                <th>IP Address</th>
                                <th>Started</th>
                                <th>Last Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <span class="user-name">{{ $session->user?->name ?? 'Unknown' }}</span>
                                            <span class="user-meta">ID: {{ $session->user_id }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $session->device_type ?? 'Unknown' }}</td>
                                    <td>{{ $session->browser ?? 'Unknown' }}</td>
                                    <td>{{ $session->os ?? 'Unknown' }}</td>
                                    <td><code class="ip-address">{{ $session->ip_address }}</code></td>
                                    <td>{{ $session->started_at->format('M d, h:i A') }}</td>
                                    <td>{{ $session->last_activity_at->diffForHumans() }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.sessions.terminate', $session->id) }}" onsubmit="return confirm('Terminate this session?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger">Terminate</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center" style="padding: 40px;">
                                        <div class="empty-state">
                                            <span class="empty-icon">&#128241;</span>
                                            <p>No active sessions found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap" style="margin-top: 20px;">
                    {{ $sessions->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
