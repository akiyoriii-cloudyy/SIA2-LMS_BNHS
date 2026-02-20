@extends('layouts.app')

@section('content')
    <div class="page-head page-head--dash">
        <div>
            <h1>Report Card</h1>
            <div class="crumbs muted">Grading / Report Card</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('report-cards.index') }}">
            <div class="grid-3" style="align-items: end;">
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
                    <button class="btn" type="submit" style="width: 100%;">Load Students</button>
                </div>
            </div>
        </form>
    </div>

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
                                <a class="btn" href="{{ route('report-cards.show', $enrollment) }}">Open</a>
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
