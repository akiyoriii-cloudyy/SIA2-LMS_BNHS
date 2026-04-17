@extends('layouts.app')

@section('title', 'Transaction Analytics Dashboard')

@section('content')
<div class="analytics-page-header">
    <div class="header-content">
        <h1>Transaction Analytics Dashboard</h1>
        <p class="header-description">Comprehensive insights into business transaction performance and system health</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">
            <span class="icon">&#8592;</span> Back to Transactions
        </a>
        <a href="{{ route('admin.transactions.logs') }}" class="btn btn-primary">
            <span class="icon">&#128196;</span> Operation Logs
        </a>
        <button class="btn btn-info" onclick="refreshAnalytics()">
            <span class="icon">&#128260;</span> Refresh
        </button>
    </div>
</div>

<div class="analytics-overview">
    <div class="overview-cards">
        <div class="overview-card total">
            <div class="card-icon">&#128202;</div>
            <div class="card-value">{{ $stats['total_transactions'] }}</div>
            <div class="card-label">Total Transactions</div>
            <div class="card-trend">
                <span class="trend-indicator {{ $stats['transactions_24h'] > 0 ? 'up' : 'stable' }}">
                    {{ $stats['transactions_24h'] > 0 ? '+' . $stats['transactions_24h'] . ' today' : 'No activity today' }}
                </span>
            </div>
        </div>
        <div class="overview-card success">
            <div class="card-icon">&#10004;</div>
            <div class="card-value">{{ $stats['success_rate'] }}%</div>
            <div class="card-label">Success Rate</div>
            <div class="card-trend">
                <span class="trend-indicator {{ $stats['success_rate'] >= 95 ? 'excellent' : ($stats['success_rate'] >= 80 ? 'good' : 'poor') }}">
                    {{ $stats['success_rate'] >= 95 ? 'Excellent' : ($stats['success_rate'] >= 80 ? 'Good' : 'Needs Attention') }}
                </span>
            </div>
        </div>
        <div class="overview-card warning">
            <div class="card-icon">&#128336;</div>
            <div class="card-value">{{ $stats['pending_transactions'] }}</div>
            <div class="card-label">Pending</div>
            <div class="card-trend">
                <span class="trend-indicator {{ $stats['pending_transactions'] > 5 ? 'up' : 'stable' }}">
                    {{ $stats['pending_transactions'] > 5 ? 'High Volume' : 'Normal' }}
                </span>
            </div>
        </div>
        <div class="overview-card danger">
            <div class="card-icon">&#128714;</div>
            <div class="card-value">{{ $stats['failed_transactions'] }}</div>
            <div class="card-label">Failed</div>
            <div class="card-trend">
                <span class="trend-indicator {{ $stats['failed_transactions'] > 2 ? 'up' : 'stable' }}">
                    {{ $stats['failed_transactions'] > 2 ? 'Investigate' : 'Normal' }}
                </span>
            </div>
        </div>
    </div>

    @if($stats['average_duration'])
        <div class="performance-bar">
            <div class="performance-item">
                <span class="performance-label">Average Duration:</span>
                <span class="performance-value {{ $stats['average_duration'] > 2 ? 'warning' : 'success' }}">
                    {{ number_format($stats['average_duration'], 2) }}s
                </span>
            </div>
            <div class="performance-item">
                <span class="performance-label">Transactions/Week:</span>
                <span class="performance-value">{{ $stats['transactions_week'] }}</span>
            </div>
            <div class="performance-item">
                <span class="performance-label">System Load:</span>
                <span class="performance-value {{ $stats['pending_transactions'] > 10 ? 'high' : 'normal' }}">
                    {{ $stats['pending_transactions'] > 10 ? 'High' : 'Normal' }}
                </span>
            </div>
            <div class="performance-item">
                <span class="performance-label">Health Score:</span>
                <span class="performance-value {{ $stats['success_rate'] >= 95 ? 'excellent' : ($stats['success_rate'] >= 80 ? 'good' : 'poor') }}">
                    {{ $stats['success_rate'] >= 95 ? 'A+' : ($stats['success_rate'] >= 80 ? 'B' : 'C') }}
                </span>
            </div>
        </div>
    @endif
