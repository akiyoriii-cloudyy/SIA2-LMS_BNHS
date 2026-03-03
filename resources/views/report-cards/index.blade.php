@extends('layouts.app')

@section('content')
    @php
        $focusValues = request()->query('focus') === 'values';
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">DepEd Report Card</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('gradebook.index') }}">Compute All</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="page-head page-head--dash" style="margin-bottom: 14px;">
        <div>
            <h1>Report Card</h1>
            <div class="crumbs muted">DepEd Form 138 — SHS</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('report-cards.index') }}">
            <div class="grid-4" style="align-items: end;">
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
                    <label class="muted" style="font-size: 12px; font-weight: 800;">Level</label>
                    <select name="grade_level" onchange="this.form.submit()">
                        @foreach ($gradeLevels as $gradeLevel)
                            <option value="{{ $gradeLevel }}" @selected($selectedGradeLevel === (int) $gradeLevel)>
                                Grade {{ $gradeLevel }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="muted" style="font-size: 12px; font-weight: 800;">Section</label>
                    <select name="section_id">
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected($selectedSection === $section->id)>
                                {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <button class="btn" type="submit" style="width: 100%;">Load Students</button>
                </div>
            </div>
        </form>
    </div>

    @if ($focusValues)
        <div class="alert">Open a student and use the Observed Values tab to encode AO/SO/RO/NO ratings.</div>
    @endif

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>General Average</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $index => $enrollment)
                        <tr>
                            <td>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $enrollment->student->full_name }}</td>
                            <td>{{ $enrollment->reportCard?->general_average !== null ? number_format($enrollment->reportCard->general_average, 2) : 'Incomplete' }}</td>
                            <td>
                                <div style="display: inline-flex; gap: 8px; flex-wrap: wrap;">
                                    <a class="btn" href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'outside']) }}">Open</a>
                                    <a class="btn {{ $focusValues ? 'btn-primary' : 'btn-outline' }}" href="{{ route('report-cards.show', [$enrollment->id, 'page' => 'values']) }}">Observed Values</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
