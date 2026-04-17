@extends('layouts.app')

@section('title', 'Activity Log #' . $log->id . ' - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.activity-logs.index') }}">Activity Logs</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Log #{{ $log->id }}</span>
        </div>
        <div class="dash-topbar-right">
            @if(auth()->user()->hasRole('admin'))
                <form method="POST" action="{{ route('admin.activity-logs.destroy', $log) }}" class="inline-form" onsubmit="return confirm('Are you sure you want to delete this log?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger">
                        <span class="icon">&#128465;</span> Delete Log
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn--ghost">
                <span class="icon">&#8592;</span> Back to List
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="dash-row-2">
        {{-- Log Details --}}
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Log Details</div>
                    <div class="dash-panel-sub">Activity Record #{{ $log->id }}</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="detail-list">
                    <div class="detail-item">
                        <span class="detail-label">User</span>
                        <div class="detail-value">
                            <span class="user-name">{{ $log->user?->name ?? 'Unknown' }}</span>
                            <span class="user-id">ID: {{ $log->user_id ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Action</span>
                        <div class="detail-value">
                            <span class="action-badge action-{{ explode('.', $log->action)[0] }}">
                                {{ $log->action }}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Description</span>
                        <div class="detail-value">{{ $log->description }}</div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <div class="detail-value">
                            <span class="status-badge status-{{ $log->status }}">
                                {{ ucfirst($log->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">IP Address</span>
                        <div class="detail-value"><code class="ip-address">{{ $log->ip_address }}</code></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Time</span>
                        <div class="detail-value time-cell">
                            <span class="time-main">{{ $log->created_at->format('F d, Y') }}</span>
                            <span class="time-sub">{{ $log->created_at->format('h:i:s A') }}</span>
                        </div>
                    </div>
                    <div class="detail-item full-width">
                        <span class="detail-label">User Agent</span>
                        <div class="detail-value"><code class="user-agent">{{ $log->user_agent ?? 'N/A' }}</code></div>
                    </div>
                    <div class="detail-item full-width">
                        <span class="detail-label">System Details</span>
                        <div class="detail-value">
                            @if($log->details)
                                <pre class="code-block">{{ json_encode($log->details, JSON_PRETTY_PRINT) }}</pre>
                            @else
                                <span class="text-muted">No additional details</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Editable Fields --}}
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Editable Fields</div>
                    <div class="dash-panel-sub">Add notes or custom labels</div>
                </div>
            </div>
            <div class="dash-panel-body">
                {{-- Custom Action --}}
                <form method="POST" action="{{ route('admin.activity-logs.custom-action.update', $log) }}" class="edit-form">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="custom_action">
                            <span class="icon">&#127991;</span> Custom Action Label
                        </label>
                        <input type="text" id="custom_action" name="custom_action" value="{{ $log->custom_action ?? '' }}" placeholder="e.g., Admin Login, Teacher Grading, etc.">
                        <small class="help-text">Add a friendly label to categorize this action</small>
                    </div>
                    <button type="submit" class="btn btn-sm">Update Label</button>
                </form>

                <hr class="form-divider">

                {{-- Notes --}}
                <form method="POST" action="{{ route('admin.activity-logs.notes.update', $log) }}" class="edit-form">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="notes">
                            <span class="icon">&#128221;</span> Notes
                        </label>
                        <textarea id="notes" name="notes" rows="4" placeholder="Add your observations, comments, or investigation notes here...">{{ $log->notes ?? '' }}</textarea>
                        <small class="help-text">Internal notes for administrators (not visible to users)</small>
                    </div>
                    <button type="submit" class="btn btn-sm">Save Notes</button>
                </form>

                @if($log->notes || $log->custom_action)
                    <div class="current-annotations">
                        <h4>Current Annotations</h4>
                        @if($log->custom_action)
                            <div class="annotation-item">
                                <span class="annotation-label">Custom Action:</span>
                                <span class="annotation-value">{{ $log->custom_action }}</span>
                            </div>
                        @endif
                        @if($log->notes)
                            <div class="annotation-item">
                                <span class="annotation-label">Notes:</span>
                                <span class="annotation-value">{{ $log->notes }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
