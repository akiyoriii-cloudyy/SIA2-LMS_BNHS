@extends('layouts.app')

@section('title', 'Business Transactions')

@section('content')
<div class="page-header">
    <h1>Business Transactions</h1>
    <div class="page-actions">
        <a href="{{ route('admin.transactions.analytics') }}" class="btn btn-primary">
            <span class="icon">&#128200;</span> Analytics Dashboard
        </a>
        <a href="{{ route('admin.transactions.logs') }}" class="btn btn-secondary">
            <span class="icon">&#128196;</span> Operation Logs
        </a>
    </div>
</div>

<div class="transactions-overview">
    <div class="metrics-grid">
        <div class="metric-card primary">
            <div class="metric-icon">&#128202;</div>
            <div class="metric-value">{{ $stats['total_transactions'] }}</div>
            <div class="metric-label">Total Transactions</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $stats['transactions_24h'] > 0 ? 'up' : 'stable' }}">
                    +{{ $stats['transactions_24h'] }} today
                </span>
            </div>
        </div>
        <div class="metric-card success">
            <div class="metric-icon">&#10004;</div>
            <div class="metric-value">{{ $stats['success_rate'] }}%</div>
            <div class="metric-label">Success Rate</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $stats['success_rate'] >= 95 ? 'excellent' : ($stats['success_rate'] >= 80 ? 'good' : 'poor') }}">
                    {{ $stats['success_rate'] >= 95 ? 'Excellent' : ($stats['success_rate'] >= 80 ? 'Good' : 'Needs Attention') }}
                </span>
            </div>
        </div>
        <div class="metric-card warning">
            <div class="metric-icon">&#128336;</div>
            <div class="metric-value">{{ $stats['pending_transactions'] }}</div>
            <div class="metric-label">Pending</div>
            <div class="metric-trend">
                <span class="trend-indicator {{ $stats['pending_transactions'] > 5 ? 'up' : 'stable' }}">
                    {{ $stats['pending_transactions'] > 5 ? 'High Volume' : 'Normal' }}
                </span>
            </div>
        </div>
        <div class="metric-card danger">
            <div class="metric-icon">&#128714;</div>
            <div class="metric-value">{{ $stats['failed_transactions'] }}</div>
            <div class="metric-label">Failed</div>
            <div class="metric-trend">
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
        </div>
    @endif
</div>

