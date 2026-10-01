<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Grade Report</title>
    <style>
        body { margin: 0; background: #f1f5f9; color: #172033; font: 14px Arial, sans-serif; }
        main { max-width: 1000px; margin: 32px auto; padding: 32px; background: white; }
        header { text-align: center; margin-bottom: 28px; }
        h1 { font-size: 22px; margin: 8px 0; }
        h2 { font-size: 18px; }
        .actions { display: flex; justify-content: flex-end; margin-bottom: 24px; }
        button { border: 0; border-radius: 6px; background: #296374; color: white; padding: 12px 18px; cursor: pointer; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 24px; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #aab4c0; padding: 10px; text-align: center; }
        th { background: #eef5f8; }
        th:first-child, td:first-child { text-align: left; }
        .note { color: #475569; font-size: 12px; line-height: 1.6; }
        @media (max-width: 600px) { main { margin: 0; padding: 16px; } .details { grid-template-columns: 1fr; } }
        @media print {
            @page { size: landscape; margin: 12mm; }
            body { background: white; }
            main { margin: 0; padding: 0; max-width: none; }
            .actions { display: none; }
            .table-wrap { overflow: visible; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
@php
    $enrollment = $selectedEnrollment;
    $periods = collect($gradingTerms)->pluck('label', 'key');
    $format = fn ($value) => $value === null ? '—' : number_format((float) $value, 0);
    $finalRatings = collect();
@endphp
<main>
    <div class="actions"><button type="button" onclick="window.print()">Print / Save as PDF</button></div>
    <header>
        <h1>Agusan National High School</h1>
        <h2>Student Grade Report</h2>
        <p>School Year {{ $enrollment->academicYear?->school_year ?? 'N/A' }}</p>
    </header>
    <div class="details">
        <div><strong>Student:</strong> {{ $student->name }}</div>
        <div><strong>LRN:</strong> {{ $student->lrn }}</div>
        <div><strong>Grade / Section:</strong> {{ $enrollment->gradeLevel?->grade_label ?? 'N/A' }} / {{ $enrollment->section?->name ?? 'N/A' }}</div>
        @if ($enrollment->semester)
            <div><strong>Semester:</strong> {{ ucfirst($enrollment->semester) }}</div>
        @endif
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr>
                <th>Subject</th>
                @foreach ($periods as $label)<th>{{ $label }}</th>@endforeach
                <th>Final Grade</th><th>Remarks</th>
            </tr></thead>
            <tbody>
                @forelse ($enrollment->subjectAssignments as $assignment)
                    @php
                        $grades = $assignment->grades->keyBy('grading_period');
                        $values = $periods->keys()->map(fn ($key) => $grades->get($key)?->numeric_grade);
                        $complete = $values->isNotEmpty() && $values->every(fn ($value) => $value !== null && $value !== '');
                        $final = $complete ? round($values->avg()) : null;
                        if (! $assignment->mapeh_component) $finalRatings->push($final);
                    @endphp
                    <tr>
                        <td>{{ $assignment->curriculumSubject?->subject?->title ?? 'N/A' }}</td>
                        @foreach ($values as $value)<td>{{ $format($value) }}</td>@endforeach
                        <td>{{ $format($final) }}</td>
                        <td>{{ $final === null ? 'Pending' : ($final >= 75 ? 'PASSED' : 'FAILED') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $periods->count() + 3 }}">No subjects available for this academic session.</td></tr>
                @endforelse
            </tbody>
            @if ($finalRatings->isNotEmpty())
                @php $average = $finalRatings->every(fn ($value) => $value !== null) ? round($finalRatings->avg()) : null; @endphp
                <tfoot><tr>
                    <th colspan="{{ $periods->count() + 1 }}">General Average</th>
                    <td>{{ $format($average) }}</td>
                    <td>{{ $average === null ? 'Pending' : ($average >= 75 ? 'PASSED' : 'FAILED') }}</td>
                </tr></tfoot>
            @endif
        </table>
    </div>
    <p class="note">Only released grades are shown. Final grades and the general average remain pending until all grading periods are complete.</p>
</main>
</body>
</html>
