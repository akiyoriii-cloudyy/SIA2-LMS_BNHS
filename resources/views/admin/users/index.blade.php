@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">User Management</span>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Add New User</div>
                    <div class="dash-panel-sub">Create an admin, teacher, or student account</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="grid-3">
                        <div>
                            <label>Role</label>
                            <select name="role" required>
                                <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                                <option value="teacher" @selected(old('role') === 'teacher')>Teacher</option>
                                <option value="student" @selected(old('role') === 'student')>Student</option>
                            </select>
                        </div>
                        <div>
                            <label>Name</label>
                            <input name="name" value="{{ old('name') }}" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div>
                            <label>Phone (optional)</label>
                            <input name="phone" value="{{ old('phone') }}">
                        </div>
                        <div>
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div>
                            <label>Confirm password</label>
                            <input type="password" name="password_confirmation" required>
                        </div>
                        <div>
                            <label>First name (Teacher/Student)</label>
                            <input name="first_name" value="{{ old('first_name') }}">
                        </div>
                        <div>
                            <label>Last name (Teacher/Student)</label>
                            <input name="last_name" value="{{ old('last_name') }}">
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button class="btn btn-primary" type="submit" style="width:100%;">Add user</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Users</div>
                    <div class="dash-panel-sub">View, delete, and restore accounts</div>
                </div>
                <div class="pill-row">
                    <a class="pill pill-link {{ $status === 'active' ? 'pill-link--active' : '' }}"
                        href="{{ route('admin.users.index', ['status' => 'active', 'q' => $query]) }}">
                        Active ({{ (int) $activeCount }})
                    </a>
                    <a class="pill pill-link {{ $status === 'deleted' ? 'pill-link--active' : '' }}"
                        href="{{ route('admin.users.index', ['status' => 'deleted', 'q' => $query]) }}">
                        Deleted ({{ (int) $deletedCount }})
                    </a>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="GET" action="{{ route('admin.users.index') }}" style="margin-bottom: 12px;">
                    <div class="grid-3" style="grid-template-columns: 1.2fr 0.6fr 0.4fr;">
                        <div>
                            <label>Search</label>
                            <input name="q" value="{{ $query }}" placeholder="Name, email, or phone">
                        </div>
                        <div>
                            <label>Status</label>
                            <select name="status">
                                <option value="active" @selected($status === 'active')>Active</option>
                                <option value="deleted" @selected($status === 'deleted')>Deleted</option>
                            </select>
                        </div>
                        <div style="display:flex; align-items:flex-end; gap: 10px;">
                            <button class="btn btn-outline" type="submit" style="width:100%;">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th style="width:240px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                @php
                                    $roleName = $u->roles->first()?->name ?? 'user';
                                @endphp
                                <tr class="{{ $u->trashed() ? 'row-deleted' : '' }}">
                                    <td>{{ $u->id }}</td>
                                    <td>
                                        <div style="font-weight:800;">{{ $u->name }}</div>
                                        @if ($u->trashed())
                                            <div class="muted" style="font-size:12px;">Deleted</div>
                                        @endif
                                    </td>
                                    <td>{{ $u->email }}</td>
                                    <td>{{ $u->phone ?? '—' }}</td>
                                    <td>
                                        <span class="role-badge role-{{ $roleName }}">{{ ucfirst($roleName) }}</span>
                                    </td>
                                    <td>
                                        @if ($u->trashed())
                                            <form method="POST" action="{{ route('admin.users.restore', $u->id) }}">
                                                @csrf
                                                <button class="btn btn-gold btn-sm" type="submit">Restore</button>
                                            </form>
                                        @else
                                            <div class="admin-actions">
                                                <details class="pw-details">
                                                    <summary class="btn btn-gold btn-sm">Change password</summary>
                                                    <div class="pw-panel">
                                                        <form method="POST" action="{{ route('admin.users.password.update', $u->id) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="pw-grid">
                                                                <input type="password" name="password" placeholder="New password" required>
                                                                <input type="password" name="password_confirmation" placeholder="Confirm" required>
                                                                <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </details>

                                                <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}"
                                                    onsubmit="return confirm('Delete this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="muted">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="pager">
                        <div class="muted" style="font-size: 12px;">
                            Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }}
                        </div>
                        <div class="pager-actions">
                            @if ($users->onFirstPage())
                                <span class="btn btn-outline btn-sm" style="opacity:.45; pointer-events:none;">Prev</span>
                            @else
                                <a class="btn btn-outline btn-sm" href="{{ $users->previousPageUrl() }}">Prev</a>
                            @endif
                            @if ($users->hasMorePages())
                                <a class="btn btn-outline btn-sm" href="{{ $users->nextPageUrl() }}">Next</a>
                            @else
                                <span class="btn btn-outline btn-sm" style="opacity:.45; pointer-events:none;">Next</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
