<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Login</title>
    <link rel="stylesheet" href="{{ asset('lms.css') }}?v={{ @filemtime(public_path('lms.css')) }}">
    <script>
        window.__LMS_THEME_SEED = "guest";
    </script>
    <script src="{{ asset('lms-theme.js') }}?v={{ @filemtime(public_path('lms-theme.js')) }}" defer></script>
</head>
<body class="lms lms-guest lms-login">
    <div class="auth-wrap auth-wrap--split">
        <div class="auth-split">
            <section class="auth-side" aria-label="School branding">
                <div class="auth-side-inner">
                    <div class="auth-logo-card" aria-hidden="true">
                        <img class="auth-side-logo" src="{{ asset('bnhs-logo.jpg') }}" alt="">
                    </div>
                    <div class="auth-side-text">
                        <div class="auth-side-school">Bawing National High School</div>
                        <div class="auth-side-sub">Learning Management System</div>
                    </div>
                </div>
            </section>

            <main class="auth-main">
                <div class="card auth-card">
                    <div class="header">
                        <h1 class="title">Welcome</h1>
                        <p class="subtitle">Sign in to continue</p>
                    </div>

                    @if ($errors->any())
                        <div class="error">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="auth-field">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
                        </div>

                        <div class="auth-field">
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" required autocomplete="current-password">
                        </div>

                        <button class="btn" type="submit" style="width:100%;">Sign In</button>
                    </form>

                    <div class="auth-meta">
                        <a class="muted" href="{{ route('password.request') }}">Forgot password?</a>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
