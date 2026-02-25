@extends('layouts.app')

@section('title', 'Admin Settings')

@section('content')
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
                            <label>Name</label>
                            <input name="name" value="{{ old('name', $user?->name) }}" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $user?->email) }}" required>
                        </div>
                        <div>
                            <label>Phone (optional)</label>
                            <input name="phone" value="{{ old('phone', $user?->phone) }}">
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
                    Tip: Admins can also reset other usersâ€™ passwords from User Management.
                </div>
            </div>
        </div>
    </div>
@endsection

