@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Quarter Grade Encoding</h2>
        <p>Encode Quiz, Assignment, and Exam once. Quarter grade, subject average, and report card are updated automatically.</p>

        <form method="GET" action="{{ route('gradebook.index') }}">
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
                <div>
                    <label>Subject</label>
                    <select name="subject_id">
                        @foreach ($subjects as $subject)
                            <option value="{{ $subject->id }}" @selected($selectedSubject === $subject->id)>
                                {{ $subject->code }} - {{ $subject->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Quarter</label>
                    <select name="quarter">
                        @for ($q = 1; $q <= 4; $q++)
                            <option value="{{ $q }}" @selected($quarter === $q)>Quarter {{ $q }}</option>
                        @endfor
                    </select>
                </div>
            </div>
            <br>
            <button class="btn" type="submit">Load Class</button>
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
        <form method="POST" action="{{ route('gradebook.store') }}">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYear }}">
            <input type="hidden" name="section_id" value="{{ $selectedSection }}">
            <input type="hidden" name="subject_id" value="{{ $selectedSubject }}">
            <input type="hidden" name="quarter" value="{{ $quarter }}">

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Quiz (30%)</th>
                            <th>Assignment (30%)</th>
                            <th>Exam (40%)</th>
                            <th>Quarter Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $index => $enrollment)
                            @php $grade = $existingGrades->get($enrollment->id); @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $enrollment->student->full_name }}</td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][quiz]" value="{{ old("grades.{$enrollment->id}.quiz", $grade?->quiz) }}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][assignment]" value="{{ old("grades.{$enrollment->id}.assignment", $grade?->assignment) }}">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" max="100" name="grades[{{ $enrollment->id }}][exam]" value="{{ old("grades.{$enrollment->id}.exam", $grade?->exam) }}">
                                </td>
                                <td class="text-right">{{ $grade?->quarter_grade !== null ? number_format($grade->quarter_grade, 2) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No enrolled students found for the selected school year and section.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <br>
            <button class="btn" type="submit">Save Grades</button>
        </form>
    </div>
@endsection
