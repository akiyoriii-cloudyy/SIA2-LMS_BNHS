@extends('layouts.app')

@section('title', 'Security Alerts')

@section('content')
<div class="page-header">
    <h1>Security Alerts</h1>
    <div class="page-actions">
        <a href="{{ route('admin.security-audit.index') }}" class="btn btn-secondary">
            <span class="icon">&#8592;</span> Back to Audit Logs
        </a>
        <a href="{{ route('admin.security-audit.reports') }}" class="btn btn-primary">
            <span class="icon">&#128196;</span> Analytics Reports
        </a>
    </div>
</div>

<div class="alerts-overview">
    <div class="metrics-grid">
        <div class="metric-card critical">
            <div class="metric-icon">&#128680;</div>
            <div class="metric-value">{{ $metrics['critical_alerts'] }}</div>
            <div class="metric-label">Critical Alerts</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['critical_alerts'] > 0 ? 'critical' : 'stable' }}">
                    {{ $metrics['critical_alerts'] > 0 ? 'Immediate Action' : 'Secured' }}
                </span>
            </div>
        </div>
        <div class="metric-card warning">
            <div class="metric-icon">&#128680;</div>
            <div class="metric-value">{{ $metrics['high_severity_alerts'] }}</div>
            <div class="metric-label">High Severity</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['high_severity_alerts'] > 3 ? 'up' : 'stable' }}">
                    {{ $metrics['high_severity_alerts'] > 3 ? 'Attention Needed' : 'Monitored' }}
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
        <div class="metric-card primary">
            <div class="metric-icon">&#128200;</div>
            <div class="metric-value">{{ $metrics['active_alerts'] }}</div>
            <div class="metric-label">Active Alerts</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $metrics['active_alerts'] > 5 ? 'up' : 'stable' }}">
                    {{ $metrics['active_alerts'] > 5 ? 'Monitoring' : 'Normal' }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="filters">
    <form method="GET" action="{{ route('admin.security-audit.alerts') }}">
        <div class="filter-row">
            <select name="alert_type" class="form-control">
                <option value="">All Alert Types</option>
                @foreach($alertTypes as $type)
                    <option value="{{ $type }}" {{ request('alert_type') == $type ? 'selected' : '' }}>
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

            <select name="status" class="form-control">
                <option value="">All Status</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="acknowledged" {{ request('status') == 'acknowledged' ? 'selected' : '' }}>Acknowledged</option>
                <option value="unacknowledged" {{ request('status') == 'unacknowledged' ? 'selected' : '' }}>Unacknowledged</option>
            </select>

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.security-audit.alerts') }}" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="alerts-container">
    @if($alerts->count() > 0)
        <div class="alerts-grid">
            @foreach($alerts as $alert)
                <div class="alert-card {{ $alert->severity }} {{ !$alert->is_acknowledged ? 'unacknowledged' : '' }}">
                    <div class="alert-header">
                        <div class="alert-title">
                            <span class="alert-icon">{{ getAlertIcon($alert->alert_type) }}</span>
                            <h4>{{ $alert->title }}</h4>
                            <span class="alert-type-badge">{{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}</span>
                        </div>
                        <div class="alert-severity">
                            <span class="severity-badge severity-{{ $alert->severity }}">
                                {{ strtoupper($alert->severity) }}
                            </span>
                        </div>
                    </div>

                    <div class="alert-body">
                        <p class="alert-description">{{ $alert->description }}</p>
                        
                        @if($alert->trigger_data)
                            <div class="alert-details">
                                <button class="details-toggle" onclick="toggleAlertDetails({{ $alert->id }})">
                                    <span class="icon">&#128220;</span> Trigger Details
                                </button>
                                <div id="alert-details-{{ $alert->id }}" class="details-content" style="display: none;">
                                    <div class="details-grid">
                                        @foreach($alert->trigger_data as $key => $value)
                                            <div class="detail-item">
                                                <label>{{ ucfirst(str_replace('_', ' ', $key)) }}:</label>
                                                <span>{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT) : $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="alert-meta">
                            <div class="meta-item">
                                <label>Target:</label>
                                <span>
                                    @if($alert->target_type === 'ip')
                                        <code>{{ $alert->target_value }}</code>
                                    @elseif($alert->target_type === 'email')
                                        {{ $alert->target_value }}
                                    @elseif($alert->target_type === 'user')
                                        User ID: {{ $alert->target_value }}
                                    @else
                                        {{ $alert->target_value ?? 'System' }}
                                    @endif
                                </span>
                            </div>
                            <div class="meta-item">
                                <label>Occurrences:</label>
                                <span class="occurrence-count">{{ $alert->occurrence_count }}</span>
                            </div>
                            <div class="meta-item">
                                <label>First Seen:</label>
                                <span>{{ $alert->first_occurrence_at->format('M j, Y H:i') }}</span>
                            </div>
                            <div class="meta-item">
                                <label>Last Seen:</label>
                                <span>{{ $alert->last_occurrence_at->format('M j, Y H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="alert-footer">
                        <div class="alert-status">
                            @if($alert->is_active)
                                <span class="status-badge active">
                                    <span class="status-icon">&#128308;</span> Active
                                </span>
                            @else
                                <span class="status-badge inactive">
                                    <span class="status-icon">&#128308;</span> Inactive
                                </span>
                            @endif

                            @if($alert->is_acknowledged)
                                <span class="status-badge acknowledged">
                                    <span class="status-icon">&#10004;</span> Acknowledged
                                    @if($alert->acknowledged_by)
                                        by {{ $alert->acknowledgedBy->name }}
                                    @endif
                                </span>
                            @else
                                <span class="status-badge pending">
                                    <span class="status-icon">&#128276;</span> Pending
                                </span>
                            @endif
                        </div>

                        <div class="alert-actions">
                            @if(!$alert->is_acknowledged)
                                <form method="POST" action="{{ route('admin.security-audit.alerts.acknowledge', $alert) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Acknowledge this alert?')">
                                        <span class="icon">&#10004;</span> Acknowledge
                                    </button>
                                </form>
                            @endif

                            @if($alert->is_active)
                                <form method="POST" action="{{ route('admin.security-audit.alerts.deactivate', $alert) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Deactivate this alert?')">
                                        <span class="icon">&#128263;</span> Deactivate
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($alert->acknowledgment_notes)
                        <div class="acknowledgment-notes">
                            <strong>Notes:</strong> {{ $alert->acknowledgment_notes }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="pagination-container">
            {{ $alerts->links() }}
        </div>
    @else
        <div class="no-alerts">
            <div class="no-alerts-icon">&#128269;</div>
            <h3>No Security Alerts</h3>
            <p>Great! There are currently no security alerts to review.</p>
        </div>
    @endif
</div>

@php
function getAlertIcon($alertType) {
    $icons = [
        'brute_force' => '&#128274;',
        'suspicious_ip' => '&#128269;',
        'account_lockout' => '&#128272;',
        'unusual_activity' => '&#128680;',
    ];
    return $icons[$alertType] ?? '&#128680;';
}
@endphp

<style>
.alerts-overview {
    margin-bottom: 2rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    position: relative;
    overflow: hidden;
    border-left: 4px solid #007bff;
    text-align: center;
}

.metric-card.critical { border-left-color: #dc3545; }
.metric-card.warning { border-left-color: #ffc107; }
.metric-card.info { border-left-color: #17a2b8; }
.metric-card.primary { border-left-color: #007bff; }

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

.alerts-container {
    margin-top: 2rem;
}

.alerts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.alert-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    border-left: 4px solid #007bff;
    position: relative;
}

.alert-card.critical { border-left-color: #dc3545; }
.alert-card.high { border-left-color: #ffc107; }
.alert-card.medium { border-left-color: #17a2b8; }
.alert-card.low { border-left-color: #28a745; }

.alert-card.unacknowledged::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 0 20px 20px 0;
    border-color: transparent #dc3545 transparent transparent;
}

.alert-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.alert-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.alert-icon {
    font-size: 1.5rem;
    opacity: 0.8;
}

.alert-title h4 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
    font-weight: 600;
}

.alert-type-badge {
    background: #e9ecef;
    color: #495057;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 0.7rem;
    font-weight: 500;
}

.severity-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.severity-low { background: #d4edda; color: #155724; }
.severity-medium { background: #fff3cd; color: #856404; }
.severity-high { background: #f8d7da; color: #721c24; }
.severity-critical { background: #721c24; color: white; }

.alert-body {
    padding: 1.5rem;
}

.alert-description {
    margin: 0 0 1rem 0;
    line-height: 1.5;
    color: #333;
}

.alert-details {
    margin-bottom: 1rem;
}

.details-toggle {
    background: none;
    border: 1px solid #dee2e6;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.details-toggle:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.details-content {
    margin-top: 0.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.25rem;
    font-size: 0.8rem;
}

.detail-item span {
    color: #333;
    font-size: 0.9rem;
}

.alert-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.meta-item {
    display: flex;
    flex-direction: column;
}

.meta-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.25rem;
    font-size: 0.8rem;
}

.meta-item span {
    color: #333;
    font-size: 0.9rem;
}

.occurrence-count {
    font-weight: 600;
    color: #007bff;
}

.alert-footer {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.alert-status {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.status-badge.acknowledged {
    background: #cce5ff;
    color: #004085;
}

.status-badge.pending {
    background: #fff3cd;
    color: #856404;
}

.status-icon {
    font-size: 0.8rem;
}

.alert-actions {
    display: flex;
    gap: 0.5rem;
}

.inline-form {
    display: inline;
    margin: 0;
}

.acknowledgment-notes {
    padding: 0.75rem 1.5rem;
    background: #e8f5e8;
    border-top: 1px solid #c3e6c3;
    font-size: 0.9rem;
    color: #155724;
}

.no-alerts {
    text-align: center;
    padding: 4rem 2rem;
    color: #666;
}

.no-alerts-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-alerts h3 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.no-alerts p {
    margin: 0;
    font-size: 1rem;
}

.pagination-container {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}

@media (max-width: 768px) {
    .alerts-grid {
        grid-template-columns: 1fr;
    }
    
    .alert-header {
        flex-direction: column;
        gap: 0.75rem;
        align-items: flex-start;
    }
    
    .alert-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .alert-status {
        order: 2;
    }
    
    .alert-actions {
        order: 1;
        align-self: stretch;
        justify-content: flex-end;
    }
}
</style>

<script>
function toggleAlertDetails(alertId) {
    const details = document.getElementById('alert-details-' + alertId);
    details.style.display = details.style.display === 'none' ? 'block' : 'none';
}
</script>
@endsection
