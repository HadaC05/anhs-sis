<?php

use App\Support\Sf2AttendancePdf;
use App\Support\Sf2ImportException;

function smallSf2Page(): string
{
    return "School Form 2 (SF2) Daily Attendance Report of Learners\n2026-2027\tSeptember 2026\nSchool Name\t7\t7-A\n1\nT\n1\n2\nW\n1\n3\nTH\n1\n1Dela, Juan C.\tX\t1 1\nSeptember 2026\n3\n";
}

test('reads all learners and printed attendance totals from the supplied two page sf2 pdf', function () {
    $report = (new Sf2AttendancePdf)->read(base_path('tests/Fixtures/sf2-september-2026-sample.pdf'));

    expect($report['month'])->toBe(9)
        ->and($report['year'])->toBe(2026)
        ->and($report['school_year'])->toBe('2026-2027')
        ->and($report['section'])->toBe('7-A')
        ->and($report['school_days'])->toBe(22)
        ->and($report['class_dates'])->toHaveCount(22)
        ->and($report['rows'])->toHaveCount(15)
        ->and(array_sum(array_column($report['rows'], 'days_present')))->toBe(317)
        ->and(array_sum(array_column($report['rows'], 'days_absent')))->toBe(13)
        ->and(array_sum(array_column($report['rows'], 'days_tardy')))->toBe(7);
    $santos = collect($report['rows'])->firstWhere('name', 'Santos, Adrian Mae A.');
    expect($santos['days_present'])->toBe(17)
        ->and($santos['days_absent'])->toBe(5)
        ->and($santos['page'])->toBe(1)
        ->and($santos['row_number'])->toBe(7);
    $layout = $report['layout'];
    expect($layout['rows'])->toHaveCount(15)
        ->and($layout['rows']['1-1']['daily']['2026-09-25'])->toBe('X')
        ->and($layout['rows']['1-7']['daily']['2026-09-01'])->toBe('X')
        ->and($layout['rows']['1-7']['daily']['2026-09-07'])->toBe('X')
        ->and($layout['rows']['1-7']['daily']['2026-09-08'])->toBe('')
        ->and($layout['rows']['2-1']['daily']['2026-09-08'])->toBe('L')
        ->and($layout['rows']['2-1']['sex'])->toBe('Female')
        ->and($layout['summary']['enrollment'])->toBe(['8', '7', '15'])
        ->and($layout['summary']['average_daily_attendance'])->toBe(['7.50', '6.91', '14.41'])
        ->and($layout['summary']['attendance_percentage'])->toBe(['93.75', '98.70', '96.06'])
        ->and($layout['summary']['consecutive_absences'])->toBe(['1', '0', '1'])
        ->and($layout['summary']['transferred_in'])->toBe(['0', '0', '0']);
    foreach ($report['rows'] as $row) {
        $extracted = $layout['rows'][$row['page'].'-'.$row['row_number']];
        expect(count(array_filter($extracted['daily'], fn ($mark) => $mark === 'X')))->toBe($row['days_absent'])
            ->and(count(array_filter($extracted['daily'], fn ($mark) => in_array($mark, ['L', 'C', 'T'], true))))->toBe($row['days_tardy'])
            ->and($extracted['tardy_dates_complete'])->toBeTrue();
    }
});

test('rejects inconsistent or unsupported sf2 data instead of guessing attendance', function (string $from, string $to) {
    (new Sf2AttendancePdf)->parsePages([str_replace($from, $to, smallSf2Page())]);
})->with([
    'absence marks disagree' => ["X\t1 1", "X\t2 1"],
    'class day summary disagrees' => ["2026\n3\n", "2026\n4\n"],
    'fractional absences' => ["1 1\nSeptember", "1.5 1\nSeptember"],
    'unreadable totals' => ["1 1\nSeptember", "? ?\nSeptember"],
    'invalid weekday' => ["1\nT\n1", "1\nM\n1"],
    'missing page' => ['School Name', "Page 1 of 2\nSchool Name"],
    'transfer period requires review' => ["1 1\nSeptember", "1 1 Transferred out\nSeptember"],
])->throws(Sf2ImportException::class);
