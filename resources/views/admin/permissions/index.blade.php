@extends('layouts.app')

@section('title', 'Permissions - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Permissions</span>
        </div>
        <div class="dash-topbar-right" style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
            <a href="#archived-permissions" class="btn btn--ghost btn-sm" title="Jump to archived permissions">
                Archived permissions
                @if($archivedPermissions->isNotEmpty())
                    <span class="nav-badge" style="margin-left: 4px;">{{ $archivedPermissions->count() }}</span>
                @endif
            </a>
            <a href="{{ route('admin.permissions.create') }}" class="btn">
                <span class="icon">&#10133;</span> Create Permission
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    <div id="active-permissions" class="dash-row-2" style="grid-template-columns: 1fr; scroll-margin-top: 5rem;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">System Permissions</div>
                    <div class="dash-panel-sub">{{ $permissions->count() }} active permissions — Delete archives to the section below (role links are kept until you permanently change them).</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Assigned Roles</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permissions as $permission)
                                <tr>
                                    <td><code>{{ $permission->name }}</code></td>
                                    <td>{{ $permission->description ?? '-' }}</td>
                                    <td>{{ $permission->roles->count() }}</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-sm btn--ghost">View</a>
                                        <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn--ghost">Edit</a>
                                        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="inline-form" onsubmit="return confirm('Archive this permission? It leaves this table and appears under Archived permissions on this page, where you can Restore.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No active permissions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="archived-permissions" class="dash-row-2" style="grid-template-columns: 1fr; margin-top: 1.5rem; scroll-margin-top: 5rem;">
        <div class="dash-panel" @if($archivedPermissions->isNotEmpty()) style="border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,.06);" @endif>
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Archived permissions</div>
                    <div class="dash-panel-sub">
                        @if($archivedPermissions->isEmpty())
                            None right now. Permissions you delete from the table above appear here; Restore makes them active again for route checks and role assignments.
                        @else
                            {{ $archivedPermissions->count() }} archived — not enforced in access control until restored.
                        @endif
                    </div>
                </div>
                @if($archivedPermissions->isNotEmpty())
                    <a href="#active-permissions" class="btn btn-sm btn--ghost">Back to active list</a>
                @endif
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Assigned Roles</th>
                                <th>Archived</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archivedPermissions as $permission)
                                <tr>
                                    <td><code>{{ $permission->name }}</code></td>
                                    <td>{{ $permission->description ?? '-' }}</td>
                                    <td>{{ $permission->roles->count() }}</td>
                                    <td>{{ $permission->deleted_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td style="text-align: center;">
                                        <form method="POST" action="{{ route('admin.permissions.restore', $permission->id) }}" class="inline-form" onsubmit="return confirm('Restore this permission to the active list above?');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm">Restore</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" style="color: var(--text-muted);">No archived permissions.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
