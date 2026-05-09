@extends('layouts.app')

@section('title', 'MFA Setup')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">MFA Setup</span>
        </div>
        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('security') }}">Back</a>
        </div>
    </div>

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">Enable MFA (Authenticator App)</div>
                <div class="dash-panel-sub">Scan the QR in an authenticator app, then verify with a code.</div>
            </div>
        </div>
        <div class="dash-panel-body">
            @php
                $qr = null;
                try {
                    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                        new \BaconQrCode\Renderer\RendererStyle\RendererStyle(180),
                        new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
                    );
                    $writer = new \BaconQrCode\Writer($renderer);
                    $qr = $writer->writeString($otpauth);
                } catch (\Throwable $e) {
                    $qr = null;
                }
            @endphp

            <div style="display:grid; grid-template-columns: 220px 1fr; gap: 16px; align-items:start;">
                <div style="background:#fff; padding:12px; border-radius:12px; border:1px solid var(--cream-dark);">
                    @if ($qr)
                        <div style="width:100%; overflow:hidden;">{!! $qr !!}</div>
                    @else
                        <div class="muted">QR preview unavailable. Use the secret below.</div>
                    @endif
                </div>
                <div>
                    <div class="muted" style="font-size:12px;">Manual secret</div>
                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 14px; padding: 10px 12px; background: #fff; border: 1px solid var(--cream-dark); border-radius: 10px;">
                        {{ $secret }}
                    </div>
                    <div class="muted" style="font-size:12px; margin-top:8px;">OTPAuth URI</div>
                    <div style="font-family: 'JetBrains Mono', monospace; font-size: 12px; padding: 10px 12px; background: #fff; border: 1px solid var(--cream-dark); border-radius: 10px; word-break: break-all;">
                        {{ $otpauth }}
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.mfa.enable') }}" style="margin-top:14px; max-width:520px;">
                @csrf
                <input type="hidden" name="secret" value="{{ $secret }}">
                <div class="auth-field">
                    <label for="code">Verify code</label>
                    <input id="code" name="code" required placeholder="123456" autocomplete="one-time-code">
                </div>
                <button class="btn btn-primary" type="submit">Enable MFA</button>
            </form>

            <form method="POST" action="{{ route('settings.mfa.disable') }}" style="margin-top:12px;">
                @csrf
                <button class="btn btn-outline" type="submit">Disable MFA</button>
            </form>
        </div>
    </div>
@endsection

