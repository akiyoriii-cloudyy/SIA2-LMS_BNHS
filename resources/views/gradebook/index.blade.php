@extends('layouts.app')

@section('content')
    @php
        $stats = $gradeEntryStats ?? [];

        $totalStudents = (int) ($stats['total_students'] ?? 0);
        $totalSubjects = (int) ($stats['total_subjects'] ?? 0);
        $pendingCount = (int) ($stats['pending'] ?? 0);
        $schoolYearName = (string) ($stats['school_year'] ?? '—');
        $sectionLabel = (string) ($stats['section_label'] ?? '—');
        $subjectTitle = (string) ($stats['subject_title'] ?? ($subjects->firstWhere('id', $selectedSubject)?->title ?? 'Subject'));
        $selectedSubjectCategory = (string) ($selectedSubjectCategory ?? 'core');
        $subjectCategoryLabel = ucfirst($selectedSubjectCategory).' subjects';
        $subjectCategoryCounts = (array) ($subjectCategoryCounts ?? []);
        $rosterNumbers = (array) ($rosterNumbers ?? []);
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Grade Entry</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <button class="btn btn-gold btn-sm" type="button" data-action="compute-all">Compute All</button>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="dash-kpi-grid">
        <div class="dash-kpi kpi-gold">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">👩‍🎓</div>
            </div>
            <div class="dash-kpi-value">{{ number_format($totalStudents ?: (int) $enrollments->count()) }}</div>
            <div class="dash-kpi-label">TOTAL STUDENTS</div>
            <div class="dash-kpi-sub">{{ $sectionLabel }}</div>
        </div>

        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">📚</div>
            </div>
            <div class="dash-kpi-value">{{ number_format($totalSubjects ?: (int) $subjects->count()) }}</div>
            <div class="dash-kpi-label">SUBJECTS</div>
            <div class="dash-kpi-sub">{{ $subjectCategoryLabel }}</div>
        </div>

        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">🗓️</div>
            </div>
            <div class="dash-kpi-value">Q{{ $quarter }}</div>
            <div class="dash-kpi-label">QUARTER</div>
            <div class="dash-kpi-sub">SY. {{ $schoolYearName }}</div>
        </div>

        <div class="dash-kpi kpi-red">
            <div class="dash-kpi-top">
                <div class="dash-kpi-icon">⏳</div>
            </div>
            <div class="dash-kpi-value">{{ number_format($pendingCount) }}</div>
            <div class="dash-kpi-label">PENDING</div>
            <div class="dash-kpi-sub">Incomplete grades</div>
        </div>
    </div>

    <div class="dash-panel ge-strip">
        <div class="dash-panel-body">
            <div class="ge-steps">
                <div class="ge-step ge-step--dark">
                    <div class="ge-step-icon">📝</div>
                    <div class="ge-step-label">Quizzes</div>
                    <div class="ge-step-sub">30%</div>
                </div>
                <div class="ge-step ge-step--dark">
                    <div class="ge-step-icon">📌</div>
                    <div class="ge-step-label">Perf. Task</div>
                    <div class="ge-step-sub">30%</div>
                </div>
                <div class="ge-step ge-step--dark">
                    <div class="ge-step-icon">📄</div>
                    <div class="ge-step-label">Exam</div>
                    <div class="ge-step-sub">40%</div>
                </div>
                <div class="ge-step ge-step--gold">
                    <div class="ge-step-icon">⚙️</div>
                    <div class="ge-step-label">Auto Avg</div>
                    <div class="ge-step-sub">Computed</div>
                </div>
                <div class="ge-step ge-step--gold">
                    <div class="ge-step-icon">📊</div>
                    <div class="ge-step-label">Qtrly</div>
                    <div class="ge-step-sub">Q1–Q4</div>
                </div>
                <div class="ge-step ge-step--sage">
                    <div class="ge-step-icon">🧾</div>
                    <div class="ge-step-label">Form 138</div>
                    <div class="ge-step-sub">Auto-filled</div>
                </div>
                <div class="ge-step ge-step--sage">
                    <div class="ge-step-icon">🏆</div>
                    <div class="ge-step-label">Gen. Avg</div>
                    <div class="ge-step-sub">Auto</div>
                </div>
                <div class="ge-step ge-step--sage">
                    <div class="ge-step-icon">🖨️</div>
                    <div class="ge-step-label">Fold &amp; Print</div>
                    <div class="ge-step-sub">Booklet</div>
                </div>
            </div>
        </div>
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

    <div class="pill-row ge-category-pills" style="margin-bottom: 12px;">
        @foreach (['core', 'applied', 'specialized'] as $categoryItem)
            <a class="pill pill-link ge-category-pill {{ $selectedSubjectCategory === $categoryItem ? 'pill-link--active' : '' }}"
               href="{{ route('gradebook.index', ['school_year_id' => $selectedSchoolYear, 'grade_level' => $selectedGradeLevel, 'section_id' => $selectedSection, 'subject_category' => $categoryItem, 'quarter' => $quarter, 'q' => $search]) }}">
                {{ ucfirst($categoryItem) }} ({{ (int) ($subjectCategoryCounts[$categoryItem] ?? 0) }})
            </a>
        @endforeach
    </div>

    <div class="dash-panel ge-table">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">{{ $subjectTitle }}</div>
                <div class="dash-panel-sub">{{ $sectionLabel }} • Quarter {{ $quarter }} | Grades auto-transfer to Form 138 Report Card</div>
            </div>
            <div class="dash-topbar-actions" style="margin-left:auto;">
                <button class="btn btn-gold btn-sm" type="button" id="auto-compute-all">⚡ Auto-Compute All</button>
            </div>
        </div>

        <div class="dash-panel-body">
            <form method="GET" action="{{ route('gradebook.index') }}" class="ge-filters">
                <input type="hidden" name="current_quarter" value="{{ $quarter }}">
                <input type="hidden" name="subject_category" value="{{ $selectedSubjectCategory }}">

                <div class="ge-filter-row">
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
                        <select name="subject_id" aria-label="Subject">
                            @if ($subjects->isEmpty())
                                <option value="">No subjects in this category</option>
                            @else
                                @foreach ($subjects as $subject)
                                    <option value="{{ $subject->id }}" @selected($selectedSubject === $subject->id)>
                                        {{ $subject->title }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="ge-filter">
                        <select name="section_id" aria-label="Section">
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}" @selected($selectedSection === $section->id)>
                                    {{ $section->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ge-filter ge-filter--sy">
                        <select name="school_year_id" aria-label="School year">
                            @foreach ($schoolYears as $schoolYear)
                                <option value="{{ $schoolYear->id }}" @selected($selectedSchoolYear === $schoolYear->id)>
                                    {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ge-filter ge-filter--search">
                        <input type="text" name="q" placeholder="Search student..." value="{{ $search ?? '' }}">
                    </div>

                    <div class="ge-filter ge-filter--btn">
                        <button class="btn btn-sm" type="submit">Load</button>
                    </div>
                </div>

                <div class="ge-filter-row" style="margin-top: 8px; align-items: center;">
                    <label class="teacher-autosave-toggle" for="autosave-toggle" style="display: inline-flex;">
                        <input type="checkbox" id="autosave-toggle">
                        Auto-save to database (optional)
                    </label>
                    <span id="autosave-status" class="muted" style="margin-left: 8px;">Auto-save is off</span>
                </div>

                <div class="ge-quarter-pills">
                    @for ($q = 1; $q <= 4; $q++)
                        <button class="pill ge-quarter-pill {{ $quarter === $q ? 'pill-link--active' : '' }}"
                                type="submit"
                                name="quarter"
                                value="{{ $q }}">
                            Quarter {{ $q }}
                        </button>
                    @endfor
                </div>
            </form>

            @if ($selectedSubject > 0)
            <form method="POST" action="{{ route('gradebook.store') }}" id="grade-entry-form">
                @csrf
                <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYear }}">
                <input type="hidden" name="grade_level" value="{{ $selectedGradeLevel }}">
                <input type="hidden" name="section_id" value="{{ $selectedSection }}">
                <input type="hidden" name="subject_id" value="{{ $selectedSubject }}">
                <input type="hidden" name="subject_category" value="{{ $selectedSubjectCategory }}">
                <input type="hidden" name="quarter" value="{{ $quarter }}">
                <input type="hidden" name="q" value="{{ $search ?? '' }}">

                <div class="table-wrap">
                    <table class="table gradebook-table gradebook-table--pro">
                        <thead>
                            <tr>
                                <th>NO.</th>
                                <th>STUDENT NAME</th>
                                <th>QUIZZES (30%)</th>
                                <th>PERFORMANCE TASK (30%)</th>
                                <th>EXAM (40%)</th>
                                <th>QTRLY AVG</th>
                                <th>STATUS</th>
                                <th>— FORM 138</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($enrollments as $index => $enrollment)
                                @php $grade = $existingGrades->get($enrollment->id); @endphp
                                <tr data-row="grade">
                                    <td>{{ str_pad((string) ((int) $quarter), 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="student-cell">{{ $enrollment->student->full_name }}</td>
                                    <td>
                                        <input class="grade-input" inputmode="decimal" type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][quiz]" value="{{ old("grades.{$enrollment->id}.quiz", $grade?->quiz) }}">
                                    </td>
                                    <td>
                                        <input class="grade-input" inputmode="decimal" type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][performance_task]" value="{{ old("grades.{$enrollment->id}.performance_task", $grade?->performance_task ?? $grade?->assignment) }}">
                                    </td>
                                    <td>
                                        <input class="grade-input" inputmode="decimal" type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][exam]" value="{{ old("grades.{$enrollment->id}.exam", $grade?->exam) }}">
                                    </td>
                                    <td class="avg-cell">
                                        <span class="avg-pill js-avg">{{ $grade?->quarter_grade !== null ? number_format($grade->quarter_grade, 2) : '—' }}</span>
                                    </td>
                                    <td class="status-cell">
                                        @php
                                            $qg = $grade?->quarter_grade;
                                            $remark = $qg === null ? '—' : ((float) $qg >= 75 ? 'PASSED' : 'FAILED');
                                        @endphp
                                        <span class="grade-status js-remark {{ $remark === 'PASSED' ? 'status-pass' : ($remark === 'FAILED' ? 'status-fail' : '') }}">{{ $remark }}</span>
                                    </td>
                                    <td class="form138-cell">
                                        <label class="form138">
                                            <input type="checkbox" checked disabled>
                                            <span>Auto → Form 138</span>
                                        </label>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">No enrolled students found for the selected school year and section.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="actions">
                    <button class="btn" type="submit">Save All Grades</button>
                    <button class="btn btn--ghost" type="reset">Reset</button>
                    <span id="autosave-status-footer" class="muted" style="align-self:center;"></span>
                    <span class="muted" style="align-self:center; font-style: italic;">
                        Grades auto-transfer to Report Card. No re-entry needed.
                    </span>
                </div>
            </form>
            @else
                <div class="card" style="margin-top: 0;">
                    <strong>No {{ ucfirst($selectedSubjectCategory) }} subjects found.</strong>
                    <p style="margin: 6px 0 0;">
                        Add subjects under <a href="{{ route('subjects.index') }}">Subjects</a> or choose another category using the buttons above.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="card card--dash-note">
        <div style="font-size: 13px;">
            Formula: <strong>Quarter Avg</strong> = (Quiz × 0.30) + (Performance Task × 0.30) + (Exam × 0.40)<br>
            General Average is computed automatically when all 4 quarters are complete.
        </div>
    </div>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('tr[data-row="grade"]'));
            const form = document.getElementById('grade-entry-form');
            if (!rows.length || !form) return;

            const autosaveToggle = document.getElementById('autosave-toggle');
            const autosaveStatus = document.getElementById('autosave-status');
            const autosaveStatusFooter = document.getElementById('autosave-status-footer');
            const autosaveKey = 'lms.gradebook.autosave';
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            let saveTimer = null;
            let isSaving = false;
            let pendingSave = false;

            const setAutosaveStatus = (text, isError = false) => {
                if (autosaveStatus) {
                    autosaveStatus.textContent = text;
                    autosaveStatus.style.color = isError ? '#b42318' : '';
                }

                if (autosaveStatusFooter) {
                    autosaveStatusFooter.textContent = text;
                    autosaveStatusFooter.style.color = isError ? '#b42318' : '';
                }
            };

            const toNumber = (value) => {
                if (value === null || value === undefined) return null;
                const s = String(value).trim();
                if (s === '') return null;
                const n = Number(s);
                return Number.isFinite(n) ? n : null;
            };

            const compute = (row) => {
                const quiz = toNumber(row.querySelector('input[name*="[quiz]"]')?.value);
                const performanceTask = toNumber(row.querySelector('input[name*="[performance_task]"]')?.value);
                const exam = toNumber(row.querySelector('input[name*="[exam]"]')?.value);

                const avgEl = row.querySelector('.js-avg');
                const remarkEl = row.querySelector('.js-remark');

                if (!avgEl || !remarkEl) return;

                if (quiz === null || performanceTask === null || exam === null) {
                    avgEl.textContent = '—';
                    remarkEl.textContent = '—';
                    remarkEl.classList.remove('status-pass', 'status-fail');
                    return;
                }

                const avg = (quiz * 0.30) + (performanceTask * 0.30) + (exam * 0.40);
                const rounded = Math.round(avg * 100) / 100;

                avgEl.textContent = rounded.toFixed(2);

                if (rounded >= 75) {
                    remarkEl.textContent = 'PASSED';
                    remarkEl.classList.add('status-pass');
                    remarkEl.classList.remove('status-fail');
                } else {
                    remarkEl.textContent = 'FAILED';
                    remarkEl.classList.add('status-fail');
                    remarkEl.classList.remove('status-pass');
                }
            };

            const computeAll = () => rows.forEach((row) => compute(row));

            const autosave = async () => {
                if (!autosaveToggle?.checked) {
                    return;
                }

                if (isSaving) {
                    pendingSave = true;
                    return;
                }

                isSaving = true;
                setAutosaveStatus('Auto-saving...');

                try {
                    const payload = new FormData(form);
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: payload,
                    });

                    if (!response.ok) {
                        throw new Error('Autosave failed.');
                    }

                    setAutosaveStatus(`Auto-saved at ${new Date().toLocaleTimeString()}`);
                } catch (error) {
                    setAutosaveStatus('Autosave failed. Click "Save All Grades".', true);
                } finally {
                    isSaving = false;
                    if (pendingSave) {
                        pendingSave = false;
                        autosave();
                    }
                }
            };

            const scheduleAutosave = () => {
                if (!autosaveToggle?.checked) return;
                if (saveTimer) clearTimeout(saveTimer);
                saveTimer = setTimeout(autosave, 1200);
            };

            if (autosaveToggle) {
                try {
                    autosaveToggle.checked = localStorage.getItem(autosaveKey) === '1';
                } catch (error) {
                    autosaveToggle.checked = false;
                }

                setAutosaveStatus(autosaveToggle.checked ? 'Auto-save is on' : 'Auto-save is off');

                autosaveToggle.addEventListener('change', () => {
                    try {
                        localStorage.setItem(autosaveKey, autosaveToggle.checked ? '1' : '0');
                    } catch (error) {
                        // Ignore storage errors.
                    }

                    setAutosaveStatus(autosaveToggle.checked ? 'Auto-save is on' : 'Auto-save is off');
                });
            }

            rows.forEach((row) => {
                row.querySelectorAll('input.grade-input').forEach((input) => {
                    input.addEventListener('input', () => {
                        compute(row);
                        scheduleAutosave();
                    });
                });
                compute(row);
            });

            document.querySelectorAll('[data-action="compute-all"], #auto-compute-all').forEach((btn) => {
                btn.addEventListener('click', () => computeAll());
            });
        })();
    </script>
@endsection
