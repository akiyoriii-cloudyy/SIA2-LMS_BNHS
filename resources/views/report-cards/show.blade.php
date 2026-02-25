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
        $fmtQ = fn ($v) => $v === null ? '—' : number_format((float) $v, 0);

        $age = $student?->date_of_birth ? $student->date_of_birth->age : null;
        $sex = $student?->sex ? ucfirst((string) $student->sex) : null;

        $months = $attendanceMonths ?? ['Jun','Jul','Aug','Sep','Oct','Nov','Dec','Jan','Feb','Mar'];
        $att = $attendanceSummary ?? [];

        $sumRow = function (string $key) use ($months, $att): int {
            $total = 0;
            foreach ($months as $m) {
                $total += (int) (($att[$m][$key] ?? 0));
            }
            return $total;
        };

        $page = request()->query('page', 'outside');
        $page = in_array($page, ['outside', 'inside'], true) ? $page : 'outside';
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
            <div class="rc-pro-title">DepEd Form 138 — SHS Report Card</div>
            <div class="rc-pro-sub">
                Booklet format: one sheet, folded. Outside = Cover &amp; Back. Inside = Grades &amp; Values.
                <span class="rc-badge">AUTO-FILLED</span>
            </div>
        </div>

        <div class="rc-pro-right no-print">
            <select class="compact-select"
                onchange="if (this.value) { window.location.href = '{{ route('report-cards.show', '__ID__') }}'.replace('__ID__', this.value) + '?page={{ $page }}'; }">
                @foreach ($peerEnrollments as $peer)
                    <option value="{{ $peer->id }}" @selected($peer->id === $enrollment->id)>{{ $peer->student?->full_name }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary btn-sm" type="button" id="print-both">Print Both Pages</button>
        </div>
    </div>

    <div class="rc-tabs no-print">
        <a class="rc-tab {{ $page === 'outside' ? 'active' : '' }}"
           href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'outside']) }}">
            Page 1 — Outside (Cover &amp; Back)
        </a>
        <a class="rc-tab {{ $page === 'inside' ? 'active' : '' }}"
           href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'inside']) }}">
            Page 2 — Inside (Grades &amp; Values)
        </a>
        <div class="rc-tip">Both pages print on ONE sheet — fold in half to form the booklet card</div>
    </div>

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
                        <div class="rc-rp">Region XII – SOCCSKSARGEN</div>
                        <div class="rc-rp"><strong>SCHOOLS DIVISION OF GENERAL SANTOS CITY</strong></div>
                    </div>
                    <div class="rc-form-code">DepEd FORM 138 — SHS</div>
                </div>

                <div class="rc-lines">
                    <div class="rc-line"><span>Bawing District</span><span class="hint">District</span></div>
                    <div class="rc-line"><span>PH Bawing National High School</span><span class="hint">School</span></div>
                </div>

                <div class="rc-meta-grid">
                    <div class="rc-meta-row">
                        <div><strong>Name:</strong> {{ $student->full_name }}</div>
                        <div><strong>Sex:</strong> {{ $sex ?? '—' }}</div>
                    </div>
                    <div class="rc-meta-row">
                        <div><strong>Age:</strong> {{ $age ?? '—' }}</div>
                        <div><strong>Section:</strong> {{ $section ? $section->name : '—' }}</div>
                    </div>
                    <div class="rc-meta-row">
                        <div><strong>Grade:</strong> {{ $section ? $section->grade_level : '—' }}</div>
                        <div><strong>School Year:</strong> {{ $schoolYear?->name ?? '—' }}</div>
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
                        <div class="sig-name">{{ $adviserName ? 'Ms. '.$adviserName : '—' }}</div>
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
                                <td>{{ $item->subjectAssignment?->subject?->title ?? '—' }}</td>
                                <td>{{ $fmtQ($item->q1) }}</td>
                                <td>{{ $fmtQ($item->q2) }}</td>
                                <td>{{ $fmtQ($item->q3) }}</td>
                                <td>{{ $fmtQ($item->q4) }}</td>
                                <td class="final">{{ $final === null ? '—' : number_format((float) $final, 0) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6">No report card items yet.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="ga">General Average for the Semester</td>
                            <td class="final">{{ $generalAverage === null ? '—' : number_format((float) $generalAverage, 0) }}</td>
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
                            <tr><td>Outstanding</td><td>90–100</td><td>Passed</td></tr>
                            <tr><td>Very Satisfactory</td><td>85–89</td><td>Passed</td></tr>
                            <tr><td>Satisfactory</td><td>80–84</td><td>Passed</td></tr>
                            <tr><td>Fairly Satisfactory</td><td>75–79</td><td>Passed</td></tr>
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
                        <tr>
                            <td rowspan="2"><strong>1. Maka-Diyos</strong></td>
                            <td>Expresses one's spiritual beliefs while respecting the spiritual beliefs of others</td>
                            <td>AO</td><td>AO</td><td>AO</td><td>SO</td>
                        </tr>
                        <tr>
                            <td>Shows adherence to ethical principles by upholding truth</td>
                            <td>AO</td><td>SO</td><td>AO</td><td>AO</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><strong>2. Makatao</strong></td>
                            <td>Is sensitive to individual, social, and cultural differences</td>
                            <td>AO</td><td>AO</td><td>AO</td><td>AO</td>
                        </tr>
                        <tr>
                            <td>Demonstrates contributions towards solidarity</td>
                            <td>SO</td><td>AO</td><td>SO</td><td>AO</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><strong>3. Maka-kalikasan</strong></td>
                            <td>Cares for the environment and utilizes resources wisely, judiciously, and economically</td>
                            <td>AO</td><td>AO</td><td>AO</td><td>AO</td>
                        </tr>
                        <tr>
                            <td>Demonstrates appropriate behavior in carrying out activities in school, community, and country</td>
                            <td>AO</td><td>AO</td><td>AO</td><td>AO</td>
                        </tr>
                        <tr>
                            <td rowspan="2"><strong>4. Makabansa</strong></td>
                            <td>Demonstrates pride in being a Filipino; exercises the rights and responsibilities of a Filipino citizen</td>
                            <td>AO</td><td>AO</td><td>SO</td><td>AO</td>
                        </tr>
                        <tr>
                            <td>Demonstrates appropriate behavior in carrying out activities in school, community, and country</td>
                            <td>AO</td><td>SO</td><td>AO</td><td>AO</td>
                        </tr>
                    </tbody>
                </table>

                <div class="rc-values-note">
                    <div><strong>Marking</strong> &nbsp; Non-numerical Rating</div>
                    <div><strong>AO</strong> Always Observed &nbsp;&nbsp; <strong>RO</strong> Rarely Observed</div>
                    <div><strong>SO</strong> Sometimes Observed &nbsp;&nbsp; <strong>NO</strong> Not Observed</div>
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
                            <tr><td>Outstanding</td><td>90–100</td><td>Passed</td></tr>
                            <tr><td>Very Satisfactory</td><td>85–89</td><td>Passed</td></tr>
                            <tr><td>Satisfactory</td><td>80–84</td><td>Passed</td></tr>
                            <tr><td>Fairly Satisfactory</td><td>75–79</td><td>Passed</td></tr>
                            <tr><td>Did Not Meet Expectations</td><td>Below 75</td><td>Failed</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

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

