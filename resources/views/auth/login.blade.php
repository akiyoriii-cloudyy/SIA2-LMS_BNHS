<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS Login</title>
    <style>
        body { margin: 0; font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: #e5e7eb; }
        .wrap { min-height: 100vh; display: grid; place-items: center; }
        .card { background: #fff; padding: 24px; border-radius: 10px; width: 100%; max-width: 380px; border: 1px solid #d1d5db; }
        input { width: 100%; padding: 10px; border: 1px solid #9ca3af; border-radius: 8px; margin-bottom: 12px; }
        button { width: 100%; padding: 10px; border: none; background: #0f766e; color: #fff; border-radius: 8px; cursor: pointer; }
        .error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; padding: 8px; border-radius: 8px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h2>School LMS Login</h2>
            <p>Admin / Teacher / Student access</p>

            @if ($errors->any())
                <div class="error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <button type="submit">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>

