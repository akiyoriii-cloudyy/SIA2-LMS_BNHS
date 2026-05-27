@extends('layouts.app')

@section('title', 'Academic Settings')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Academic Settings</span>
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
        <div class="dash-panel-hd"><div class="dash-panel-title">Academic Years</div></div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('admin.system.academic-years.store') }}">
                @csrf
                <div class="grid-3">
                    <div>
                        <label>Academic Year</label>
                        <input name="name" placeholder="e.g., 2025-2026" required>
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <label style="display:flex; gap:8px; align-items:center;"><input type="checkbox" name="is_active" value="1"> Set as Current Academic Year</label>
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <button class="btn btn-primary" type="submit" style="width:100%;">Create Academic Year</button>
                    </div>
                </div>
            </form>

            <div class="table-wrap" style="margin-top:12px;">
                <table class="table">
                    <thead><tr><th>Year</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($schoolYears as $year)
                            <tr>
                                <td>{{ $year->name }}</td>
                                <td>{{ $year->is_active ? 'Current' : 'Inactive' }}</td>
                                <td>{{ $year->created_at?->format('M d, Y') }}</td>
                                <td>
                                    @if(!$year->is_active)
                                        <form method="POST" action="{{ route('admin.system.academic-years.set-current', $year) }}">
                                            @csrf
                                            <button class="btn btn-outline btn-sm" type="submit">Set as Current</button>
                                        </form>
                                    @else
                                        <span class="btn btn-gold btn-sm" style="opacity:.85; pointer-events:none;">Currently Active</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">No academic years yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd"><div class="dash-panel-title">Semesters</div></div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('admin.system.semesters.store') }}">
                @csrf
                <div class="grid-3">
                    <div>
                        <label>Semester Name</label>
                        <input name="name" placeholder="e.g., First Semester" required>
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <label style="display:flex; gap:8px; align-items:center;"><input type="checkbox" name="is_current" value="1"> Set as Current Semester</label>
                    </div>
                    <div style="display:flex; align-items:flex-end;">
                        <button class="btn btn-primary" type="submit" style="width:100%;">Create Semester</button>
                    </div>
                </div>
            </form>

            <div class="table-wrap" style="margin-top:12px;">
                <table class="table">
                    <thead><tr><th>Semester</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($semesters as $semester)
                            <tr>
                                <td>{{ $semester->name }}</td>
                                <td>{{ $semester->is_current ? 'Current' : 'Inactive' }}</td>
                                <td>{{ $semester->created_at?->format('M d, Y') }}</td>
                                <td>
                                    @if(!$semester->is_current)
                                        <form method="POST" action="{{ route('admin.system.semesters.set-current', $semester) }}">
                                            @csrf
                                            <button class="btn btn-outline btn-sm" type="submit">Set as Current</button>
                                        </form>
                                    @else
                                        <span class="btn btn-gold btn-sm" style="opacity:.85; pointer-events:none;">Currently Active</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="muted">No semesters yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
