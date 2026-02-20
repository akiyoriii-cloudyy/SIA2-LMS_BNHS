@extends('layouts.app')

@section('content')
    <div class="page-head page-head--dash">
        <div>
            <h1>Dashboard</h1>
            <div class="crumbs muted">Home / Dashboard</div>
        </div>
        <div class="pill-row">
            <div class="pill">{{ $activeSchoolYear?->name ?? 'No SY' }}</div>
            <div class="pill">Q{{ $quarter }} Active</div>
            <div class="pill pill--dot">Online</div>
        </div>
    </div>

    <div class="grid-4">
        <div class="card stat stat--flat">
            <div class="stat-icon">◎</div>
            <div class="k">Total Students</div>
            <div class="v">{{ number_format($stats['total_students'] ?? 0) }}</div>
        </div>
        <div class="card stat stat--flat">
            <div class="stat-icon">≡</div>
            <div class="k">Subjects</div>
            <div class="v">{{ number_format($stats['total_subjects'] ?? 0) }}</div>
        </div>
        <div class="card stat stat--flat">
            <div class="stat-icon">!</div>
            <div class="k">Absence Alerts</div>
            <div class="v">{{ number_format($stats['absence_alerts'] ?? 0) }}</div>
        </div>
        <div class="card stat stat--flat">
            <div class="stat-icon">✓</div>
            <div class="k">Attendance Rate</div>
            <div class="v">{{ number_format($stats['attendance_rate'] ?? 0) }}%</div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="card">
            <div class="card-title">Recent Grade Entries</div>
            <div class="table-wrap" style="margin-top: 10px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Quiz</th>
                            <th>Assign.</th>
                            <th>Exam</th>
                            <th>Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentGrades as $row)
                            <tr>
                                <td>{{ $row->enrollment?->student?->last_name }}, {{ mb_substr((string) ($row->enrollment?->student?->first_name ?? ''), 0, 1) }}.</td>
                                <td>{{ $row->subjectAssignment?->subject?->title ?? '-' }}</td>
                                <td>{{ $row->quiz ?? '-' }}</td>
                                <td>{{ $row->assignment ?? '-' }}</td>
                                <td>{{ $row->exam ?? '-' }}</td>
                                <td><span class="chip">{{ $row->quarter_grade ?? '-' }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted">No grade entries yet for Q{{ $quarter }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (! empty($quickLinks))
                <div style="display:flex; flex-wrap: wrap; gap: 10px; margin-top: 12px;">
                    @foreach ($quickLinks as $link)
                        <a class="btn btn--ghost" href="{{ route($link['route']) }}">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-title">Absence Alerts</div>
            <div class="muted" style="margin-top: 4px;">Weekly trigger is 5 absences.</div>

            <div class="alert-list" style="margin-top: 10px;">
                @forelse ($alertLogs as $log)
                    <div class="alert-item">
                        <div class="alert-icon">⚠</div>
                        <div class="alert-body">
                            <div><strong>{{ $log->student?->last_name }}, {{ mb_substr((string) ($log->student?->first_name ?? ''), 0, 1) }}.</strong> — {{ (int) $log->absences_count }}/5 absences.</div>
                            <div class="muted">{{ $log->status === 'sent' ? 'SMS sent to parent.' : 'SMS status: '.$log->status }}</div>
                        </div>
                    </div>
                @empty
                    <div class="muted">No SMS alerts yet.</div>
                @endforelse

                @foreach ($nearThreshold as $row)
                    <div class="alert-item alert-item--warn">
                        <div class="alert-icon">!</div>
                        <div class="alert-body">
                            <div><strong>{{ $row->enrollment?->student?->last_name }}, {{ mb_substr((string) ($row->enrollment?->student?->first_name ?? ''), 0, 1) }}.</strong> — {{ (int) $row->absences_count }}/5.</div>
                            <div class="muted">1 more triggers SMS.</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
