@extends('layouts.app')

@section('content')
    <div class="page-head">
        <div>
            <h1>Students</h1>
            <p class="muted">Enrollment list aligned to school year + section.</p>
        </div>
        <div class="badge">
            <span class="dot"></span>
            {{ number_format($enrollments->count()) }} enrollments
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('students.index') }}" class="grid-3" style="align-items: end;">
            <div>
                <label class="muted" style="font-size: 12px; font-weight: 800;">School Year</label>
                <select name="school_year_id">
                    @foreach ($schoolYears as $sy)
                        <option value="{{ $sy->id }}" @selected($selectedSchoolYear === $sy->id)>{{ $sy->name }}{{ $sy->is_active ? ' (Active)' : '' }}</option>
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
                <button class="btn" type="submit" style="width: 100%;">Apply</button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Student</th>
                        <th>Sex</th>
                        <th>Section</th>
                        <th>Status</th>
                        <th>Guardians</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td>{{ $enrollment->student?->lrn ?? '-' }}</td>
                            <td>{{ $enrollment->student?->full_name ?? '-' }}</td>
                            <td>{{ $enrollment->student?->sex ?? '-' }}</td>
                            <td>Grade {{ $enrollment->section?->grade_level }} - {{ $enrollment->section?->name }}</td>
                            <td><span class="chip">{{ $enrollment->status }}</span></td>
                            <td>{{ number_format((int) ($enrollment->student?->guardians_count ?? 0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">No enrollments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

