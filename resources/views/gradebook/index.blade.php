@extends('layouts.app')

@section('content')
    <div class="page-head page-head--dash">
        <div>
            <h1>Grade Entry</h1>
            <div class="crumbs muted">Grading / Enter Grades</div>
        </div>
        <div class="pill-row">
            <div class="pill">Quarter {{ $quarter }}</div>
            <div class="pill">
                {{ $subjects->firstWhere('id', $selectedSubject)?->title ?? 'Subject' }}
            </div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('gradebook.index') }}" class="filters">
            <div class="grid-4">
                <div>
                    <label class="muted" style="font-size: 12px; font-weight: 800;">School Year</label>
                    <select name="school_year_id">
                        @foreach ($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}" @selected($selectedSchoolYear === $schoolYear->id)>
                                {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="muted" style="font-size: 12px; font-weight: 800;">Section</label>
                    <select name="section_id">
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected($selectedSection === $section->id)>
                                Grade {{ $section->grade_level }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="muted" style="font-size: 12px; font-weight: 800;">Subject</label>
                    <select name="subject_id">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubject === $subject->id)>
                                {{ $subject->code }} - {{ $subject->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="muted" style="font-size: 12px; font-weight: 800;">Quarter</label>
                    <select name="quarter">
                        @for ($q = 1; $q <= 4; $q++)
                            <option value="{{ $q }}" @selected($quarter === $q)>Quarter {{ $q }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div style="display:flex; gap: 10px; margin-top: 12px; flex-wrap: wrap;">
                <button class="btn" type="submit">Load</button>
                <span class="muted" style="align-self:center; font-style: italic;">
                    Grades auto-transfer to Report Card. No re-entry needed.
                </span>
            </div>
        </form>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    @if ($subjectAssignment === null)
        <div class="card">
            <strong>Subject assignment does not exist yet.</strong>
            <p>Submit grades below to auto-create the subject assignment for this section, subject, and school year.</p>
        </div>
    @endif

    <div class="card">
        <div class="tabs">
            @foreach ($subjects as $subject)
                <a class="tab {{ $selectedSubject === $subject->id ? 'active' : '' }}"
                   href="{{ route('gradebook.index', ['school_year_id' => $selectedSchoolYear, 'section_id' => $selectedSection, 'subject_id' => $subject->id, 'quarter' => $quarter]) }}">
                    {{ $subject->title }}
                </a>
            @endforeach
        </div>

        <div class="pill-row" style="margin: 12px 0 8px;">
            @for ($q = 1; $q <= 4; $q++)
                <a class="pill pill-link {{ $quarter === $q ? 'pill-link--active' : '' }}"
                   href="{{ route('gradebook.index', ['school_year_id' => $selectedSchoolYear, 'section_id' => $selectedSection, 'subject_id' => $selectedSubject, 'quarter' => $q]) }}">
                    Q{{ $q }}
                </a>
            @endfor
        </div>

        <form method="POST" action="{{ route('gradebook.store') }}" id="grade-entry-form">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYear }}">
            <input type="hidden" name="section_id" value="{{ $selectedSection }}">
            <input type="hidden" name="subject_id" value="{{ $selectedSubject }}">
            <input type="hidden" name="quarter" value="{{ $quarter }}">

            <div class="table-wrap">
                <table class="table gradebook-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Quiz (30%)</th>
                            <th>Assignment (30%)</th>
                            <th>Exam (40%)</th>
                            <th>Quarter Avg ← auto</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $index => $enrollment)
                            @php $grade = $existingGrades->get($enrollment->id); @endphp
                            <tr data-row="grade">
                                <td>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $enrollment->student->full_name }}</td>
                                <td>
                                    <input class="grade-input" inputmode="decimal" type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][quiz]" value="{{ old("grades.{$enrollment->id}.quiz", $grade?->quiz) }}">
                                </td>
                                <td>
                                    <input class="grade-input" inputmode="decimal" type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][assignment]" value="{{ old("grades.{$enrollment->id}.assignment", $grade?->assignment) }}">
                                </td>
                                <td>
                                    <input class="grade-input" inputmode="decimal" type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][exam]" value="{{ old("grades.{$enrollment->id}.exam", $grade?->exam) }}">
                                </td>
                                <td class="avg-cell">
                                    <span class="chip js-avg">{{ $grade?->quarter_grade !== null ? number_format($grade->quarter_grade, 2) : '—' }}</span>
                                </td>
                                <td class="remarks-cell">
                                    @php
                                        $qg = $grade?->quarter_grade;
                                        $remark = $qg === null ? '—' : ((float) $qg >= 75 ? 'Passed' : 'Failed');
                                    @endphp
                                    <span class="chip js-remark {{ $remark === 'Passed' ? 'chip--ok' : ($remark === 'Failed' ? 'chip--bad' : '') }}">{{ $remark }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">No enrolled students found for the selected school year and section.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="actions">
                <button class="btn" type="submit">Save All Grades</button>
                <button class="btn btn--ghost" type="reset">Reset</button>
                <span class="muted" style="align-self:center; font-style: italic;">
                    Grades auto-transfer to Report Card. No re-entry needed.
                </span>
            </div>
        </form>
    </div>

    <div class="card card--dash-note">
        <div style="font-size: 13px;">
            Formula: <strong>Quarter Avg</strong> = (Quiz × 0.30) + (Assignment × 0.30) + (Exam × 0.40)<br>
            General Average is computed automatically when all 4 quarters are complete.
        </div>
    </div>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('tr[data-row="grade"]'));
            if (!rows.length) return;

            const toNumber = (value) => {
                if (value === null || value === undefined) return null;
                const s = String(value).trim();
                if (s === '') return null;
                const n = Number(s);
                return Number.isFinite(n) ? n : null;
            };

            const compute = (row) => {
                const quiz = toNumber(row.querySelector('input[name*=\"[quiz]\"]')?.value);
                const assign = toNumber(row.querySelector('input[name*=\"[assignment]\"]')?.value);
                const exam = toNumber(row.querySelector('input[name*=\"[exam]\"]')?.value);

                const avgEl = row.querySelector('.js-avg');
                const remarkEl = row.querySelector('.js-remark');

                if (!avgEl || !remarkEl) return;

                if (quiz === null || assign === null || exam === null) {
                    avgEl.textContent = '—';
                    remarkEl.textContent = '—';
                    remarkEl.classList.remove('chip--ok', 'chip--bad');
                    return;
                }

                const avg = (quiz * 0.30) + (assign * 0.30) + (exam * 0.40);
                const rounded = Math.round(avg * 10) / 10;

                avgEl.textContent = rounded.toFixed(1);

                if (rounded >= 75) {
                    remarkEl.textContent = 'Passed';
                    remarkEl.classList.add('chip--ok');
                    remarkEl.classList.remove('chip--bad');
                } else {
                    remarkEl.textContent = 'Failed';
                    remarkEl.classList.add('chip--bad');
                    remarkEl.classList.remove('chip--ok');
                }
            };

            rows.forEach((row) => {
                row.querySelectorAll('input.grade-input').forEach((input) => {
                    input.addEventListener('input', () => compute(row));
                });
                compute(row);
            });
        })();
    </script>
@endsection
