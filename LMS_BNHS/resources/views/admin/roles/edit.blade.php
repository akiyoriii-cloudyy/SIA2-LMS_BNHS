@extends('layouts.app')

@section('title', 'Edit Role: ' . $role->name . ' - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.roles.index') }}">Roles</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Edit</span>
        </div>
    </div>

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div class="dash-panel-title">Edit Role</div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" value="{{ old('description', $role->description) }}">
                    </div>
                    <div class="form-group">
                        <label for="level">Level (Higher = More Privileges)</label>
                        <input type="number" id="level" name="level" value="{{ old('level', $role->level) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Permissions</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 12px;">
                            @foreach($permissions as $permission)
                                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-weight: normal;">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id, old('permissions', $rolePermissions)) ? 'checked' : '' }}>
                                    <span>{{ $permission->name }}</span>
                                    <small style="color: var(--text-muted);">- {{ $permission->description }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="dash-form-actions">
                        <button type="submit" class="btn">Update Role</button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
