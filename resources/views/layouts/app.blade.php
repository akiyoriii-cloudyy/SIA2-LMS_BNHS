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
<body class="lms {{ auth()->check() ? 'lms-auth' : 'lms-guest' }} {{ auth()->check() ? (auth()->user()->hasRole('admin') ? 'lms-role-admin' : (auth()->user()->hasRole('adviser') ? 'lms-role-adviser' : (auth()->user()->hasRole('subject_teacher') ? 'lms-role-subject-teacher' : 'lms-role-user'))) : '' }}">
    @if (auth()->check())
        @php
            $sidebarMissingGrades = 0;
            $sidebarPrintableReports = 0;

            $sidebarIsAdviser = auth()->user()->hasRole('adviser');
            $sidebarIsSubjectTeacher = auth()->user()->hasRole('subject_teacher');
            if ($sidebarIsAdviser || $sidebarIsSubjectTeacher) {
                $sidebarSemesterInput = (int) request()->integer('semester', 0);
                $sidebarQuarterInput = (int) request()->integer('quarter', request()->integer('current_quarter', 0));

                if (in_array($sidebarSemesterInput, [1, 2], true)) {
                    $sidebarSemester = $sidebarSemesterInput;
                    $sidebarQuarterInSemester = max(1, min(2, $sidebarQuarterInput > 0 ? $sidebarQuarterInput : 1));
                    $sidebarQuarter = $sidebarSemester === 1 ? $sidebarQuarterInSemester : $sidebarQuarterInSemester + 2;
                } else {
                    $sidebarQuarter = max(1, min(4, (int) request()->integer('quarter', \App\Support\SidebarMetrics::currentQuarter())));
                    $sidebarSemester = $sidebarQuarter <= 2 ? 1 : 2;
                    $sidebarQuarterInSemester = $sidebarQuarter <= 2 ? $sidebarQuarter : $sidebarQuarter - 2;
                }

                $sidebarMissingGrades = \App\Support\SidebarMetrics::teacherMissingGradesCount(auth()->user(), $sidebarQuarter);
                $sidebarPrintableReports = $sidebarIsAdviser
                    ? \App\Support\SidebarMetrics::printableReportCardsCount(auth()->user())
                    : 0;
            }
        @endphp

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
                        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="icon">&#128200;</span>
                            Dashboard
                        </a>

                        <a href="{{ route('notifications.index') }}"
                            class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                            <span class="icon">&#128276;</span>
                            Notifications
                            @if (($inAppUnreadCount ?? 0) > 0)
                                <span class="nav-badge">{{ $inAppUnreadCount > 99 ? '99+' : $inAppUnreadCount }}</span>
                            @endif
                        </a>

                        <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <span class="icon">&#128100;</span>
                            User Management
                        </a>

                        <div class="group">Communications</div>
                        @if (auth()->user()->hasPermission('sms_logs.view'))
                            <a href="{{ route('sms-logs.index') }}" class="{{ request()->routeIs('sms-logs.*') ? 'active' : '' }}">
                                <span class="icon">&#128241;</span>
                                SMS logs
                            </a>
                        @endif
                        <a href="{{ route('mobile.app') }}" class="{{ request()->routeIs('mobile.app') ? 'active' : '' }}">
                            <span class="icon">&#128242;</span>
                            RFID mobile app
                        </a>

                        <div class="group">Admin</div>
                        @if (auth()->user()->hasPermission('activity_logs.view'))
                            <a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') || request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
                                <span class="icon">&#128220;</span>
                                Activity Logs
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('roles.manage'))
                            <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <span class="icon">&#128101;</span>
                                Role Management
                            </a>
                        @endif
                        @if (auth()->user()->hasPermission('permissions.manage'))
                            <a href="{{ route('admin.permissions.index') }}" class="{{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                                <span class="icon">&#128295;</span>
                                Permissions
                            </a>
                        @endif
                        <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings*') ? 'active' : '' }}">
                            <span class="icon">&#9881;</span>
                            Settings
                        </a>
                    @else
                        <a href="{{ ($sidebarIsAdviser || $sidebarIsSubjectTeacher) ? route('dashboard', ['semester' => $sidebarSemester, 'quarter' => $sidebarQuarterInSemester]) : route('dashboard') }}"
                            class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="icon">&#128200;</span>
                            Dashboard
                        </a>

                        @if ($sidebarIsAdviser || $sidebarIsSubjectTeacher)
                            <a href="{{ route('notifications.index') }}"
                                class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                                <span class="icon">&#128276;</span>
                                Notifications
                                @if (($inAppUnreadCount ?? 0) > 0)
                                    <span class="nav-badge">{{ $inAppUnreadCount > 99 ? '99+' : $inAppUnreadCount }}</span>
                                @endif
                            </a>
                        @endif
                    @endif

                    @if ($sidebarIsSubjectTeacher && ! $sidebarIsAdviser)
                        <a href="{{ route('gradebook.index', ['subject_category' => 'core', 'semester' => $sidebarSemester, 'quarter' => $sidebarQuarterInSemester]) }}" class="{{ request()->routeIs('gradebook.*') ? 'active' : '' }}">
                            <span class="icon">&#9999;</span>
                            Grade Entry
                            @if ($sidebarMissingGrades > 0)
                                <span class="nav-badge">{{ $sidebarMissingGrades }}</span>
                            @endif
                        </a>

                        @if (auth()->user()->hasPermission('activity_logs.view'))
                            <div class="group">Activity</div>
                            <a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') || request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
                                <span class="icon">&#128220;</span>
                                Activity Logs
                            </a>
                        @endif
                    @endif

                    @if ($sidebarIsAdviser)
                        <a href="{{ route('gradebook.index', ['subject_category' => 'core', 'semester' => $sidebarSemester, 'quarter' => $sidebarQuarterInSemester]) }}" class="{{ request()->routeIs('gradebook.*') ? 'active' : '' }}">
                            <span class="icon">&#9999;</span>
                            Grade Entry
                            @if ($sidebarMissingGrades > 0)
                                <span class="nav-badge">{{ $sidebarMissingGrades }}</span>
                            @endif
                        </a>

                        <a href="{{ route('master-sheet.index', ['semester' => $sidebarSemester, 'quarter' => $sidebarQuarterInSemester]) }}" class="{{ request()->routeIs('master-sheet.*') ? 'active' : '' }}">
                            <span class="icon">&#128196;</span>
                            Master Sheet
                        </a>

                        <a href="{{ route('subject-teacher.index', ['semester' => $sidebarSemester, 'quarter' => $sidebarQuarterInSemester]) }}" class="{{ request()->routeIs('subject-teacher.*') ? 'active' : '' }}">
                            <span class="icon">&#127891;</span>
                            Subject load
                            @if ($sidebarMissingGrades > 0)
                                <span class="nav-badge">{{ $sidebarMissingGrades }}</span>
                            @endif
                        </a>

                        <a href="{{ route('report-cards.index') }}"
                            class="{{ request()->routeIs('report-cards.*') ? 'active' : '' }}">
                            <span class="icon">&#128221;</span>
                            DepEd Report Card
                        </a>

                        <div class="group">Records</div>
                        <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students.*') ? 'active' : '' }}">
                            <span class="icon">&#128101;</span>
                            Students
                        </a>
                        <a href="{{ route('subjects.index') }}" class="{{ request()->routeIs('subjects.*') ? 'active' : '' }}">
                            <span class="icon">&#128218;</span>
                            Subjects
                        </a>
                        <a href="{{ route('report-cards.index') }}" class="{{ request()->routeIs('report-cards.*') ? 'active' : '' }}">
                            <span class="icon">&#128424;</span>
                            Print Reports
                            @if ($sidebarPrintableReports > 0)
                                <span class="nav-badge">{{ $sidebarPrintableReports }}</span>
                            @endif
                        </a>

                        <div class="group">Communications</div>
                        @if (auth()->user()->hasPermission('sms_logs.view'))
                            <a href="{{ route('sms-logs.index') }}" class="{{ request()->routeIs('sms-logs.*') ? 'active' : '' }}">
                                <span class="icon">&#128241;</span>
                                SMS logs
                            </a>
                        @endif
                        <a href="{{ route('mobile.app') }}" class="{{ request()->routeIs('mobile.app') ? 'active' : '' }}">
                            <span class="icon">&#128242;</span>
                            RFID mobile app
                        </a>

                        @if (auth()->user()->hasPermission('activity_logs.view'))
                            <div class="group">Activity</div>
                            <a href="{{ route('admin.activity-logs.index') }}" class="{{ request()->routeIs('admin.activity-logs.*') || request()->routeIs('admin.sessions.*') ? 'active' : '' }}">
                                <span class="icon">&#128220;</span>
                                Activity Logs
                            </a>
                        @endif
                    @endif
                </nav>

                <div class="sidebar-footer">
                    <a class="sidebar-footer-link {{ request()->routeIs('profile.show') ? 'active' : '' }}"
                        href="{{ route('profile.show') }}">
                        <span class="icon">&#128100;</span> My Profile
                    </a>
                    @if (auth()->user()->hasRole('admin'))
                        <a class="sidebar-footer-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}"
                            href="{{ route('admin.settings') }}">
                            <span class="icon">&#9881;</span> Settings
                        </a>
                    @elseif ($sidebarIsAdviser || $sidebarIsSubjectTeacher)
                        <a class="sidebar-footer-link {{ request()->routeIs('settings*') ? 'active' : '' }}"
                            href="{{ route('settings') }}">
                            <span class="icon">&#9881;</span> Settings
                        </a>
                    @endif

                    <div class="teacher-card">
                        <div class="teacher-avatar">{{ mb_substr((string) auth()->user()->name, 0, 1) }}</div>
                        <div class="teacher-info">
                            <div class="tname">{{ auth()->user()->name }}</div>
                            <div class="trole">
                                @php
                                    $role = auth()->user()->roles->first()?->name ?? 'user';
                                    $roleLabel = $role === 'subject_teacher' ? 'Subject teacher' : str_replace('_', ' ', $role);
                                @endphp
                                {{ ucfirst($roleLabel) }}
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
