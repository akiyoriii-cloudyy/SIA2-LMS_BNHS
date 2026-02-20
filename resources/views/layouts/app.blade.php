<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BNHS LMS')</title>
    <link rel="stylesheet" href="{{ asset('lms.css') }}">
    <script>
        window.__LMS_THEME_SEED = @json(auth()->check() ? (auth()->id().'|'.auth()->user()->email) : 'guest');
    </script>
    <script src="{{ asset('lms-theme.js') }}" defer></script>
</head>
<body class="lms">
    <input class="nav-toggle" type="checkbox" id="nav-toggle">

    <div class="shell">
        <aside class="sidebar">
            <div class="brand">
                <div>
                    <strong>BNHS LMS</strong><br>
                    <small>Senior High</small>
                </div>
                <label class="toggle-btn" for="nav-toggle">&#10005;</label>
            </div>

            @auth
                <div class="sidebar-user">
                    Signed in as <strong>{{ auth()->user()->name }}</strong>
                </div>

                <nav class="menu">
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>

                    <div class="group">LMS</div>
                    <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}">Courses</a>

                    @if (auth()->user()->hasRole('admin', 'teacher'))
                        <div class="group">Grading</div>
                        <a href="{{ route('gradebook.index') }}" class="{{ request()->routeIs('gradebook.*') ? 'active' : '' }}">Gradebook</a>
                        <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}">Attendance</a>
                        <a href="{{ route('report-cards.index') }}" class="{{ request()->routeIs('report-cards.*') ? 'active' : '' }}">Report Cards</a>

                        <div class="group">System</div>
                        <a href="{{ route('system.tables') }}" class="{{ request()->routeIs('system.tables') ? 'active' : '' }}">Database Tables</a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn" type="submit" style="width:100%;">Logout</button>
                    </form>
                </div>
            @endauth
        </aside>

        <main class="main">
            <div class="topbar">
                <label class="toggle-btn" for="nav-toggle">&#9776;</label>
                <div style="font-weight:800;">BNHS LMS</div>
                <div style="width:42px;"></div>
            </div>

            <div class="container">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
