<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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
                <h1 class="title">Reset Password</h1>
                <p class="subtitle">Set a new password for your account.</p>
            </div>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <label>Email</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required>

                <label>New password</label>
                <input type="password" name="password" required>

                <label>Confirm new password</label>
                <input type="password" name="password_confirmation" required>

                <button class="btn" type="submit" style="width:100%;">Reset password</button>
            </form>

            <div class="auth-meta">
                <a class="muted" href="{{ route('login') }}">Back to login</a>
                <span class="muted">Password reset</span>
            </div>
        </div>
    </div>
</body>
</html>

