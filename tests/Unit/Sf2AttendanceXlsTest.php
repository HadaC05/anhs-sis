<?php

use App\Support\Sf2AttendanceXls;
use App\Support\Sf2ImportException;
use Shuchkin\SimpleXLS;

function lisSf2Cells(): array
{
    return SimpleXLS::parseFile(base_path('tests/Fixtures/sf2-lis-september-2026.xls'))->rows();
}

test('LIS Excel imports present totals and preserves male female daily records', function () {
    $report = (new Sf2AttendanceXls)->read(base_path('tests/Fixtures/sf2-lis-september-2026.xls'), 2026);
    expect($report['month'])->toBe(9)->and($report['year'])->toBe(2026)
        ->and($report['grade'])->toBe(7)->and($report['section'])->toBe('Einstein')
        ->and($report['school_days'])->toBe(4)->and($report['rows'])->toHaveCount(2)
        ->and($report['rows'][0]['days_present'])->toBe(3)
        ->and($report['rows'][0]['days_absent'])->toBe(1)
        ->and($report['rows'][0]['days_tardy'])->toBe(1)
        ->and($report['rows'][1]['days_present'])->toBe(4)
        ->and(array_column($report['layout']['rows'], 'sex'))->toBe(['Male', 'Female']);
});

test('LIS Excel rejects incomplete or inconsistent attendance', function (int $row, int $column, string $value, string $message) {
    $cells = lisSf2Cells();
    $cells[$row][$column] = $value;
    expect(fn () => (new Sf2AttendanceXls)->parseRows($cells, 2026))->toThrow(Sf2ImportException::class, $message);
})->with([
    'blank month' => [2, 26, '', 'Complete the report month'],
    'blank total' => [7, 38, '', 'Complete whole-number'],
    'fractional total' => [7, 38, '0.5', 'Complete whole-number'],
    'incorrect present' => [7, 40, '4', 'must equal'],
    'incorrect absence mark' => [7, 7, '', 'marks and printed total disagree'],
    'invalid date' => [5, 7, '31', 'class dates are invalid'],
    'duplicate date' => [5, 8, '1', 'class dates are invalid'],
    'incorrect weekday' => [6, 7, 'M', 'do not agree'],
    'unsupported mark' => [7, 8, '/', 'Unsupported attendance mark'],
    'undated mark' => [7, 5, 'X', 'has no class date'],
    'transferred learner' => [7, 42, 'Transferred out', 'needs review'],
]);

test('LIS Excel reads an explicit report year without guessing from school year', function () {
    $cells = lisSf2Cells();
    $cells[2][26] = 'September 2026';
    expect((new Sf2AttendanceXls)->parseRows($cells)['year'])->toBe(2026);
    $cells[2][26] = 'September';
    expect(fn () => (new Sf2AttendanceXls)->parseRows($cells))->toThrow(Sf2ImportException::class, 'Include the report year');
});
