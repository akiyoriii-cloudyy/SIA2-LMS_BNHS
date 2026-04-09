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
            <a class="btn btn-outline btn-sm" href="{{ route('notifications.index') }}">Notifications</a>
            <a class="btn btn-primary btn-sm" href="{{ route('gradebook.index', ['subject_category' => 'core', 'semester' => $semester, 'quarter' => $quarterInSemester]) }}">Open gradebook</a>
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
                        <strong>Grading period</strong> — applies to completion counts below.
                    </div>
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
                        <button class="btn btn-sm" type="submit">Apply</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-kpi-grid" style="margin-top:12px;">
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
            <div class="dash-kpi-sub">S{{ $semester }} Q{{ $quarterInSemester }} · quiz / PT / exam</div>
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
