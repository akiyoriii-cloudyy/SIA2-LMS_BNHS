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
            ->sortBy(fn ($i) => $i->subjectAssignment?->subject?->title ?? '')
            ->values();

        $fmtQ = fn ($v) => $v === null ? '' : number_format((float) $v, 0);

        $age = $ageAtCutoff ?? ($student?->date_of_birth ? $student->date_of_birth->age : null);
        $sex = $student?->sex ? mb_strtoupper((string) $student->sex) : null;

        $months = $attendanceMonths ?? ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr'];
        $att = $attendanceSummary ?? [];

        $sumRow = function (string $key) use ($months, $att): int {
            $total = 0;
            foreach ($months as $m) {
                $total += (int) (($att[$m][$key] ?? 0));
            }

            return $total;
        };

        $quarterLabels = ['q1' => '1', 'q2' => '2', 'q3' => '3', 'q4' => '4'];

        $lrn = trim((string) ($student?->lrn ?? ''));
        $track = mb_strtoupper(trim((string) ($section?->track ?? '')));
        $strand = mb_strtoupper(trim((string) ($section?->strand ?? '')));
        $trackStrand = trim($track !== '' && $strand !== '' ? $track.' / '.$strand : ($track !== '' ? $track : $strand));

        $teacherDisplayName = $adviserName ? mb_strtoupper($adviserName) : 'SHEILA MAE O. DALUPAN';
        $principalName = 'CARLITO V. GILZA';
        $principalDesignation = 'Principal II';

        $semesterHint = function (?string $title): ?int {
            $raw = trim((string) $title);
            if ($raw === '') {
                return null;
            }

            if (preg_match('/(?:\\s|\\-|\\/|\\()([1-4])\\)?$/', $raw, $m)) {
                return ((int) $m[1]) <= 2 ? 1 : 2;
            }

            return null;
        };

        $calcSemesterFinal = function ($a, $b): ?float {
            $a = $a !== null ? (float) $a : null;
            $b = $b !== null ? (float) $b : null;

            if ($a === null && $b === null) {
                return null;
            }

            if ($a === null) {
                return $b;
            }

            if ($b === null) {
                return $a;
            }

            return round(($a + $b) / 2, 2);
        };

        $remarkFor = function (?float $grade): string {
            if ($grade === null) {
                return '';
            }

            return $grade >= 75 ? 'Passed' : 'Failed';
        };

        $hintedItems = $items->filter(fn ($item) => $semesterHint($item->subjectAssignment?->subject?->title) !== null);

        if ($hintedItems->isNotEmpty()) {
            $firstSemesterItems = $items->filter(fn ($item) => $semesterHint($item->subjectAssignment?->subject?->title) === 1)->values();
            $secondSemesterItems = $items->filter(fn ($item) => $semesterHint($item->subjectAssignment?->subject?->title) === 2)->values();
        } else {
            $firstSemesterItems = $items
                ->filter(fn ($item) => $item->q1 !== null || $item->q2 !== null)
                ->values();

            $secondSemesterItems = $items
                ->filter(fn ($item) => $item->q3 !== null || $item->q4 !== null)
                ->values();

            if ($firstSemesterItems->isEmpty() && $items->isNotEmpty()) {
                $firstSemesterItems = $items;
            }

            if ($secondSemesterItems->isEmpty() && $items->isNotEmpty()) {
                $split = (int) ceil($items->count() / 2);
                $secondSemesterItems = $items->skip($split)->values();
            }
        }

        $buildSemesterRows = function ($source, string $qa, string $qb) use ($calcSemesterFinal, $remarkFor) {
            return collect($source)->map(function ($item) use ($qa, $qb, $calcSemesterFinal, $remarkFor): array {
                $first = $item->{$qa};
                $second = $item->{$qb};
                $final = $calcSemesterFinal($first, $second);

                return [
                    'subject' => (string) ($item->subjectAssignment?->subject?->title ?? '-'),
                    'q1' => $first,
                    'q2' => $second,
                    'final' => $final,
                    'remarks' => $remarkFor($final),
                ];
            })->values();
        };

        $firstSemesterRows = $buildSemesterRows($firstSemesterItems, 'q1', 'q2');
        $secondSemesterRows = $buildSemesterRows($secondSemesterItems, 'q3', 'q4');

        $semesterAverage = function ($rows): ?float {
            $grades = collect($rows)
                ->pluck('final')
                ->filter(fn ($grade) => $grade !== null)
                ->values();

            if ($grades->isEmpty()) {
                return null;
            }

            return round((float) $grades->avg(), 2);
        };

        $firstSemesterAverage = $semesterAverage($firstSemesterRows);
        $secondSemesterAverage = $semesterAverage($secondSemesterRows);
        $firstSemesterRemarks = $remarkFor($firstSemesterAverage);
        $secondSemesterRemarks = $remarkFor($secondSemesterAverage);

        $minSemesterRows = 8;
        $firstSemesterPad = max(0, $minSemesterRows - $firstSemesterRows->count());
        $secondSemesterPad = max(0, $minSemesterRows - $secondSemesterRows->count());

        $learningModality = 'FACE TO FACE';
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
                Aligned to standard booklet layout (outside and inside pages).
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
            Page 1 - Outside
        </a>
        <a class="rc-tab {{ $page === 'inside' ? 'active' : '' }}"
            href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'inside']) }}">
            Page 2 - Inside
        </a>
        <a class="rc-tab {{ $page === 'values' ? 'active' : '' }}"
            href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'values']) }}">
            Observed Values Entry
        </a>
        <div class="rc-tip">
            {{ $page === 'values'
                ? 'Rate each behavior statement by quarter, then save before printing the report card.'
                : 'Print both pages on one sheet, then fold at center for booklet format.' }}
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
            <div class="rc-paper rcf-paper">
                <div class="rc-fold"></div>

                <section class="rc-side rc-side--left rc-page rc-page--outside rcf-side">
                    <div class="rcf-title-main">Report on Attendance</div>
                    <table class="rcf-attendance-table">
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

                    <div class="rcf-signature-block">
                        <div class="rcf-title-main">Parent / Guardian's Signature</div>
                        <div class="rcf-sem-label">1st Semester</div>
                        <div class="rcf-sign-row"><span>PRELIM</span><span class="line"></span></div>
                        <div class="rcf-sign-row"><span>FINAL</span><span class="line"></span></div>

                        <div class="rcf-sem-label" style="margin-top: 26px;">2nd Semester</div>
                        <div class="rcf-sign-row"><span>PRELIM</span><span class="line"></span></div>
                        <div class="rcf-sign-row"><span>FINAL</span><span class="line"></span></div>
                    </div>
                </section>

                <section class="rc-side rc-side--right rc-page rc-page--outside rcf-side">
                    <div class="rcf-cover-topline">
                        <div class="rcf-form-code">DEPED FORM 138</div>
                        <div class="rcf-lrn">LRN: <span>{{ $lrn !== '' ? $lrn : '____________________' }}</span></div>
                    </div>

                    <div class="rcf-header-logos">
                        <img src="{{ asset('bnhs-logo.jpg') }}" alt="School Logo" class="rcf-logo">
                        <div class="rcf-school-head">
                            <div class="rcf-rp">Republic of the Philippines</div>
                            <div class="rcf-rp">Department of Education</div>
                            <div class="rcf-rp">Region XII</div>
                            <div class="rcf-school-name">BAWING NATIONAL HIGH SCHOOL</div>
                            <div class="rcf-school-city">Bawing, General Santos City</div>
                        </div>
                        <img src="{{ asset('bnhs-logo.jpg') }}" alt="Division Logo" class="rcf-logo rcf-logo--faded">
                    </div>

                    <div class="rcf-identity-grid">
                        <div class="rcf-id-row">
                            <div class="rcf-id-line"><label>Name:</label><span>{{ $student?->full_name ?? '-' }}</span></div>
                            <div class="rcf-id-line"><label>Sex:</label><span>{{ $sex ?? '-' }}</span></div>
                        </div>
                        <div class="rcf-id-row">
                            <div class="rcf-id-line"><label>Age:</label><span>{{ $age ?? '-' }}</span></div>
                            <div class="rcf-id-line"><label>Section:</label><span>{{ $section?->name ?? '-' }}</span></div>
                        </div>
                        <div class="rcf-id-row">
                            <div class="rcf-id-line"><label>Grade:</label><span>{{ $section?->grade_level ?? '-' }}</span></div>
                            <div class="rcf-id-line"><label>School Year:</label><span>{{ $schoolYear?->name ?? '-' }}</span></div>
                        </div>
                        <div class="rcf-id-row rcf-id-row--single">
                            <div class="rcf-id-line"><label>Track / Strand:</label><span>{{ $trackStrand !== '' ? $trackStrand : '-' }}</span></div>
                        </div>
                    </div>

                    <div class="rcf-parent-note">
                        <div class="rcf-parent-note-title">Dear Parent:</div>
                        <p>
                            This report card shows the ability and progress your child has made in the different
                            learning areas as well as his/her core values.
                        </p>
                        <p>
                            The school welcomes you should you desire to know more about your child's progress.
                        </p>
                    </div>

                    <div class="rcf-sign-pair">
                        <div class="rcf-sign-box">
                            <div class="rcf-sign-line">{{ $teacherDisplayName }}</div>
                            <div class="rcf-sign-role">Teacher</div>
                        </div>
                        <div class="rcf-sign-box">
                            <div class="rcf-sign-line">{{ $principalName }}</div>
                            <div class="rcf-sign-role">{{ $principalDesignation }}</div>
                        </div>
                    </div>

                    <div class="rcf-transfer-box">
                        <div class="rcf-transfer-title">Certificate of Transfer</div>
                        <div class="rcf-transfer-row"><span>Admitted to Grade:</span><span class="line"></span><span>Section:</span><span class="line"></span></div>
                        <div class="rcf-transfer-row"><span>Eligibility for Admission to Grade:</span><span class="line line--wide"></span></div>
                        <div class="rcf-transfer-row"><span>Admitted in:</span><span class="line line--wide"></span></div>
                        <div class="rcf-transfer-row"><span>Date:</span><span class="line line--wide"></span></div>
                        <div class="rcf-transfer-signatures">
                            <div>
                                <div class="line"></div>
                                <div>Principal</div>
                            </div>
                            <div>
                                <div class="line"></div>
                                <div>Teacher</div>
                            </div>
                        </div>

                        <div class="rcf-transfer-title" style="margin-top: 8px;">Cancellation of Eligibility to Transfer</div>
                        <div class="rcf-transfer-row"><span>Admitted in:</span><span class="line line--wide"></span></div>
                        <div class="rcf-transfer-row"><span>Date:</span><span class="line line--wide"></span></div>
                        <div class="rcf-transfer-signatures rcf-transfer-signatures--single">
                            <div>
                                <div class="line"></div>
                                <div>Principal</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rc-side rc-side--left rc-page rc-page--inside rcf-side">
                    <div class="rcf-title-main">Report on Learning Progress and Achievement</div>

                    <div class="rcf-semester-box">
                        <div class="rcf-semester-title">First Semester</div>
                        <table class="rcf-semester-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width: 52%;">Subjects</th>
                                    <th colspan="2">Quarter</th>
                                    <th rowspan="2" style="width: 14%;">Semester Final Grade</th>
                                    <th rowspan="2" style="width: 13%;">Remarks</th>
                                </tr>
                                <tr>
                                    <th style="width: 7%;">1</th>
                                    <th style="width: 7%;">2</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($firstSemesterRows as $row)
                                    <tr>
                                        <td class="subject">{{ $row['subject'] }}</td>
                                        <td>{{ $fmtQ($row['q1']) }}</td>
                                        <td>{{ $fmtQ($row['q2']) }}</td>
                                        <td>{{ $fmtQ($row['final']) }}</td>
                                        <td>{{ $row['remarks'] }}</td>
                                    </tr>
                                @endforeach
                                @for ($i = 0; $i < $firstSemesterPad; $i++)
                                    <tr>
                                        <td class="subject">&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor
                                <tr class="summary">
                                    <td class="subject">General Average for the semester</td>
                                    <td></td>
                                    <td></td>
                                    <td>{{ $fmtQ($firstSemesterAverage) }}</td>
                                    <td>{{ $firstSemesterRemarks }}</td>
                                </tr>
                                <tr class="modality">
                                    <td class="subject">Learning Modality</td>
                                    <td colspan="4">{{ $learningModality }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rcf-semester-box">
                        <div class="rcf-semester-title">Second Semester</div>
                        <table class="rcf-semester-table">
                            <thead>
                                <tr>
                                    <th rowspan="2" style="width: 52%;">Subjects</th>
                                    <th colspan="2">Quarter</th>
                                    <th rowspan="2" style="width: 14%;">Semester Final Grade</th>
                                    <th rowspan="2" style="width: 13%;">Remarks</th>
                                </tr>
                                <tr>
                                    <th style="width: 7%;">1</th>
                                    <th style="width: 7%;">2</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($secondSemesterRows as $row)
                                    <tr>
                                        <td class="subject">{{ $row['subject'] }}</td>
                                        <td>{{ $fmtQ($row['q1']) }}</td>
                                        <td>{{ $fmtQ($row['q2']) }}</td>
                                        <td>{{ $fmtQ($row['final']) }}</td>
                                        <td>{{ $row['remarks'] }}</td>
                                    </tr>
                                @endforeach
                                @for ($i = 0; $i < $secondSemesterPad; $i++)
                                    <tr>
                                        <td class="subject">&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                @endfor
                                <tr class="summary">
                                    <td class="subject">General Average for the semester</td>
                                    <td></td>
                                    <td></td>
                                    <td>{{ $fmtQ($secondSemesterAverage) }}</td>
                                    <td>{{ $secondSemesterRemarks }}</td>
                                </tr>
                                <tr class="modality">
                                    <td class="subject">Learning Modality</td>
                                    <td colspan="4">{{ $learningModality }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rc-side rc-side--right rc-page rc-page--inside rcf-side">
                    <div class="rcf-title-main">Report on Learner's Observed Values</div>
                    <table class="rcf-values-table">
                        <thead>
                            <tr>
                                <th style="width: 22%;">Core Values</th>
                                <th style="width: 48%;">Behavior Statements</th>
                                <th colspan="4">Quarter</th>
                            </tr>
                            <tr>
                                <th></th>
                                <th></th>
                                <th>1</th>
                                <th>2</th>
                                <th>3</th>
                                <th>4</th>
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
                                            <td rowspan="{{ count($group['rows']) }}" class="core">{{ $group['label'] }}</td>
                                        @endif
                                        <td class="statement">{{ $row['statement'] }}</td>
                                        @foreach ($quarterLabels as $quarterKey => $quarterLabel)
                                            <td>{{ $saved[$quarterKey] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>

                    <div class="rcf-legend-wrap">
                        <div class="rcf-legend-block">
                            <div class="rcf-legend-title">Observed Values</div>
                            <div class="rcf-legend-row rcf-legend-row--values rcf-legend-row--head"><span>Marking</span><span>Non-numerical Rating</span></div>
                            <div class="rcf-legend-row rcf-legend-row--values"><span>AO</span><span>Always Observed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--values"><span>SO</span><span>Sometimes Observed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--values"><span>RO</span><span>Rarely Observed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--values"><span>NO</span><span>Not Observed</span></div>
                        </div>

                        <div class="rcf-legend-block">
                            <div class="rcf-legend-title">Learning Progress and Achievement</div>
                            <div class="rcf-legend-row rcf-legend-row--achievement rcf-legend-row--head"><span>Descriptors</span><span>Grading Scale</span><span>Remarks</span></div>
                            <div class="rcf-legend-row rcf-legend-row--achievement"><span>Outstanding</span><span>90 - 100</span><span>Passed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--achievement"><span>Very Satisfactory</span><span>85 - 89</span><span>Passed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--achievement"><span>Satisfactory</span><span>80 - 84</span><span>Passed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--achievement"><span>Fairly Satisfactory</span><span>75 - 79</span><span>Passed</span></div>
                            <div class="rcf-legend-row rcf-legend-row--achievement"><span>Did Not Meet Expectations</span><span>below 75</span><span>Failed</span></div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    @endif

    <style>
        .rcf-paper {
            width: min(1160px, 100%);
            border: 1.5px solid #111;
            border-radius: 0;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.16);
            font-family: "Times New Roman", Times, serif;
            color: #111;
        }

        .rcf-side {
            min-height: 760px;
            padding: 14px 14px 12px;
        }

        .rcf-title-main {
            text-transform: uppercase;
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 8px;
        }

        .rcf-attendance-table,
        .rcf-semester-table,
        .rcf-values-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .rcf-attendance-table th,
        .rcf-attendance-table td,
        .rcf-semester-table th,
        .rcf-semester-table td,
        .rcf-values-table th,
        .rcf-values-table td {
            border: 1px solid #222;
            padding: 4px 5px;
            text-align: center;
            vertical-align: middle;
        }

        .rcf-attendance-table td:first-child,
        .rcf-semester-table td.subject {
            text-align: left;
            font-weight: 600;
        }

        .rcf-signature-block {
            margin-top: 18px;
            padding-top: 6px;
        }

        .rcf-sem-label {
            font-size: 26px;
            font-weight: 600;
            margin: 16px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .rcf-sign-row {
            display: grid;
            grid-template-columns: 88px 1fr;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .rcf-sign-row .line {
            border-bottom: 1px solid #111;
            height: 18px;
        }

        .rcf-cover-topline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            margin-bottom: 8px;
            gap: 10px;
            text-transform: uppercase;
        }

        .rcf-form-code {
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .rcf-lrn {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 700;
        }

        .rcf-lrn span {
            display: inline-block;
            border-bottom: 1px solid #111;
            min-width: 220px;
            padding-bottom: 1px;
        }

        .rcf-header-logos {
            display: grid;
            grid-template-columns: 68px 1fr 68px;
            align-items: start;
            gap: 10px;
            margin-bottom: 8px;
        }

        .rcf-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
        }

        .rcf-logo--faded {
            filter: grayscale(100%);
            opacity: 0.62;
        }

        .rcf-school-head {
            text-align: center;
        }

        .rcf-rp {
            font-size: 12px;
            line-height: 1.2;
        }

        .rcf-school-name {
            margin-top: 3px;
            font-size: 34px;
            line-height: 1.05;
            font-weight: 700;
            text-transform: uppercase;
        }

        .rcf-school-city {
            font-size: 15px;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .rcf-identity-grid {
            margin-top: 8px;
        }

        .rcf-id-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 6px;
        }

        .rcf-id-row--single {
            grid-template-columns: 1fr;
        }

        .rcf-id-line {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
        }

        .rcf-id-line label {
            font-weight: 700;
            white-space: nowrap;
        }

        .rcf-id-line span {
            flex: 1;
            border-bottom: 1px solid #111;
            min-height: 17px;
            display: inline-flex;
            align-items: flex-end;
            padding-bottom: 1px;
            font-size: 14px;
            font-weight: 600;
        }

        .rcf-parent-note {
            margin-top: 8px;
            font-size: 13px;
            line-height: 1.4;
        }

        .rcf-parent-note-title {
            font-weight: 700;
            margin-bottom: 2px;
        }

        .rcf-parent-note p {
            margin: 4px 0;
            text-align: justify;
        }

        .rcf-sign-pair {
            margin-top: 10px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .rcf-sign-box {
            text-align: center;
            font-size: 12px;
        }

        .rcf-sign-line {
            border-bottom: 1px solid #111;
            min-height: 20px;
            font-weight: 700;
            text-transform: uppercase;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 2px;
        }

        .rcf-sign-role {
            margin-top: 3px;
        }

        .rcf-transfer-box {
            margin-top: 8px;
            border-top: 1px solid #111;
            padding-top: 6px;
            font-size: 12px;
        }

        .rcf-transfer-title {
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 4px;
        }

        .rcf-transfer-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 4px;
        }

        .rcf-transfer-row .line {
            border-bottom: 1px solid #111;
            min-width: 115px;
            height: 16px;
            display: inline-block;
        }

        .rcf-transfer-row .line--wide {
            flex: 1;
            min-width: 140px;
        }

        .rcf-transfer-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 6px;
            gap: 12px;
            font-size: 12px;
        }

        .rcf-transfer-signatures > div {
            width: 48%;
            text-align: center;
        }

        .rcf-transfer-signatures .line {
            border-bottom: 1px solid #111;
            height: 18px;
            margin-bottom: 2px;
        }

        .rcf-transfer-signatures--single {
            justify-content: flex-end;
        }

        .rcf-transfer-signatures--single > div {
            width: 48%;
        }

        .rcf-semester-box + .rcf-semester-box {
            margin-top: 10px;
        }

        .rcf-semester-title {
            font-size: 15px;
            margin: 2px 0 4px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .rcf-semester-table th {
            font-weight: 700;
            text-transform: none;
        }

        .rcf-semester-table td {
            height: 22px;
        }

        .rcf-semester-table tr.summary td {
            font-weight: 700;
        }

        .rcf-semester-table tr.modality td {
            font-size: 10.5px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .rcf-values-table th {
            font-weight: 700;
        }

        .rcf-values-table td.core {
            font-weight: 700;
            text-align: left;
            vertical-align: top;
        }

        .rcf-values-table td.statement {
            text-align: left;
            vertical-align: top;
            font-size: 10.5px;
            line-height: 1.2;
        }

        .rcf-legend-wrap {
            margin-top: 8px;
            display: grid;
            gap: 6px;
            grid-template-columns: 1fr;
            font-size: 11px;
            line-height: 1.2;
        }

        .rcf-legend-block {
            padding: 0;
        }

        .rcf-legend-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 3px;
            text-align: left;
        }

        .rcf-legend-row {
            display: grid;
            gap: 8px;
            margin-bottom: 1px;
            align-items: start;
        }

        .rcf-legend-row--values {
            grid-template-columns: 72px 1fr;
        }

        .rcf-legend-row--achievement {
            grid-template-columns: 1.35fr 0.85fr 0.65fr;
        }

        .rcf-legend-row--head {
            font-weight: 700;
        }

        @media (max-width: 1300px) {
            .rcf-school-name {
                font-size: 28px;
            }

            .rcf-cover-topline {
                font-size: 16px;
            }
        }

        @media (max-width: 1120px) {
            .rcf-paper {
                grid-template-columns: 1fr;
            }

            .rc-fold {
                display: none;
            }

            .rcf-side {
                min-height: auto;
            }

            .rcf-legend-wrap,
            .rcf-sign-pair,
            .rcf-id-row {
                grid-template-columns: 1fr;
            }

            .rcf-transfer-signatures,
            .rcf-transfer-signatures--single {
                justify-content: stretch;
            }

            .rcf-transfer-signatures > div,
            .rcf-transfer-signatures--single > div {
                width: 100%;
            }
        }

        @media print {
            .rcf-paper {
                width: 100%;
                box-shadow: none;
                border: 1px solid #000;
            }

            .rcf-side {
                min-height: auto;
            }

            .rcf-school-name {
                font-size: 30px;
            }
        }
    </style>

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
