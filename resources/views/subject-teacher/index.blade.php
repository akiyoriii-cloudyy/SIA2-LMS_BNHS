@extends('layouts.app')

@section('title', 'Subject Teacher Dashboard')

@section('content')
    @php
        $s = $stats ?? [];
        $completionPct = (int) ($s['completion_pct'] ?? 0);
        $missingCount = (int) ($s['missing'] ?? 0);
        $classAvg = $s['class_avg'] ?? null;
        $studentsCount = (int) ($s['students'] ?? 0);
        $lastUpdated = $s['last_updated'] ?? null;
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Subject Teacher</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('master-sheet.index') }}">Master Sheet</a>
            <a class="btn btn-gold btn-sm" href="{{ $openGradebookUrl }}">Open Grade Entry</a>
            <a class="btn btn-primary btn-sm" href="{{ route('report-cards.index') }}">Report Cards</a>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('subject-teacher.index') }}" class="teacher-filter-form">
                <div class="ge-filter-row">
                    <div class="ge-filter ge-filter--sy">
                        <select name="school_year_id" aria-label="School year">
                            @foreach ($schoolYears as $schoolYear)
                                <option value="{{ $schoolYear->id }}" @selected($selectedSchoolYear === $schoolYear->id)>
                                    {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ge-filter">
                        <select name="assignment_id" aria-label="Subject load">
                            @forelse ($assignments as $assignment)
                                <option value="{{ $assignment->id }}" @selected($selectedAssignmentId === $assignment->id)>
                                    {{ $assignment->subject?->title ?? 'Subject' }} -
                                    Grade {{ $assignment->section?->grade_level ?? '-' }}
                                    {{ $assignment->section?->name ?? '-' }}
                                </option>
                            @empty
                                <option value="0">No subject assignment found</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="ge-filter">
                        <select name="quarter" aria-label="Quarter">
                            @for ($q = 1; $q <= 4; $q++)
                                <option value="{{ $q }}" @selected($quarter === $q)>Quarter {{ $q }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="ge-filter ge-filter--btn">
                        <button class="btn btn-sm" type="submit">Apply</button>
                    </div>

                    <label class="teacher-autosave-toggle" for="autosave-toggle">
                        <input type="checkbox" id="autosave-toggle">
                        Auto-save (local)
                    </label>
                </div>
            </form>
        </div>
    </div>

    @if (! $selectedAssignment)
        <div class="card">
            <strong>No subject assignment found for this filter.</strong>
            <p class="muted">Assign teacher + section + subject first, then this dashboard will show completion and missing grades.</p>
        </div>
    @else
        <div class="dash-kpi-grid">
            <div class="dash-kpi kpi-sage">
                <div class="dash-kpi-top">
                    <span class="kpi-trend trend-neutral">{{ $selectedAssignment->subject?->code ?? '-' }}</span>
                </div>
                <div class="dash-kpi-value">{{ $completionPct }}%</div>
                <div class="dash-kpi-label">COMPLETION</div>
                <div class="dash-kpi-sub">{{ number_format($studentsCount) }} students in scope</div>
            </div>

            <div class="dash-kpi kpi-red">
                <div class="dash-kpi-value">{{ number_format($missingCount) }}</div>
                <div class="dash-kpi-label">MISSING GRADES</div>
                <div class="dash-kpi-sub">Needs teacher action</div>
            </div>

            <div class="dash-kpi kpi-gold">
                <div class="dash-kpi-value">{{ $classAvg === null ? '-' : number_format((float) $classAvg, 1) }}</div>
                <div class="dash-kpi-label">CLASS AVERAGE</div>
                <div class="dash-kpi-sub">Saved quarter grades only</div>
            </div>

            <div class="dash-kpi kpi-navy">
                <div class="dash-kpi-value">{{ $lastUpdated ? $lastUpdated->format('Y-m-d') : '-' }}</div>
                <div class="dash-kpi-label">LAST UPDATED</div>
                <div class="dash-kpi-sub">{{ $lastUpdated ? $lastUpdated->diffForHumans() : 'No updates yet' }}</div>
            </div>
        </div>

        <div class="dash-row-2">
            <div class="dash-panel">
                <div class="dash-panel-hd">
                    <div>
                        <div class="dash-panel-title">Missing Grade Alerts</div>
                        <div class="dash-panel-sub">Quarter {{ $quarter }} status for {{ $selectedAssignment->subject?->title }}</div>
                    </div>
                    <a class="btn btn-outline btn-sm" href="{{ $openGradebookUrl }}" style="margin-left:auto;">Open Grade Entry</a>
                </div>
                <div class="dash-panel-body">
                    <div class="activity-list">
                        @forelse (array_slice($missingStudents, 0, 12) as $item)
                            <div class="activity-item">
                                <span class="activity-dot ad-red"></span>
                                <div>
                                    <div class="activity-text">
                                        <strong>Missing:</strong> {{ $item['name'] }}
                                        <span class="muted">({{ $item['section'] }} - {{ $item['strand'] }})</span>
                                    </div>
                                    <div class="activity-time">Quarter {{ $quarter }} not fully saved</div>
                                </div>
                            </div>
                        @empty
                            <div class="activity-item">
                                <span class="activity-dot ad-sage"></span>
                                <div>
                                    <div class="activity-text"><strong>All set.</strong> No missing grades for this subject and quarter.</div>
                                    <div class="activity-time">Everything is complete</div>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if (count($missingStudents) > 12)
                        <div class="muted" style="margin-top:10px;">Showing 12 of {{ number_format(count($missingStudents)) }} missing students.</div>
                    @endif
                </div>
            </div>

            <div class="dash-panel">
                <div class="dash-panel-hd">
                    <div>
                        <div class="dash-panel-title">Grading Weights</div>
                        <div class="dash-panel-sub">Automatic computation used in Grade Entry</div>
                    </div>
                </div>
                <div class="dash-panel-body">
                    <div class="teacher-weight-chips">
                        <span class="grade-pill-sm gp-pass">Quizzes: 30%</span>
                        <span class="grade-pill-sm gp-pass">Performance Task: 30%</span>
                        <span class="grade-pill-sm gp-pass">Exam: 40%</span>
                    </div>
                    <div class="teacher-formula">
                        Formula:
                        <strong>Average = (Quiz x 0.30) + (Performance Task x 0.30) + (Exam x 0.40)</strong>
                    </div>
                    <div class="muted">Subject: {{ $selectedAssignment->subject?->title }} ({{ $selectedAssignment->subject?->code }})</div>
                    <div class="muted">
                        Class:
                        Grade {{ $selectedAssignment->section?->grade_level }} - {{ $selectedAssignment->section?->name }}
                        @if ($selectedAssignment->section?->strand)
                            ({{ $selectedAssignment->section?->strand }})
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Recent Grade Updates</div>
                    <div class="dash-panel-sub">Latest saved rows for this subject and quarter</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <table class="subj-avg-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Quarter Grade</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentChanges as $item)
                            <tr>
                                <td>{{ $item['student'] }}</td>
                                <td>
                                    @if ($item['quarter_grade'] !== null)
                                        <span class="grade-pill-sm gp-pass">{{ number_format((float) $item['quarter_grade'], 1) }}</span>
                                    @else
                                        <span class="grade-pill-sm gp-fail">-</span>
                                    @endif
                                </td>
                                <td>{{ $item['updated_at'] ? $item['updated_at']->diffForHumans() : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No recent updates yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const key = 'lms.subjectTeacher.autosave';
            const toggle = document.getElementById('autosave-toggle');
            if (!toggle) return;

            try {
                toggle.checked = localStorage.getItem(key) !== '0';
                toggle.addEventListener('change', () => {
                    localStorage.setItem(key, toggle.checked ? '1' : '0');
                });
            } catch (e) {
                // Ignore storage errors in restrictive browsers.
            }
        })();
    </script>
@endsection
