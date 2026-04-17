@extends('layouts.app')

@section('title', 'Security Audit Logs')

@section('content')
<div class="page-header">
    <h1>Security Audit Logs</h1>
    <div class="page-actions">
        <a href="{{ route('admin.security-audit.alerts') }}" class="btn btn-warning">
            <span class="icon">&#128680;</span> Security Alerts
            @if($metrics['unacknowledged_alerts'] > 0)
                <span class="badge badge-danger">{{ $metrics['unacknowledged_alerts'] }}</span>
            @endif
        </a>
        <a href="{{ route('admin.security-audit.reports') }}" class="btn btn-primary">
            <span class="icon">&#128196;</span> Analytics Reports
        </a>
        <a href="{{ route('admin.security-audit.export') }}" class="btn btn-secondary">
            <span class="icon">&#128229;</span> Export Data
        </a>
    </div>
</div>

<div class="security-overview">
    <div class="metrics-grid">
        <div class="metric-card danger">
            <div class="metric-icon">&#128274;</div>
            <div class="metric-value">{{ $metrics['failed_logins_24h'] }}</div>
            <div class="metric-label">Failed Logins (24h)</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['failed_logins_24h'] > 10 ? 'up' : 'stable' }}">
                    {{ $metrics['failed_logins_24h'] > 10 ? 'High' : 'Normal' }}
                </span>
            </div>
        </div>
        <div class="metric-card warning">
            <div class="metric-icon">&#128680;</div>
            <div class="metric-value">{{ $metrics['active_alerts'] }}</div>
            <div class="metric-label">Active Security Alerts</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['active_alerts'] > 5 ? 'up' : 'stable' }}">
                    {{ $metrics['active_alerts'] > 5 ? 'Attention Needed' : 'Monitored' }}
                </span>
            </div>
        </div>
        <div class="metric-card info">
            <div class="metric-icon">&#128276;</div>
            <div class="metric-value">{{ $metrics['unacknowledged_alerts'] }}</div>
            <div class="metric-label">Unacknowledged</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['unacknowledged_alerts'] > 0 ? 'up' : 'stable' }}">
                    {{ $metrics['unacknowledged_alerts'] > 0 ? 'Action Required' : 'All Clear' }}
                </span>
            </div>
        </div>
        <div class="metric-card critical">
            <div class="metric-icon">&#128269;</div>
            <div class="metric-value">{{ $metrics['critical_alerts'] }}</div>
            <div class="metric-label">Critical Threats</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['critical_alerts'] > 0 ? 'critical' : 'stable' }}">
                    {{ $metrics['critical_alerts'] > 0 ? 'Immediate Action' : 'Secured' }}
                </span>
            </div>
        </div>
    </div>

    <div class="security-status-bar">
        <div class="status-item">
            <span class="status-label">System Status:</span>
            <span class="status-value {{ $metrics['critical_alerts'] > 0 ? 'danger' : ($metrics['active_alerts'] > 5 ? 'warning' : 'success') }}">
                {{ $metrics['critical_alerts'] > 0 ? 'CRITICAL' : ($metrics['active_alerts'] > 5 ? 'WARNING' : 'SECURE') }}
            </span>
        </div>
        <div class="status-item">
            <span class="status-label">Failed Logins (Week):</span>
            <span class="status-value">{{ $metrics['failed_logins_week'] }}</span>
        </div>
        <div class="status-item">
            <span class="status-label">High Severity Alerts:</span>
            <span class="status-value">{{ $metrics['high_severity_alerts'] }}</span>
        </div>
    </div>
</div>

