@extends('layouts.app')

@section('title', 'Security Analytics Reports')

@section('content')
<div class="page-header">
    <h1>Security Analytics Reports</h1>
    <div class="page-actions">
        <a href="{{ route('admin.security-audit.index') }}" class="btn btn-secondary">
            <span class="icon">&#8592;</span> Back to Audit Logs
        </a>
        <a href="{{ route('admin.security-audit.alerts') }}" class="btn btn-warning">
            <span class="icon">&#128680;</span> Security Alerts
            @if($metrics['unacknowledged_alerts'] > 0)
                <span class="badge badge-danger">{{ $metrics['unacknowledged_alerts'] }}</span>
            @endif
        </a>
        <a href="{{ route('admin.security-audit.export') }}" class="btn btn-primary">
            <span class="icon">&#128229;</span> Export Data
        </a>
    </div>
</div>

<div class="reports-overview">
    <div class="summary-cards">
        <div class="summary-card critical">
            <div class="card-icon">&#128269;</div>
            <div class="card-title">System Status</div>
            <div class="card-value {{ $metrics['critical_alerts'] > 0 ? 'danger' : ($metrics['active_alerts'] > 5 ? 'warning' : 'success') }}">
                {{ $metrics['critical_alerts'] > 0 ? 'CRITICAL' : ($metrics['active_alerts'] > 5 ? 'WARNING' : 'SECURE') }}
            </div>
            <div class="card-description">{{ $metrics['active_alerts'] }} active alerts</div>
        </div>

        <div class="summary-card danger">
            <div class="card-icon">&#128274;</div>
            <div class="card-title">Failed Logins (24h)</div>
            <div class="card-value">{{ $metrics['failed_logins_24h'] }}</div>
            <div class="card-description">{{ $metrics['failed_logins_week'] }} this week</div>
        </div>

        <div class="summary-card warning">
            <div class="card-icon">&#128680;</div>
            <div class="card-title">Threat Level</div>
            <div class="card-value {{ $metrics['high_severity_alerts'] > 3 ? 'high' : ($metrics['high_severity_alerts'] > 0 ? 'medium' : 'low') }}">
                {{ $metrics['high_severity_alerts'] > 3 ? 'HIGH' : ($metrics['high_severity_alerts'] > 0 ? 'MEDIUM' : 'LOW') }}
            </div>
            <div class="card-description">{{ $metrics['high_severity_alerts'] }} high severity alerts</div>
        </div>

        <div class="summary-card info">
            <div class="card-icon">&#128276;</div>
            <div class="card-title">Response Status</div>
            <div class="card-value">{{ $metrics['unacknowledged_alerts'] }}</div>
            <div class="card-description">unacknowledged alerts</div>
        </div>
    </div>
</div>

