<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - {{ $enrollment->student->full_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #9ca3af; padding: 8px; }
        th { background: #e5e7eb; }
        .head { display: flex; justify-content: space-between; }
        .btn {
            display: inline-block;
            margin-top: 10px;
            margin-right: 6px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #4b5563;
            text-decoration: none;
            color: #111827;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body>
    <div class="head">
        <div>
            <h2>Senior High School Report Card</h2>
            <p><strong>Student:</strong> {{ $enrollment->student->full_name }}</p>
            <p><strong>Section:</strong> Grade {{ $enrollment->section->grade_level }} - {{ $enrollment->section->name }}</p>
            <p><strong>School Year:</strong> {{ $enrollment->schoolYear->name }}</p>
        </div>
        <div>
            <p><strong>General Average:</strong> {{ $reportCard?->general_average !== null ? number_format($reportCard->general_average, 2) : 'Incomplete' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Subject</th>
                <th>Q1</th>
                <th>Q2</th>
                <th>Q3</th>
                <th>Q4</th>
                <th>Final Grade</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reportCard?->items ?? [] as $item)
                <tr>
                    <td>{{ $item->subjectAssignment->subject->title }}</td>
                    <td>{{ $item->q1 ?? '-' }}</td>
                    <td>{{ $item->q2 ?? '-' }}</td>
                    <td>{{ $item->q3 ?? '-' }}</td>
                    <td>{{ $item->q4 ?? '-' }}</td>
                    <td>{{ $item->final_grade ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No report card data available yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="no-print">
        <button class="btn" onclick="window.print()">Print</button>
        <a class="btn" href="{{ route('report-cards.index', ['school_year_id' => $enrollment->school_year_id, 'section_id' => $enrollment->section_id]) }}">Back</a>
    </div>
</body>
</html>

