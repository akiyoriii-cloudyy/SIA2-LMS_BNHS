<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Attendance Monthly Report Export</title>
    </head>
    <body>
        @php
            $section = $report->section;
            $schoolYear = $report->schoolYear;
            $totalAbsent = (int) $report->lines->sum('absent_days');
            $monthDate = \Carbon\Carbon::create((int) $report->report_year, (int) $report->report_month, 1);
            $coverageStart = $monthDate->copy()->startOfMonth()->format('M j');
            $coverageEnd = $monthDate->copy()->endOfMonth()->format('j, Y');
        @endphp

        <table border="0" cellpadding="4" cellspacing="0" style="border-collapse:collapse; font-family:Calibri, Arial, sans-serif; font-size:11pt; color:#111;">
            <tr>
                <td colspan="9" style="font-style:italic; color:#333; border-bottom:1px solid #c7c7c7;">
                    Printed from BNHS LMS - same data as web print view &amp; mobile sync.
                </td>
            </tr>
            <tr><td colspan="9">&nbsp;</td></tr>

            <tr>
                <td></td>
                <td colspan="7" style="text-align:center; font-size:18pt; font-weight:bold; border:1px solid #c7c7c7;">
                    Monthly Attendance Report
                </td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td colspan="7" style="text-align:center; border:1px solid #c7c7c7; border-top:0;">
                    School Year {{ $schoolYear?->name ?? '—' }}
                </td>
                <td></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Section</td>
                <td colspan="3">{{ $section?->name ?? '—' }} (Grade {{ $section?->grade_level ?? '—' }})</td>
                <td style="font-weight:bold; text-align:right;">Month</td>
                <td>{{ $monthDate->format('F') }}</td>
                <td style="font-weight:bold; text-align:right;">Year</td>
                <td>{{ $report->report_year }}</td>
                <td></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Coverage</td>
                <td colspan="3">{{ $coverageStart }} - {{ $coverageEnd }}</td>
                <td style="font-weight:bold; text-align:right;">Total Absences</td>
                <td>{{ $totalAbsent }}</td>
                <td style="font-weight:bold; text-align:right;">Report ID</td>
                <td>#{{ $report->id }}</td>
                <td></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Generated</td>
                <td colspan="3">{{ $report->generated_at?->format('M j, Y g:i A') ?? '—' }}</td>
                <td style="font-weight:bold; text-align:right;">School Days</td>
                <td>{{ $report->school_days_total }}</td>
                <td colspan="3"></td>
            </tr>

            <tr><td colspan="9">&nbsp;</td></tr>

            <tr style="background:#0b2a63; color:#ffffff; font-weight:bold;">
                <td style="border:1px solid #0b2a63; text-align:center;">#</td>
                <td style="border:1px solid #0b2a63;">Student</td>
                <td style="border:1px solid #0b2a63;">LRN</td>
                <td style="border:1px solid #0b2a63; text-align:center;">School Days</td>
                <td style="border:1px solid #0b2a63; text-align:center;">Present</td>
                <td style="border:1px solid #0b2a63; text-align:center;">Late</td>
                <td style="border:1px solid #0b2a63; text-align:center;">Excused</td>
                <td style="border:1px solid #0b2a63; text-align:center;">Absent</td>
                <td style="border:1px solid #0b2a63;">Remarks</td>
            </tr>
            @foreach ($report->lines as $index => $line)
                <tr>
                    <td style="border:1px solid #d3dbe8; text-align:center;">{{ $index + 1 }}</td>
                    <td style="border:1px solid #d3dbe8;">{{ $line->student_name }}</td>
                    <td style="border:1px solid #d3dbe8; mso-number-format:'\@';">{{ $line->lrn ?? '—' }}</td>
                    <td style="border:1px solid #d3dbe8; text-align:center;">{{ $line->school_days }}</td>
                    <td style="border:1px solid #d3dbe8; text-align:center;">{{ $line->present_days }}</td>
                    <td style="border:1px solid #d3dbe8; text-align:center;">{{ $line->late_days }}</td>
                    <td style="border:1px solid #d3dbe8; text-align:center;">{{ $line->excused_days }}</td>
                    <td style="border:1px solid #d3dbe8; text-align:center;">{{ $line->absent_days }}</td>
                    <td style="border:1px solid #d3dbe8;">{!! nl2br(e($line->remarks ?? '—')) !!}</td>
                </tr>
            @endforeach

            <tr><td colspan="9">&nbsp;</td></tr>
            <tr>
                <td colspan="9" style="font-style:italic; color:#4a5568; border-top:1px solid #c7c7c7;">
                    Printed from BNHS LMS - same data as web print view &amp; mobile sync. Report #{{ $report->id }}.
                </td>
            </tr>
        </table>
    </body>
</html>
