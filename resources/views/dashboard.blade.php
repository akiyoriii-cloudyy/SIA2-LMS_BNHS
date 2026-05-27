@extends('layouts.app')

@section('content')
    @if (!empty($isAdminDashboard))
        @php
            $a = $adminStats ?? [];
            $recentUsersList = collect($recentUsers ?? []);
            $recentSmsLogsList = collect($recentSmsLogs ?? []);
            $publicBase = rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?: '', '/');
        @endphp

        <div class="dash-topbar">
            <div class="dash-topbar-left">
                <span class="dash-topbar-title">EduTrack</span>
                <span class="dash-topbar-sep">/</span>
                <span class="dash-topbar-bc">Admin dashboard</span>
            </div>
            <div class="dash-topbar-actions">
                <a class="btn btn-outline btn-sm" href="{{ route('admin.users.index') }}">User management</a>
                <a class="btn btn-outline btn-sm" href="{{ route('mobile.app') }}">RFID mobile app</a>
                <a class="btn btn-outline btn-sm" href="{{ $publicBase }}/download-app">Download mobile app</a>
                <a class="btn btn-primary btn-sm" href="{{ route('sms-logs.index') }}">SMS logs</a>
            </div>
        </div>

        <div class="dash-panel admin-dash-intro">
            <div class="dash-panel-body">
                <p class="muted" style="margin:0;font-size:13px;line-height:1.5;">
                    School-wide <strong>grades, averages, and report-card analytics</strong> are available on teacher (adviser) accounts only.
                    Use the links below for accounts, system setup, SMS delivery, and the RFID scanner app.
                </p>
            </div>
        </div>

        <div class="dash-kpi-grid">
            <div class="dash-kpi kpi-gold">
                <div class="dash-kpi-top">
                    <div class="dash-kpi-icon">👥</div>
                    <span class="kpi-trend trend-neutral">Accounts</span>
                </div>
                <div class="dash-kpi-value">{{ number_format((int) ($a['total_users'] ?? 0)) }}</div>
                <div class="dash-kpi-label">Total users</div>
                <div class="dash-kpi-sub">Teachers {{ (int) ($a['total_teachers'] ?? 0) }} · Admins {{ (int) ($a['total_admins'] ?? 0) }}</div>
            </div>
            <div class="dash-kpi kpi-sage">
                <div class="dash-kpi-top">
                    <div class="dash-kpi-icon">🧑‍🎓</div>
                    <span class="kpi-trend trend-neutral">{{ $activeSchoolYear?->name ?? 'SY' }}</span>
                </div>
                <div class="dash-kpi-value">{{ number_format((int) ($a['total_students'] ?? 0)) }}</div>
                <div class="dash-kpi-label">Enrolled students</div>
                <div class="dash-kpi-sub">Active school year headcount</div>
            </div>
            <div class="dash-kpi kpi-navy">
                <div class="dash-kpi-top">
                    <div class="dash-kpi-icon">🏫</div>
                    <span class="kpi-trend trend-neutral">Structure</span>
                </div>
                <div class="dash-kpi-value">{{ number_format((int) ($a['total_enrollments'] ?? 0)) }}</div>
                <div class="dash-kpi-label">Enrollments</div>
                <div class="dash-kpi-sub">{{ (int) ($a['total_sections'] ?? 0) }} sections · {{ (int) ($a['total_subjects'] ?? 0) }} catalog subjects · {{ (int) ($a['total_courses'] ?? 0) }} courses</div>
            </div>
            <div class="dash-kpi kpi-red">
                <div class="dash-kpi-top">
                    <div class="dash-kpi-icon">📱</div>
                    <span class="kpi-trend trend-down">{{ (int) ($a['sms_failed'] ?? 0) > 0 ? 'Review failed' : 'OK' }}</span>
                </div>
                <div class="dash-kpi-value">{{ number_format((int) ($a['weekly_alerts'] ?? 0)) }}</div>
                <div class="dash-kpi-label">Absence alerts (week)</div>
                <div class="dash-kpi-sub">Queued {{ (int) ($a['sms_queued'] ?? 0) }} · Failed {{ (int) ($a['sms_failed'] ?? 0) }}</div>
            </div>
        </div>

        <div class="dash-panel admin-quick-admin-links">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Admin setup</div>
                    <div class="dash-panel-sub">Configure the school year, catalog, and staff accounts</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="admin-qgroups" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                    <div class="admin-qgroup" style="margin:0;">
                        <div class="admin-qgroup-title">People &amp; access</div>
                        <div class="admin-qgroup-cards">
                            <a class="admin-qcard" href="{{ route('admin.users.index') }}">
                                <span class="admin-qcard-icon">👥</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">User management</span>
                                    <span class="admin-qcard-desc">Accounts, roles, and profiles</span>
                                </span>
                            </a>
                            <button type="button" class="admin-qcard admin-qcard--btn js-open-notifications">
                                <span class="admin-qcard-icon">🔔</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">Notifications</span>
                                    <span class="admin-qcard-desc">System and assignment alerts</span>
                                </span>
                            </button>
                            <a class="admin-qcard" href="{{ route('settings') }}">
                                <span class="admin-qcard-icon">👤</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">Profile</span>
                                    <span class="admin-qcard-desc">Your account and password</span>
                                </span>
                            </a>
                        </div>
                    </div>
                    <div class="admin-qgroup" style="margin:0;">
                        <div class="admin-qgroup-title">Academic setup</div>
                        <div class="admin-qgroup-cards">
                            <a class="admin-qcard" href="{{ route('admin.system.academic-settings') }}">
                                <span class="admin-qcard-icon">📅</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">Academic settings</span>
                                    <span class="admin-qcard-desc">School years &amp; semesters</span>
                                </span>
                            </a>
                            <a class="admin-qcard" href="{{ route('admin.system.strands') }}">
                                <span class="admin-qcard-icon">🧭</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">Strands</span>
                                    <span class="admin-qcard-desc">Tracks and strand catalog</span>
                                </span>
                            </a>
                            <a class="admin-qcard" href="{{ route('admin.system.terms') }}">
                                <span class="admin-qcard-icon">📆</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">Terms</span>
                                    <span class="admin-qcard-desc">Grading periods</span>
                                </span>
                            </a>
                            <a class="admin-qcard" href="{{ route('admin.system.subjects') }}">
                                <span class="admin-qcard-icon">📚</span>
                                <span class="admin-qcard-body">
                                    <span class="admin-qcard-label">Subject management</span>
                                    <span class="admin-qcard-desc">Catalog &amp; teacher assignment</span>
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel admin-ops-strip">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Operations snapshot</div>
                    <div class="dash-panel-sub">Live counters for day-to-day supervision</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <div class="admin-ops-grid">
                    <div class="admin-ops-item">
                        <span class="admin-ops-val">{{ number_format((int) ($a['attendance_today'] ?? 0)) }}</span>
                        <span class="admin-ops-lbl">Attendance marks today</span>
                    </div>
                    <div class="admin-ops-item">
                        <span class="admin-ops-val">{{ number_format((int) ($a['total_courses'] ?? 0)) }}</span>
                        <span class="admin-ops-lbl">LMS courses</span>
                    </div>
                    <div class="admin-ops-item">
                        <span class="admin-ops-val">{{ $activeSchoolYear?->name ?? '—' }}</span>
                        <span class="admin-ops-lbl">Active school year</span>
                    </div>
                    <div class="admin-ops-item">
                        <span class="admin-ops-val">{{ number_format((int) ($a['subject_assignment_count'] ?? 0)) }}</span>
                        <span class="admin-ops-lbl">Subject assignments (active year)</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-row-2">
            <div class="dash-panel">
                <div class="dash-panel-hd">
                    <div class="dash-panel-title">Recently added users</div>
                </div>
                <div class="dash-panel-body">
                    <table class="subj-avg-table">
                        <thead>
                            <tr><th>Name</th><th>Email</th><th>Role</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentUsersList as $u)
                                <tr>
                                    <td>{{ data_get($u, 'display_name', data_get($u, 'name', '-')) }}</td>
                                    <td>{{ data_get($u, 'email', '-') }}</td>
                                    <td>{{ ucfirst((string) data_get($u, 'roles.0.name', 'user')) }}</td>
                                    <td>{{ data_get($u, 'created_at')?->diffForHumans() ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">No user records found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-hd">
                    <div class="dash-panel-title">Recent SMS logs</div>
                </div>
                <div class="dash-panel-body">
                    <table class="subj-avg-table">
                        <thead>
                            <tr><th>Student</th><th>Status</th><th>Absences</th><th>Created</th></tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSmsLogsList as $log)
                                <tr>
                                    <td>{{ data_get($log, 'student.full_name', '-') }}</td>
                                    <td>{{ strtoupper((string) data_get($log, 'status', '-')) }}</td>
                                    <td>{{ (int) data_get($log, 'absences_count', 0) }}</td>
                                    <td>{{ data_get($log, 'created_at')?->diffForHumans() ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted">No SMS logs available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
    @php
        $totalStudents = (int) ($stats['total_students'] ?? 0);
        $belowPassing = max(0, $totalStudents - (int) ($kpis['passing_count'] ?? 0));
        $semester = (int) ($semester ?? (($quarter ?? 1) <= 2 ? 1 : 2));
        $quarterInSemester = (int) ($quarterInSemester ?? (($quarter ?? 1) <= 2 ? ($quarter ?? 1) : (($quarter ?? 1) - 2)));
        $periodQuery = ['semester' => $semester, 'quarter' => $quarterInSemester];
        $gradebookPeriodQuery = ['subject_category' => 'core', 'semester' => $semester, 'quarter' => $quarterInSemester];
        $publicBase = rtrim(parse_url(config('app.url'), PHP_URL_PATH) ?: '', '/');

        $distTotal = (int) array_sum($distribution ?? []);
        $donutTotal = $distTotal > 0 ? $distTotal : $totalStudents;

        $donutSegments = [
            ['label' => 'Outstanding (90-100)', 'key' => 'outstanding', 'color' => 'var(--gold)'],
            ['label' => 'Very Good (85-89)', 'key' => 'very_good', 'color' => 'var(--sage-light)'],
            ['label' => 'Satisfactory (75-84)', 'key' => 'satisfactory', 'color' => 'var(--navy-mid)'],
            ['label' => 'Did Not Meet (Below 75)', 'key' => 'below_75', 'color' => 'var(--red)'],
        ];

        $r = 50;
        $circ = 2 * pi() * $r;
        $offset = 0.0;
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Dashboard</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">📋 Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('gradebook.index', $gradebookPeriodQuery) }}">⚡ Compute All</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">🖨️ Print</button>
        </div>
    </div>

    <div class="dash-panel" style="margin-top: 12px;">
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('dashboard') }}" class="ge-filters">
                <div class="ge-filter-row">
                    <div class="ge-filter">
                        <select name="semester" aria-label="Semester">
                            <option value="1" @selected($semester === 1)>1st Semester</option>
                            <option value="2" @selected($semester === 2)>2nd Semester</option>
                        </select>
                    </div>
                    <div class="ge-filter">
                        <select name="quarter" aria-label="Quarter">
                            <option value="1" @selected($quarterInSemester === 1)>Quarter 1</option>
                            <option value="2" @selected($quarterInSemester === 2)>Quarter 2</option>
                        </select>
                    </div>
                    <div class="ge-filter ge-filter--btn">
                        <button class="btn btn-sm" type="submit">Apply Period</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-kpi-grid">
        <div class="dash-kpi kpi-gold">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">🧑‍🎓</div>
                <span class="kpi-trend trend-neutral">{{ $kpis['school_year'] ?? '—' }}</span>
            </div>
            <div class="dash-kpi-value">{{ number_format($totalStudents) }}</div>
            <div class="dash-kpi-label">TOTAL STUDENTS</div>
            <div class="dash-kpi-sub">{{ $kpis['scope'] ?? '—' }}</div>
        </div>

        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">📊</div>
                @php
                    $delta = (float) ($kpis['class_average_delta'] ?? 0);
                    $deltaLabel = ($delta >= 0 ? '+'.number_format($delta, 1) : number_format($delta, 1));
                    $deltaClass = $delta >= 0 ? 'trend-up' : 'trend-down';
                @endphp
                <span class="kpi-trend {{ $deltaClass }}">↑ {{ $deltaLabel }}</span>
            </div>
            <div class="dash-kpi-value">{{ number_format((float) ($kpis['class_average'] ?? 0), 1) }}</div>
            <div class="dash-kpi-label">CLASS AVERAGE</div>
            <div class="dash-kpi-sub">Across all subjects S{{ $semester }} Q{{ $quarterInSemester }}</div>
        </div>

        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">✅</div>
                <span class="kpi-trend trend-up">↑ {{ (int) ($kpis['pass_rate'] ?? 0) }}%</span>
            </div>
            <div class="dash-kpi-value">{{ number_format((int) ($kpis['passing_count'] ?? 0)) }}</div>
            <div class="dash-kpi-label">STUDENTS PASSING</div>
            <div class="dash-kpi-sub">{{ $belowPassing }} below 75 average</div>
        </div>

        <div class="dash-kpi kpi-red">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">⏳</div>
                <span class="kpi-trend trend-down">{{ (int) ($kpis['incomplete_grades'] ?? 0) > 0 ? 'Action needed' : 'All complete' }}</span>
            </div>
            <div class="dash-kpi-value">{{ number_format((int) ($kpis['incomplete_grades'] ?? 0)) }}</div>
            <div class="dash-kpi-label">INCOMPLETE GRADES</div>
            <div class="dash-kpi-sub">Missing S{{ $semester }} Q{{ $quarterInSemester }} entries</div>
        </div>
    </div>

    <div class="dash-row-2">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Subject Performance Overview</div>
                    <div class="dash-panel-sub">Class average per subject — Semester {{ $semester }} - Quarter {{ $quarterInSemester }}</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <table class="subj-avg-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Avg</th>
                            <th style="width:140px">Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjectAverages as $row)
                            @php
                                $avg = (float) ($row['avg'] ?? 0);
                                $status = (string) ($row['status'] ?? '');
                                $statusClass = $status === 'EXCELLENT' ? 'status-pass' : ($status === 'GOOD' || $status === 'SATISFACTORY' ? 'status-good' : 'status-fail');
                                $pillClass = $avg >= 75 ? 'gp-pass' : 'gp-fail';
                                $barColor = $avg >= 90 ? 'var(--gold)' : ($avg >= 85 ? 'var(--sage-light)' : ($avg >= 75 ? 'var(--navy-mid)' : 'var(--red)'));
                            @endphp
                            <tr>
                                <td style="font-size:11px;font-weight:600;color:var(--navy)">{{ $row['title'] }}</td>
                                <td><span class="grade-pill-sm {{ $pillClass }}">{{ number_format($avg, 1) }}</span></td>
                                <td>
                                    <div class="mini-bar-track">
                                        <div class="mini-bar-fill" style="width:{{ max(0, min(100, (int) round($avg))) }}%; background: {{ $barColor }}"></div>
                                    </div>
                                </td>
                                <td><span class="grade-status {{ $statusClass }}">{{ $status }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="muted">No grade data yet for Semester {{ $semester }} - Quarter {{ $quarterInSemester }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Grade Distribution</div>
                    <div class="dash-panel-sub">Students by grade range — S{{ $semester }} Q{{ $quarterInSemester }}</div>
                </div>
            </div>
            <div class="dash-panel-body" style="display:flex; flex-direction:column; gap: 16px;">
                <div class="donut-wrap">
                    <svg class="donut-svg" width="130" height="130" viewBox="0 0 130 130" aria-label="Grade distribution donut chart">
                        @foreach ($donutSegments as $seg)
                            @php
                                $val = (int) ($distribution[$seg['key']] ?? 0);
                                $pct = $donutTotal > 0 ? ($val / $donutTotal) : 0;
                                $dash = $circ * $pct;
                            @endphp
                            <circle cx="65" cy="65" r="{{ $r }}" fill="none" stroke="{{ $seg['color'] }}" stroke-width="20"
                                    stroke-dasharray="{{ number_format($dash, 2, '.', '') }} {{ number_format(max(0, $circ - $dash), 2, '.', '') }}"
                                    stroke-dashoffset="{{ number_format(-$offset, 2, '.', '') }}" transform="rotate(-90 65 65)"/>
                            @php $offset += $dash; @endphp
                        @endforeach
                        <text x="65" y="61" text-anchor="middle" font-family="DM Serif Display,serif" font-size="18" fill="#0a1628" font-weight="bold">{{ $donutTotal }}</text>
                        <text x="65" y="79" text-anchor="middle" font-family="DM Sans,sans-serif" font-size="10" fill="#5a6b8a">students</text>
                    </svg>

                    <div class="donut-legend">
                        @foreach ($donutSegments as $seg)
                            @php $val = (int) ($distribution[$seg['key']] ?? 0); @endphp
                            <div class="donut-leg-item">
                                <span class="donut-dot" style="background: {{ $seg['color'] }}"></span>
                                <div style="min-width:0;">
                                    <div class="donut-leg-val">{{ $val }}</div>
                                    <div class="donut-leg-label">{{ $seg['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <div class="dash-panel-title" style="font-size:11px;">Semester Quarter Completion</div>
                    <div class="completion-grid">
                        @foreach ($quarterCompletion as $qc)
                            @php
                                $pct = (int) ($qc['pct'] ?? 0);
                                $label = !empty($qc['is_current'])
                                    ? "Q{$qc['quarter']} PROGRESS"
                                    : (((int) ($qc['quarter'] ?? 0)) < $quarterInSemester ? "Q{$qc['quarter']} DONE" : "Q{$qc['quarter']} UPCOMING");
                                $color = $pct >= 95 ? 'var(--sage-light)' : ($pct >= 60 ? 'var(--gold)' : 'var(--cream-dark)');
                            @endphp
                            <div class="comp-item">
                                <div class="comp-pct">{{ $pct }}%</div>
                                <div class="comp-label">{{ $label }}</div>
                                <div class="comp-bar">
                                    <div class="comp-bar-fill" style="width:{{ $pct }}%; background: {{ $color }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dash-row-3">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">🏅 Top Performers</div>
                    <div class="dash-panel-sub">Ranked by general average — S{{ $semester }} Q{{ $quarterInSemester }}</div>
                </div>
            </div>
            <div class="dash-panel-body" style="padding-top: 10px;">
                <table class="perf-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Gen. Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topPerformers as $idx => $row)
                            @php
                                $rank = (int) $idx + 1;
                                $rankClass = $rank === 1 ? 'rank-1' : ($rank === 2 ? 'rank-2' : ($rank === 3 ? 'rank-3' : 'rank-n'));
                                $avg = (float) ($row['avg'] ?? 0);
                            @endphp
                            <tr>
                                <td><span class="rank-badge {{ $rankClass }}">{{ $rank }}</span></td>
                                <td>{{ $row['student'] ?? '—' }}</td>
                                <td><span class="grade-pill-sm {{ $avg >= 75 ? 'gp-pass' : 'gp-fail' }}">{{ number_format($avg, 1) }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No complete averages yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">🕘 Recent Activity</div>
                    <div class="dash-panel-sub">Latest grade entries &amp; changes</div>
                </div>
            </div>
            <div class="dash-panel-body" style="padding-top: 10px;">
                <div class="activity-list">
                    @forelse ($activity as $item)
                        @php
                            $type = (string) ($item['type'] ?? 'grade');
                            $dotClass = $type === 'alert' ? 'ad-red' : ($type === 'report' ? 'ad-navy' : 'ad-sage');
                            $at = $item['at'] ?? null;
                        @endphp
                        <div class="activity-item">
                            <span class="activity-dot {{ $dotClass }}"></span>
                            <div>
                                <div class="activity-text"><strong>{{ $item['title'] ?? '—' }}</strong> — {{ $item['text'] ?? '—' }}</div>
                                <div class="activity-time">{{ $at ? $at->diffForHumans() : '—' }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="muted">No recent activity.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">⚡ Quick Actions</div>
                    <div class="dash-panel-sub">Common tasks</div>
                </div>
            </div>
            <div class="dash-panel-body" style="display:grid; gap: 14px;">
                <div class="quick-actions">
                    <a class="qa-btn" href="{{ route('gradebook.index', $gradebookPeriodQuery) }}">
                        <span class="qa-icon">✏️</span>
                        <span>
                            <div class="qa-label">Enter Grades</div>
                            <div class="qa-sub">Open grade entry</div>
                        </span>
                    </a>
                    <a class="qa-btn" href="{{ route('gradebook.index', $gradebookPeriodQuery) }}">
                        <span class="qa-icon">⚡</span>
                        <span>
                            <div class="qa-label">Compute All</div>
                            <div class="qa-sub">Auto compute averages</div>
                        </span>
                    </a>
                    <a class="qa-btn" href="{{ route('master-sheet.index', $periodQuery) }}">
                        <span class="qa-icon">📄</span>
                        <span>
                            <div class="qa-label">Master Sheet</div>
                            <div class="qa-sub">Semester quarter view</div>
                        </span>
                    </a>
                    <a class="qa-btn" href="{{ route('subject-teacher.index', $periodQuery) }}">
                        <span class="qa-icon">🎓</span>
                        <span>
                            <div class="qa-label">Subject Teacher</div>
                            <div class="qa-sub">Track completion</div>
                        </span>
                    </a>
                    <a class="qa-btn" href="{{ $publicBase }}/download-app">
                        <span class="qa-icon">📲</span>
                        <span>
                            <div class="qa-label">Download Teacher Mobile App</div>
                            <div class="qa-sub">Install Android attendance app</div>
                        </span>
                    </a>
                    <a class="qa-btn" href="{{ route('report-cards.index') }}">
                        <span class="qa-icon">📋</span>
                        <span>
                            <div class="qa-label">Report Card</div>
                            <div class="qa-sub">View Form 138</div>
                        </span>
                    </a>
                    <a class="qa-btn" href="{{ route('report-cards.index') }}">
                        <span class="qa-icon">🖨️</span>
                        <span>
                            <div class="qa-label">Print Cards</div>
                            <div class="qa-sub">All students</div>
                        </span>
                    </a>
                    {{--
                    <a class="qa-btn" href="#">
                        <span class="qa-icon">🗄️</span>
                        <span>
                            <div class="qa-label">Database</div>
                            <div class="qa-sub">View DB tables</div>
                        </span>
                    </a>
                    --}}
                    <a class="qa-btn is-disabled" href="#" aria-disabled="true" onclick="return false;">
                        <span class="qa-icon">📦</span>
                        <span>
                            <div class="qa-label">Export CSV</div>
                            <div class="qa-sub">Download grades</div>
                        </span>
                    </a>
                </div>

                <div>
                    <div class="dash-panel-title" style="font-size:11px;">Submission Status</div>
                    <div class="status-bars">
                        <div class="status-row">
                            <div class="status-label">Quizzes (S{{ $semester }} Q{{ $quarterInSemester }})</div>
                            <div class="status-track"><div class="status-fill status-fill--sage" style="width:{{ (int) ($submissionStatus['quiz'] ?? 0) }}%"></div></div>
                            <div class="status-val">{{ (int) ($submissionStatus['quiz'] ?? 0) }}%</div>
                        </div>
                        <div class="status-row">
                            <div class="status-label">Performance Task (S{{ $semester }} Q{{ $quarterInSemester }})</div>
                            <div class="status-track"><div class="status-fill status-fill--sage" style="width:{{ (int) ($submissionStatus['performance_task'] ?? 0) }}%"></div></div>
                            <div class="status-val">{{ (int) ($submissionStatus['performance_task'] ?? 0) }}%</div>
                        </div>
                        <div class="status-row">
                            <div class="status-label">Exams (S{{ $semester }} Q{{ $quarterInSemester }})</div>
                            <div class="status-track"><div class="status-fill status-fill--gold" style="width:{{ (int) ($submissionStatus['exam'] ?? 0) }}%"></div></div>
                            <div class="status-val">{{ (int) ($submissionStatus['exam'] ?? 0) }}%</div>
                        </div>
                        <div class="status-row">
                            <div class="status-label">Report Cards</div>
                            <div class="status-track"><div class="status-fill status-fill--navy" style="width:{{ (int) ($submissionStatus['report_cards'] ?? 0) }}%"></div></div>
                            <div class="status-val">{{ (int) ($submissionStatus['report_cards'] ?? 0) }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endsection

