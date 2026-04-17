@extends('layouts.app')

@section('title', 'Settings')

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
                    <div class="dash-panel-sub">Update your account details</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('settings.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="grid-3" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div>
                            <label>First name</label>
                            <input name="first_name" value="{{ old('first_name', $user?->profile?->first_name) }}" required>
                        </div>
                        <div>
                            <label>Middle name (optional)</label>
                            <input name="middle_name" value="{{ old('middle_name', $user?->profile?->middle_name) }}">
                        </div>
                        <div>
                            <label>Last name</label>
                            <input name="last_name" value="{{ old('last_name', $user?->profile?->last_name) }}" required>
                        </div>
                        <div>
                            <label>Suffix (optional)</label>
                            <input name="suffix" value="{{ old('suffix', $user?->profile?->suffix) }}">
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" value="{{ $user?->email }}" readonly disabled>
                            <div class="muted" style="font-size:12px; margin-top:6px;">Email changes are managed by the admin.</div>
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
    </div>

    @if (auth()->user()->hasRole('admin'))
    <div class="dash-row-2" style="grid-template-columns: 1fr; margin-top:12px;">
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
            </div>
        </div>
    </div>
    @endif
@endsection
