<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BNHS LMS')</title>
    <link rel="stylesheet" href="{{ asset('lms.css') }}?v={{ @filemtime(public_path('lms.css')) }}">
    <script>
        window.__LMS_THEME_SEED = @json(auth()->check() ? (auth()->id().'|'.auth()->user()->email) : 'guest');
    </script>
    <script src="{{ asset('lms-theme.js') }}?v={{ @filemtime(public_path('lms-theme.js')) }}" defer></script>
</head>
<body class="lms">
    <input class="nav-toggle" type="checkbox" id="nav-toggle">

    <div class="shell">
        <aside class="sidebar">
            @auth
                <div class="sidebar-logo">
                    <div class="logo-box">EduTrack</div>
                    <div class="logo-sub">SHS Management System</div>
                    <label class="toggle-btn" for="nav-toggle" aria-label="Close menu">&#10005;</label>
                </div>

                <nav class="menu">
                    <div class="group">Overview</div>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <span class="icon">▣</span> Dashboard
                    </a>

                    <div class="group">LMS</div>
                    <a href="{{ route('courses.index') }}" class="{{ request()->routeIs('courses.*') ? 'active' : '' }}">
                        <span class="icon">≡</span> Courses
                    </a>

                    @if (auth()->user()->hasRole('admin', 'teacher'))
                        <div class="group">Grading</div>
                        <a href="{{ route('gradebook.index') }}" class="{{ request()->routeIs('gradebook.*') ? 'active' : '' }}">
                            <span class="icon">✎</span> Grade Entry
                        </a>
                        <a href="{{ route('report-cards.index') }}" class="{{ request()->routeIs('report-cards.*') ? 'active' : '' }}">
                            <span class="icon">☰</span> Report Card
                        </a>

                        <div class="group">Attendance</div>
                        <a href="{{ route('mobile.app') }}" class="{{ request()->routeIs('mobile.app') ? 'active' : '' }}">
                            <span class="icon">▦</span> Mobile App
                        </a>
                        <a href="{{ route('attendance.index') }}" class="{{ request()->routeIs('attendance.*') ? 'active' : '' }}">
                            <span class="icon">✓</span> Attendance
                        </a>
                        <a href="{{ route('sms-logs.index') }}" class="{{ request()->routeIs('sms-logs.*') ? 'active' : '' }}">
                            <span class="icon">✉</span> SMS Logs
                        </a>

                        <div class="group">System</div>
                        <a href="{{ route('system.tables') }}" class="{{ request()->routeIs('system.tables') ? 'active' : '' }}">
                            <span class="icon">▦</span> Database
                        </a>
                        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active' : '' }}">
                            <span class="icon">◎</span> Students
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <div class="sidebar-profile">
                        <div class="avatar">{{ mb_substr((string) auth()->user()->name, 0, 1) }}</div>
                        <div>
                            <div style="font-weight:900;">{{ auth()->user()->name }}</div>
                            <div class="muted" style="font-size: 12px;">
                                @php
                                    $role = auth()->user()->roles->first()?->name ?? 'user';
                                @endphp
                                {{ ucfirst($role) }}
                            </div>
                        </div>
                    </div>
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
                <div style="font-weight:900;">EduTrack</div>
                <div style="width:42px;"></div>
            </div>

            <div class="container">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
