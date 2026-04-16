@extends('layouts.app')

@section('title', 'Create Permission - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.permissions.index') }}">Permissions</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Create</span>
        </div>
    </div>

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div class="dash-panel-title">Create New Permission</div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('admin.permissions.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="name">Permission Name (format: action.resource)</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g., users.manage, reports.view" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}" placeholder="Brief description of what this permission allows">
                    </div>
                    <div class="form-group">
                        <label>Assign to Roles</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 12px;">
                            @foreach($roles as $role)
                                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-weight: normal;">
                                    <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                    <span>{{ $role->name }}</span>
                                    <small style="color: var(--text-muted);">- Level {{ $role->level }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="dash-form-actions">
                        <button type="submit" class="btn">Create Permission</button>
                        <a href="{{ route('admin.permissions.index') }}" class="btn btn--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
