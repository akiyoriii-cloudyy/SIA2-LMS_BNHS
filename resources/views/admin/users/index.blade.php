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
                    <div class="dash-panel-sub">Create an admin, adviser, or subject teacher account</div>
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
                                <option value="adviser" @selected(old('role') === 'adviser')>Adviser</option>
                                <option value="subject_teacher" @selected(old('role') === 'subject_teacher')>Subject teacher</option>
                            </select>
                        </div>
                        <div>
                            <label>First name</label>
                            <input name="first_name" value="{{ old('first_name') }}" required>
                        </div>
                        <div>
                            <label>Middle name (optional)</label>
                            <input name="middle_name" value="{{ old('middle_name') }}">
                        </div>
                        <div>
                            <label>Last name</label>
                            <input name="last_name" value="{{ old('last_name') }}" required>
                        </div>
                        <div>
                            <label>Suffix (optional)</label>
                            <input name="suffix" value="{{ old('suffix') }}">
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required>
                        </div>
                        <div>
                            <label>Phone</label>
                            <input name="phone" value="{{ old('phone') }}" required>
                        </div>
                        <div>
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div>
                            <label>Confirm password</label>
                            <input type="password" name="password_confirmation" required>
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
                                    $nameParts = preg_split('/\s+/', trim((string) $u->name)) ?: [];
                                    $fallbackFirstName = $nameParts[0] ?? '';
                                    $fallbackLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
                                    $profileFirstName = $u->profile?->first_name ?? $fallbackFirstName;
                                    $profileMiddleName = $u->profile?->middle_name ?? '';
                                    $profileLastName = $u->profile?->last_name ?? $fallbackLastName;
                                    $profileSuffix = $u->profile?->suffix ?? '';
                                @endphp
                                <tr class="{{ $u->trashed() ? 'row-deleted' : '' }}">
                                    <td>{{ $u->id }}</td>
                                    <td>
                                        <div style="font-weight:800;">{{ $u->display_name }}</div>
                                        @if ($u->trashed())
                                            <div class="muted" style="font-size:12px;">Deleted</div>
                                        @endif
                                    </td>
                                    <td>{{ $u->email }}</td>
                                    <td>{{ $u->phone ?? '—' }}</td>
                                    <td>
                                        @php
                                            $roleBadgeLabel = match ($roleName) {
                                                'subject_teacher' => 'Subject teacher',
                                                'adviser' => 'Adviser',
                                                default => ucfirst($roleName),
                                            };
                                        @endphp
                                        <span class="role-badge role-{{ str_replace('_', '-', $roleName) }}">{{ $roleBadgeLabel }}</span>
                                    </td>
                                    <td>
                                        @if ($u->trashed())
                                            <form method="POST" action="{{ route('admin.users.restore', $u->id) }}">
                                                @csrf
                                                <button class="btn btn-gold btn-sm" type="submit">Restore</button>
                                            </form>
                                        @else
                                            <div class="admin-actions">
                                                <button
                                                    class="btn btn-outline btn-sm js-open-edit-modal"
                                                    type="button"
                                                    data-update-url="{{ route('admin.users.update', $u->id) }}"
                                                    data-role="{{ $roleName }}"
                                                    data-is-admin="{{ $roleName === 'admin' ? '1' : '0' }}"
                                                    data-first-name="{{ $profileFirstName }}"
                                                    data-middle-name="{{ $profileMiddleName }}"
                                                    data-last-name="{{ $profileLastName }}"
                                                    data-email="{{ $u->email }}"
                                                    data-suffix="{{ $profileSuffix }}"
                                                    data-phone="{{ $u->phone ?? '' }}"
                                                >
                                                    Edit
                                                </button>

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

    <div id="user-edit-modal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1200; align-items:center; justify-content:center; padding:20px;">
        <div style="width:min(760px, 96vw); max-height:90vh; overflow:auto; background:#fff; border-radius:14px; border:1px solid #d5ddda; box-shadow:0 20px 45px rgba(0,0,0,.2);">
            <div style="display:flex; justify-content:space-between; align-items:center; padding:16px 18px; border-bottom:1px solid #e5ebe8;">
                <div>
                    <div style="font-size:18px; font-weight:800; color:#0f2f24;">Edit User Details</div>
                    <div class="muted" style="font-size:12px;">Update role and account information</div>
                </div>
                <button id="user-edit-close" class="btn btn-outline btn-sm" type="button">Close</button>
            </div>
            <div style="padding:16px 18px;">
                <form id="user-edit-form" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div style="display:grid; gap:12px;">
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">Role</label>
                            <div id="edit-role-admin" style="display:none;">
                                <input type="text" value="Admin" disabled style="height:48px; font-size:17px; background:#f5f5f5; cursor:not-allowed;">
                                <input type="hidden" name="role" value="admin">
                                <div class="muted" style="font-size:12px; margin-top:4px;">Admin role cannot be changed.</div>
                            </div>
                            <select id="edit-role" name="role" required style="height:48px; font-size:17px;">
                                <option value="admin">Admin</option>
                                <option value="adviser">Adviser</option>
                                <option value="subject_teacher">Subject teacher</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">First name</label>
                            <input id="edit-first-name" type="text" name="first_name" required style="height:48px; font-size:17px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">Middle name</label>
                            <input id="edit-middle-name" type="text" name="middle_name" style="height:48px; font-size:17px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">Last name</label>
                            <input id="edit-last-name" type="text" name="last_name" required style="height:48px; font-size:17px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">Email</label>
                            <input id="edit-email" type="email" name="email" required style="height:48px; font-size:17px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">Suffix (optional)</label>
                            <input id="edit-suffix" type="text" name="suffix" style="height:48px; font-size:17px;">
                        </div>
                        <div>
                            <label style="display:block; font-size:14px; font-weight:700; margin-bottom:6px;">Phone</label>
                            <input id="edit-phone" type="text" name="phone" style="height:48px; font-size:17px;">
                        </div>
                    </div>
                    <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:14px;">
                        <button class="btn btn-outline" type="button" id="user-edit-cancel">Back</button>
                        <button class="btn btn-primary" type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const modal = document.getElementById('user-edit-modal');
            const form = document.getElementById('user-edit-form');
            const closeBtn = document.getElementById('user-edit-close');
            const cancelBtn = document.getElementById('user-edit-cancel');
            const openButtons = document.querySelectorAll('.js-open-edit-modal');

            if (!modal || !form) return;

            const openModal = (button) => {
                form.action = button.dataset.updateUrl || '';
                const isAdmin = button.dataset.isAdmin === '1';
                const roleSelect = document.getElementById('edit-role');
                const roleAdminDiv = document.getElementById('edit-role-admin');

                if (isAdmin) {
                    roleSelect.style.display = 'none';
                    roleSelect.removeAttribute('required');
                    roleAdminDiv.style.display = 'block';
                } else {
                    roleSelect.style.display = 'block';
                    roleSelect.setAttribute('required', 'required');
                    roleAdminDiv.style.display = 'none';
                    roleSelect.value = button.dataset.role || 'adviser';
                }

                document.getElementById('edit-first-name').value = button.dataset.firstName || '';
                document.getElementById('edit-middle-name').value = button.dataset.middleName || '';
                document.getElementById('edit-last-name').value = button.dataset.lastName || '';
                document.getElementById('edit-email').value = button.dataset.email || '';
                document.getElementById('edit-suffix').value = button.dataset.suffix || '';
                document.getElementById('edit-phone').value = button.dataset.phone || '';
                modal.style.display = 'flex';
            };

            const closeModal = () => {
                modal.style.display = 'none';
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', () => openModal(button));
            });

            closeBtn?.addEventListener('click', closeModal);
            cancelBtn?.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal();
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.style.display === 'flex') closeModal();
            });
        })();
    </script>
@endsection
