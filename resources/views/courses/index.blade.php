@extends('layouts.app')

@section('content')
    <div class="card">
        <h2>LMS Courses</h2>
        <p>Integrated course list from the same centralized school database.</p>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr>
                    <th>Course</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($courses as $course)
                    <tr>
                        <td>{{ $course->title }}</td>
                        <td>{{ $course->subject?->title }}</td>
                        <td>{{ $course->teacher?->full_name ?? 'N/A' }}</td>
                        <td>{{ $course->is_published ? 'Published' : 'Draft' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No courses found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

