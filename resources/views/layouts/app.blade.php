<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior High Grading System</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --text: #1f2937;
            --card: #ffffff;
            --line: #d1d5db;
            --accent: #0f766e;
            --accent-dark: #0b5f58;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .nav {
            background: #0b1324;
            padding: 12px 20px;
            display: flex;
            gap: 16px;
        }

        .nav a {
            color: #e2e8f0;
            text-decoration: none;
            font-weight: 600;
        }

        .btn {
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover { background: var(--accent-dark); }

        .grid-4 {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid var(--line);
            padding: 8px;
            text-align: left;
        }

        th { background: #e5e7eb; }

        input, select {
            width: 100%;
            padding: 7px;
            border: 1px solid var(--line);
            border-radius: 6px;
        }

        .text-right { text-align: right; }
        .alert {
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 12px;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        @media (max-width: 800px) {
            .grid-4 { grid-template-columns: 1fr; }
            .container { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="nav">
        @auth
            <a href="{{ route('courses.index') }}">Courses</a>
            @if (auth()->user()->hasRole('admin', 'teacher'))
                <a href="{{ route('gradebook.index') }}">Gradebook</a>
                <a href="{{ route('attendance.index') }}">Attendance</a>
                <a href="{{ route('report-cards.index') }}">Report Cards</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="margin-left:auto;">
                @csrf
                <button class="btn" type="submit">Logout</button>
            </form>
        @endauth
    </div>
    <div class="container">
        @yield('content')
    </div>
</body>
</html>
