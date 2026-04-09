<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Challenge</title>
    <link rel="stylesheet" href="{{ asset('lms.css') }}?v={{ @filemtime(public_path('lms.css')) }}">
</head>
<body class="lms lms-guest lms-login">
    <div class="auth-wrap auth-wrap--split">
        <div class="auth-split">
            <main class="auth-main" style="grid-column: 1 / -1;">
                <div class="card auth-card" style="max-width:520px;margin:40px auto;">
                    <div class="header">
                        <h1 class="title">Two-step verification</h1>
                        <p class="subtitle">Enter the 6-digit code from your authenticator app or a recovery code.</p>
                    </div>

                    @if ($errors->any())
                        <div class="error">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('mfa.verify') }}">
                        @csrf
                        <div class="auth-field">
                            <label for="code">Authentication code</label>
                            <input id="code" name="code" required autocomplete="one-time-code" placeholder="123456 or RECOVERY-CODE">
                            @if (!empty($email))
                                <div class="muted" style="font-size:12px; margin-top:6px;">Signing in as {{ $email }}</div>
                            @endif
                        </div>
                        <button class="btn" type="submit" style="width:100%;">Verify</button>
                    </form>

                    <div class="auth-meta">
                        <a class="muted" href="{{ route('login') }}">Back to login</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>

