<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Attendance Monthly Report Export</title>
    </head>
    <body>
        @php
            /** @var \App\Models\AttendanceMonthlyReport $report */
            $section = $report->section;
            $schoolYear = $report->schoolYear;
            $totalAbsent = (int) $report->lines->sum('absent_days');
        @endphp

        <table border="1" cellpadding="4" cellspacing="0">
            <thead>
                <tr>
                    <th colspan="9">Monthly Attendance Report</th>
                </tr>
                <tr>
                    <td colspan="9">
                        <strong>Period:</strong> {{ $report->periodLabel() }} &nbsp;|&nbsp;
                        <strong>Section:</strong> {{ $section?->name ?? '—' }} &nbsp;|&nbsp;
                        <strong>School Year:</strong> {{ $schoolYear?->name ?? '—' }}
                    </td>
                </tr>
                <tr>
                    <td colspan="9">
                        <strong>School Days (section):</strong> {{ $report->school_days_total }} &nbsp;|&nbsp;
                        <strong>Total Absences:</strong> {{ $totalAbsent }}
                    </td>
                </tr>
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
                        <td>{{ $line->absent_days }}</td>
                        <td>
                            {!! nl2br(e($line->remarks ?? '')) !!}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>

