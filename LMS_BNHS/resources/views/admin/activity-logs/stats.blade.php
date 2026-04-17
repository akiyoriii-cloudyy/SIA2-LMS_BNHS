@extends('layouts.app')

@section('title', 'Activity Statistics - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.activity-logs.index') }}">Activity Logs</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Statistics</span>
        </div>
    </div>

    {{-- Stats Overview --}}
    <div class="grid-4" style="margin-bottom: 20px;">
        <div class="stat-card stat-primary">
            <div class="stat-icon">&#128203;</div>
            <div class="stat-value">{{ $stats['total_logs_today'] }}</div>
            <div class="stat-label">Logs Today</div>
        </div>
        <div class="stat-card stat-info">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-value">{{ $stats['total_logs_week'] }}</div>
            <div class="stat-label">Logs This Week</div>
        </div>
        <div class="stat-card stat-danger">
            <div class="stat-icon">&#128274;</div>
            <div class="stat-value">{{ $stats['failed_logins_today'] }}</div>
            <div class="stat-label">Failed Logins</div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">&#128241;</div>
            <div class="stat-value">{{ $stats['active_sessions'] }}</div>
            <div class="stat-label">Active Sessions</div>
        </div>
    </div>

    <div class="dash-row-2">
        {{-- Top Actions --}}
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Top Actions</div>
                    <div class="dash-panel-sub">Most frequent activities (Last 7 days)</div>
                </div>
            </div>
            <div class="dash-panel-body">
                @if($stats['top_actions']->isEmpty())
                    <div class="empty-state">
                        <span class="empty-icon">&#128203;</span>
                        <p>No activity data available</p>
                    </div>
                @else
                    <div class="activity-list">
                        @foreach($stats['top_actions'] as $action)
                            <div class="activity-item">
                                <div class="activity-info">
                                    @php
                                        $icon = match(explode('.', $action->action)[0]) {
                                            'login' => '&#128275;',
                                            'logout' => '&#128682;',
                                            'profile' => '&#128100;',
                                            default => '&#128203;'
                                        };
                                    @endphp
                                    <span class="activity-icon">{!! $icon !!}</span>
                                    <span class="activity-name">{{ $action->action }}</span>
                                </div>
                                <div class="activity-bar-wrap">
                                    <div class="activity-bar" style="width: {{ min(100, ($action->count / $stats['top_actions']->max('count')) * 100) }}%;"></div>
                                    <span class="activity-count">{{ $action->count }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Failed Logins --}}
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Security Alerts</div>
                    <div class="dash-panel-sub">Failed login attempts (Last 24 hours)</div>
                </div>
            </div>
            <div class="dash-panel-body">
                @if($recentFailedLogins->isEmpty())
                    <div class="empty-state">
                        <span class="empty-icon" style="color: #22c55e;">&#9989;</span>
                        <p>No failed login attempts</p>
                        <p class="empty-sub">Your system is secure</p>
                    </div>
                @else
                    <div class="security-list">
                        @foreach($recentFailedLogins as $login)
                            <div class="security-item">
                                <div class="security-icon">&#128274;</div>
                                <div class="security-info">
                                    <div class="security-title">{{ $login->user?->name ?? 'Unknown User' }}</div>
                                    <div class="security-meta">
                                        {{ $login->ip_address }} · {{ $login->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="dash-form-actions" style="margin-top: 20px;">
        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn--ghost">
            <span class="icon">&#8592;</span> Back to Logs
        </a>
    </div>
@endsection
