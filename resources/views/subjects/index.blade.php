@extends('layouts.app')

@section('content')
    @php
        $s = $stats ?? [];
        $total = (int) ($s['total'] ?? 0);
        $filtered = (int) ($s['filtered'] ?? $subjects->count());
        $q = (string) ($search ?? '');
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Subjects</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('gradebook.index') }}">Compute All</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="dash-kpi-grid dash-kpi-grid--2">
        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">📚</div></div>
            <div class="dash-kpi-value">{{ number_format($total) }}</div>
            <div class="dash-kpi-label">TOTAL SUBJECTS</div>
            <div class="dash-kpi-sub">Catalog</div>
        </div>

        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">🔎</div></div>
            <div class="dash-kpi-value">{{ number_format($filtered) }}</div>
            <div class="dash-kpi-label">RESULTS</div>
            <div class="dash-kpi-sub">{{ $q !== '' ? 'Filtered' : 'All subjects' }}</div>
        </div>
    </div>

    <div class="dash-panel">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">Subject Records</div>
                <div class="dash-panel-sub">Codes and titles used across Grade Entry and Report Cards</div>
            </div>
        </div>
        <div class="dash-panel-body">
            <form method="GET" action="{{ route('subjects.index') }}" class="records-filters">
                <div class="records-filter-row">
                    <div class="records-filter records-filter--search" style="flex: 1 1 380px;">
                        <label class="records-label">Search</label>
                        <input type="text" name="q" placeholder="Code / title..." value="{{ $q }}">
                    </div>
                    <div class="records-filter records-filter--btn">
                        <button class="btn btn-sm" type="submit">Search</button>
                    </div>
                    @if ($q !== '')
                        <div class="records-filter records-filter--btn">
                            <a class="btn btn--ghost btn-sm" href="{{ route('subjects.index') }}">Clear</a>
                        </div>
                    @endif
                </div>
            </form>

            <div class="table-wrap" style="margin-top: 12px;">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 160px;">Code</th>
                            <th>Title</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $subject)
                            <tr>
                                <td style="font-family: 'JetBrains Mono', monospace; font-weight: 800;">{{ $subject->code }}</td>
                                <td style="font-weight: 800;">{{ $subject->title }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="muted">No subjects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

