@extends('layouts.app')

@section('title', 'Subject teacher dashboard')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Subject teacher</span>
        </div>
        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('notifications.index') }}">
                Notifications
                @if (($inAppUnreadCount ?? $inAppUnreadNotifications ?? 0) > 0)
                    <span class="nav-badge" style="margin-left:6px;">
                        {{ (int) ($inAppUnreadCount ?? $inAppUnreadNotifications ?? 0) > 99 ? '99+' : (int) ($inAppUnreadCount ?? $inAppUnreadNotifications ?? 0) }}
                    </span>
                @endif
            </a>
            <a class="btn btn-outline btn-sm" href="{{ route('subject-teacher.index', $periodQuery ?? ['term' => ($term ?? $quarterInSemester)]) }}">Subject load</a>
            <a class="btn btn-primary btn-sm" href="{{ route('gradebook.index', ['subject_category' => 'core', 'term' => ($term ?? $quarterInSemester)]) }}">Open gradebook</a>
        </div>
    </div>

    @if (!empty($missingTeacher))
        <div class="error" style="margin-top:12px;">Your account is not linked to a teacher profile. Contact an administrator.</div>
    @endif

    <div class="dash-panel admin-dash-period" style="margin-top:12px;">
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('dashboard') }}" class="ge-filters">
                <div class="ge-filter-row" style="align-items:center;">
                    <div class="muted" style="font-size:12px;margin-right:12px;">
                        <strong>Trimester term</strong> — applies to completion counts below.
                    </div>
                    <div class="ge-filter">
                        <select name="term" aria-label="Term">
                            <option value="1" @selected(($term ?? $quarterInSemester) === 1)>Term 1</option>
                            <option value="2" @selected(($term ?? $quarterInSemester) === 2)>Term 2</option>
                            <option value="3" @selected(($term ?? $quarterInSemester) === 3)>Term 3</option>
                        </select>
                    </div>
                    <div class="ge-filter ge-filter--btn">
                        <button class="btn btn-sm" type="submit">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-kpi-grid" style="margin-top:12px;">
        <div class="dash-kpi kpi-gold">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">🧭</div>
                <span class="kpi-trend trend-neutral">Mapping</span>
            </div>
            <div class="dash-kpi-value">{{ number_format((int) ($teacherSubjectCount ?? 0)) }}</div>
            <div class="dash-kpi-label">Teacher-subject links</div>
            <div class="dash-kpi-sub">Active rows from subject assignment map</div>
        </div>
        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">📚</div>
                <span class="kpi-trend trend-neutral">{{ $activeSchoolYear?->name ?? '—' }}</span>
            </div>
            <div class="dash-kpi-value">{{ $assignmentRows->count() }}</div>
            <div class="dash-kpi-label">Assigned subjects</div>
            <div class="dash-kpi-sub">Section + subject loads for this school year</div>
        </div>
        <div class="dash-kpi kpi-red">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">⏳</div>
                <span class="kpi-trend {{ $totalPending > 0 ? 'trend-down' : 'trend-up' }}">{{ $totalPending > 0 ? 'Action' : 'Clear' }}</span>
            </div>
            <div class="dash-kpi-value">{{ number_format($totalPending) }}</div>
            <div class="dash-kpi-label">Pending grade cells</div>
            <div class="dash-kpi-sub">Term {{ $term ?? $quarterInSemester }} · quiz / PT / exam</div>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">Recent grading updates</div>
                <div class="dash-panel-sub">Latest entries from your assigned subjects for Term {{ $term ?? $quarterInSemester }}.</div>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="activity-list">
                @forelse (($recentUpdates ?? collect()) as $entry)
                    <div class="activity-item">
                        <span class="activity-dot ad-sage"></span>
                        <div>
                            <div class="activity-text">
                                <strong>{{ $entry->enrollment?->student?->full_name ?? 'Student' }}</strong>
                                — {{ $entry->subjectAssignment?->subject?->title ?? 'Subject' }}
                                @if ($entry->quarter_grade !== null)
                                    ({{ number_format((float) $entry->quarter_grade, 1) }})
                                @endif
                            </div>
                            <div class="activity-time">{{ $entry->updated_at?->diffForHumans() ?? '—' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="muted">No grading updates yet for this period.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">My assignments</div>
                <div class="dash-panel-sub">Open the gradebook for each class; only your loads are listed.</div>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="st-assign-grid">
                @forelse ($assignmentRows as $row)
                    @php
                        $sa = $row['assignment'];
                        $sec = $sa->section;
                        $sub = $sa->subject;
                    @endphp
                    <div class="st-assign-card">
                        <div class="st-assign-main">
                            <div class="st-assign-subject">{{ $sub?->title ?? 'Subject' }}</div>
                            <div class="st-assign-meta">
                                {{ $sub?->code ?? '—' }}
                                · Grade {{ $sec?->grade_level ?? '—' }} — {{ $sec?->name ?? 'Section' }}
                            </div>
                            <div class="st-assign-pending">
                                @if ($row['total'] > 0)
                                    <span class="grade-pill-sm {{ $row['pending'] > 0 ? 'gp-fail' : 'gp-pass' }}">
                                        {{ $row['pending'] > 0 ? $row['pending'].' pending' : 'Complete' }}
                                    </span>
                                    <span class="muted" style="font-size:11px;margin-left:8px;">{{ $row['total'] }} students</span>
                                @else
                                    <span class="muted">No enrollments in this section</span>
                                @endif
                            </div>
                        </div>
                        <a class="btn btn-primary btn-sm" href="{{ $row['gradebook_url'] }}">Encode grades</a>
                    </div>
                @empty
                    <p class="muted">No subject assignments for {{ $activeSchoolYear?->name ?? 'this school year' }}.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection
