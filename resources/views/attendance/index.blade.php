@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>Attendance Monitoring</h2>
        <p>If a student reaches 5 absences within one week, SMS is sent automatically to guardian.</p>

        <form method="GET" action="{{ route('attendance.index') }}">
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
                    <label>Date</label>
                    <input type="date" name="attendance_date" value="{{ $attendanceDate }}">
                </div>
            </div>
            <br>
            <button class="btn" type="submit">Load</button>
        </form>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif

    <div class="card">
        <form method="POST" action="{{ route('attendance.store') }}">
            @csrf
            <input type="hidden" name="school_year_id" value="{{ $selectedSchoolYear }}">
            <input type="hidden" name="section_id" value="{{ $selectedSection }}">
            <input type="hidden" name="attendance_date" value="{{ $attendanceDate }}">

            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $index => $enrollment)
                        @php $record = $records->get($enrollment->id); @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $enrollment->student->full_name }}</td>
                            <td>
                                <select name="attendance[{{ $enrollment->id }}][status]">
                                    @foreach (['present', 'late', 'absent', 'excused'] as $status)
                                        <option value="{{ $status }}" @selected(($record->status ?? 'present') === $status)>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="text" name="attendance[{{ $enrollment->id }}][remarks]" value="{{ $record->remarks ?? '' }}">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No students found.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <br>
            <button class="btn" type="submit">Save Attendance</button>
        </form>
    </div>
@endsection

