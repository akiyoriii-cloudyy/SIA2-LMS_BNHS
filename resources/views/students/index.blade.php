@extends('layouts.app')

@section('content')
    @php
        $s = $stats ?? [];
        $totalStudents = (int) ($s['total_students'] ?? 0);
        $totalEnrollments = (int) ($s['total_enrollments'] ?? 0);
        $maleCount = (int) ($s['male'] ?? 0);
        $femaleCount = (int) ($s['female'] ?? 0);
        $guardiansTotal = (int) ($s['guardians'] ?? 0);

        $selectedSY = $schoolYears->firstWhere('id', $selectedSchoolYear);
        $selectedSec = $sections->firstWhere('id', $selectedSection);
        $scopeLabel = $selectedSec ? ('Grade '.$selectedSec->grade_level.' — '.$selectedSec->name) : '—';
        $syLabel = $selectedSY?->name ?? '—';
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Students</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('gradebook.index') }}">Compute All</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="dash-kpi-grid dash-kpi-grid--5">
        <div class="dash-kpi kpi-gold">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">👩‍🎓</div></div>
            <div class="dash-kpi-value">{{ number_format($totalStudents) }}</div>
            <div class="dash-kpi-label">TOTAL STUDENTS</div>
            <div class="dash-kpi-sub">{{ $scopeLabel }}</div>
        </div>

        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">🧾</div></div>
            <div class="dash-kpi-value">{{ number_format($totalEnrollments) }}</div>
            <div class="dash-kpi-label">ENROLLMENTS</div>
            <div class="dash-kpi-sub">SY. {{ $syLabel }}</div>
        </div>

        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">👦</div></div>
            <div class="dash-kpi-value">{{ number_format($maleCount) }}</div>
            <div class="dash-kpi-label">MALE</div>
            <div class="dash-kpi-sub">Distinct learners</div>
        </div>

        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">👧</div></div>
            <div class="dash-kpi-value">{{ number_format($femaleCount) }}</div>
            <div class="dash-kpi-label">FEMALE</div>
            <div class="dash-kpi-sub">Distinct learners</div>
        </div>

        <div class="dash-kpi kpi-red">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">👪</div></div>
            <div class="dash-kpi-value">{{ number_format($guardiansTotal) }}</div>
            <div class="dash-kpi-label">GUARDIANS</div>
            <div class="dash-kpi-sub">Linked contacts</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">Enrollment List</div>
                <div class="dash-panel-sub">Records aligned to school year + section</div>
            </div>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('students.index') }}" class="records-filters">
                <div class="records-filter-row">
                    <div class="records-filter">
                        <label class="records-label">School Year</label>
                        <select name="school_year_id">
                            @foreach ($schoolYears as $sy)
                                <option value="{{ $sy->id }}" @selected($selectedSchoolYear === $sy->id)>{{ $sy->name }}{{ $sy->is_active ? ' (Active)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="records-filter">
                        <label class="records-label">Section</label>
                        <select name="section_id">
                            @foreach ($sections as $sec)
                                <option value="{{ $sec->id }}" @selected($selectedSection === $sec->id)>
                                    Grade {{ $sec->grade_level }} — {{ $sec->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="records-filter records-filter--search">
                        <label class="records-label">Search</label>
                        <input type="text" name="q" placeholder="LRN / name..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="records-filter records-filter--btn">
                        <button class="btn btn-sm" type="submit">Apply</button>
                    </div>
                </div>
            </form>

            <div class="table-wrap" style="margin-top: 12px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Student</th>
                            <th>Sex</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Guardians</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td style="font-family: 'JetBrains Mono', monospace; font-weight: 800;">
                                    {{ $enrollment->student?->lrn ?? '—' }}
                                </td>
                                <td style="font-weight: 800;">{{ $enrollment->student?->full_name ?? '—' }}</td>
                                <td>{{ $enrollment->student?->sex ?? '—' }}</td>
                                <td>Grade {{ $enrollment->section?->grade_level }} — {{ $enrollment->section?->name }}</td>
                                <td><span class="chip">{{ $enrollment->status }}</span></td>
                                <td>{{ number_format((int) ($enrollment->student?->guardians_count ?? 0)) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="muted">No enrollments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

