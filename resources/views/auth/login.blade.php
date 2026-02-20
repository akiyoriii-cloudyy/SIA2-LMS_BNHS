<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Login</title>
    <link rel="stylesheet" href="{{ asset('lms.css') }}">
    <script>
        window.__LMS_THEME_SEED = "guest";
    </script>
    <script src="{{ asset('lms-theme.js') }}" defer></script>
</head>
<body class="lms">
    <div class="auth-wrap">
        <div class="card auth-card">
            <div class="header">
                <div class="badge"><span class="dot"></span> BNHS LMS</div>
                <h1 class="title">School LMS Login</h1>
                <p class="subtitle">Admin / Teacher / Student access</p>
            </div>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <button class="btn" type="submit" style="width:100%;">Sign In</button>
            </form>

            <div class="auth-meta">
                <span>Secure access portal</span>
                <span class="muted">Theme adapts per user</span>
            </div>
        </div>
    </div>
</body>
</html>