</div>

<div class="analytics-grid">
    <div class="analytics-card">
        <div class="card-header">
            <h3>Daily Transaction Volume</h3>
            <div class="card-icon">&#128200;</div>
        </div>
        <div class="chart-container">
            <canvas id="dailyVolumeChart"></canvas>
        </div>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Committed</th>
                        <th>Rolled Back</th>
                        <th>Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyticsData['daily_transaction_volume'] as $date => $data)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</td>
                            <td class="total-value">{{ $data['total'] }}</td>
                            <td class="success-value">{{ $data['committed'] }}</td>
                            <td class="warning-value">{{ $data['rolled_back'] }}</td>
                            <td class="danger-value">{{ $data['failed'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-card">
        <div class="card-header">
            <h3>Transaction Types Breakdown</h3>
            <div class="card-icon">&#128295;</div>
        </div>
        <div class="chart-container">
            <canvas id="typesChart"></canvas>
        </div>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Total</th>
                        <th>Success Rate</th>
                        <th>Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyticsData['transaction_types_breakdown'] as $type)
                        <tr>
                            <td>
                                <div class="transaction-type-cell">
                                    <span class="type-icon">{{ getTransactionTypeIcon($type['transaction_type']) }}</span>
                                    <span>{{ ucfirst(str_replace('_', ' ', $type['transaction_type'])) }}</span>
                                </div>
                            </td>
                            <td class="total-value">{{ $type['count'] }}</td>
                            <td class="success-value">{{ $type['committed'] }}</td>
                            <td class="danger-value">{{ $type['failed'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-card">
        <h3>Success/Failure Trends</h3>
        <div class="chart-container">
            <canvas id="trendsChart"></canvas>
        </div>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Total</th>
                        <th>Successful</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyticsData['success_failure_trends'] as $period => $data)
                        <tr>
                            <td>{{ $period }}</td>
                            <td class="total-value">{{ $data['total'] }}</td>
                            <td class="success-value">{{ $data['successful'] }}</td>
                            <td>
                                <span class="rate-badge {{ $data['success_rate'] >= 90 ? 'excellent' : ($data['success_rate'] >= 70 ? 'good' : 'poor') }}">
                                    {{ $data['success_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-card">
        <h3>Table Activity Analysis</h3>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Table</th>
                        <th>Operations</th>
                        <th>Inserts</th>
                        <th>Updates</th>
                        <th>Deletes</th>
                        <th>Avg Time</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyticsData['table_activity_analysis'] as $table)
                        <tr>
                            <td><code class="table-name">{{ $table['table_name'] }}</code></td>
                            <td class="total-value">{{ $table['operation_count'] }}</td>
                            <td class="success-value">{{ $table['inserts'] }}</td>
                            <td class="primary-value">{{ $table['updates'] }}</td>
                            <td class="danger-value">{{ $table['deletes'] }}</td>
                            <td>
                                <span class="time-badge {{ $table['avg_execution_time'] > 1000 ? 'slow' : 'fast' }}">
                                    {{ number_format($table['avg_execution_time'], 2) }}ms
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($analyticsData['performance_metrics']['total_operations'] > 0)
        <div class="analytics-card">
            <div class="card-header">
                <h3>Performance Metrics</h3>
                <div class="card-icon">&#128200;</div>
            </div>
            <div class="performance-grid">
                <div class="perf-item">
                    <label>Avg Execution Time:</label>
                    <span class="perf-value {{ $analyticsData['performance_metrics']['avg_execution_time'] > 500 ? 'warning' : 'success' }}">
                        {{ number_format($analyticsData['performance_metrics']['avg_execution_time'], 2) }}ms
                    </span>
                </div>
                <div class="perf-item">
                    <label>Slow Operations:</label>
                    <span class="perf-value danger">{{ $analyticsData['performance_metrics']['slow_operations'] }}</span>
                </div>
                <div class="perf-item">
                    <label>Failed Operations:</label>
                    <span class="perf-value danger">{{ $analyticsData['performance_metrics']['failed_operations'] }}</span>
                </div>
                <div class="perf-item">
                    <label>Total Operations:</label>
                    <span class="perf-value">{{ $analyticsData['performance_metrics']['total_operations'] }}</span>
                </div>
                <div class="perf-item">
                    <label>Success Rate:</label>
                    <span class="perf-value {{ $analyticsData['performance_metrics']['success_rate'] >= 95 ? 'excellent' : ($analyticsData['performance_metrics']['success_rate'] >= 80 ? 'good' : 'poor') }}">
                        {{ $analyticsData['performance_metrics']['success_rate'] }}%
                    </span>
                </div>
                <div class="perf-item">
                    <label>System Health:</label>
                    <span class="perf-value {{ $analyticsData['performance_metrics']['success_rate'] >= 95 ? 'excellent' : ($analyticsData['performance_metrics']['success_rate'] >= 80 ? 'good' : 'poor') }}">
                        {{ $analyticsData['performance_metrics']['success_rate'] >= 95 ? 'Optimal' : ($analyticsData['performance_metrics']['success_rate'] >= 80 ? 'Good' : 'Needs Attention') }}
                    </span>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
.analytics-overview {
    margin-bottom: 2rem;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.metric-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #007bff;
}

.metric-card.primary { border-left-color: #007bff; }
.metric-card.success { border-left-color: #28a745; }
.metric-card.warning { border-left-color: #ffc107; }
.metric-card.danger { border-left-color: #dc3545; }

.metric-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.metric-label {
    font-size: 0.9rem;
    color: #666;
    margin-bottom: 0.5rem;
}

.metric-change {
    font-size: 0.8rem;
    color: #999;
}

.change-positive {
    color: #28a745;
    font-weight: 600;
}

.performance-metrics {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.performance-metrics h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.metric-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.metric-item {
    display: flex;
    flex-direction: column;
}

.metric-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.25rem;
    font-size: 0.9em;
}

.metric-item span {
    color: #333;
    font-size: 1.1em;
    font-weight: 600;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.analytics-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.analytics-card h3 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1.2rem;
}

.chart-container {
    height: 200px;
    margin-bottom: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
    font-size: 0.9em;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9em;
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
}

.success { color: #28a745; }
.primary { color: #007bff; }
.warning { color: #ffc107; }
.danger { color: #dc3545; }

.rate-badge {
    padding: 2px 6px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.rate-badge.excellent { background: #d4edda; color: #155724; }
.rate-badge.good { background: #cce5ff; color: #004085; }
.rate-badge.poor { background: #f8d7da; color: #721c24; }

.time-badge {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 600;
    font-family: monospace;
}

.time-badge.fast { background: #d4edda; color: #155724; }
.time-badge.slow { background: #f8d7da; color: #721c24; }

.performance-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.perf-item {
    display: flex;
    flex-direction: column;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
}

.perf-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.5rem;
    font-size: 0.9em;
}

.perf-item span {
    color: #333;
    font-size: 1.2em;
    font-weight: 600;
}

code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.9em;
}
</style>

@php
function getTransactionTypeIcon($transactionType) {
    $icons = [
        'user_creation' => '&#128100;',
        'grade_entry' => '&#128221;',
        'profile_update' => '&#128104;',
        'course_enrollment' => '&#127891;',
        'attendance_update' => '&#128197;',
        'report_generation' => '&#128196;',
        'system_maintenance' => '&#128295;',
    ];
    return $icons[$transactionType] ?? '&#128202;';
}
@endphp

<script>
// Simple chart placeholders - in a real implementation, you'd use Chart.js or similar
document.addEventListener('DOMContentLoaded', function() {
    const chartContainers = document.querySelectorAll('.chart-container');
    chartContainers.forEach(container => {
        container.innerHTML = '<div style="text-align: center; padding: 2rem; color: #666;">Chart visualization would be implemented here</div>';
    });
});
</script>
@endsection
