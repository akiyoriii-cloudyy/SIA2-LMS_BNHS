@extends('layouts.app')

@section('content')
    <div class="page-head">
        <div>
            <h1>Dashboard</h1>
            <p class="muted">Welcome back. Use the sidebar to navigate the LMS modules.</p>
        </div>
        @auth
            <div class="badge" title="Signed in">
                <span class="dot"></span>
                {{ auth()->user()->name }}
            </div>
        @endauth
    </div>

    <div class="card card--hero">
        <div class="muted" style="font-weight:800;">Quick Links</div>
        <div style="display:flex; flex-wrap: wrap; gap: 10px; margin-top: 10px;">
            @foreach ($quickLinks as $link)
                <a class="btn" href="{{ route($link['route']) }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    </div>

    <div class="grid-3">
        <div class="card stat">
            <div class="k">Students</div>
            <div class="v">{{ number_format($stats['students'] ?? 0) }}</div>
        </div>
        <div class="card stat">
            <div class="k">Courses</div>
            <div class="v">{{ number_format($stats['courses'] ?? 0) }}</div>
        </div>
        <div class="card stat">
            <div class="k">School Years</div>
            <div class="v">{{ number_format($stats['school_years'] ?? 0) }}</div>
        </div>
    </div>
@endsection
