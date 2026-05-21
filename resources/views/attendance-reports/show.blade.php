@extends('layouts.app')

@section('title', 'Monthly Attendance Report — ' . $report->periodLabel())

@section('content')
    @php
        $section = $report->section;
        $schoolYear = $report->schoolYear;
        $totalAbsent = (int) $report->lines->sum('absent_days');
        $printUrl = $report->printUrl();
        $webUrl = $report->webUrl();
    @endphp

    <div class="dash-topbar amr-no-print">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <a href="{{ route('attendance-reports.index', ['school_year_id' => $report->school_year_id]) }}" class="dash-topbar-bc">Attendance Reports</a>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">{{ $report->periodLabel() }}</span>
        </div>
        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('attendance-reports.index') }}">All Reports</a>
            <a class="btn btn-outline btn-sm" href="{{ $printUrl }}" target="_blank" rel="noopener">Print View</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    @if ($openedFromEmail ?? false)
        <div class="alert amr-no-print" style="border-left:4px solid #0b1f44;">
            You opened this report from your email. It is linked to BNHS LMS — edit below, save, then print. Report #{{ $report->id }}.
        </div>
    @endif

    @if (session('success'))
        <div class="alert amr-no-print">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-error amr-no-print">{{ session('error') }}</div>
    @endif

    <div class="card amr-print-block">
        <div class="amr-header-grid">
            <div>
                <h1 style="margin:0 0 6px 0;">Monthly Attendance Report</h1>
                <p class="muted" style="margin:0;">Section: <strong>{{ $section?->name ?? '—' }}</strong> · Grade {{ $section?->grade_level ?? '—' }}</p>
                <p class="muted" style="margin:4px 0 0 0;">School Year: <strong>{{ $schoolYear?->name ?? '—' }}</strong> · Period: <strong>{{ $report->periodLabel() }}</strong></p>
                <p class="muted amr-print-only-meta" style="margin:4px 0 0 0;display:none;">Report #{{ $report->id }} · Synced with web &amp; mobile daily attendance</p>
            </div>
            <div class="amr-meta-box amr-no-print">
                <div><span class="muted">Status</span> {{ $report->isSent() ? 'Sent via email' : 'Draft' }}</div>
                @if ($report->emailed_at)
                    <div><span class="muted">Last emailed</span> {{ $report->emailed_at->format('M j, Y g:i A') }}</div>
                @endif
                <div><span class="muted">School days (section)</span> {{ $report->school_days_total }}</div>
                <div><span class="muted">Total absences</span> <strong>{{ $totalAbsent }}</strong></div>
                <div><span class="muted">LMS link</span> <a href="{{ $webUrl }}">#{{ $report->id }}</a></div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('attendance-reports.update', $report) }}" class="amr-no-print">
        @csrf
        @method('PUT')

        <div class="card">
            <h2>Report Notes</h2>
            <textarea name="notes" rows="2" placeholder="Optional notes for this monthly report">{{ old('notes', $report->notes) }}</textarea>
            <div style="margin-top: 12px;">
                <label>School days (month total for section)</label>
                <input type="number" name="school_days_total" min="0" max="31" value="{{ old('school_days_total', $report->school_days_total) }}" style="max-width: 120px;">
            </div>
        </div>

        <div class="card amr-print-block">
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:12px;">
                <h2 style="margin:0;">Student Attendance — {{ $report->periodLabel() }}</h2>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                    <a class="btn btn-outline" href="{{ $printUrl }}" target="_blank" rel="noopener">Print View</a>
                    <button class="btn btn-primary" type="button" onclick="window.print()">Print</button>
                </div>
            </div>
            <p class="muted amr-no-print">Counts come from daily attendance (web + mobile). Edit here before emailing or printing — daily records are not changed.</p>

            <div class="table-wrap">
                <table class="table amr-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>LRN</th>
                            <th>School Days</th>
                            <th>Present</th>
                            <th>Late</th>
                            <th>Excused</th>
                            <th>Absent</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report->lines as $index => $line)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $line->student_name }}</td>
                                <td>{{ $line->lrn ?? '—' }}</td>
                                <td><input type="number" name="lines[{{ $index }}][school_days]" min="0" max="31" value="{{ old('lines.'.$index.'.school_days', $line->school_days) }}" class="amr-num-input"></td>
                                <td><input type="number" name="lines[{{ $index }}][present_days]" min="0" max="31" value="{{ old('lines.'.$index.'.present_days', $line->present_days) }}" class="amr-num-input"></td>
                                <td><input type="number" name="lines[{{ $index }}][late_days]" min="0" max="31" value="{{ old('lines.'.$index.'.late_days', $line->late_days) }}" class="amr-num-input"></td>
                                <td><input type="number" name="lines[{{ $index }}][excused_days]" min="0" max="31" value="{{ old('lines.'.$index.'.excused_days', $line->excused_days) }}" class="amr-num-input"></td>
                                <td><input type="number" name="lines[{{ $index }}][absent_days]" min="0" max="31" value="{{ old('lines.'.$index.'.absent_days', $line->absent_days) }}" class="amr-num-input amr-absent-input"></td>
                                <td>
                                    <input type="hidden" name="lines[{{ $index }}][id]" value="{{ $line->id }}">
                                    <input type="text" name="lines[{{ $index }}][remarks]" value="{{ old('lines.'.$index.'.remarks', $line->remarks) }}" placeholder="Optional">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    {{-- Print-only table (saved DB values) --}}
    <div class="card amr-print-table-only">
        @if ($report->notes)
            <p><strong>Notes:</strong> {{ $report->notes }}</p>
        @endif
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>LRN</th>
                        <th>School Days</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Excused</th>
                        <th>Absent</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report->lines as $index => $line)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $line->student_name }}</td>
                            <td>{{ $line->lrn ?? '—' }}</td>
                            <td>{{ $line->school_days }}</td>
                            <td>{{ $line->present_days }}</td>
                            <td>{{ $line->late_days }}</td>
                            <td>{{ $line->excused_days }}</td>
                            <td><strong>{{ $line->absent_days }}</strong></td>
                            <td>{{ $line->remarks ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card amr-no-print">
        <h2>Email &amp; Sync</h2>
        <p class="muted" style="margin-bottom:12px;">
            <strong>Web ↔ Email ↔ Mobile:</strong> RFID/mobile sync writes to the same <code>attendance_records</code> table as the web.
            This monthly report reads those records. Email sends this table plus links back here and to the print page.
        </p>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <form method="POST" action="{{ route('attendance-reports.send-email', $report) }}" onsubmit="return confirm('Send report #{{ $report->id }} to {{ auth()->user()->email }}?');">
                @csrf
                <button class="btn btn-primary" type="submit">Send to My Email</button>
            </form>
            <a class="btn btn-outline" href="{{ $printUrl }}" target="_blank" rel="noopener">Open Print Page</a>
            <form method="POST" action="{{ route('attendance-reports.refresh', $report) }}" onsubmit="return confirm('Reload from daily attendance? Manual edits to counts will be overwritten.');">
                @csrf
                <button class="btn btn-outline" type="submit">Refresh from Daily Records</button>
            </form>
        </div>
        <p class="muted" style="margin-top:10px;">
            SMTP: <code>PHPM_MAIL_*</code> in <code>.env</code>. After email, check <strong>Notifications</strong> (bell icon) for a quick link to this report.
        </p>
    </div>

    <style>
        .amr-header-grid { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .amr-meta-box { font-size: 14px; line-height: 1.6; }
        .amr-num-input { width: 56px; padding: 4px 6px; text-align: center; }
        .amr-absent-input { font-weight: 700; }
        .amr-print-table-only { display: none; }
        @media print {
            .sidebar, .topbar, .admin-strip-desktop, .amr-no-print,
            form.amr-no-print, nav, .toggle-btn { display: none !important; }
            .app-shell { display: block !important; }
            .main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
            .container { max-width: 100% !important; padding: 0 !important; }
            .amr-print-block form, .amr-print-block .amr-num-input,
            .amr-print-block input[type="text"] { display: none !important; }
            .amr-print-table-only { display: block !important; }
            .amr-print-only-meta { display: block !important; }
            .card { box-shadow: none !important; border: 1px solid #ccc !important; break-inside: avoid; }
        }
    </style>
@endsection