<div class="reports-grid">
    <div class="report-card">
        <div class="report-header">
            <h3>Daily Security Trends</h3>
            <div class="report-period">Last 7 Days</div>
        </div>
        <div class="chart-container">
            <canvas id="dailyTrendsChart"></canvas>
        </div>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Failed Logins</th>
                        <th>Security Breaches</th>
                        <th>Suspicious Activity</th>
                        <th>Alerts Generated</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reportData['daily_stats'] as $date => $data)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</td>
                            <td class="danger">{{ $data['failed_logins'] }}</td>
                            <td class="critical">{{ $data['security_breaches'] }}</td>
                            <td class="warning">{{ $data['suspicious_activities'] }}</td>
                            <td class="info">{{ $data['alerts_generated'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="report-card">
        <div class="report-header">
            <h3>Weekly Performance Analysis</h3>
            <div class="report-period">Last 4 Weeks</div>
        </div>
        <div class="chart-container">
            <canvas id="weeklyPerformanceChart"></canvas>
        </div>
        <div class="performance-summary">
            @foreach($reportData['weekly_trends'] as $period => $data)
                <div class="week-summary">
                    <div class="week-label">{{ $period }}</div>
                    <div class="week-stats">
                        <div class="stat-item">
                            <label>Failed Logins:</label>
                            <span class="value danger">{{ $data['failed_logins'] }}</span>
                        </div>
                        <div class="stat-item">
                            <label>Breaches:</label>
                            <span class="value critical">{{ $data['security_breaches'] }}</span>
                        </div>
                        <div class="stat-item">
                            <label>Alerts:</label>
                            <span class="value info">{{ $data['alerts_generated'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="report-card full-width">
        <div class="report-header">
            <h3>Top Security Threats</h3>
            <div class="report-period">Last 30 Days</div>
        </div>
        <div class="threats-analysis">
            @if(count($reportData['top_threats']) > 0)
                <div class="threats-grid">
                    @foreach($reportData['top_threats'] as $threat)
                        <div class="threat-item">
                            <div class="threat-type">
                                <span class="threat-icon">{{ getThreatIcon($threat['event_type']) }}</span>
                                <span class="threat-name">{{ ucfirst(str_replace('_', ' ', $threat['event_type'])) }}</span>
                            </div>
                            <div class="threat-severity {{ $threat['severity'] }}">
                                {{ strtoupper($threat['severity']) }}
                            </div>
                            <div class="threat-count">
                                <span class="count-value">{{ $threat['count'] }}</span>
                                <span class="count-label">incidents</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-threats">
                    <div class="no-threats-icon">&#128269;</div>
                    <h4>No Major Threats Detected</h4>
                    <p>System has been secure with no significant security incidents.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="report-card">
        <div class="report-header">
            <h3>IP Address Analysis</h3>
            <div class="report-period">Last 7 Days</div>
        </div>
        <div class="ip-analysis">
            @if(count($reportData['ip_analysis']) > 0)
                <div class="ip-list">
                    @foreach($reportData['ip_analysis'] as $ip)
                        <div class="ip-item">
                            <div class="ip-address">
                                <code>{{ $ip['ip_address'] }}</code>
                            </div>
                            <div class="ip-stats">
                                <div class="failed-attempts">
                                    <span class="stat-value danger">{{ $ip['failed_attempts'] }}</span>
                                    <span class="stat-label">failed attempts</span>
                                </div>
                            </div>
                            <div class="ip-risk {{ $ip['failed_attempts'] > 10 ? 'high' : ($ip['failed_attempts'] > 5 ? 'medium' : 'low') }}">
                                {{ $ip['failed_attempts'] > 10 ? 'HIGH RISK' : ($ip['failed_attempts'] > 5 ? 'MEDIUM RISK' : 'LOW RISK') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-ips">
                    <p>No suspicious IP activity detected in the last 7 days.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="report-card">
        <div class="report-header">
            <h3>User Activity Analysis</h3>
            <div class="report-period">Last 30 Days</div>
        </div>
        <div class="user-analysis">
            @if(count($reportData['user_analysis']) > 0)
                <div class="user-list">
                    @foreach($reportData['user_analysis'] as $user)
                        <div class="user-item">
                            <div class="user-info">
                                <div class="user-avatar">
                                    {{ substr($user['user']['name'] ?? 'Unknown', 0, 1) }}
                                </div>
                                <div class="user-details">
                                    <div class="user-name">{{ $user['user']['name'] ?? 'Unknown User' }}</div>
                                    <div class="user-email">{{ $user['user']['email'] ?? 'N/A' }}</div>
                                </div>
                            </div>
                            <div class="user-activity">
                                <div class="activity-count">
                                    <span class="count-value warning">{{ $user['incident_count'] }}</span>
                                    <span class="count-label">incidents</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="no-users">
                    <p>No unusual user activity detected in the last 30 days.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="export-section">
    <div class="export-card">
        <h3>Export Security Data</h3>
        <p>Download comprehensive security reports for external analysis or compliance requirements.</p>
        <div class="export-options">
            <a href="{{ route('admin.security-audit.export') }}?format=csv&period=30" class="btn btn-primary">
                <span class="icon">&#128229;</span> Export CSV (30 Days)
            </a>
            <a href="{{ route('admin.security-audit.export') }}?format=csv&period=7" class="btn btn-secondary">
                <span class="icon">&#128229;</span> Export CSV (7 Days)
            </a>
        </div>
    </div>
</div>

@php
function getThreatIcon($eventType) {
    $icons = [
        'failed_login' => '&#128274;',
        'security_breach' => '&#128269;',
        'suspicious_activity' => '&#128680;',
    ];
    return $icons[$eventType] ?? '&#128680;';
}
@endphp

<style>
.reports-overview {
    margin-bottom: 2rem;
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #007bff;
    position: relative;
    overflow: hidden;
}

.summary-card.critical { border-left-color: #dc3545; }
.summary-card.danger { border-left-color: #dc3545; }
.summary-card.warning { border-left-color: #ffc107; }
.summary-card.info { border-left-color: #17a2b8; }

.card-icon {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.card-title {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.card-value {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1;
}

.card-value.success { color: #28a745; }
.card-value.warning { color: #ffc107; }
.card-value.danger { color: #dc3545; }
.card-value.high { color: #dc3545; }
.card-value.medium { color: #ffc107; }
.card-value.low { color: #28a745; }

.card-description {
    font-size: 0.8rem;
    color: #666;
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.report-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.report-card.full-width {
    grid-column: 1 / -1;
}

.report-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.report-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.report-period {
    font-size: 0.8rem;
    color: #666;
    background: #e9ecef;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
}

.chart-container {
    height: 200px;
    padding: 1rem;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 0.9rem;
}

.data-table {
    padding: 1rem;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.data-table th,
.data-table td {
    padding: 0.5rem;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #666;
    font-size: 0.8rem;
    text-transform: uppercase;
}

.danger { color: #dc3545; font-weight: 600; }
.critical { color: #721c24; font-weight: 600; }
.warning { color: #ffc107; font-weight: 600; }
.info { color: #17a2b8; font-weight: 600; }

.performance-summary {
    padding: 1rem;
}

.week-summary {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
    margin-bottom: 0.5rem;
}

.week-label {
    font-weight: 600;
    color: #333;
}

.week-stats {
    display: flex;
    gap: 1rem;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-item label {
    font-size: 0.7rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.stat-item .value {
    font-weight: 600;
    font-size: 0.9rem;
}

.threats-analysis {
    padding: 1.5rem;
}

.threats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
}

.threat-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.threat-item .threat-severity.low { border-left-color: #28a745; }
.threat-item .threat-severity.medium { border-left-color: #ffc107; }
.threat-item .threat-severity.high { border-left-color: #dc3545; }
.threat-item .threat-severity.critical { border-left-color: #721c24; }

.threat-type {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.threat-icon {
    font-size: 1.5rem;
    opacity: 0.8;
}

.threat-name {
    font-weight: 600;
    color: #333;
}

.threat-severity {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.threat-severity.low { background: #d4edda; color: #155724; }
.threat-severity.medium { background: #fff3cd; color: #856404; }
.threat-severity.high { background: #f8d7da; color: #721c24; }
.threat-severity.critical { background: #721c24; color: white; }

.threat-count {
    text-align: center;
}

.count-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}

.count-label {
    font-size: 0.7rem;
    color: #666;
    text-transform: uppercase;
}

.no-threats, .no-ips, .no-users {
    text-align: center;
    padding: 2rem;
    color: #666;
}

.no-threats-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.ip-analysis, .user-analysis {
    padding: 1.5rem;
}

.ip-list, .user-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.ip-item, .user-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.ip-address code {
    background: #fff;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-family: monospace;
}

.ip-stats, .user-activity {
    text-align: center;
}

.failed-attempts, .activity-count {
    display: flex;
    flex-direction: column;
}

.ip-risk {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.ip-risk.low { background: #d4edda; color: #155724; }
.ip-risk.medium { background: #fff3cd; color: #856404; }
.ip-risk.high { background: #f8d7da; color: #721c24; }

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
}

.user-name {
    font-weight: 600;
    color: #333;
}

.user-email {
    font-size: 0.8rem;
    color: #666;
}

.export-section {
    margin-top: 2rem;
}

.export-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-align: center;
}

.export-card h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.export-card p {
    margin: 0 0 1.5rem 0;
    color: #666;
}

.export-options {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .summary-cards {
        grid-template-columns: 1fr;
    }
    
    .reports-grid {
        grid-template-columns: 1fr;
    }
    
    .week-summary {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .week-stats {
        gap: 0.5rem;
    }
    
    .threat-item {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .ip-item, .user-item {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .export-options {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple chart placeholders - in a real implementation, you'd use Chart.js or similar
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #666;">Chart visualization would be implemented here</div>';
    });
});
</script>
@endsection
