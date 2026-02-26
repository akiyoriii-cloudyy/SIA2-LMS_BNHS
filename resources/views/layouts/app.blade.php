<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BNHS LMS')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('lms.css') }}?v={{ @filemtime(public_path('lms.css')) }}">
    <script>
        window.__LMS_THEME_SEED = @json(auth()->check() ? (auth()->id().'|'.auth()->user()->email) : 'guest');
    </script>
    <script src="{{ asset('lms-theme.js') }}?v={{ @filemtime(public_path('lms-theme.js')) }}" defer></script>
</head>
<body class="lms {{ auth()->check() ? 'lms-auth' : 'lms-guest' }}">
    @if (auth()->check())
        <input class="nav-toggle" type="checkbox" id="nav-toggle">

        <div class="shell">
            <aside class="sidebar">
                <div class="sidebar-logo">
                    <div class="logo-badge">
                        <div class="logo-icon">ET</div>
                        <div>
                            <div class="logo-text">EduTrack</div>
                            <div class="logo-sub">SHS Management System</div>
                        </div>
                    </div>
                    <div class="school-tag">
                        Bawing District • Division of General Santos<br>
                        Region XII – SOCCSKSARGEN
                    </div>
                    <label class="toggle-btn" for="nav-toggle" aria-label="Close menu">&#10005;</label>
                </div>

                <nav class="menu">
                    <div class="group">Main</div>
                    @if (auth()->user()->hasRole('admin'))
                        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') || request()->routeIs('dashboard') ? 'active' : '' }}">
                            User Management
                        </a>

                        <div class="group">Admin</div>
                        <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                            Settings
                        </a>
                        <a href="{{ route('sms-logs.index') }}" class="{{ request()->routeIs('sms-logs.*') ? 'active' : '' }}">
                            SMS Logs
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            Dashboard
                        </a>
                    @endif

                    @if (auth()->user()->hasRole('teacher'))
                        <a href="{{ route('gradebook.index') }}" class="{{ request()->routeIs('gradebook.*') ? 'active' : '' }}">
                            Grade Entry
                        </a>
                        <a href="{{ route('report-cards.index') }}" class="{{ request()->routeIs('report-cards.*') ? 'active' : '' }}">
                            DepEd Report Card
                        </a>

                        <div class="group">Records</div>
                        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active' : '' }}">
                            Students
                        </a>
                        <a href="{{ route('subjects.index') }}" class="{{ request()->routeIs('subjects.*') ? 'active' : '' }}">
                            Subjects
                        </a>
                        <a href="{{ route('report-cards.index') }}" class="{{ request()->routeIs('report-cards.*') ? 'active' : '' }}">
                            Print Reports
                            <span class="nav-badge">3</span>
                        </a>
                    @endif
                </nav>

                <div class="sidebar-footer">
                    @if (auth()->user()->hasRole('admin'))
                        <a class="sidebar-footer-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}"
                            href="{{ route('admin.settings') }}">
                            Settings
                        </a>
                    @elseif (auth()->user()->hasRole('teacher'))
                        <a class="sidebar-footer-link {{ request()->routeIs('settings*') ? 'active' : '' }}"
                            href="{{ route('settings') }}">
                            Settings
                        </a>
                    @endif

                    <div class="teacher-card">
                        <div class="teacher-avatar">{{ mb_substr((string) auth()->user()->name, 0, 1) }}</div>
                        <div class="teacher-info">
                            <div class="tname">{{ auth()->user()->name }}</div>
                            <div class="trole">
                                @php
                                    $role = auth()->user()->roles->first()?->name ?? 'user';
                                @endphp
                                {{ ucfirst($role) }}
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="btn" type="submit">Logout</button>
                    </form>
                </div>
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
    @else
        <main class="main main--guest">
            <div class="container">
                @yield('content')
            </div>
        </main>
    @endif
</body>
</html>
