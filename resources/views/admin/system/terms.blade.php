@extends('layouts.app')

@section('title', 'Manage Terms')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Manage Terms</span>
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
        <div class="dash-panel-hd"><div class="dash-panel-title">Create New Term</div></div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('admin.system.terms.store') }}">
                @csrf
                <div class="grid-3">
                    <div>
                        <label>Semester</label>
                        <select name="semester_id" required>
                            <option value="">Choose semester...</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ $semester->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Academic Year</label>
                        <select name="school_year_id" required>
                            <option value="">Choose academic year...</option>
                            @foreach($schoolYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>Term Number</label><input type="number" name="term_number" min="1" max="10" required></div>
                    <div><label>Term Name</label><input name="name" placeholder="e.g., Prelim, Midterm, Final" required></div>
                    <div><label>Start Date</label><input type="date" name="start_date"></div>
                    <div><label>End Date</label><input type="date" name="end_date"></div>
                </div>
                <div style="margin-top:10px; display:flex; gap:8px; align-items:center;">
                    <label style="display:flex; gap:8px; align-items:center;"><input type="checkbox" name="is_active" value="1"> Set as Active</label>
                    <button class="btn btn-primary" type="submit">Create Term</button>
                </div>
            </form>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd"><div class="dash-panel-title">Existing Terms</div></div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>Term Number</th><th>Term Name</th><th>Semester</th><th>Academic Year</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($terms as $index => $term)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $term->term_number }}</td>
                                <td>{{ $term->name }}</td>
                                <td>{{ $term->semester?->name ?? '-' }}</td>
                                <td>{{ $term->schoolYear?->name ?? '-' }}</td>
                                <td>{{ $term->is_active ? 'Active' : 'Inactive' }}</td>
                                <td>
                                    <details class="inline-details">
                                        <summary class="btn btn-outline btn-sm">Edit</summary>
                                        <div class="inline-panel">
                                            <form method="POST" action="{{ route('admin.system.terms.update', $term) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="grid-3">
                                                    <select name="semester_id" required>
                                                        @foreach($semesters as $semester)
                                                            <option value="{{ $semester->id }}" @selected($term->semester_id === $semester->id)>{{ $semester->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <select name="school_year_id" required>
                                                        @foreach($schoolYears as $year)
                                                            <option value="{{ $year->id }}" @selected($term->school_year_id === $year->id)>{{ $year->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="number" name="term_number" min="1" max="10" value="{{ $term->term_number }}" required>
                                                    <input name="name" value="{{ $term->name }}" required>
                                                    <input type="date" name="start_date" value="{{ optional($term->start_date)->format('Y-m-d') }}">
                                                    <input type="date" name="end_date" value="{{ optional($term->end_date)->format('Y-m-d') }}">
                                                </div>
                                                <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                                                    <label style="display:flex; gap:8px; align-items:center;"><input type="checkbox" name="is_active" value="1" @checked($term->is_active)> Set as Active</label>
                                                    <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </details>
                                    <form method="POST" action="{{ route('admin.system.terms.destroy', $term) }}" style="margin-top:6px;" onsubmit="return confirm('Delete this term?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">No terms yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
