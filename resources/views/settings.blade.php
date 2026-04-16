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

    <div class="dash-row-2" style="grid-template-columns: 1fr; margin-top:12px;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Security</div>
                    <div class="dash-panel-sub">Manage MFA</div>
                </div>
            </div>
            <div class="dash-panel-body">
                @php
                    $enabled = (bool) ($user?->mfa_enabled && $user?->mfa_confirmed_at);
                    $codes = (array) session('recovery_codes', []);
                @endphp

                <div class="muted" style="margin-bottom:10px;">
                    Status:
                    <strong>{{ $enabled ? 'Enabled' : 'Disabled' }}</strong>
                </div>

                @if (!empty($codes))
                    <div class="alert" style="margin-bottom:12px;">
                        <strong>Recovery codes (save these now):</strong>
                        <div class="muted" style="margin-top:6px;">Each code can be used once if you lose access to your authenticator app.</div>
                        <ul style="margin:8px 0 0; padding-left:18px; font-family:'JetBrains Mono', monospace;">
                            @foreach ($codes as $c)
                                <li>{{ $c }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <a class="btn btn-primary" href="{{ route('settings.mfa') }}">Manage MFA</a>
            </div>
        </div>
    </div>
@endsection