<div class="filters">
    <form method="GET" action="{{ route('admin.security-audit.index') }}">
        <div class="filter-row">
            <select name="event_type" class="form-control">
                <option value="">All Event Types</option>
                @foreach($eventTypes as $type)
                    <option value="{{ $type }}" {{ request('event_type') == $type ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>

            <select name="severity" class="form-control">
                <option value="">All Severities</option>
                @foreach($severities as $severity)
                    <option value="{{ $severity }}" {{ request('severity') == $severity ? 'selected' : '' }}>
                        {{ ucfirst($severity) }}
                    </option>
                @endforeach
            </select>

            <select name="resolved" class="form-control">
                <option value="">All Status</option>
                <option value="resolved" {{ request('resolved') == 'resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="unresolved" {{ request('resolved') == 'unresolved' ? 'selected' : '' }}>Unresolved</option>
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="To">

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.security-audit.index') }}" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="security-table-container">
    <div class="table-header">
        <h3>Security Events</h3>
        <div class="table-actions">
            <span class="record-count">{{ $logs->total() }} records</span>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="security-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Event Type</th>
                    <th>Severity</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>IP Address</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="security-row {{ $log->severity === 'critical' ? 'critical-row' : ($log->severity === 'high' ? 'high-row' : '') }}">
                        <td>
                            <span class="log-id">#{{ $log->id }}</span>
                        </td>
                        <td>
                            <div class="event-type">
                                <span class="event-icon event-{{ $log->event_type }}">
                                    {{ getEventIcon($log->event_type) }}
                                </span>
                                <span class="event-label">
                                    {{ ucfirst(str_replace('_', ' ', $log->event_type)) }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <span class="severity-indicator severity-{{ $log->severity }}">
                                {{ strtoupper($log->severity) }}
                            </span>
                        </td>
                        <td>
                            <div class="description-cell">
                                <p class="description-text" title="{{ $log->description }}">
                                    {{ $log->description }}
                                </p>
                                @if($log->details)
                                    <button class="details-toggle" onclick="toggleDetails({{ $log->id }})">
                                        <span class="icon">&#128220;</span> Details
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="user-cell">
                                @if($log->user)
                                    <span class="user-name">{{ $log->user->name }}</span>
                                    <span class="user-email">{{ $log->user->email }}</span>
                                @else
                                    <span class="user-unknown">System/Anonymous</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="ip-cell">
                                <code class="ip-address">{{ $log->ip_address }}</code>
                                @if($log->location)
                                    <span class="location">{{ $log->location }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="status-cell">
                                @if($log->is_resolved)
                                    <span class="status-badge resolved">
                                        <span class="status-icon">&#10004;</span> Resolved
                                    </span>
                                @else
                                    <span class="status-badge pending">
                                        <span class="status-icon">&#128276;</span> Pending
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="time-cell">
                                <span class="time-main">{{ $log->created_at->format('M j, Y') }}</span>
                                <span class="time-sub">{{ $log->created_at->format('H:i:s') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-cell">
                                <a href="{{ route('admin.security-audit.show', $log) }}" class="action-btn view-btn" title="View Details">
                                    <span class="icon">&#128065;</span>
                                </a>
                                @if(!$log->is_resolved)
                                    <form method="POST" action="{{ route('admin.security-audit.resolve', $log) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="action-btn resolve-btn" title="Resolve" onclick="return confirm('Resolve this security event?')">
                                            <span class="icon">&#10004;</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @if($log->details)
                        <tr id="details-{{ $log->id }}" class="details-row" style="display: none;">
                            <td colspan="9">
                                <div class="details-content">
                                    <h4>Event Details</h4>
                                    <div class="details-grid">
                                        @foreach($log->details as $key => $value)
                                            <div class="detail-item">
                                                <label>{{ ucfirst(str_replace('_', ' ', $key)) }}:</label>
                                                <span>{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if($log->user_agent)
                                        <div class="detail-item full-width">
                                            <label>User Agent:</label>
                                            <code>{{ $log->user_agent }}</code>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        {{ $logs->links() }}
    </div>
</div>

@php
function getEventIcon($eventType) {
    $icons = [
        'failed_login' => '&#128274;',
        'security_breach' => '&#128269;',
        'suspicious_activity' => '&#128680;',
        'login_success' => '&#10004;',
        'logout' => '&#128075;',
        'password_change' => '&#128272;',
        'profile_update' => '&#128100;',
    ];
    return $icons[$eventType] ?? '&#128196;';
}
@endphp

<style>
.security-overview {
    margin-bottom: 2rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
    border-left: 4px solid #007bff;
}

.metric-card.danger { border-left-color: #dc3545; }
.metric-card.warning { border-left-color: #ffc107; }
.metric-card.info { border-left-color: #17a2b8; }
.metric-card.critical { border-left-color: #721c24; }

.metric-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
    border-radius: 0 12px 0 60px;
}

.metric-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.metric-value {
    font-size: 2.2rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.metric-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.metric-trend {
    font-size: 0.8rem;
}

.trend-indicator {
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.75rem;
}

.trend-indicator.stable {
    background: #d4edda;
    color: #155724;
}

.trend-indicator.up {
    background: #f8d7da;
    color: #721c24;
}

.trend-indicator.critical {
    background: #721c24;
    color: white;
}

.security-status-bar {
    background: white;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.status-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.status-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.status-value {
    font-weight: 600;
    font-size: 0.9rem;
}

.status-value.success { color: #28a745; }
.status-value.warning { color: #ffc107; }
.status-value.danger { color: #dc3545; }

.security-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.record-count {
    background: #f8f9fa;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    color: #666;
}

.table-responsive {
    overflow-x: auto;
}

.security-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.security-table th {
    background: #f8f9fa;
    padding: 1rem 0.75rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.security-table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.critical-row {
    background: rgba(220, 53, 69, 0.05);
}

.high-row {
    background: rgba(255, 193, 7, 0.05);
}

.log-id {
    font-family: monospace;
    font-size: 0.8em;
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 4px;
    color: #666;
}

.event-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.event-icon {
    font-size: 1.2rem;
    width: 24px;
    text-align: center;
}

.event-label {
    font-size: 0.85rem;
    font-weight: 500;
}

.severity-indicator {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.severity-low {
    background: #d4edda;
    color: #155724;
}

.severity-medium {
    background: #fff3cd;
    color: #856404;
}

.severity-high {
    background: #f8d7da;
    color: #721c24;
}

.severity-critical {
    background: #721c24;
    color: white;
}

.description-cell {
    max-width: 300px;
}

.description-text {
    margin: 0;
    line-height: 1.4;
    color: #333;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.details-toggle {
    background: none;
    border: 1px solid #dee2e6;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.75rem;
    color: #666;
    cursor: pointer;
    margin-top: 0.25rem;
}

.details-toggle:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.user-cell {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    color: #333;
}

.user-email {
    font-size: 0.75rem;
    color: #666;
}

.user-unknown {
    color: #999;
    font-style: italic;
}

.ip-cell {
    display: flex;
    flex-direction: column;
}

.ip-address {
    font-family: monospace;
    font-size: 0.8em;
    background: #f1f3f4;
    padding: 2px 4px;
    border-radius: 3px;
}

.location {
    font-size: 0.75rem;
    color: #666;
    margin-top: 0.25rem;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-badge.resolved {
    background: #d4edda;
    color: #155724;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-icon {
    font-size: 0.8rem;
}

.time-cell {
    display: flex;
    flex-direction: column;
}

.time-main {
    font-weight: 500;
    color: #333;
}

.time-sub {
    font-size: 0.75rem;
    color: #666;
}

.action-cell {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 32px;
    height: 32px;
    border: none;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.view-btn {
    background: #007bff;
    color: white;
}

.view-btn:hover {
    background: #0056b3;
}

.resolve-btn {
    background: #28a745;
    color: white;
}

.resolve-btn:hover {
    background: #1e7e34;
}

.inline-form {
    display: inline;
    margin: 0;
}

.details-row {
    background: #f8f9fa;
}

.details-content {
    padding: 1rem;
}

.details-content h4 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1rem;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item.full-width {
    grid-column: 1 / -1;
}

.detail-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.25rem;
    font-size: 0.85rem;
}

.detail-item span {
    color: #333;
    font-size: 0.9rem;
}

.detail-item code {
    background: #fff;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    max-height: 150px;
    overflow-y: auto;
    display: block;
}

.table-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}

.badge {
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-danger {
    background: #dc3545;
    color: white;
}

@media (max-width: 768px) {
    .security-status-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .status-item {
        align-items: flex-start;
    }
    
    .metrics-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function toggleDetails(logId) {
    const detailsRow = document.getElementById('details-' + logId);
    detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
}
</script>
@endsection
