@extends('layouts.app')

@section('title', 'Role Management - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Role Management</span>
        </div>
        <div class="dash-topbar-right">
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

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">System Roles</div>
                    <div class="dash-panel-sub">{{ $roles->count() }} roles defined</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Level</th>
                                <th>Permissions</th>
                                <th>Users</th>
                                <th style="text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                                <tr>
                                    <td><strong>{{ $role->name }}</strong></td>
                                    <td>{{ $role->description ?? '-' }}</td>
                                    <td><span class="nav-badge">{{ $role->level }}</span></td>
                                    <td>{{ $role->permissions->count() }}</td>
                                    <td>{{ $role->users->count() }}</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('admin.roles.show', $role) }}" class="btn btn-sm btn--ghost">View</a>
                                        <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn--ghost">Edit</a>
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline-form" onsubmit="return confirm('Delete this role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn--danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
