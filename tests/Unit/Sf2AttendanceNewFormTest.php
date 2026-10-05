<?php

use App\Support\Sf2AttendanceLisPdf;
use App\Support\Sf2AttendancePdf;
use App\Support\Sf2AttendanceXls;
use App\Support\Sf2ImportException;
use Shuchkin\SimpleXLSX;
use Smalot\PdfParser\Parser;

test('the provided PDF and XLSX samples produce identical attendance', function () {
    $excel = (new Sf2AttendanceXls)->read(base_path('tests/Fixtures/sf2-lis-june-2025-sample.xlsx'));
    $pdf = (new Sf2AttendancePdf)->read(base_path('tests/Fixtures/sf2-lis-june-2025-sample.pdf'));
    foreach (['format', 'month', 'year', 'school_year', 'grade', 'section', 'school_days', 'class_dates'] as $key) {
        expect($pdf[$key])->toBe($excel[$key]);
    }
    expect($pdf['format'])->toBe('lis')->and($pdf['school_days'])->toBe(21)
        ->and($pdf['rows'])->toHaveCount(15)
        ->and(array_sum(array_column($pdf['rows'], 'days_present')))->toBe(241)
        ->and(array_sum(array_column($pdf['rows'], 'days_absent')))->toBe(74);
    foreach ($pdf['rows'] as $i => $row) {
        expect(collect($row)->except('raw_text')->all())->toBe(collect($excel['rows'][$i])->except('raw_text')->all());
    }
    expect($pdf['layout']['rows'])->toBe($excel['layout']['rows'])
        ->and($pdf['layout']['summary']['attendance_percentage'][2])->toBe('76.51')
        ->and($excel['layout']['summary']['attendance_percentage'][2])->toBe('76.51');
});

test('new PDF rejects inconsistent totals and incomplete date or learner cells', function (string $mutation, string $message) {
    $document = (new Parser)->parseFile(base_path('tests/Fixtures/sf2-lis-june-2025-sample.pdf'));
    $cells = array_map(fn ($item) => ['x' => (float) $item[0][4], 'y' => (float) $item[0][5], 'text' => trim($item[1])], $document->getPages()[0]->getDataTm());
    foreach ($cells as &$cell) {
        if ($mutation === 'present' && $cell['text'] === '13' && $cell['y'] > 870 && $cell['x'] > 580) {
            $cell['text'] = '14';
        }
        if ($mutation === 'class days' && $cell['text'] === 'Class days: 21') {
            $cell['text'] = 'Class days: 20';
        }
        if ($mutation === 'number' && $cell['text'] === '1' && $cell['y'] > 870) {
            $cell['text'] = '';
        }
        if ($mutation === 'weekday' && $cell['text'] === 'M' && $cell['x'] < 155 && $cell['y'] > 880) {
            $cell['text'] = 'T';
        }
    }
    unset($cell);
    expect(fn () => (new Sf2AttendanceLisPdf)->parseCells($cells))->toThrow(Sf2ImportException::class, $message);
})->with([
    ['present', 'must equal'], ['class days', 'printed number'], ['number', 'learner count'], ['weekday', 'do not agree'],
]);

test('Excel supports the old absent tardy form without treating tardiness as present days', function () {
    $cells = SimpleXLSX::parseFile(base_path('tests/Fixtures/sf2-lis-june-2025-sample.xlsx'))->rows();
    $cells[6][40] = 'TARDY';
    foreach ($cells as $index => &$row) {
        if ($index >= 7 && $index < 34 && str_contains($row[2] ?? '', ',')) {
            $row[40] = '0';
        }
    }
    unset($row);
    $report = (new Sf2AttendanceXls)->parseRows($cells);
    expect($report['format'])->toBe('legacy')->and($report['rows'][0]['days_present'])->toBe(13)
        ->and($report['rows'][0]['days_tardy'])->toBe(0);
});
