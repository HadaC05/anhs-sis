<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Summary - {{ $section->name }}</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        thead { display: table-header-group; }
        tr { break-inside: avoid; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; margin: 0; padding: 16px; color: #111; }
        @media print { .no-print { display: none; } body { padding: 0; } }
        .header { text-align: center; margin-bottom: 12px; }
        .title { font-size: 14px; font-weight: bold; }
        .subtitle { font-size: 11px; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #111; padding: 6px; }
        th { background: #f3f4f6; text-transform: uppercase; font-size: 10px; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()" style="padding:6px 12px; background:#296374; color:white; border:none; border-radius:4px;">Print</button>
    </div>

    @php
        $descriptorBands = \App\Support\Sf9PerformanceScale::forSection($section);
        $subject = $assignment->subject;
        $subjectLabel = $subject ? ($subject->code . ' - ' . $subject->title) : 'Subject';
        $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $section->grade_level));
    @endphp

    <div class="header">
        <div class="title">Grade Summary</div>
        <div class="subtitle">{{ $section->name }} | {{ $gradeLabel }} | {{ $subjectLabel }}</div>
        <div class="subtitle">School Year: {{ $section->academicYear?->school_year ?? 'N/A' }}</div>
    </div>

    <p>All configured and recorded terms &middot; Saved grades only &middot; Averages use available grades.</p>
    <table>
        <thead>
            <tr>
                <th>Student</th>
                @foreach($periods as $period)
                    <th>{{ $period['label'] }}</th>
                @endforeach
                <th>Average</th><th>Remarks</th><th>Descriptor</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summaries as $enrollmentId => $summary)
                @php
                    $gradeSet = $summary['grades'] ?? collect();
                @endphp
                <tr>
                    <td>{{ $summary['name'] ?? '' }}</td>
                    @foreach($periods as $period)
                        <td class="right">{{ $gradeSet->get($period['key'])?->numeric_grade ?? '' }}</td>
                    @endforeach
                    <td class="right">{{ $summary['average'] !== null ? $summary['average'] : '' }}</td>
                    <td>{{ $summary['average'] === null ? '' : ($summary['average'] >= 75 ? 'Passed' : 'Failed') }}</td>
                    <td>{{ \App\Support\Sf9PerformanceScale::descriptor($summary['average'], $descriptorBands) ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
