@extends('layouts.app')

@section('title', 'Transaction Operation Logs')

@section('content')
<div class="logs-page-header">
    <div class="header-content">
        <h1>Transaction Operation Logs</h1>
        <p class="header-description">Detailed logging of all database operations within business transactions</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.transactions.index') }}" class="btn btn-secondary">
            <span class="icon">&#8592;</span> Back to Transactions
        </a>
        <a href="{{ route('admin.transactions.analytics') }}" class="btn btn-primary">
            <span class="icon">&#128200;</span> Analytics Dashboard
        </a>
    </div>
</div>

<div class="logs-overview">
    <div class="overview-cards">
        <div class="overview-card total">
            <div class="card-icon">&#128196;</div>
            <div class="card-value">{{ $logs->total() }}</div>
            <div class="card-label">Total Operations</div>
        </div>
        <div class="overview-card success">
            <div class="card-icon">&#10004;</div>
            <div class="card-value">{{ $logs->where('was_successful', true)->count() }}</div>
            <div class="card-label">Successful</div>
        </div>
        <div class="overview-card failed">
            <div class="card-icon">&#128714;</div>
            <div class="card-value">{{ $logs->where('was_successful', false)->count() }}</div>
            <div class="card-label">Failed</div>
        </div>
        <div class="overview-card performance">
            <div class="card-icon">&#128200;</div>
            <div class="card-value">{{ number_format($logs->avg('execution_time_ms') ?? 0, 2) }}ms</div>
            <div class="card-label">Avg Execution</div>
        </div>
    </div>
</div>

