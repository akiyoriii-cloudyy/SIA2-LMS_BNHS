@extends('layouts.app')

@section('title', 'Admin Settings')

@section('content')
    @php
        $nameParts = preg_split('/\s+/', trim((string) ($user?->name ?? ''))) ?: [];
        $fallbackFirstName = $nameParts[0] ?? '';
        $fallbackLastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';
        $roleName = ucfirst((string) ($user?->roles?->first()?->name ?? 'admin'));
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Settings</span>
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
                    <div class="dash-panel-title">Profile</div>
                    <div class="dash-panel-sub">Update your admin account details</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('admin.settings.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="grid-3" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div>
                            <label>Role</label>
                            <input value="{{ $roleName }}" readonly disabled>
                            <div class="muted" style="font-size:12px; margin-top:6px;">Role is managed via admin user permissions.</div>
                        </div>
                        <div>
                            <label>First name</label>
                            <input name="first_name" value="{{ old('first_name', $user?->profile?->first_name ?? $fallbackFirstName) }}" required>
                        </div>
                        <div>
                            <label>Middle name</label>
                            <input name="middle_name" value="{{ old('middle_name', $user?->profile?->middle_name) }}">
                        </div>
                        <div>
                            <label>Last name</label>
                            <input name="last_name" value="{{ old('last_name', $user?->profile?->last_name ?? $fallbackLastName) }}" required>
                        </div>
                        <div>
                            <label>Suffix (optional)</label>
                            <input name="suffix" value="{{ old('suffix', $user?->profile?->suffix) }}">
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
                        </div>
                        <div>
                            <label>Phone</label>
                            <input name="phone" value="{{ old('phone', $user?->phone) }}" required>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button class="btn btn-primary" type="submit" style="width:100%;">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Password</div>
                    <div class="dash-panel-sub">Change your password</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('admin.settings.password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="grid-3" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div>
                            <label>Current password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div>
                            <label>New password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div>
                            <label>Confirm new password</label>
                            <input type="password" name="password_confirmation" required>
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button class="btn btn-gold" type="submit" style="width:100%;">Update password</button>
                        </div>
                    </div>
                </form>
                <div class="muted" style="font-size:12px; margin-top: 10px;">
                    Tip: Admins can also reset other users' passwords from User Management.
                </div>
            </div>
        </div>
    </div>
@endsection
