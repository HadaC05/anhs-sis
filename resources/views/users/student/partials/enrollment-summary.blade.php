@php
    $enrollment = $enrollment ?? null;
    $isSeniorHigh = $enrollment?->isSeniorHigh() ?? false;
    $studentName = optional($application)->last_name
        ? trim(optional($application)->last_name.', '.optional($application)->first_name.(optional($application)->middle_name ? ' '.optional($application)->middle_name : '').(optional($application)->suffix ? ' '.optional($application)->suffix : ''))
        : (Auth::user()->name ?? 'Student');

    $infoRows = [
        ['label' => 'LRN', 'value' => optional($student)->lrn ?? '—'],
        ['label' => 'Student Name', 'value' => $studentName],
        ['label' => 'School Year', 'value' => $enrollment?->academicYear?->school_year ?? $activeYear?->school_year ?? '—'],
        ['label' => 'Grade Level', 'value' => $enrollment?->gradeLevel?->grade_label
            ?? ($enrollment?->grade_level ? str_replace(['grade_', '_'], ['Grade ', ' '], $enrollment->grade_level) : '—')],
        ['label' => 'Section', 'value' => $enrollment?->section?->name ?? 'Not Assigned'],
    ];

    if ($isSeniorHigh) {
        $infoRows[] = ['label' => 'Semester', 'value' => $enrollment?->semester ? ucfirst($enrollment->semester).' Semester' : 'N/A'];
        $infoRows[] = ['label' => 'Cluster', 'value' => $enrollment?->cluster?->name ?? 'N/A'];
        $infoRows[] = ['label' => 'Preferred Course / Curriculum', 'value' => $enrollment?->preferredCourse?->name ?? $enrollment?->section?->curriculum?->name ?? 'N/A'];
    }
@endphp

<div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
    <div class="grid grid-cols-1 gap-x-10 gap-y-4 md:grid-cols-2">
        @foreach ($infoRows as $index => $row)
            @php
                $isLastRow = $index >= count($infoRows) - (count($infoRows) % 2 === 0 ? 2 : 1);
            @endphp
            <div @class([
                'flex items-start justify-between gap-4',
                'border-b border-gray-100 pb-3' => ! $isLastRow,
            ])>
                <span class="text-sm text-gray-500">{{ $row['label'] }}</span>
                <span class="text-right text-sm font-semibold text-[#296374]">{{ $row['value'] }}</span>
            </div>
        @endforeach
    </div>
</div>
