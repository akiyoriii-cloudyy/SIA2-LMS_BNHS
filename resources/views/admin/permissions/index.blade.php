@extends('layouts.app')

@section('title', 'Permissions - EduTrack')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Permissions</span>
        </div>
        <div class="dash-topbar-right">
            <a href="{{ route('admin.permissions.create') }}" class="btn">
                <span class="icon">&#10133;</span> Create Permission
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">System Permissions</div>
                    <div class="dash-panel-sub">{{ $permissions->count() }} permissions defined</div>
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
                            @foreach($permissions as $permission)
                                <tr>
                                    <td><code>{{ $permission->name }}</code></td>
                                    <td>{{ $permission->description ?? '-' }}</td>
                                    <td>{{ $permission->roles->count() }}</td>
                                    <td style="text-align: center;">
                                        <a href="{{ route('admin.permissions.show', $permission) }}" class="btn btn-sm btn--ghost">View</a>
                                        <a href="{{ route('admin.permissions.edit', $permission) }}" class="btn btn-sm btn--ghost">Edit</a>
                                        <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="inline-form" onsubmit="return confirm('Delete this permission?');">
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
