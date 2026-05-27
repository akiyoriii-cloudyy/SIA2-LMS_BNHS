@extends('layouts.app')

@section('title', 'Subject Management')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Subject Management</span>
        </div>
        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('admin.system.index') }}">Back to Dashboard</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-panel">
        <div class="dash-panel-hd"><div class="dash-panel-title">Assign Subject to Teacher</div></div>
        <div class="dash-panel-body">
            <div class="muted" style="margin-bottom:8px;">Teacher Assignment: assign a teacher to handle a specific subject.</div>
            <form method="POST" action="{{ route('admin.system.subjects.assign-teacher') }}">
                @csrf
                <div class="grid-3">
                    <div>
                        <label>School Year</label>
                        <select name="school_year_id" required>
                            <option value="">Choose school year...</option>
                            @foreach($schoolYears as $schoolYear)
                                <option value="{{ $schoolYear->id }}" @selected((int) $schoolYear->id === (int) ($activeSchoolYearId ?? 0))>
                                    {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Section</label>
                        <select name="section_id" required>
                            <option value="">Choose section...</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}">Grade {{ $section->grade_level }} - {{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Select Teacher</label>
                        <select name="teacher_id" required>
                            <option value="">Choose a teacher...</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->full_name }}{{ $teacher->user?->email ? ' ('.$teacher->user->email.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="grid-column: span 2;">
                        <label>Select Subject</label>
                        <select name="subject_id" required>
                            <option value="">Choose a subject...</option>
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}">{{ $subject->code }} - {{ $subject->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="display:flex; align-items:flex-end; grid-column: span 1;">
                        <button class="btn btn-primary" type="submit" style="width:100%;">Assign Subject to Teacher</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd"><div class="dash-panel-title">Create New Subject</div></div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('admin.system.subjects.store') }}">
                @csrf
                <div class="grid-3">
                    <div><label>Subject Code</label><input name="code" placeholder="e.g., MATH11" required></div>
                    <div><label>Subject Title</label><input name="title" placeholder="e.g., General Mathematics" required></div>
                    <div>
                        <label>Category</label>
                        <select name="category">
                            <option value="core">Core</option>
                            <option value="applied">Applied</option>
                            <option value="specialized">Specialized</option>
                        </select>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:10px;">Create Subject</button>
            </form>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd"><div class="dash-panel-title">Subjects Management</div></div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>Code</th><th>Subject Title</th><th>Category</th><th>Instructor</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($subjects as $index => $subject)
                            @php
                                $assign = $assignments->get($subject->id);
                                $teacherName = $assign?->teacher?->full_name ?: ($assign?->teacher?->user?->name ?? '-');
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $subject->code }}</td>
                                <td>{{ $subject->title }}</td>
                                <td>{{ ucfirst((string) $subject->category) }}</td>
                                <td>{{ $teacherName }}</td>
                                <td>{{ $subject->created_at?->format('M d, Y') }}</td>
                                <td>
                                    <details class="inline-details">
                                        <summary class="btn btn-outline btn-sm">Edit</summary>
                                        <div class="inline-panel">
                                            <form method="POST" action="{{ route('admin.system.subjects.update', $subject) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="grid-3">
                                                    <input name="code" value="{{ $subject->code }}" required>
                                                    <input name="title" value="{{ $subject->title }}" required>
                                                    <select name="category">
                                                        <option value="core" @selected($subject->category === 'core')>Core</option>
                                                        <option value="applied" @selected($subject->category === 'applied')>Applied</option>
                                                        <option value="specialized" @selected($subject->category === 'specialized')>Specialized</option>
                                                    </select>
                                                </div>
                                                <button class="btn btn-primary btn-sm" type="submit" style="margin-top:8px;">Save</button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">No subjects yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
