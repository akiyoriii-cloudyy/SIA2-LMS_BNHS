@extends('layouts.app')

@section('title', 'DepEd Report Card')

@section('content')
    @php
        $student = $enrollment->student;
        $section = $enrollment->section;
        $schoolYear = $enrollment->schoolYear;

        $adviserName = null;
        if (isset($adviserTeacher) && $adviserTeacher) {
            $adviserName = $adviserTeacher->user?->name ?: trim(($adviserTeacher->first_name ?? '').' '.($adviserTeacher->last_name ?? ''));
            $adviserName = $adviserName !== '' ? $adviserName : null;
        }

        $items = collect($reportCard?->items ?? [])
            ->sortBy(fn ($i) => $i->subjectAssignment?->subject?->title ?? '');

        $generalAverage = $reportCard?->general_average;
        $fmtQ = fn ($v) => $v === null ? '-' : number_format((float) $v, 0);

        $age = $ageAtCutoff ?? ($student?->date_of_birth ? $student->date_of_birth->age : null);
        $ageCutoffLabel = isset($ageCutoffDate) && $ageCutoffDate
            ? $ageCutoffDate->format('M d, Y')
            : null;
        $sex = $student?->sex ? ucfirst((string) $student->sex) : null;

        $months = $attendanceMonths ?? ['Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'];
        $att = $attendanceSummary ?? [];

        $sumRow = function (string $key) use ($months, $att): int {
            $total = 0;
            foreach ($months as $m) {
                $total += (int) (($att[$m][$key] ?? 0));
            }

            return $total;
        };

        $quarterLabels = ['q1' => 'Q1', 'q2' => 'Q2', 'q3' => 'Q3', 'q4' => 'Q4'];
    @endphp

    <div class="dash-topbar no-print">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">DepEd Form 138 Report Card</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('gradebook.index') }}">Compute All</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="rc-pro-head">
        <div>
            <div class="rc-pro-title">DepEd Form 138 - SHS Report Card</div>
            <div class="rc-pro-sub">
                Booklet format: one sheet, folded. Outside = Cover and Back. Inside = Grades and Values.
                <span class="rc-badge {{ $missingObservedValuesCount > 0 ? 'rc-badge--warn' : '' }}">
                    {{ $missingObservedValuesCount > 0 ? $missingObservedValuesCount.' value slots need teacher rating' : 'Observed values complete' }}
                </span>
            </div>
        </div>

        <div class="rc-pro-right no-print">
            <select class="compact-select"
                onchange="if (this.value) { window.location.href = '{{ route('report-cards.show', '__ID__') }}'.replace('__ID__', this.value) + '?page={{ $page }}'; }">
                @foreach ($peerEnrollments as $peer)
                    <option value="{{ $peer->id }}" @selected($peer->id === $enrollment->id)>{{ $peer->student?->full_name }}</option>
                @endforeach
            </select>
            @if ($page !== 'values')
                <button class="btn btn-primary btn-sm" type="button" id="print-both">Print Both Pages</button>
            @endif
        </div>
    </div>

    <div class="rc-tabs no-print">
        <a class="rc-tab {{ $page === 'outside' ? 'active' : '' }}"
            href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'outside']) }}">
            Page 1 - Outside (Cover and Back)
        </a>
        <a class="rc-tab {{ $page === 'inside' ? 'active' : '' }}"
            href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'inside']) }}">
            Page 2 - Inside (Grades and Values)
        </a>
        <a class="rc-tab {{ $page === 'values' ? 'active' : '' }}"
            href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'values']) }}">
            Observed Values Entry
        </a>
        <div class="rc-tip">
            {{ $page === 'values'
                ? 'Rate each behavior statement by quarter, then save before printing the report card.'
                : 'Both pages print on one sheet - fold in half to form the booklet card.' }}
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert">{{ $errors->first() }}</div>
    @endif

    @if ($page === 'values')
        <div class="card rc-values-entry">
            <form method="POST" action="{{ route('report-cards.observed-values.update', $enrollment->id) }}">
                @csrf
                <input type="hidden" name="page" value="values">

                <div class="rc-values-entry-head">
                    <div>
                        <div class="rc-values-entry-title">Learner's Observed Values - Teacher Entry</div>
                        <div class="rc-values-entry-sub">
                            Select a student then rate each behavior statement per quarter before printing the report card.
                        </div>
                    </div>
                    <select class="compact-select"
                        onchange="if (this.value) { window.location.href = '{{ route('report-cards.show', '__ID__') }}'.replace('__ID__', this.value) + '?page=values'; }">
                        @foreach ($peerEnrollments as $peer)
                            <option value="{{ $peer->id }}" @selected($peer->id === $enrollment->id)>{{ $peer->student?->full_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="table-wrap">
                    <table class="table rc-values-entry-table">
                        <thead>
                            <tr>
                                <th style="width: 24%;">Core Value</th>
                                <th>Behavior Statement</th>
                                <th style="width: 7%;">Q1</th>
                                <th style="width: 7%;">Q2</th>
                                <th style="width: 7%;">Q3</th>
                                <th style="width: 7%;">Q4</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($observedValueRows as $group)
                                @foreach ($group['rows'] as $statementIndex => $row)
                                    @php
                                        $rowKey = $row['key'];
                                        $saved = $observedValues[$rowKey] ?? [];
                                    @endphp
                                    <tr>
                                        @if ($statementIndex === 0)
                                            <td rowspan="{{ count($group['rows']) }}" class="rc-values-entry-core">
                                                {{ $group['label'] }}
                                            </td>
                                        @endif
                                        <td>{{ $row['statement'] }}</td>
                                        @foreach ($quarterLabels as $quarterKey => $quarterLabel)
                                            <td>
                                                <select class="compact-select compact-select--xs"
                                                    name="observed_values[{{ $rowKey }}][{{ $quarterKey }}]">
                                                    <option value="">-</option>
                                                    @foreach ($observedValueScale as $scale)
                                                        <option value="{{ $scale }}" @selected(($saved[$quarterKey] ?? null) === $scale)>
                                                            {{ $scale }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="rc-values-entry-foot">
                    <div class="muted">
                        Ratings: AO - Always Observed | SO - Sometimes Observed | RO - Rarely Observed | NO - Not Observed
                    </div>
                    <button class="btn btn-primary" type="submit">Save to Report Card</button>
                </div>
            </form>
        </div>
    @endif

    @if ($page !== 'values')
        <div class="rc-pro-preview" data-page="{{ $page }}">
            <div class="rc-paper">
                <div class="rc-fold"></div>

                <section class="rc-side rc-side--left rc-page rc-page--outside">
                    <div class="rc-block">
                        <div class="rc-block-title">REPORT ON ATTENDANCE</div>
                        <table class="rc-mini-table">
                            <thead>
                                <tr>
                                    <th></th>
                                    @foreach ($months as $m)
                                        <th>{{ $m }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>No. of school days</td>
                                    @foreach ($months as $m)
                                        <td>{{ (int) ($att[$m]['school_days'] ?? 0) }}</td>
                                    @endforeach
                                    <td>{{ $sumRow('school_days') }}</td>
                                </tr>
                                <tr>
                                    <td>No. of days present</td>
                                    @foreach ($months as $m)
                                        <td>{{ (int) ($att[$m]['present_days'] ?? 0) }}</td>
                                    @endforeach
                                    <td>{{ $sumRow('present_days') }}</td>
                                </tr>
                                <tr>
                                    <td>No. of days absent</td>
                                    @foreach ($months as $m)
                                        <td>{{ (int) ($att[$m]['absent_days'] ?? 0) }}</td>
                                    @endforeach
                                    <td>{{ $sumRow('absent_days') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rc-block" style="margin-top: 18px;">
                        <div class="rc-block-title">PARENT/GUARDIAN'S SIGNATURE</div>
                        <div class="rc-sign-grid">
                            <div class="rc-sign-row"><span>First Semester</span><span class="line"></span></div>
                            <div class="rc-sign-row"><span>1st Quarter</span><span class="line"></span></div>
                            <div class="rc-sign-row"><span>2nd Quarter</span><span class="line"></span></div>
                            <div class="rc-sign-row"><span>Second Semester</span><span class="line"></span></div>
                            <div class="rc-sign-row"><span>3rd Quarter</span><span class="line"></span></div>
                            <div class="rc-sign-row"><span>4th Quarter</span><span class="line"></span></div>
                        </div>
                    </div>
                </section>

                <section class="rc-side rc-side--right rc-page rc-page--outside">
                    <div class="rc-cover-top">
                        <div class="rc-seal">DepEd</div>
                        <div>
                            <div class="rc-rp">Republic of the Philippines</div>
                            <div class="rc-rp">Department of Education</div>
                            <div class="rc-rp">Region XII - SOCCSKSARGEN</div>
                            <div class="rc-rp"><strong>SCHOOLS DIVISION OF GENERAL SANTOS CITY</strong></div>
                        </div>
                        <div class="rc-form-code">DepEd FORM 138 - SHS</div>
                    </div>

                    <div class="rc-lines">
                        <div class="rc-line"><span>Bawing District</span><span class="hint">District</span></div>
                        <div class="rc-line"><span>PH Bawing National High School</span><span class="hint">School</span></div>
                    </div>

                    <div class="rc-meta-grid">
                        <div class="rc-meta-row">
                            <div><strong>Name:</strong> {{ $student->full_name }}</div>
                            <div><strong>Sex:</strong> {{ $sex ?? '-' }}</div>
                        </div>
                        <div class="rc-meta-row">
                            <div><strong>Age{{ $ageCutoffLabel ? ' (as of '.$ageCutoffLabel.')' : '' }}:</strong> {{ $age ?? '-' }}</div>
                            <div><strong>Section:</strong> {{ $section ? $section->name : '-' }}</div>
                        </div>
                        <div class="rc-meta-row">
                            <div><strong>Grade:</strong> {{ $section ? $section->grade_level : '-' }}</div>
                            <div><strong>School Year:</strong> {{ $schoolYear?->name ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="rc-letter">
                        <div>Dear Parent:</div>
                        <p>This report card shows the ability and progress your child has made in the different learning areas as well as his/her core values.</p>
                        <p>The school welcomes you should you desire to know more about your child's progress.</p>
                    </div>

                    <div class="rc-sig-area">
                        <div class="sig">
                            <div class="sig-line"></div>
                            <div class="sig-name">{{ $adviserName ? 'Ms. '.$adviserName : '-' }}</div>
                            <div class="sig-role">Teacher</div>
                        </div>
                    </div>

                    <div class="rc-transfer">
                        <div class="rc-transfer-title">Certificate of Transfer</div>
                        <div class="rc-transfer-row"><span>Admitted to Grade:</span><span class="line"></span><span>Section:</span><span class="line"></span></div>
                        <div class="rc-transfer-row"><span>Eligibility for Admission to Grade:</span><span class="line" style="flex:1"></span></div>
                        <div class="rc-transfer-row"><span>Approved:</span><span class="line" style="flex:1"></span></div>
                        <div class="rc-transfer-sign">
                            <div><div class="sig-line"></div><div class="sig-role">Principal</div></div>
                            <div><div class="sig-line"></div><div class="sig-role">Teacher</div></div>
                        </div>
                        <div class="rc-transfer-title" style="margin-top: 10px;">Cancellation of Eligibility to Transfer</div>
                        <div class="rc-transfer-row"><span>Admitted in:</span><span class="line" style="flex:1"></span></div>
                        <div class="rc-transfer-row"><span>Date:</span><span class="line" style="flex:1"></span></div>
                        <div class="rc-transfer-sign" style="justify-content:flex-end;">
                            <div style="width: 180px;">
                                <div class="sig-line"></div>
                                <div class="sig-role">Principal</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rc-side rc-side--left rc-page rc-page--inside">
                    <div class="rc-block-title">REPORT ON LEARNING PROGRESS AND ACHIEVEMENT</div>
                    <table class="rc-grade-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 48%;">Subjects</th>
                                <th colspan="4">Quarter</th>
                                <th rowspan="2" style="width: 22%;">Semester Final Grade</th>
                            </tr>
                            <tr>
                                <th>1</th><th>2</th><th>3</th><th>4</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php $final = $item->final_grade; @endphp
                                <tr>
                                    <td>{{ $item->subjectAssignment?->subject?->title ?? '-' }}</td>
                                    <td>{{ $fmtQ($item->q1) }}</td>
                                    <td>{{ $fmtQ($item->q2) }}</td>
                                    <td>{{ $fmtQ($item->q3) }}</td>
                                    <td>{{ $fmtQ($item->q4) }}</td>
                                    <td class="final">{{ $final === null ? '-' : number_format((float) $final, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No report card items yet.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="5" class="ga">General Average for the Semester</td>
                                <td class="final">{{ $generalAverage === null ? '-' : number_format((float) $generalAverage, 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="rc-desc-wrap">
                        <table class="rc-desc">
                            <thead>
                                <tr>
                                    <th>Descriptors</th>
                                    <th>Grading Scale</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Outstanding</td><td>90-100</td><td>Passed</td></tr>
                                <tr><td>Very Satisfactory</td><td>85-89</td><td>Passed</td></tr>
                                <tr><td>Satisfactory</td><td>80-84</td><td>Passed</td></tr>
                                <tr><td>Fairly Satisfactory</td><td>75-79</td><td>Passed</td></tr>
                                <tr><td>Did Not Meet Expectations</td><td>Below 75</td><td>Failed</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rc-side rc-side--right rc-page rc-page--inside">
                    <div class="rc-block-title">REPORT ON LEARNER'S OBSERVED VALUES</div>
                    <table class="rc-values">
                        <thead>
                            <tr>
                                <th style="width: 22%;">Core Values</th>
                                <th style="width: 52%;">Behavior statements</th>
                                <th colspan="4">Quarter</th>
                            </tr>
                            <tr>
                                <th></th><th></th>
                                <th>1</th><th>2</th><th>3</th><th>4</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($observedValueRows as $group)
                                @foreach ($group['rows'] as $statementIndex => $row)
                                    @php
                                        $rowKey = $row['key'];
                                        $saved = $observedValues[$rowKey] ?? [];
                                    @endphp
                                    <tr>
                                        @if ($statementIndex === 0)
                                            <td rowspan="{{ count($group['rows']) }}"><strong>{{ $group['label'] }}</strong></td>
                                        @endif
                                        <td>{{ $row['statement'] }}</td>
                                        @foreach ($quarterLabels as $quarterKey => $quarterLabel)
                                            <td>{{ $saved[$quarterKey] ?? '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <div class="rc-values-note">
                        <div><strong>Marking</strong> Non-numerical Rating</div>
                        <div><strong>AO</strong> Always Observed | <strong>RO</strong> Rarely Observed</div>
                        <div><strong>SO</strong> Sometimes Observed | <strong>NO</strong> Not Observed</div>
                    </div>

                    <div class="rc-desc-wrap">
                        <div class="rc-block-title" style="margin-bottom: 6px;">LEARNER PROGRESS AND ACHIEVEMENT</div>
                        <table class="rc-desc">
                            <thead>
                                <tr>
                                    <th>Descriptors</th>
                                    <th>Grading Scale</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>Outstanding</td><td>90-100</td><td>Passed</td></tr>
                                <tr><td>Very Satisfactory</td><td>85-89</td><td>Passed</td></tr>
                                <tr><td>Satisfactory</td><td>80-84</td><td>Passed</td></tr>
                                <tr><td>Fairly Satisfactory</td><td>75-79</td><td>Passed</td></tr>
                                <tr><td>Did Not Meet Expectations</td><td>Below 75</td><td>Failed</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const btn = document.getElementById('print-both');
            if (!btn) return;
            btn.addEventListener('click', () => {
                document.body.classList.add('print-both-pages');
                window.print();
                setTimeout(() => document.body.classList.remove('print-both-pages'), 250);
            });
        })();
    </script>
@endsection
