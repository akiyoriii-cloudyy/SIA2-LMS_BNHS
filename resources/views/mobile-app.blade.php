@extends('layouts.app')

@section('title', 'Mobile App')

@section('content')
    <div class="page-head">
        <div>
            <h1>Mobile App</h1>
            <div class="crumbs muted">Attendance / Mobile App</div>
        </div>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <a class="btn" href="{{ asset('mobile-attendance.html') }}" target="_blank" rel="noreferrer" style="width:auto;">
                Open in new tab
            </a>
        </div>
    </div>

    <div class="phone-preview-shell">
        <div class="phone-frame" aria-label="Mobile app preview">
            <iframe
                class="phone-iframe"
                src="{{ asset('mobile-attendance.html') }}"
                title="EduTrack Mobile Attendance"
                loading="lazy"
            ></iframe>
        </div>
    </div>
@endsection

