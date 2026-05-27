@extends('layouts.app')

@section('title', 'User Sessions - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.activity-logs.index') }}">Activity Logs</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">User #{{ $userId }} Sessions</span>
        </div>
    </div>

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Session History</div>
                    <div class="dash-panel-sub">{{ $sessions->total() }} sessions found ({{ $activeCount }} active)</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Session ID</th>
                                <th>Device</th>
                                <th>IP Address</th>
                                <th>Started</th>
                                <th>Ended</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sessions as $session)
                                <tr>
                                    <td><code>{{ Str::limit($session->session_id, 20) }}</code></td>
                                    <td>{{ $session->device_type ?? 'Unknown' }} / {{ $session->browser ?? 'Unknown' }}</td>
                                    <td><code class="ip-address">{{ $session->ip_address }}</code></td>
                                    <td>{{ $session->started_at->format('M d, Y h:i A') }}</td>
                                    <td>{{ $session->ended_at ? $session->ended_at->format('M d, Y h:i A') : '-' }}</td>
                                    <td>
                                        <span class="status-badge {{ $session->is_active ? 'status-success' : 'status-failed' }}">
                                            {{ $session->is_active ? 'Active' : 'Ended' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center" style="padding: 40px;">
                                        <div class="empty-state">
                                            <span class="empty-icon">&#128241;</span>
                                            <p>No sessions found.</p>
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
