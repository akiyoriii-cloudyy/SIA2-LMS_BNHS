@extends('layouts.app')

@section('title', 'System Management')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">System Management</span>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="dash-row-2">
        <div class="dash-panel">
            <div class="dash-panel-hd"><div class="dash-panel-title">Current Academic Year</div></div>
            <div class="dash-panel-body">
                <div style="font-size:30px; font-weight:900; color:#0d3b2b;">{{ $currentSchoolYear?->name ?? 'Not set' }}</div>
                <div class="muted" style="margin-top:6px;">Status: <strong>{{ $currentSchoolYear?->is_active ? 'Active' : 'Inactive' }}</strong></div>
                <a class="btn btn-primary btn-sm" style="margin-top:10px;" href="{{ route('admin.system.academic-settings') }}">Manage Academic Years</a>
            </div>
        </div>
        <div class="dash-panel">
            <div class="dash-panel-hd"><div class="dash-panel-title">Current Semester</div></div>
            <div class="dash-panel-body">
                <div style="font-size:30px; font-weight:900; color:#0d3b2b;">{{ $currentSemester?->name ?? 'Not set' }}</div>
                <div class="muted" style="margin-top:6px;">Status: <strong>{{ $currentSemester?->is_current ? 'Active' : 'Inactive' }}</strong></div>
                <a class="btn btn-primary btn-sm" style="margin-top:10px;" href="{{ route('admin.system.academic-settings') }}">Manage Semesters</a>
            </div>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd">
            <div class="dash-panel-title">System Management</div>
        </div>
        <div class="dash-panel-body">
            <div class="quick-actions" style="grid-template-columns: repeat(3, minmax(0,1fr));">
                <a class="qa-btn" href="{{ route('admin.system.strands') }}">
                    <span class="qa-icon">🏢</span>
                    <span><div class="qa-label">Manage Strands</div><div class="qa-sub">{{ (int) $strandsCount }} entries</div></span>
                </a>
                <a class="qa-btn" href="{{ route('admin.system.subjects') }}">
                    <span class="qa-icon">📚</span>
                    <span><div class="qa-label">Subject Management</div><div class="qa-sub">{{ (int) $subjectsCount }} entries</div></span>
                </a>
                <a class="qa-btn" href="{{ route('admin.system.terms') }}">
                    <span class="qa-icon">🗓️</span>
                    <span><div class="qa-label">Manage Terms</div><div class="qa-sub">{{ (int) $termsCount }} entries</div></span>
                </a>
            </div>
        </div>
    </div>
@endsection
