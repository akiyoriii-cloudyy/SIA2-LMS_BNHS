@extends('layouts.app')

@section('title', 'Permission: ' . $permission->name . ' - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.permissions.index') }}">Permissions</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">{{ $permission->name }}</span>
        </div>
    </div>

    <div class="dash-row-2">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Permission Details</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="detail-list">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <div class="detail-value"><code>{{ $permission->name }}</code></div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Description</span>
                        <div class="detail-value">{{ $permission->description ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Assigned Roles</div>
                    <div class="dash-panel-sub">{{ $permission->roles->count() }} roles</div>
                </div>
            </div>
            <div class="dash-panel-body">
                @if($permission->roles->isEmpty())
                    <p class="text-muted">No roles assigned</p>
                @else
                    <div class="activity-list">
                        @foreach($permission->roles as $role)
                            <div class="activity-item">
                                <div class="activity-info">
                                    <span class="activity-name">{{ $role->name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="dash-form-actions">
        <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn">Edit Permission</a>
        <a href="{{ route('admin.permissions.index') }}" class="btn btn--ghost">Back</a>
    </div>
@endsection
