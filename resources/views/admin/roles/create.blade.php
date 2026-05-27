@extends('layouts.app')

@section('title', 'Create Role - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc"><a href="{{ route('admin.roles.index') }}">Roles</a></span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Create</span>
        </div>
    </div>

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div class="dash-panel-title">Create New Role</div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('admin.roles.store') }}">
                    @csrf
                    <div class="form-group">
                        <label for="name">Role Name</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" id="description" name="description" value="{{ old('description') }}">
                    </div>
                    <div class="form-group">
                        <label for="parent_id">Hierarchy (reports to)</label>
                        <select id="parent_id" name="parent_id">
                            <option value="">None — top-level role</option>
                            @foreach($hierarchyRoles as $hRole)
                                <option value="{{ $hRole->id }}" data-level="{{ $hRole->level }}" @selected((string) old('parent_id') === (string) $hRole->id)>
                                    {{ $hRole->name }}@if($hRole->description) — {{ $hRole->description }} @endif
                                </option>
                            @endforeach
                        </select>
                        <small style="display: block; margin-top: 6px; color: var(--text-muted);">RBAC hierarchy (reporting line only): parent role for organization charts. Permissions for users come only from this role’s checked permissions below—not from the parent.</small>
                        @error('parent_id')
                            <div class="error" style="margin-top: 8px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label for="level">Level (Higher = More Privileges)</label>
                        <input type="number" id="level" name="level" value="{{ old('level') }}" min="0">
                        <small id="level-help" style="display: block; margin-top: 6px; color: var(--text-muted);">Required when this is a top-level role (no parent). If you pick a parent above, the level is set automatically from that role.</small>
                        @error('level')
                            <div class="error" style="margin-top: 8px;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Permissions</label>
                        <div style="max-height: 300px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 12px;">
                            @foreach($permissions as $permission)
                                <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-weight: normal;">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" {{ in_array($permission->id, old('permissions', [])) ? 'checked' : '' }}>
                                    <span>{{ $permission->name }}</span>
                                    <small style="color: var(--text-muted);">- {{ $permission->description }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="dash-form-actions">
                        <button type="submit" class="btn">Create Role</button>
                        <a href="{{ route('admin.roles.index') }}" class="btn btn--ghost">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function () {
            var parentEl = document.getElementById('parent_id');
            var levelEl = document.getElementById('level');
            var helpEl = document.getElementById('level-help');
            if (!parentEl || !levelEl) return;
            function syncLevelFromParent() {
                var opt = parentEl.selectedOptions[0];
                var pl = opt && opt.getAttribute('data-level');
                if (parentEl.value && pl != null && pl !== '') {
                    levelEl.value = pl;
                    levelEl.readOnly = true;
                    levelEl.required = false;
                    levelEl.removeAttribute('required');
                    if (helpEl) helpEl.textContent = 'Level is set automatically from the parent role you selected.';
                } else {
                    levelEl.readOnly = false;
                    levelEl.required = true;
                    levelEl.setAttribute('required', 'required');
                    if (helpEl) helpEl.textContent = 'Required when this is a top-level role (no parent).';
                }
            }
            parentEl.addEventListener('change', syncLevelFromParent);
            syncLevelFromParent();
        })();
    </script>
@endsection
