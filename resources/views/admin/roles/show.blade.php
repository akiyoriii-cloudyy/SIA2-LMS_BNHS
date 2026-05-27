@extends('layouts.app')

@section('title', 'Role: ' . $role->name . ' - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.roles.index') }}">Roles</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">{{ $role->name }}</span>
        </div>
    </div>

    <div class="dash-row-2">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Role Details</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="detail-list">
                    <div class="detail-item">
                        <span class="detail-label">Name</span>
                        <div class="detail-value">{{ $role->name }}</div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Description</span>
                        <div class="detail-value">{{ $role->description ?? '-' }}</div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Hierarchy (reports to)</span>
                        <div class="detail-value">
                            @if($role->parent)
                                @if($role->parent->trashed())
                                    {{ $role->parent->name }}
                                    <small style="color: var(--text-muted);"> (archived)</small>
                                @else
                                    <a href="{{ route('admin.roles.show', $role->parent) }}">{{ $role->parent->name }}</a>
                                @endif
                            @else
                                —
                            @endif
                        </div>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Level</span>
                        <div class="detail-value"><span class="nav-badge">{{ $role->level }}</span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Permissions</div>
                    <div class="dash-panel-sub">{{ $role->permissions->count() }} permissions</div>
                </div>
            </div>
            <div class="dash-panel-body">
                @if($role->permissions->isEmpty())
                    <p class="text-muted">No permissions assigned</p>
                @else
                    <div class="activity-list">
                        @foreach($role->permissions as $permission)
                            <div class="activity-item">
                                <div class="activity-info">
                                    <span class="activity-name">{{ $permission->name }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="dash-form-actions">
        <a href="{{ route('admin.roles.edit', $role) }}" class="btn">Edit Role</a>
        <a href="{{ route('admin.roles.index') }}" class="btn btn--ghost">Back</a>
    </div>
@endsection
