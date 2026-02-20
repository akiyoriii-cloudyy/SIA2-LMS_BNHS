@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Report Card List</h2>
        <form method="GET" action="{{ route('report-cards.index') }}">
            <div class="grid-4">
                <div>
                    <label>School Year</label>
                    <select name="school_year_id">
                        @foreach ($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}" @selected($selectedSchoolYear === $schoolYear->id)>
                                {{ $schoolYear->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Section</label>
                    <select name="section_id">
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected($selectedSection === $section->id)>
                                Grade {{ $section->grade_level }} - {{ $section->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <br>
            <button class="btn" type="submit">Load Students</button>
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
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $enrollment->student->full_name }}</td>
                            <td>{{ $enrollment->reportCard?->general_average !== null ? number_format($enrollment->reportCard->general_average, 2) : 'Incomplete' }}</td>
                            <td>
                                <a class="btn" href="{{ route('report-cards.show', $enrollment) }}">View / Print</a>
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