<div class="filters">
    <form method="GET" action="{{ route('admin.transactions.index') }}">
        <div class="filter-row">
            <select name="transaction_type" class="form-control">
                <option value="">All Types</option>
                @foreach($transactionTypes as $type)
                    <option value="{{ $type }}" {{ request('transaction_type') == $type ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="form-control">
                <option value="">All Status</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" placeholder="From">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" placeholder="To">

            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<div class="transactions-table-container">
    <div class="table-header">
        <h3>Transaction History</h3>
        <div class="table-actions">
            <span class="record-count">{{ $transactions->total() }} records</span>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>User</th>
                    <th>Duration</th>
                    <th>Started At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                    <tr class="transaction-row {{ $transaction->status === 'failed' ? 'failed-row' : ($transaction->status === 'pending' ? 'pending-row' : '') }}">
                        <td>
                            <code class="transaction-id">{{ $transaction->transaction_id }}</code>
                        </td>
                        <td>
                            <div class="transaction-type">
                                <span class="type-icon">{{ getTransactionIcon($transaction->transaction_type) }}</span>
                                <span class="type-label">{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $transaction->status }}">
                                <span class="status-icon">{{ getStatusIcon($transaction->status) }}</span>
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
                        <td>
                            @if($transaction->user)
                                <div class="user-info">
                                    <span class="user-name">{{ $transaction->user->name }}</span>
                                    <span class="user-email">{{ $transaction->user->email }}</span>
                                </div>
                            @else
                                <span class="user-unknown">System</span>
                            @endif
                        </td>
                        <td>
                            @if($transaction->duration)
                                <span class="duration-value {{ $transaction->duration > 2 ? 'slow' : 'fast' }}">
                                    {{ number_format($transaction->duration, 2) }}s
                                </span>
                            @else
                                <span class="duration-value">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="time-info">
                                <span class="time-main">{{ $transaction->started_at->format('M j, Y') }}</span>
                                <span class="time-sub">{{ $transaction->started_at->format('H:i:s') }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="{{ route('admin.transactions.show', $transaction) }}" class="btn btn-sm btn-primary" title="View Details">
                                    View
                                </a>
                                @if($transaction->status === 'committed' && $transaction->completed_at && $transaction->completed_at->diffInHours(now()) <= 24)
                                    <form method="POST" action="{{ route('admin.transactions.rollback', $transaction) }}" class="inline-form">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-warning" title="Rollback Transaction" onclick="return confirm('Are you sure you want to rollback this transaction?')">
                                            Rollback
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="pagination-info">
            <span>Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions</span>
        </div>
        {{ $transactions->links() }}
    </div>
</div>

@php
function getTransactionIcon($transactionType) {
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

function getStatusIcon($status) {
    $icons = [
        'pending' => '&#128336;',
        'committed' => '&#10004;',
        'rolled_back' => '&#21ba;',
        'failed' => '&#128714;',
    ];
    return $icons[$status] ?? '&#128196;';
}
@endphp

<style>
.transactions-overview {
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
    text-align: center;
}

.metric-card.primary { border-left-color: #007bff; }
.metric-card.success { border-left-color: #28a745; }
.metric-card.warning { border-left-color: #ffc107; }
.metric-card.danger { border-left-color: #dc3545; }

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

.trend-indicator.excellent {
    background: #d4edda;
    color: #155724;
}

.trend-indicator.good {
    background: #cce5ff;
    color: #004085;
}

.trend-indicator.poor {
    background: #f8d7da;
    color: #721c24;
}

.performance-bar {
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

.performance-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.performance-label {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 0.25rem;
}

.performance-value {
    font-weight: 600;
    font-size: 0.9rem;
}

.performance-value.success { color: #28a745; }
.performance-value.warning { color: #ffc107; }
.performance-value.normal { color: #333; }
.performance-value.high { color: #dc3545; }

.transactions-table-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-top: 1rem;
}

.table-header {
    padding: 1rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.table-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.record-count {
    background: #e9ecef;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    color: #666;
}

.table-responsive {
    overflow-x: auto;
}

.transactions-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.transactions-table th {
    background: #f8f9fa;
    padding: 0.75rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    font-size: 0.85rem;
}

.transactions-table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #dee2e6;
}

.transaction-row.failed-row {
    background: rgba(220, 53, 69, 0.05);
}

.transaction-row.pending-row {
    background: rgba(255, 193, 7, 0.05);
}

.transaction-id {
    font-family: monospace;
    font-size: 0.8em;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    color: #333;
    font-weight: 600;
}

.transaction-type {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.type-icon {
    font-size: 1.1rem;
    opacity: 0.8;
}

.type-label {
    font-weight: 500;
    color: #333;
    font-size: 0.85rem;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-committed {
    background: #d4edda;
    color: #155724;
}

.status-rolled_back {
    background: #f8d7da;
    color: #721c24;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}

.status-icon {
    font-size: 0.8rem;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    font-weight: 500;
    color: #333;
}

.user-unknown {
    color: #999;
    font-style: italic;
    font-size: 0.85rem;
}

.duration-value {
    font-family: monospace;
    font-weight: 600;
    font-size: 0.85rem;
}

.duration-value.fast {
    color: #28a745;
}

.duration-value.slow {
    color: #dc3545;
}

.time-info {
    display: flex;
    flex-direction: column;
}

.time-main {
    font-weight: 500;
    color: #333;
}

.time-sub {
    font-size: 0.7rem;
    color: #666;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn {
    padding: 0.25rem 0.75rem;
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.2s;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.7rem;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-warning {
    background: #ffc107;
    color: #333;
}

.btn-warning:hover {
    background: #e0a800;
}

.inline-form {
    display: inline;
    margin: 0;
}

.table-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    font-size: 0.8rem;
    color: #666;
}

.filters {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.form-control {
    padding: 0.5rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 0.9rem;
    min-width: 150px;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-primary:hover {
    background: #0056b3;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .performance-bar {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .table-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
    
    .table-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .transactions-table {
        font-size: 0.8rem;
    }
    
    .transactions-table th,
    .transactions-table td {
        padding: 0.5rem;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .form-control {
        min-width: auto;
    }
    
    .table-footer {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>

<script>
function setTableView(view) {
    const buttons = document.querySelectorAll('.view-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Here you would implement the view switching logic
    console.log('Switching to ' + view + ' view');
}

function monitorTransaction(transactionId) {
    // Implement transaction monitoring functionality
    alert('Monitoring transaction ' + transactionId + '. This would open a real-time monitoring view.');
}
</script>
@endsection
