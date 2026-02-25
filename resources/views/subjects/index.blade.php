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
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-kpi-grid dash-kpi-grid--2">
        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">SB</div></div>
            <div class="dash-kpi-value">{{ number_format($total) }}</div>
            <div class="dash-kpi-label">TOTAL SUBJECTS</div>
            <div class="dash-kpi-sub">Catalog</div>
        </div>

        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">SR</div></div>
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
            <details class="inline-details" style="margin-bottom: 12px;">
                <summary class="btn btn-gold btn-sm">Add subject</summary>
                <div class="inline-panel">
                    <form method="POST" action="{{ route('subjects.store') }}">
                        @csrf
                        <div class="inline-grid">
                            <input name="code" placeholder="Code (e.g., ENG11)" required>
                            <input name="title" placeholder="Title (e.g., Oral Communication)" required>
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </details>

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
                            <th style="width: 240px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($subjects as $subject)
                            <tr>
                                <td style="font-family: 'JetBrains Mono', monospace; font-weight: 800;">{{ $subject->code }}</td>
                                <td style="font-weight: 800;">{{ $subject->title }}</td>
                                <td>
                                    <div class="admin-actions">
                                        <details class="inline-details">
                                            <summary class="btn btn-outline btn-sm">Edit</summary>
                                            <div class="inline-panel">
                                                <form method="POST" action="{{ route('subjects.update', $subject) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="inline-grid">
                                                        <input name="code" value="{{ $subject->code }}" required>
                                                        <input name="title" value="{{ $subject->title }}" required>
                                                        <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </details>

                                        <form method="POST" action="{{ route('subjects.destroy', $subject) }}"
                                            onsubmit="return confirm('Delete this subject?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="muted">No subjects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