<div class="logs-filters">
    <div class="filter-header">
        <h3>Filter Operations</h3>
        <div class="filter-summary">
            <span class="active-filters-count">
                {{ collect(request()->only(['operation', 'table_name', 'was_successful', 'date_from', 'date_to']))->filter()->count() }} filters active
            </span>
        </div>
    </div>
    <form method="GET" action="{{ route('admin.transactions.logs') }}" class="filter-form">
        <div class="filter-grid">
            <div class="filter-group">
                <label for="operation">Operation Type</label>
                <select name="operation" id="operation" class="form-control">
                    <option value="">All Operations</option>
                    @foreach($operations as $operation)
                        <option value="{{ $operation }}" {{ request('operation') == $operation ? 'selected' : '' }}>
                            {{ getOperationIcon($operation) }} {{ ucfirst($operation) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="table_name">Table Name</label>
                <select name="table_name" id="table_name" class="form-control">
                    <option value="">All Tables</option>
                    @foreach($tables as $table)
                        <option value="{{ $table }}" {{ request('table_name') == $table ? 'selected' : '' }}>
                            {{ $table }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <label for="was_successful">Status</label>
                <select name="was_successful" id="was_successful" class="form-control">
                    <option value="">All Status</option>
                    <option value="true" {{ request('was_successful') == 'true' ? 'selected' : '' }}>
                        &#10004; Successful
                    </option>
                    <option value="false" {{ request('was_successful') == 'false' ? 'selected' : '' }}>
                        &#128714; Failed
                    </option>
                </select>
            </div>

            <div class="filter-group">
                <label for="date_from">Date From</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>

            <div class="filter-group">
                <label for="date_to">Date To</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <span class="icon">&#128269;</span> Apply Filters
                </button>
                <a href="{{ route('admin.transactions.logs') }}" class="btn btn-secondary">
                    <span class="icon">&#128276;</span> Clear
                </a>
            </div>
        </div>
    </form>
</div>

<div class="logs-table-container">
    <div class="table-header">
        <h3>Operation History</h3>
        <div class="table-actions">
            <span class="record-count">{{ $logs->total() }} records</span>
            <div class="export-options">
                <button class="export-btn" onclick="exportLogs()">
                    <span class="icon">&#128229;</span> Export
                </button>
            </div>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="logs-table">
            <thead>
                <tr>
                    <th>Transaction ID</th>
                    <th>Operation</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Execution Time</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr class="log-row {{ $log->was_successful ? 'success' : 'failed' }}">
                        <td>
                            <div class="transaction-cell">
                                <code class="transaction-id">{{ $log->transaction_id }}</code>
                                @if($log->businessTransaction)
                                    <div class="transaction-type">
                                        {{ ucfirst(str_replace('_', ' ', $log->businessTransaction->transaction_type)) }}
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="operation-cell">
                                <span class="operation-icon operation-{{ $log->operation }}">
                                    {{ getOperationIcon($log->operation) }}
                                </span>
                                <span class="operation-name">{{ strtoupper($log->operation) }}</span>
                            </div>
                        </td>
                        <td>
                            <div class="table-cell">
                                <code class="table-name">{{ $log->table_name }}</code>
                            </div>
                        </td>
                        <td>
                            <div class="record-cell">
                                @if($log->record_id)
                                    <code class="record-id">{{ $log->record_id }}</code>
                                @else
                                    <span class="no-record">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="execution-cell">
                                @if($log->execution_time_ms)
                                    <div class="execution-metrics">
                                        <span class="execution-time {{ $log->execution_time_ms > 1000 ? 'slow' : 'fast' }}">
                                            {{ number_format($log->execution_time_ms, 2) }}ms
                                        </span>
                                        <div class="execution-bar">
                                            <div class="execution-fill" style="width: {{ min(100, ($log->execution_time_ms / 2000) * 100) }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <span class="no-time">-</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="status-cell">
                                <span class="status-indicator {{ $log->was_successful ? 'success' : 'failed' }}">
                                    <span class="status-icon">{{ $log->was_successful ? '&#10004;' : '&#128714;' }}</span>
                                    {{ $log->was_successful ? 'Success' : 'Failed' }}
                                </span>
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
                                <button class="action-btn view-btn" onclick="toggleLogDetails({{ $log->id }})" title="View Details">
                                    <span class="icon">&#128065;</span>
                                </button>
                                @if($log->businessTransaction)
                                    <a href="{{ route('admin.transactions.show', $log->businessTransaction) }}" class="action-btn transaction-btn" title="View Transaction">
                                        <span class="icon">&#128202;</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    <tr id="log-details-{{ $log->id }}" class="details-row" style="display: none;">
                        <td colspan="8">
                            <div class="log-details-content">
                                <div class="details-header">
                                    <h4>Operation Details</h4>
                                    <div class="details-meta">
                                        <span class="log-id">Log ID: {{ $log->id }}</span>
                                        <span class="log-timestamp">{{ $log->created_at->format('M j, Y H:i:s') }}</span>
                                    </div>
                                </div>
                                
                                <div class="details-grid">
                                    @if($log->old_values)
                                        <div class="detail-section old-values">
                                            <h5>Old Values</h5>
                                            <div class="data-container">
                                                <pre>{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->new_values)
                                        <div class="detail-section new-values">
                                            <h5>New Values</h5>
                                            <div class="data-container">
                                                <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->error_message)
                                        <div class="detail-section error-section">
                                            <h5>Error Information</h5>
                                            <div class="error-content">
                                                <div class="error-message">{{ $log->error_message }}</div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->sql_query)
                                        <div class="detail-section sql-section">
                                            <h5>SQL Query</h5>
                                            <div class="sql-container">
                                                <code>{{ $log->sql_query }}</code>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($log->businessTransaction)
                                        <div class="detail-section transaction-section">
                                            <h5>Transaction Information</h5>
                                            <div class="transaction-info">
                                                <div class="info-row">
                                                    <label>Transaction Type:</label>
                                                    <span>{{ ucfirst(str_replace('_', ' ', $log->businessTransaction->transaction_type)) }}</span>
                                                </div>
                                                <div class="info-row">
                                                    <label>Status:</label>
                                                    <span class="status-badge status-{{ $log->businessTransaction->status }}">
                                                        {{ ucfirst($log->businessTransaction->status) }}
                                                    </span>
                                                </div>
                                                <div class="info-row">
                                                    <label>User:</label>
                                                    <span>{{ $log->businessTransaction->user?->name ?? 'System' }}</span>
                                                </div>
                                                <div class="info-row">
                                                    <label>Started At:</label>
                                                    <span>{{ $log->businessTransaction->started_at->format('M j, Y H:i:s') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-footer">
        <div class="pagination-info">
            <span>Showing {{ $logs->firstItem() }} to {{ $logs->lastItem() }} of {{ $logs->total() }} operations</span>
        </div>
        {{ $logs->links() }}
    </div>
</div>

@php
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
.logs-page-header {
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

.header-content h1 {
    margin: 0 0 0.5rem 0;
    color: #333;
    font-size: 2rem;
}

.header-description {
    margin: 0;
    color: #666;
    font-size: 1rem;
}

.header-actions {
    display: flex;
    gap: 0.75rem;
}

.logs-overview {
    margin-bottom: 2rem;
}

.overview-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.overview-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    text-align: center;
    border-left: 4px solid #007bff;
}

.overview-card.total { border-left-color: #007bff; }
.overview-card.success { border-left-color: #28a745; }
.overview-card.failed { border-left-color: #dc3545; }
.overview-card.performance { border-left-color: #17a2b8; }

.card-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    opacity: 0.8;
}

.card-value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.25rem;
}

.card-label {
    font-size: 0.9rem;
    color: #666;
    font-weight: 500;
}

.logs-filters {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.filter-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.active-filters-count {
    font-size: 0.8rem;
    color: #666;
    background: #f8f9fa;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-group label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.form-control {
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.filter-actions {
    display: flex;
    gap: 0.75rem;
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

.logs-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    overflow: hidden;
}

.table-header {
    padding: 1.5rem;
    background: #f8f9fa;
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

.export-btn {
    background: none;
    border: 1px solid #dee2e6;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
    transition: all 0.2s;
}

.export-btn:hover {
    background: #f8f9fa;
    border-color: #adb5bd;
}

.table-responsive {
    overflow-x: auto;
}

.logs-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.logs-table th {
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

.logs-table td {
    padding: 0.75rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

.log-row.success {
    background: rgba(40, 167, 69, 0.02);
}

.log-row.failed {
    background: rgba(220, 53, 69, 0.02);
}

.transaction-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.transaction-id {
    font-family: monospace;
    font-size: 0.8em;
    background: #f1f3f4;
    padding: 2px 6px;
    border-radius: 4px;
    color: #333;
    font-weight: 600;
}

.transaction-type {
    font-size: 0.7rem;
    color: #666;
}

.operation-cell {
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
    font-size: 0.85rem;
}

.table-name {
    font-family: monospace;
    font-size: 0.8em;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    color: #333;
}

.record-id {
    font-family: monospace;
    font-size: 0.8em;
    background: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    color: #333;
}

.no-record {
    color: #999;
    font-style: italic;
}

.execution-cell {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.execution-metrics {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.execution-time {
    font-family: monospace;
    font-weight: 600;
    font-size: 0.85rem;
}

.execution-time.fast {
    color: #28a745;
}

.execution-time.slow {
    color: #dc3545;
}

.execution-bar {
    width: 60px;
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}

.execution-fill {
    height: 100%;
    background: #007bff;
    transition: width 0.3s ease;
}

.no-time {
    color: #999;
    font-style: italic;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-indicator.success {
    background: #d4edda;
    color: #155724;
}

.status-indicator.failed {
    background: #f8d7da;
    color: #721c24;
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
    font-size: 0.85rem;
}

.time-sub {
    font-size: 0.7rem;
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
    text-decoration: none;
}

.view-btn {
    background: #007bff;
    color: white;
}

.view-btn:hover {
    background: #0056b3;
}

.transaction-btn {
    background: #17a2b8;
    color: white;
}

.transaction-btn:hover {
    background: #138496;
}

.details-row {
    background: #f8f9fa;
}

.log-details-content {
    padding: 1.5rem;
}

.details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.details-header h4 {
    margin: 0;
    color: #333;
    font-size: 1.1rem;
}

.details-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.8rem;
    color: #666;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
}

.detail-section {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    border: 1px solid #eee;
}

.detail-section h5 {
    margin: 0 0 1rem 0;
    color: #333;
    font-size: 1rem;
    font-weight: 600;
}

.data-container {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    overflow-x: auto;
    max-height: 200px;
    overflow-y: auto;
}

.data-container pre {
    margin: 0;
    font-family: monospace;
    font-size: 0.8rem;
    line-height: 1.4;
    color: #333;
}

.error-section {
    border-left: 4px solid #dc3545;
}

.error-content {
    background: #f8d7da;
    padding: 1rem;
    border-radius: 6px;
    color: #721c24;
    font-weight: 500;
}

.sql-container {
    background: #2d3748;
    color: #e2e8f0;
    padding: 1rem;
    border-radius: 6px;
    overflow-x: auto;
}

.sql-container code {
    color: inherit;
    background: none;
    font-family: monospace;
    font-size: 0.8rem;
}

.transaction-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.info-row label {
    font-weight: 600;
    color: #666;
    font-size: 0.9rem;
}

.info-row span {
    color: #333;
    font-weight: 500;
}

.status-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
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

.table-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #eee;
    background: #f8f9fa;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pagination-info {
    font-size: 0.8rem;
    color: #666;
}

@media (max-width: 768px) {
    .logs-page-header {
        flex-direction: column;
        gap: 1.5rem;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
        justify-content: flex-start;
    }
    
    .overview-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-actions {
        justify-content: stretch;
    }
    
    .filter-actions .btn {
        flex: 1;
        justify-content: center;
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
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .details-header {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }
    
    .details-meta {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<script>
function toggleLogDetails(logId) {
    const detailsRow = document.getElementById('log-details-' + logId);
    detailsRow.style.display = detailsRow.style.display === 'none' ? 'table-row' : 'none';
}

function exportLogs() {
    // Implement export functionality
    alert('Export functionality would be implemented here. This would allow exporting the filtered logs to CSV or Excel format.');
}
</script>
@endsection

<style>
.transaction-id {
    font-size: 0.8em;
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
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

.execution-time {
    font-family: monospace;
    font-size: 0.9em;
    font-weight: 600;
}

.execution-time.fast {
    color: #28a745;
}

.execution-time.slow {
    color: #dc3545;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: 600;
}

.success {
    background: #d4edda;
    color: #155724;
}

.failed {
    background: #f8d7da;
    color: #721c24;
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

.data-section pre {
    background: #fff;
    padding: 0.5rem;
    border-radius: 3px;
    margin: 0;
    font-size: 0.8em;
    line-height: 1.4;
    max-height: 200px;
    overflow-y: auto;
}

.data-section.error h4 {
    color: #dc3545;
}

.data-section.error p {
    color: #dc3545;
    margin: 0;
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
