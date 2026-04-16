@extends('layouts.app')

@section('title', 'Activity Logs - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Activity Logs</span>
        </div>
        <div class="dash-topbar-right">
            <a href="{{ route('admin.activity-logs.export', request()->all()) }}" class="btn btn--ghost">
                <span class="icon">&#128229;</span> Export CSV
            </a>
            <a href="{{ route('admin.activity-logs.stats') }}" class="btn btn--ghost">
                <span class="icon">&#128202;</span> Statistics
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    {{-- Filters Panel --}}
    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Filter Logs</div>
                    <div class="dash-panel-sub">Search, filter, and manage activity records</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="GET" action="{{ route('admin.activity-logs.index') }}">
                    <div class="grid-3">
                        @if(auth()->user()->hasRole('admin'))
                            <div>
                                <label>User ID</label>
                                <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="Filter by user">
                            </div>
                        @endif
                        <div>
                            <label>Action</label>
                            <select name="action">
                                <option value="">All Actions</option>
                                @foreach($actions as $action)
                                    <option value="{{ $action }}" {{ ($filters['action'] ?? '') == $action ? 'selected' : '' }}>
                                        {{ $action }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="success" {{ ($filters['status'] ?? '') == 'success' ? 'selected' : '' }}>Success</option>
                                <option value="failed" {{ ($filters['status'] ?? '') == 'failed' ? 'selected' : '' }}>Failed</option>
                                <option value="warning" {{ ($filters['status'] ?? '') == 'warning' ? 'selected' : '' }}>Warning</option>
                            </select>
                        </div>
                        <div>
                            <label>IP Address</label>
                            <input type="text" name="ip_address" value="{{ $filters['ip_address'] ?? '' }}" placeholder="Filter by IP">
                        </div>
                        <div>
                            <label>Date From</label>
                            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div>
                            <label>Date To</label>
                            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                    </div>
                    <div class="dash-form-actions" style="margin-top: 16px;">
                        <button type="submit" class="btn">Apply Filters</button>
                        <a href="{{ route('admin.activity-logs.index') }}" class="btn btn--ghost">Clear All</a>
                    </div>
                </form>

                @if(auth()->user()->hasRole('admin') && auth()->user()->hasPermission('activity_logs.manage'))
                    <hr class="form-divider">
                    <form method="POST" action="{{ route('admin.activity-logs.bulk-delete') }}" class="bulk-delete-form" onsubmit="return confirm('WARNING: This will permanently delete logs. Are you sure?');">
                        @csrf
                        <div class="grid-4">
                            <div>
                                <label>Delete logs older than (days)</label>
                                <input type="number" name="older_than_days" min="1" placeholder="e.g., 30">
                            </div>
                            <div>
                                <label>Delete before date</label>
                                <input type="date" name="date_from">
                            </div>
                            <div>
                                <label>Filter by action</label>
                                <select name="action_filter">
                                    <option value="">All actions</option>
                                    @foreach($actions as $action)
                                        <option value="{{ $action }}">{{ $action }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn--danger" style="width: 100%;">
                                    <span class="icon">&#128465;</span> Bulk Delete
                                </button>
                            </div>
                        </div>
                        <small class="help-text">Delete logs matching the criteria above. Use with caution!</small>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Activity Logs Table --}}
    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Activity Records</div>
                    <div class="dash-panel-sub">{{ $logs->total() }} total entries found</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                @if(auth()->user()->hasRole('admin'))
                                    <th>User</th>
                                @endif
                                <th>Action</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>IP Address</th>
                                <th>Time</th>
                                <th style="text-align: center;">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td><code>#{{ $log->id }}</code></td>
                                    @if(auth()->user()->hasRole('admin'))
                                        <td>
                                            <div class="user-cell">
                                                <span class="user-name">{{ $log->user?->name ?? 'Unknown' }}</span>
                                                <span class="user-meta">ID: {{ $log->user_id ?? 'N/A' }}</span>
                                            </div>
                                        </td>
                                    @endif
                                    <td>
                                        @php
                                            $actionIcon = match($log->action) {
                                                'login.success' => '&#128275;',
                                                'login.failed' => '&#128274;',
                                                'logout' => '&#128682;',
                                                'profile.update' => '&#128100;',
                                                'password.change' => '&#128273;',
                                                default => '&#128203;'
                                            };
                                        @endphp
                                        <span class="action-badge action-{{ explode('.', $log->action)[0] }}">
                                            <span class="action-icon">{!! $actionIcon !!}</span>
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td>{{ Str::limit($log->description, 50) }}</td>
                                    <td>
                                        <span class="status-badge status-{{ $log->status }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td><code class="ip-address">{{ $log->ip_address }}</code></td>
                                    <td>
                                        <div class="time-cell">
                                            <span class="time-main">{{ $log->created_at->format('M d, Y') }}</span>
                                            <span class="time-sub">{{ $log->created_at->format('h:i A') }}</span>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="action-btns">
                                            <a href="{{ route('admin.activity-logs.show', $log) }}" class="btn btn-sm btn--ghost" title="View Details">
                                                <span class="icon">&#128065;</span>
                                            </a>
                                            @if(auth()->user()->hasRole('admin') && auth()->user()->hasPermission('activity_logs.manage'))
                                                <form method="POST" action="{{ route('admin.activity-logs.destroy', $log) }}" class="inline-form" onsubmit="return confirm('Delete this log?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn--danger" title="Delete">
                                                        <span class="icon">&#128465;</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()->hasRole('admin') ? 8 : 7 }}" class="text-center" style="padding: 40px;">
                                        <div class="empty-state">
                                            <span class="empty-icon">&#128203;</span>
                                            <p>No activity logs found.</p>
                                            <p class="empty-sub">Activity will appear here when users perform actions.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="pagination-wrap" style="margin-top: 20px;">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
