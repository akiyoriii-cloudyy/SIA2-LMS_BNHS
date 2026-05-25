@extends('layouts.print')

@section('title', 'Monthly Attendance — ' . $report->periodLabel())

@section('content')
    @php
        $section = $report->section;
        $schoolYear = $report->schoolYear;
        $totalAbsent = (int) $report->lines->sum('absent_days');
    @endphp

    <div class="print-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
        <a class="btn btn-outline" href="{{ $report->webUrl() }}">Back to Report</a>
    </div>

    <div class="print-doc-header">
        <h1>Monthly Attendance Report</h1>
        <div class="print-doc-meta">
            <div><strong>Section:</strong> {{ $section?->name ?? '—' }} (Grade {{ $section?->grade_level ?? '—' }})</div>
            <div><strong>School Year:</strong> {{ $schoolYear?->name ?? '—' }}</div>
            <div><strong>Period:</strong> {{ $report->periodLabel() }}</div>
            <div><strong>School Days (section):</strong> {{ $report->school_days_total }} · <strong>Total Absences:</strong> {{ $totalAbsent }}</div>
            <div><strong>Report ID:</strong> #{{ $report->id }} · <strong>Generated:</strong> {{ $report->generated_at?->format('M j, Y g:i A') ?? '—' }}</div>
            @if ($report->emailed_at)
                <div><strong>Emailed:</strong> {{ $report->emailed_at->format('M j, Y g:i A') }}</div>
            @endif
        </div>
    </div>

    @if ($report->notes)
        <p><strong>Notes:</strong> {{ $report->notes }}</p>
    @endif

    <table class="print-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Student</th>
                <th>LRN</th>
                <th class="num">School Days</th>
                <th class="num">Present</th>
                <th class="num">Late</th>
                <th class="num">Excused</th>
                <th class="num">Absent</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($report->lines as $index => $line)
                <tr>
                    <td class="num">{{ $index + 1 }}</td>
                    <td>{{ $line->student_name }}</td>
                    <td>{{ $line->lrn ?? '—' }}</td>
                    <td class="num">{{ $line->school_days }}</td>
                    <td class="num">{{ $line->present_days }}</td>
                    <td class="num">{{ $line->late_days }}</td>
                    <td class="num">{{ $line->excused_days }}</td>
                    <td class="num absent">{{ $line->absent_days }}</td>
                    <td>{{ $line->remarks ?? '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p style="margin-top:24px;font-size:12px;color:#6b7280;">
        Printed from BNHS LMS · Same data as mobile app and email report #{{ $report->id }}.
    </p>
@endsection
