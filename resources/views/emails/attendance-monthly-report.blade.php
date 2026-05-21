<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Attendance Report</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;font-family:Arial,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px;background:#ffffff;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
                    <tr>
                        <td style="padding:22px 24px;background:#0b1f44;color:#ffffff;">
                            <h1 style="margin:0;font-size:20px;line-height:1.3;">Monthly Attendance Report</h1>
                            <p style="margin:8px 0 0 0;font-size:14px;opacity:0.9;">{{ $sectionName }} — {{ $period }}</p>
                            <p style="margin:6px 0 0 0;font-size:12px;opacity:0.85;">Report #{{ $report->id }} · BNHS LMS</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:24px;">
                            <p style="margin:0 0 12px 0;font-size:15px;">Hi {{ $name }},</p>
                            <p style="margin:0 0 16px 0;font-size:15px;line-height:1.6;">
                                Your monthly attendance summary is saved in <strong>BNHS LMS</strong> (adviser dashboard).
                                Daily marks from the <strong>web system</strong> and <strong>mobile RFID app</strong> are included.
                                School year: <strong>{{ $schoolYear }}</strong>.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;margin-bottom:20px;">
                                <thead>
                                    <tr style="background:#f3f4f6;">
                                        <th style="padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">#</th>
                                        <th style="padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Student</th>
                                        <th style="padding:10px 8px;text-align:center;border:1px solid #e5e7eb;">School Days</th>
                                        <th style="padding:10px 8px;text-align:center;border:1px solid #e5e7eb;">Present</th>
                                        <th style="padding:10px 8px;text-align:center;border:1px solid #e5e7eb;">Absent</th>
                                        <th style="padding:10px 8px;text-align:left;border:1px solid #e5e7eb;">Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($report->lines as $index => $line)
                                        <tr>
                                            <td style="padding:8px;border:1px solid #e5e7eb;">{{ $index + 1 }}</td>
                                            <td style="padding:8px;border:1px solid #e5e7eb;">{{ $line->student_name }}</td>
                                            <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;">{{ $line->school_days }}</td>
                                            <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;">{{ $line->present_days }}</td>
                                            <td style="padding:8px;border:1px solid #e5e7eb;text-align:center;font-weight:700;color:{{ $line->absent_days > 0 ? '#b91c1c' : '#1f2937' }};">{{ $line->absent_days }}</td>
                                            <td style="padding:8px;border:1px solid #e5e7eb;">{{ $line->remarks ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;">
                                <strong>Connected to your adviser dashboard</strong> — open the report to edit values, then print.
                            </p>
                            <p style="margin:0 0 16px 0;">
                                <a href="{{ $reportUrl }}" style="display:inline-block;background:#0b1f44;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 18px;border-radius:8px;margin-right:8px;">Open in Adviser Dashboard</a>
                                <a href="{{ $printUrl }}" style="display:inline-block;background:#ffffff;color:#0b1f44;text-decoration:none;font-weight:700;padding:11px 17px;border-radius:8px;border:2px solid #0b1f44;">Print Page</a>
                            </p>
                            <p style="margin:0 0 8px 0;font-size:13px;line-height:1.6;color:#6b7280;">Dashboard link:</p>
                            <p style="margin:0 0 12px 0;font-size:13px;line-height:1.6;word-break:break-all;">
                                <a href="{{ $reportUrl }}" style="color:#2563eb;text-decoration:underline;">{{ $reportUrl }}</a>
                            </p>
                            <p style="margin:0;font-size:13px;line-height:1.6;word-break:break-all;">
                                Print link: <a href="{{ $printUrl }}" style="color:#2563eb;text-decoration:underline;">{{ $printUrl }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
