@extends('layouts.app')

@section('title', 'Role Management - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Role Management</span>
        </div>
        <div class="dash-topbar-right" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="#archived-roles" class="btn btn--ghost btn-sm" title="Jump to archived roles (soft-deleted)">
                Archived roles
                @if($archivedRoles->isNotEmpty())
                    <span class="nav-badge" style="margin-left: 4px;">{{ $archivedRoles->count() }}</span>
                @endif
            </a>
            <a href="{{ route('admin.roles.create') }}" class="btn">
                <span class="icon">&#10133;</span> Create Role
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    <div id="active-roles" class="dash-row-2" style="grid-template-columns: 1fr; scroll-margin-top: 5rem;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">System Roles</div>
                    <div class="dash-panel-sub">{{ $roles->count() }} active roles — Delete moves a role to Archived below (data and user links are kept).</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Hierarchy</th>
                                <th>Level</th>
                                <th>Permissions</th>
                                <th>Users</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                                <tr>
                                    <td><strong>{{ $role->name }}</strong></td>
                                    <td>{{ $role->description ?? '-' }}</td>
                                    <td>
                                        @if($role->parent)
                                            {{ $role->parent->name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="nav-badge">{{ $role->level }}</span></td>
                                    <td>{{ $role->permissions->count() }}</td>
                                    <td>{{ $role->users_count }}</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn--ghost">View</a>
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn--ghost">Edit</a>
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline-form" onsubmit="return confirm('Archive this role? It will leave this table and appear under Archived roles on this same page, where you can click Restore.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No active roles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="archived-roles" class="dash-row-2" style="grid-template-columns: 1fr; margin-top: 1.5rem; scroll-margin-top: 5rem;">
        <div class="dash-panel" @if($archivedRoles->isNotEmpty()) style="border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,.06);" @endif>
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Archived roles</div>
                    <div class="dash-panel-sub">
                        @if($archivedRoles->isEmpty())
                            None right now. Roles you delete from the table above show up here; use Restore to return them to the active list.
                        @else
                            {{ $archivedRoles->count() }} archived — removed from the active list only; user assignments stay in the database until you restore.
                        @endif
                    </div>
                </div>
                @if($archivedRoles->isNotEmpty())
                    <a href="#active-roles" class="btn btn-sm btn--ghost">Back to active list</a>
                @endif
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Hierarchy</th>
                                <th>Level</th>
                                <th>Permissions</th>
                                <th>Users</th>
                                <th>Archived</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archivedRoles as $role)
                                <tr>
                                    <td><strong>{{ $role->name }}</strong></td>
                                    <td>{{ $role->description ?? '-' }}</td>
                                    <td>
                                        @if($role->parent)
                                            {{ $role->parent->name }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="nav-badge">{{ $role->level }}</span></td>
                                    <td>{{ $role->permissions->count() }}</td>
                                    <td>{{ $role->users_count }}</td>
                                    <td>{{ $role->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td style="text-align: center;">
                                        <form method="POST" action="{{ route('admin.roles.restore', $role->id) }}" class="inline-form" onsubmit="return confirm('Restore this role to the active list above?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="color: var(--text-muted);">No archived roles.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
