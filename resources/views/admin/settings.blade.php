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
                    <div class="dash-panel-title">Security</div>
                    <div class="dash-panel-sub">Protect your account with multi-factor authentication (MFA).</div>
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
