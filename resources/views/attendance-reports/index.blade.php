@extends('layouts.app')

@section('title', 'Monthly Attendance Reports')

@section('content')
    <div class="dash-topbar no-print">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Attendance Reports</span>
        </div>
        <div class="dash-topbar-actions">
        </div>
    </div>

    <div class="page-head page-head--dash" style="margin-bottom: 14px;">
        <div>
            <h1>Monthly Attendance Reports</h1>
            <div class="crumbs muted">Generate, email, edit, and print section monthly absence summaries</div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error">{{ session('error') }}</div>
    @endif

    <div class="card">
        <h2>Generate New Report</h2>
        <p class="muted" style="margin-bottom: 14px;">
            Pulls data from daily attendance records for the selected calendar month. After generating, you can adjust counts and remarks, send email, and print.
        </p>
        <form method="POST" action="{{ route('attendance-reports.store') }}">
            @csrf
            <div class="grid-4" style="align-items: end;">
                <div>
                    <label>School Year</label>
                    <select name="school_year_id" required>
                        @foreach ($schoolYears as $schoolYear)
                            <option value="{{ $schoolYear->id }}" @selected($selectedSchoolYear === $schoolYear->id)>
                                {{ $schoolYear->name }}{{ $schoolYear->is_active ? ' (Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Section</label>
                    <select name="section_id" required>
                        @forelse ($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }} (Grade {{ $section->grade_level }})</option>
                        @empty
                            <option value="">No assigned sections</option>
                        @endforelse
                    </select>
                </div>
                <div>
                    <label>Month</label>
                    <select name="report_month" required>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected($defaultMonth === $m)>{{ \Carbon\Carbon::create(null, $m, 1)->format('F') }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Year</label>
                    <input type="number" name="report_year" value="{{ $defaultYear }}" min="2020" max="2100" required>
                </div>
            </div>
            <br>
            <label style="display:flex;align-items:center;gap:8px;margin:12px 0;">
                <input type="checkbox" name="send_email" value="1">
                <span>Also email report to me after generating (links to this dashboard for view &amp; print)</span>
            </label>
            <button class="btn btn-primary" type="submit" @disabled($sections->isEmpty())>Generate Report</button>
        </form>
    </div>

    <div class="card">
        <h2>Saved Reports</h2>
        <form method="GET" action="{{ route('attendance-reports.index') }}" class="grid-4" style="align-items: end; margin-bottom: 16px;">
            <div>
                <label>Filter School Year</label>
                <select name="school_year_id" onchange="this.form.submit()">
                    @foreach ($schoolYears as $schoolYear)
                        <option value="{{ $schoolYear->id }}" @selected($selectedSchoolYear === $schoolYear->id)>
                            {{ $schoolYear->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Section</th>
                        <th>School Year</th>
                        <th>Students</th>
                        <th>Total Absences</th>
                        <th>Status</th>
                        <th>Email</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td>{{ $report->periodLabel() }}</td>
                            <td>{{ $report->section?->name ?? '—' }}</td>
                            <td>{{ $report->schoolYear?->name ?? '—' }}</td>
                            <td>{{ $report->lines_count }}</td>
                            <td>{{ (int) ($report->total_absent_days ?? 0) }}</td>
                            <td>
                                @if ($report->isSent())
                                    <span class="badge badge-success">Sent</span>
                                @else
                                    <span class="badge">Draft</span>
                                @endif
                            </td>
                            <td>{{ $report->emailed_at?->format('M j, Y g:i A') ?? '—' }}</td>
                            <td style="white-space:nowrap;">
                                <a class="btn btn-sm" href="{{ route('attendance-reports.show', $report) }}">Open</a>
                                <a class="btn btn-sm btn-outline" href="{{ route('attendance-reports.print', $report) }}" target="_blank" rel="noopener">Print</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted">No reports yet. Generate one above.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $reports->links() }}
    </div>
@endsection
