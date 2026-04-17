@extends('layouts.app')

@section('title', 'Transaction Analytics')

@section('content')
<div class="page-header">
    <h1>Transaction Analytics</h1>
    <div class="page-actions">
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">Back to Transactions</a>
        <a href="{{ route('admin.transactions.logs') }}" class="btn btn-primary">Operation Logs</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon total"> Transactions</div>
        <div class="stat-value">{{ $stats['total_transactions'] }}</div>
        <div class="stat-label">Total Transactions</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success"> Success</div>
        <div class="stat-value">{{ $stats['success_rate'] }}%</div>
        <div class="stat-label">Success Rate</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning"> Pending</div>
        <div class="stat-value">{{ $stats['pending_transactions'] }}</div>
        <div class="stat-label">Pending</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger"> Failed</div>
        <div class="stat-value">{{ $stats['failed_transactions'] }}</div>
        <div class="stat-label">Failed</div>
    </div>
</div>

@if($stats['average_duration'])
    <div class="performance-bar">
        <div class="perf-item">
            <label>Avg Duration:</label>
            <span>{{ number_format($stats['average_duration'], 2) }}s</span>
        </div>
        <div class="perf-item">
            <label>Transactions/Week:</label>
            <span>{{ $stats['transactions_week'] }}</span>
        </div>
    </div>
@endif

<div class="analytics-grid">
    <div class="analytics-card">
        <h3>Daily Transaction Volume</h3>
        <div class="chart-placeholder">
            <p>Chart visualization would be implemented here</p>
        </div>
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Committed</th>
                        <th>Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($analyticsData['daily_transaction_volume'] as $date => $data)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->format('M j') }}</td>
                            <td>{{ $data['total'] }}</td>
                            <td class="success">{{ $data['committed'] }}</td>
                            <td class="danger">{{ $data['failed'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-card">
        <h3>Transaction Types</h3>
        <div class="chart-placeholder">
            <p>Chart visualization would be implemented here</p>
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
                            <td>{{ ucfirst(str_replace('_', ' ', $type['transaction_type'])) }}</td>
                            <td>{{ $type['count'] }}</td>
                            <td class="success">{{ $type['committed'] }}</td>
                            <td class="danger">{{ $type['failed'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

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

<style>
.page-header {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header h1 {
    margin: 0;
    color: #333;
    font-size: 1.75rem;
    font-weight: 600;
    letter-spacing: -0.5px;
}

.page-actions {
    display: flex;
    gap: 1rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #007bff;
}

.stat-card.total { border-left-color: #007bff; }
.stat-card.success { border-left-color: #28a745; }
.stat-card.warning { border-left-color: #ffc107; }
.stat-card.danger { border-left-color: #dc3545; }

.stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.performance-bar {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    justify-content: space-around;
    margin-bottom: 1.5rem;
}

.perf-item {
    text-align: center;
}

.perf-item label {
    display: block;
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.perf-item span {
    font-weight: 600;
    color: #333;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}

.analytics-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.analytics-card h3 {
    margin: 0;
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    color: #333;
    font-size: 1.1rem;
}

.chart-placeholder {
    padding: 2rem;
    text-align: center;
    color: #666;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
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
}

.data-table .success {
    color: #28a745;
    font-weight: 600;
}

.data-table .danger {
    color: #dc3545;
    font-weight: 600;
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    min-width: 140px;
}

.btn-primary {
    background: #007bff;
    color: white;
    border: 1px solid #007bff;
}

.btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

.btn-secondary {
    background: white;
    color: #6c757d;
    border: 1px solid #6c757d;
}

.btn-secondary:hover {
    background: #6c757d;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(108,117,125,0.3);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .page-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .performance-bar {
        flex-direction: column;
        gap: 1rem;
    }
    
    .analytics-grid {
        grid-template-columns: 1fr;
    }
}
</style>
@endsection
