@extends('layouts.app')

@section('title', 'Transaction Details - ' . $transaction->transaction_id)

@section('content')
<div class="transaction-show-header">
    <div class="header-content">
        <div class="title-section">
            <h1>Transaction Details</h1>
            <div class="transaction-id-display">
                <code class="transaction-code">{{ $transaction->transaction_id }}</code>
                <span class="transaction-type-badge type-{{ $transaction->transaction_type }}">
                    {{ getTransactionIcon($transaction->transaction_type) }}
                    {{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}
                </span>
            </div>
        </div>
        <div class="status-section">
            <div class="status-indicator status-{{ $transaction->status }}">
                <span class="status-icon">{{ getStatusIcon($transaction->status) }}</span>
                <span class="status-text">{{ ucfirst($transaction->status) }}</span>
            </div>
            @if($transaction->completed_at)
                <div class="completion-info">
                    <span class="completion-time">{{ $transaction->completed_at->diffForHumans() }}</span>
                </div>
            @endif
        </div>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">
            <span class="icon">&#8592;</span> Back to Transactions
        </a>
        @if($transaction->status === 'committed' && $transaction->completed_at && $transaction->completed_at->diffInHours(now()) <= 24)
            <form method="POST" action="{{ route('admin.transactions.rollback', $transaction) }}" class="inline-form">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to rollback this transaction?')">
                    <span class="icon">&#21ba;</span> Rollback Transaction
                </button>
            </form>
        @endif
        @if($transaction->status === 'pending')
            <button class="btn btn-info" onclick="monitorTransaction({{ $transaction->id }})">
                <span class="icon">&#128276;</span> Monitor
            </button>
        @endif
    </div>
</div>

<div class="transaction-details-container">
    <div class="details-grid">
        <div class="main-details">
            <div class="detail-card">
                <div class="card-header">
                    <h3>Transaction Information</h3>
                    <div class="card-icon">&#128202;</div>
                </div>
                <div class="card-content">
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Transaction ID:</label>
                            <span><code class="detail-code">{{ $transaction->transaction_id }}</code></span>
                        </div>
                        <div class="detail-item">
                            <label>Type:</label>
                            <div class="transaction-type-detail">
                                <span class="type-icon">{{ getTransactionIcon($transaction->transaction_type) }}</span>
                                <span>{{ ucfirst(str_replace('_', ' ', $transaction->transaction_type)) }}</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge status-{{ $transaction->status }}">
                                <span class="status-icon">{{ getStatusIcon($transaction->status) }}</span>
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label>Started At:</label>
                            <div class="time-detail">
                                <span class="time-main">{{ $transaction->started_at->format('M j, Y') }}</span>
                                <span class="time-sub">{{ $transaction->started_at->format('H:i:s') }}</span>
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Completed At:</label>
                            <div class="time-detail">
                                @if($transaction->completed_at)
                                    <span class="time-main">{{ $transaction->completed_at->format('M j, Y') }}</span>
                                    <span class="time-sub">{{ $transaction->completed_at->format('H:i:s') }}</span>
                                @else
                                    <span class="time-unknown">Not completed</span>
                                @endif
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Duration:</label>
                            <div class="duration-detail">
                                @if($transaction->duration)
                                    <span class="duration-value {{ $transaction->duration > 2 ? 'slow' : 'fast' }}">
                                        {{ number_format($transaction->duration, 2) }}s
                                    </span>
                                    <div class="duration-bar">
                                        <div class="duration-fill" style="width: {{ min(100, ($transaction->duration / 5) * 100) }}%"></div>
                                    </div>
                                @else
                                    <span class="duration-value">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>User:</label>
                            <div class="user-detail">
                                @if($transaction->user)
                                    <div class="user-avatar">{{ substr($transaction->user->name, 0, 1) }}</div>
                                    <div class="user-info">
                                        <span class="user-name">{{ $transaction->user->name }}</span>
                                        <span class="user-email">{{ $transaction->user->email }}</span>
                                    </div>
                                @else
                                    <span class="user-unknown">System</span>
                                @endif
                            </div>
                        </div>
                        <div class="detail-item">
                            <label>Performed By:</label>
                            <div class="user-detail">
                                @if($transaction->performedBy)
                                    <div class="user-avatar">{{ substr($transaction->performedBy->name, 0, 1) }}</div>
                                    <div class="user-info">
                                        <span class="user-name">{{ $transaction->performedBy->name }}</span>
                                        <span class="user-email">{{ $transaction->performedBy->email }}</span>
                                    </div>
                                @else
                                    <span class="user-unknown">System</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($transaction->description)
                <div class="detail-card">
                    <div class="card-header">
                        <h3>Description</h3>
                        <div class="card-icon">&#128221;</div>
                    </div>
                    <div class="card-content">
                        <p class="description-text">{{ $transaction->description }}</p>
                    </div>
                </div>
            @endif

            @if($transaction->error_message)
                <div class="detail-card error">
                    <div class="card-header">
                        <h3>Error Information</h3>
                        <div class="card-icon">&#128714;</div>
                    </div>
                    <div class="card-content">
                        <div class="error-details">
                            <div class="error-message">
                                <label>Error:</label>
                                <span class="error-text">{{ $transaction->error_message }}</span>
                            </div>
                            <div class="retry-count">
                                <label>Retry Count:</label>
                                <span class="retry-value">{{ $transaction->retry_count }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="sidebar-details">
            <div class="timeline-card">
                <div class="card-header">
                    <h3>Transaction Timeline</h3>
                    <div class="card-icon">&#128336;</div>
                </div>
                <div class="card-content">
                    <div class="timeline">
                        <div class="timeline-item started">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <div class="timeline-title">Started</div>
                                <div class="timeline-time">{{ $transaction->started_at->format('M j, H:i:s') }}</div>
                            </div>
                        </div>
                        @if($transaction->completed_at)
                            <div class="timeline-item completed">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">Completed</div>
                                    <div class="timeline-time">{{ $transaction->completed_at->format('M j, H:i:s') }}</div>
                                </div>
                            </div>
                        @endif
                        @if($transaction->status === 'pending')
                            <div class="timeline-item pending">
                                <div class="timeline-marker pulse"></div>
                                <div class="timeline-content">
                                    <div class="timeline-title">In Progress</div>
                                    <div class="timeline-time">Processing...</div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="performance-card">
                <div class="card-header">
                    <h3>Performance Metrics</h3>
                    <div class="card-icon">&#128200;</div>
                </div>
                <div class="card-content">
                    <div class="performance-metrics">
                        @if($transaction->duration)
                            <div class="metric-item">
                                <label>Execution Time:</label>
                                <span class="metric-value {{ $transaction->duration > 2 ? 'warning' : 'success' }}">
                                    {{ number_format($transaction->duration, 2) }}s
                                </span>
                            </div>
                        @endif
                        <div class="metric-item">
                            <label>Operations:</label>
                            <span class="metric-value">{{ $transaction->transactionLogs->count() }}</span>
                        </div>
                        <div class="metric-item">
                            <label>Success Rate:</label>
                            <span class="metric-value {{ $transaction->status === 'committed' ? 'success' : ($transaction->status === 'failed' ? 'danger' : 'warning') }}">
                                {{ $transaction->status === 'committed' ? '100%' : ($transaction->status === 'failed' ? '0%' : 'Processing') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="data-sections">
        <div class="data-card">
            <div class="card-header">
                <h3>Transaction Data</h3>
                <div class="card-icon">&#128196;</div>
            </div>
            <div class="card-content">
                <div class="data-container">
                    <pre>{{ json_encode($transaction->transaction_data, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>

        @if($transaction->rollback_data)
            <div class="data-card">
                <div class="card-header">
                    <h3>Rollback Data</h3>
                    <div class="card-icon">&#21ba;</div>
                </div>
                <div class="card-content">
                    <div class="data-container">
                        <pre>{{ json_encode($transaction->rollback_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

    <div class="operation-logs-section">
    <div class="logs-header">
        <h3>Operation Logs</h3>
        <div class="logs-summary">
            <span class="total-operations">{{ $transaction->transactionLogs->count() }} operations</span>
            <span class="success-rate">
                {{ $transaction->transactionLogs->where('was_successful', true)->count() }} successful
            </span>
        </div>
    </div>
    
    @if($transaction->transactionLogs->count() > 0)
        <div class="logs-container">
            @foreach($transaction->transactionLogs as $index => $log)
                <div class="log-item {{ $log->was_successful ? 'success' : 'failed' }}">
                    <div class="log-header">
                        <div class="log-operation">
                            <span class="operation-icon operation-{{ $log->operation }}">
                                {{ getOperationIcon($log->operation) }}
                            </span>
                            <span class="operation-name">{{ strtoupper($log->operation) }}</span>
                        </div>
                        <div class="log-status">
                            <span class="status-indicator {{ $log->was_successful ? 'success' : 'failed' }}">
                                {{ $log->was_successful ? 'Success' : 'Failed' }}
                            </span>
                            @if($log->execution_time_ms)
                                <span class="execution-time {{ $log->execution_time_ms > 1000 ? 'slow' : 'fast' }}">
                                    {{ number_format($log->execution_time_ms, 2) }}ms
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="log-details">
                        <div class="log-info">
                            <div class="info-item">
                                <label>Table:</label>
                                <code>{{ $log->table_name }}</code>
                            </div>
                            @if($log->record_id)
                                <div class="info-item">
                                    <label>Record ID:</label>
                                    <code>{{ $log->record_id }}</code>
                                </div>
                            @endif
                            <div class="info-item">
                                <label>Timestamp:</label>
                                <span>{{ $log->created_at->format('M j, Y H:i:s') }}</span>
                            </div>
                        </div>
                        
                        @if($log->old_values || $log->new_values || $log->error_message || $log->sql_query)
                            <div class="log-expandable">
                                <button class="expand-btn" onclick="toggleLogDetails({{ $log->id }})">
                                    <span class="icon">&#128220;</span> View Details
                                </button>
                                
                                <div id="log-details-{{ $log->id }}" class="log-expanded-content" style="display: none;">
                                    @if($log->old_values)
                                        <div class="data-section">
                                            <h4>Old Values</h4>
                                            <div class="data-container">
                                                <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->new_values)
                                        <div class="data-section">
                                            <h4>New Values</h4>
                                            <div class="data-container">
                                                <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->error_message)
                                        <div class="data-section error">
                                            <h4>Error Message</h4>
                                            <div class="error-content">
                                                {{ $log->error_message }}
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->sql_query)
                                        <div class="data-section">
                                            <h4>SQL Query</h4>
                                            <div class="sql-container">
                                                <code>{{ $log->sql_query }}</code>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="no-logs">
            <div class="no-logs-icon">&#128196;</div>
            <h4>No Operation Logs</h4>
            <p>No operation logs found for this transaction.</p>
        </div>
    @endif
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

function getOperationIcon($operation) {
    $icons = [
        'insert' => '&#2795;',
        'update' => '&#270F;',
        'delete' => '&#2796;',
        'select' => '&#128065;',
    ];
    return $icons[$operation] ?? '&#128196;';
}
@endphp

<style>
.transaction-show-header {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.header-content {
    flex: 1;
}

.title-section h1 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 2rem;
}

.transaction-id-display {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.transaction-code {
    font-family: monospace;
    font-size: 1rem;
    background: #f8f9fa;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    color: #333;
    font-weight: 600;
}

.transaction-type-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: #007bff;
    color: white;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: 500;
}

.status-section {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 1rem;
}

.status-indicator.pending {
    background: #fff3cd;
    color: #856404;
}

.status-indicator.committed {
    background: #d4edda;
    color: #155724;
}

.status-indicator.rolled_back {
    background: #f8d7da;
    color: #721c24;
}

.status-indicator.failed {
    background: #f8d7da;
    color: #721c24;
}

.status-icon {
    font-size: 1.2rem;
}

.completion-time {
    font-size: 0.9rem;
    color: #666;
    font-style: italic;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
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
    gap: 0.5rem;
    transition: all 0.2s;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-warning {
    background: #ffc107;
    color: #333;
}

.btn-warning:hover {
    background: #e0a800;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}

.inline-form {
    display: inline;
    margin: 0;
}

.transaction-details-container {
    display: grid;
    gap: 2rem;
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 2rem;
}

.detail-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.detail-card.error {
    border-left: 4px solid #dc3545;
}

.card-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.card-icon {
    font-size: 1.5rem;
    opacity: 0.7;
}

.card-content {
    padding: 1.5rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.detail-code {
    font-family: monospace;
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 4px;
    font-size: 0.9rem;
}

.transaction-type-detail {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.type-icon {
    font-size: 1.2rem;
    opacity: 0.8;
}

.status-badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
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

.time-detail {
    display: flex;
    flex-direction: column;
}

.time-main {
    font-weight: 500;
    color: #333;
}

.time-sub {
    font-size: 0.8rem;
    color: #666;
}

.time-unknown {
    color: #999;
    font-style: italic;
}

.duration-detail {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.duration-value {
    font-family: monospace;
    font-weight: 600;
    font-size: 1rem;
}

.duration-value.fast {
    color: #28a745;
}

.duration-value.slow {
    color: #dc3545;
}

.duration-bar {
    width: 100px;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.duration-fill {
    height: 100%;
    background: #007bff;
    transition: width 0.3s ease;
}

.user-detail {
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

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    color: #333;
}

.user-email {
    font-size: 0.8rem;
    color: #666;
}

.user-unknown {
    color: #999;
    font-style: italic;
}

.description-text {
    line-height: 1.6;
    color: #333;
    margin: 0;
}

.error-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.error-message, .retry-count {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.error-text {
    color: #dc3545;
    font-weight: 500;
}

.retry-value {
    font-weight: 600;
    color: #333;
}

.timeline-card, .performance-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-bottom: 1.5rem;
}

.timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.25rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
    border: 2px solid white;
    box-shadow: 0 0 0 2px #007bff;
}

.timeline-item.completed .timeline-marker {
    background: #28a745;
    box-shadow: 0 0 0 2px #28a745;
}

.timeline-item.pending .timeline-marker {
    background: #ffc107;
    box-shadow: 0 0 0 2px #ffc107;
}

.timeline-marker.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 2px #ffc107; }
    50% { box-shadow: 0 0 0 6px rgba(255, 193, 7, 0.3); }
    100% { box-shadow: 0 0 0 2px #ffc107; }
}

.timeline-content {
    padding-left: 0.5rem;
}

.timeline-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
}

.timeline-time {
    font-size: 0.8rem;
    color: #666;
}

.performance-metrics {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.metric-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    background: #f8f9fa;
    border-radius: 6px;
}

.metric-item label {
    font-weight: 500;
    color: #666;
}

.metric-value {
    font-weight: 600;
    font-size: 1.1rem;
}

.metric-value.success { color: #28a745; }
.metric-value.warning { color: #ffc107; }
.metric-value.danger { color: #dc3545; }

.data-sections {
    display: grid;
    gap: 1.5rem;
}

.data-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.data-container {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    overflow-x: auto;
}

.data-container pre {
    margin: 0;
    font-family: monospace;
    font-size: 0.9rem;
    line-height: 1.5;
    color: #333;
}

.operation-logs-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
    margin-top: 2rem;
}

.logs-header {
    padding: 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logs-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.logs-summary {
    display: flex;
    gap: 1rem;
    font-size: 0.9rem;
    color: #666;
}

.total-operations {
    font-weight: 600;
}

.logs-container {
    padding: 1rem;
}

.log-item {
    border: 1px solid #eee;
    border-radius: 8px;
    margin-bottom: 1rem;
    overflow: hidden;
}

.log-item.success {
    border-left: 4px solid #28a745;
}

.log-item.failed {
    border-left: 4px solid #dc3545;
}

.log-header {
    padding: 1rem;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.log-operation {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.operation-icon {
    font-size: 1.2rem;
    opacity: 0.8;
}

.operation-name {
    font-weight: 600;
    color: #333;
    font-family: monospace;
}

.log-status {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.status-indicator.success {
    background: #d4edda;
    color: #155724;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

.status-indicator.failed {
    background: #f8d7da;
    color: #721c24;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

.execution-time {
    font-family: monospace;
    font-weight: 600;
    font-size: 0.8rem;
}

.execution-time.fast {
    color: #28a745;
}

.execution-time.slow {
    color: #dc3545;
}

.log-details {
    padding: 1rem;
}

.log-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-weight: 600;
    color: #666;
    font-size: 0.8rem;
}

.info-item code {
    font-family: monospace;
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 3px;
    font-size: 0.8rem;
}

.log-expandable {
    border-top: 1px solid #eee;
    padding-top: 1rem;
}

.expand-btn {
    background: none;
    border: 1px solid #dee2e6;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
    transition: all 0.2s;
}

.expand-btn:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.log-expanded-content {
    margin-top: 1rem;
    display: grid;
    gap: 1rem;
}

.log-expanded-content .data-section h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 1rem;
}

.log-expanded-content .data-container {
    background: white;
    border: 1px solid #eee;
}

.log-expanded-content .error-content {
    color: #dc3545;
    font-weight: 500;
    padding: 0.75rem;
    background: #f8d7da;
    border-radius: 4px;
}

.sql-container {
    background: #2d3748;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: 4px;
    overflow-x: auto;
}

.sql-container code {
    color: inherit;
    background: none;
}

.no-logs {
    text-align: center;
    padding: 3rem 2rem;
    color: #666;
}

.no-logs-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.no-logs h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

@media (max-width: 1024px) {
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .transaction-show-header {
        flex-direction: column;
        gap: 1.5rem;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }
}

@media (max-width: 768px) {
    .transaction-show-header {
        padding: 1.5rem;
    }
    
    .title-section h1 {
        font-size: 1.5rem;
    }
    
    .transaction-id-display {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .log-info {
        grid-template-columns: 1fr;
    }
    
    .logs-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }
}
</style>

<script>
function toggleLogDetails(logId) {
    const details = document.getElementById('log-details-' + logId);
    details.style.display = details.style.display === 'none' ? 'grid' : 'none';
}

function monitorTransaction(transactionId) {
    // Implement transaction monitoring functionality
    alert('Monitoring transaction ' + transactionId + '. This would open a real-time monitoring view.');
}
</script>
@endsection

<style>
.transaction-details {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #007bff;
}

.detail-card.error {
    border-left-color: #dc3545;
}

.detail-card h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-item label {
    font-weight: 600;
    color: #666;
    margin-bottom: 0.25rem;
    font-size: 0.9em;
}

.detail-item span {
    color: #333;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
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

.success {
    background: #d4edda;
    color: #155724;
}

.failed {
    background: #f8d7da;
    color: #721c24;
}

.operation-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 600;
    font-family: monospace;
}

.operation-insert {
    background: #d4edda;
    color: #155724;
}

.operation-update {
    background: #cce5ff;
    color: #004085;
}

.operation-delete {
    background: #f8d7da;
    color: #721c24;
}

.data-container {
    background: #f8f9fa;
    border-radius: 4px;
    padding: 1rem;
    overflow-x: auto;
}

.data-container pre {
    margin: 0;
    font-size: 0.9em;
    line-height: 1.4;
}

.error-message, .retry-count {
    margin-bottom: 0.5rem;
}

.error-message {
    color: #dc3545;
}

.operation-logs {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.log-details {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 4px;
    margin: 0.5rem 0;
}

.data-section {
    margin-bottom: 1rem;
}

.data-section:last-child {
    margin-bottom: 0;
}

.data-section h4 {
    margin: 0 0 0.5rem 0;
    color: #666;
    font-size: 0.9em;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: #666;
}

code {
    background: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: monospace;
    font-size: 0.9em;
}
</style>

<script>
function toggleLogDetails(logId) {
    const detailsRow = document.getElementById('log-details-' + logId);
    detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
}
</script>
@endsection
