<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Recovery</title>
    <link rel="stylesheet" href="{{ asset('lms.css') }}?v={{ @filemtime(public_path('lms.css')) }}">
    <script>
        window.__LMS_THEME_SEED = "guest";
    </script>
    <script src="{{ asset('lms-theme.js') }}?v={{ @filemtime(public_path('lms-theme.js')) }}" defer></script>
</head>
<body class="lms">
    <div class="auth-wrap">
        <div class="card auth-card">
            <div class="header">
                <div class="badge"><span class="dot"></span> BNHS LMS</div>
                <h1 class="title">Forgot Password</h1>
                <p class="subtitle">Enter your email to receive a reset link.</p>
            </div>

            @if (session('status'))
                <div class="alert">{{ session('status') }}</div>
                <div class="muted" style="font-size:12px;">
                    Note: This project uses <code>MAIL_MAILER=log</code> by default, so the reset link will appear in <code>storage/logs/laravel.log</code>.
                </div>
            @endif

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
                <button class="btn" type="submit" style="width:100%;">Send reset link</button>
            </form>

            <div class="auth-meta">
                <a class="muted" href="{{ route('login') }}">Back to login</a>
                <span class="muted">Secure recovery</span>
            </div>
        </div>
    </div>
</body>
</html>

