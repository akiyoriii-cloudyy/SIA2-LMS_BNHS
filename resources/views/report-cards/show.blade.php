@extends('layouts.app')

@section('title', 'Report Card')

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
        $isPromoted = $generalAverage !== null && (float) $generalAverage >= 75;
    @endphp

    <div class="page-head page-head--dash no-print">
        <div>
            <h1>Report Card</h1>
            <div class="crumbs muted">Grading / Report Card</div>
        </div>
        <div style="display:flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <select class="compact-select" onchange="if (this.value) { window.location.href = '{{ route('report-cards.show', '__ID__') }}'.replace('__ID__', this.value); }">
                @foreach ($peerEnrollments as $peer)
                    <option value="{{ $peer->id }}" @selected($peer->id === $enrollment->id)>{{ $peer->student?->full_name }}</option>
                @endforeach
            </select>
            <button class="btn" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="report-card-shell">
        <div class="report-card-frame">
            <div class="report-card-paper">
                <div class="rc-head">
                    <div class="rc-head-line">DEPARTMENT OF EDUCATION — REGION XI</div>
                    <div class="rc-head-sub">Batasang Pambansa National High School · Senior High School</div>
                    <div class="rc-title">Learner's Progress Report Card</div>
                    <div class="rc-sy">School Year {{ $schoolYear->name }}</div>
                </div>

                <div class="rc-divider"></div>

                <div class="rc-meta">
                    <div class="rc-field">
                        <div class="rc-label">Learner's Name</div>
                        <div class="rc-value">{{ $student->full_name }}</div>
                    </div>
                    <div class="rc-field">
                        <div class="rc-label">LRN</div>
                        <div class="rc-value">{{ $student->lrn ?? '—' }}</div>
                    </div>

                    <div class="rc-field">
                        <div class="rc-label">Grade &amp; Section</div>
                        <div class="rc-value">Grade {{ $section->grade_level }} — {{ $section->name }}</div>
                    </div>
                    <div class="rc-field">
                        <div class="rc-label">School Year</div>
                        <div class="rc-value">{{ $schoolYear->name }}</div>
                    </div>

                    <div class="rc-field">
                        <div class="rc-label">Adviser</div>
                        <div class="rc-value">{{ $adviserName ? 'Teacher '.$adviserName : '—' }}</div>
                    </div>
                    <div class="rc-field">
                        <div class="rc-label">Date Printed</div>
                        <div class="rc-value">{{ now()->format('F d, Y') }}</div>
                    </div>
                </div>

                <div class="rc-table">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Q1</th>
                                <th>Q2</th>
                                <th>Q3</th>
                                <th>Q4</th>
                                <th>Final Avg ← auto</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $final = $item->final_grade;
                                    $remark = $final === null ? '—' : ((float) $final >= 75 ? 'Passed' : 'Failed');
                                    $fmtQ = fn ($v) => $v === null ? '—' : number_format((float) $v, 0);
                                @endphp
                                <tr>
                                    <td>{{ $item->subjectAssignment?->subject?->title ?? '—' }}</td>
                                    <td>{{ $fmtQ($item->q1) }}</td>
                                    <td>{{ $fmtQ($item->q2) }}</td>
                                    <td>{{ $fmtQ($item->q3) }}</td>
                                    <td>{{ $fmtQ($item->q4) }}</td>
                                    <td><span class="chip">{{ $final === null ? '—' : number_format((float) $final, 1) }}</span></td>
                                    <td>{{ $remark }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="muted">No report card data available yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="rc-ga">
                    <div class="rc-ga-label">GENERAL AVERAGE (auto-computed)</div>
                    <div class="rc-ga-value">{{ $generalAverage === null ? '—' : number_format((float) $generalAverage, 1) }}</div>
                </div>

                <div class="rc-promo">
                    <span class="rc-promo-icon {{ $isPromoted ? 'rc-promo-icon--ok' : 'rc-promo-icon--warn' }}">{{ $isPromoted ? '✓' : '!' }}</span>
                    <div>
                        <strong>{{ $isPromoted ? 'PROMOTED' : 'INCOMPLETE' }}</strong>
                        — Learner has {{ $isPromoted ? 'satisfactorily completed' : 'not yet completed' }} all required learning competencies for Grade {{ $section->grade_level }}.
                    </div>
                </div>

                <div class="rc-sign">
                    <div class="rc-sign-cell">
                        <div class="rc-sign-line"></div>
                        <div class="rc-sign-label">Class Adviser</div>
                    </div>
                    <div class="rc-sign-cell">
                        <div class="rc-sign-line"></div>
                        <div class="rc-sign-label">Parent / Guardian</div>
                    </div>
                    <div class="rc-sign-cell">
                        <div class="rc-sign-line"></div>
                        <div class="rc-sign-label">Principal</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
