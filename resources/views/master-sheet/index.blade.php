@extends('layouts.app')

@section('title', 'Master Sheet')

@section('content')
    @php
        $s = $stats ?? [];
        $studentsShown = (int) ($s['students'] ?? 0);
        $subjectsCount = (int) ($s['subjects'] ?? 0);
        $maleCount = (int) ($s['male'] ?? 0);
        $femaleCount = (int) ($s['female'] ?? 0);
        $missingCells = (int) ($s['missing'] ?? 0);

        $exportQuery = request()->query();
        $exportQuery['export'] = 'csv';
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Master Sheet</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('master-sheet.index', $exportQuery) }}">Export CSV</a>
            <a class="btn btn-primary btn-sm" href="{{ route('gradebook.index') }}">Open Grade Entry</a>
        </div>
    </div>

    <div class="dash-kpi-grid dash-kpi-grid--5">
        <div class="dash-kpi kpi-gold">
            <div class="dash-kpi-value">{{ number_format($studentsShown) }}</div>
            <div class="dash-kpi-label">STUDENTS SHOWN</div>
            <div class="dash-kpi-sub">Filtered roster</div>
        </div>
        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-value">{{ number_format($subjectsCount) }}</div>
            <div class="dash-kpi-label">SUBJECT COLUMNS</div>
            <div class="dash-kpi-sub">Per selected scope</div>
        </div>
        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-value">Q{{ $quarter }}</div>
            <div class="dash-kpi-label">QUARTER</div>
            <div class="dash-kpi-sub">Current view</div>
        </div>
        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-value">{{ number_format($maleCount) }} / {{ number_format($femaleCount) }}</div>
            <div class="dash-kpi-label">MALE / FEMALE</div>
            <div class="dash-kpi-sub">Student count</div>
        </div>
        <div class="dash-kpi kpi-red">
            <div class="dash-kpi-value">{{ number_format($missingCells) }}</div>
            <div class="dash-kpi-label">MISSING CELLS</div>
            <div class="dash-kpi-sub">Incomplete entries</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">Master Grade Sheet</div>
                <div class="dash-panel-sub">Quarter {{ $quarter }} summary by student and subject</div>
            </div>
        </div>

        <div class="dash-panel-body">
            <form method="GET" action="{{ route('master-sheet.index') }}" class="ge-filters master-filters">
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
                        <select name="grade_level" aria-label="Grade level" onchange="this.form.submit()">
                            @foreach ($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel }}" @selected($selectedGradeLevel === (int) $gradeLevel)>
                                    Grade {{ $gradeLevel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ge-filter">
                        <select name="section_id" aria-label="Section">
                            <option value="0" @selected($selectedSection === 0)>All sections</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected($selectedSection === $section->id)>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ge-filter">
                        <select name="strand" aria-label="Strand">
                            <option value="ALL" @selected($selectedStrand === 'ALL')>All Strands</option>
                            @foreach ($strandOptions as $strand)
                                <option value="{{ $strand }}" @selected($selectedStrand === $strand)>{{ $strand }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ge-filter">
                        <select name="quarter" aria-label="Quarter">
                            @for ($q = 1; $q <= 4; $q++)
                                <option value="{{ $q }}" @selected($quarter === $q)>Quarter {{ $q }}</option>
                            @endfor
                        </select>
                    </div>

                    <div class="ge-filter ge-filter--search">
                        <input type="text" name="q" placeholder="Search student..." value="{{ $search }}">
                    </div>

                    <div class="ge-filter ge-filter--btn">
                        <button class="btn btn-sm" type="submit">Apply</button>
                    </div>

                    <div class="ge-filter ge-filter--btn">
                        <a class="btn btn--ghost btn-sm" href="{{ route('master-sheet.index') }}">Clear</a>
                    </div>
                </div>
            </form>

            <div class="table-wrap master-sheet-wrap">
                <table class="table master-sheet-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Student</th>
                            <th>Strand</th>
                            <th>Section</th>
                            @forelse ($subjects as $subject)
                                <th>
                                    {{ $subject->title }}
                                    <div class="master-head-sub">{{ $subject->code }}</div>
                                </th>
                            @empty
                                <th>No subjects</th>
                            @endforelse
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $index => $enrollment)
                            <tr>
                                <td>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                                <td>
                                    <div style="font-weight:700;">{{ $enrollment->student?->full_name ?? 'N/A' }}</div>
                                    <div class="master-date">{{ $enrollment->student?->lrn ?? 'No LRN' }}</div>
                                </td>
                                <td>{{ $enrollment->section?->strand ?? '-' }}</td>
                                <td>
                                    @if ($enrollment->section)
                                        Grade {{ $enrollment->section->grade_level }} - {{ $enrollment->section->name }}
                                    @else
                                        -
                                    @endif
                                </td>

                                @if ($subjects->isNotEmpty())
                                    @foreach ($subjects as $subject)
                                        @php
                                            $cell = data_get($gradesByEnrollment, "{$enrollment->id}.{$subject->id}", []);
                                            $isComplete = (bool) ($cell['complete'] ?? false);
                                            $grade = $cell['quarter_grade'] ?? null;
                                            $date = $cell['date'] ?? null;
                                        @endphp
                                        <td class="master-grade-cell">
                                            <span class="grade-pill-sm {{ $isComplete ? 'gp-pass' : 'gp-fail' }}">
                                                {{ $isComplete ? number_format((float) $grade, 0) : '-' }}
                                            </span>
                                            <div class="master-date">{{ $date ?? '-' }}</div>
                                        </td>
                                    @endforeach
                                @else
                                    <td class="muted">No assigned subjects in this scope.</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 5 + max(1, (int) $subjects->count()) }}" class="muted">
                                    No students found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="master-counts">
                <span class="grade-pill-sm gp-pass">Male: {{ number_format($maleCount) }}</span>
                <span class="grade-pill-sm gp-pass">Female: {{ number_format($femaleCount) }}</span>
                <span class="grade-pill-sm gp-pass">Total: {{ number_format($studentsShown) }}</span>
            </div>
        </div>
    </div>
@endsection
