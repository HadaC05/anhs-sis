<?php

namespace App\Support;

use Smalot\PdfParser\Document;

/** Reconstruct LIS table cells from PDF positions before validating the shared spreadsheet layout. */
class Sf2AttendanceLisPdf
{
    public function read(Document $document): array
    {
        if (! preg_match('/School Form 2\s*\(SF2\)/i', $document->getText())) {
            throw new Sf2ImportException('No readable SF2 attendance form was found.');
        }
        $report = null;
        foreach ($document->getPages() as $pageIndex => $page) {
            $cells = collect($page->getDataTm())->map(fn ($item) => [
                'x' => (float) $item[0][4], 'y' => (float) $item[0][5], 'text' => trim($item[1]),
            ])->filter(fn ($cell) => $cell['text'] !== '')->values()->all();
            $part = $this->parseCells($cells);
            if ($report !== null && array_intersect_key($report, array_flip(['month', 'year', 'school_year', 'grade', 'section', 'class_dates'])) !== array_intersect_key($part, array_flip(['month', 'year', 'school_year', 'grade', 'section', 'class_dates']))) {
                throw new Sf2ImportException('The SF2 pages have inconsistent headers or class dates.');
            }
            if ($report === null) {
                $report = $part;
                $report['rows'] = [];
                $report['layout']['rows'] = [];
            }
            foreach ($part['rows'] as $row) {
                $layout = $part['layout']['rows']['1-'.$row['row_number']];
                $row['page'] = $pageIndex + 1;
                $report['rows'][] = $row;
                $report['layout']['rows'][$row['page'].'-'.$row['row_number']] = $layout;
            }
            if ($part['layout']['summary']) {
                $report['layout']['summary'] = $part['layout']['summary'];
            }
        }

        return $report ?? throw new Sf2ImportException('No readable SF2 attendance sheet was found.');
    }

    public function parseCells(array $cells): array
    {
        $find = function (string $text) use ($cells): array {
            $matches = array_values(array_filter($cells, fn ($cell) => strcasecmp($cell['text'], $text) === 0));
            if (count($matches) !== 1) {
                throw new Sf2ImportException('The PDF SF2 header could not be read reliably: '.$text.'.');
            }

            return $matches[0];
        };
        $absent = $find('ABSENT');
        $present = $find('PRESENT');
        $width = $present['x'] - $absent['x'];
        if ($width <= 0 || abs($absent['y'] - $present['y']) > $width / 5) {
            throw new Sf2ImportException('The PDF SF2 totals columns could not be read reliably.');
        }
        $tolerance = $width / 5;
        $after = function (string $label) use ($find, $cells, $tolerance): string {
            $heading = $find($label);
            $matches = array_values(array_filter($cells, fn ($cell) => $cell['x'] > $heading['x'] && abs($cell['y'] - $heading['y']) < $tolerance));
            usort($matches, fn ($a, $b) => $a['x'] <=> $b['x']);
            if ($matches === []) {
                throw new Sf2ImportException('Complete the '.$label.' header in the SF2 PDF.');
            }

            return $matches[0]['text'];
        };
        $dateHeading = $find('(1st row for date)');
        $dateCells = array_values(array_filter($cells, fn ($cell) => $cell['y'] > $absent['y'] + $tolerance && $cell['y'] < $dateHeading['y'] && $cell['x'] < $absent['x'] && preg_match('/^(\d{1,2}|-)$/', $cell['text'])));
        usort($dateCells, fn ($a, $b) => $a['x'] <=> $b['x']);
        if (count($dateCells) < 2 || count($dateCells) > 25) {
            throw new Sf2ImportException('The class dates in the SF2 PDF could not be read reliably.');
        }
        $spacing = $dateCells[1]['x'] - $dateCells[0]['x'];
        if ($spacing <= 0) {
            throw new Sf2ImportException('The SF2 PDF contains overlapping class dates.');
        }
        // The merged columns of the LIS Excel template, in display order.
        $columns = [5, 7, 8, 9, 10, 11, 13, 14, 15, 16, 17, 19, 20, 21, 23, 25, 27, 28, 29, 30, 31, 32, 34, 35, 36];
        $sheet = array_fill(0, 7, []);
        $sheet[0][0] = 'School Form 2 (SF2)';
        $sheet[2] = [9 => 'School Year', 12 => $after('School Year'), 18 => 'Report for the Month of', 26 => $after('Report for the Month of')];
        $sheet[3] = [0 => 'Name of School', 5 => $after('Name of School'), 18 => 'Grade Level', 26 => $after('Grade Level'), 34 => 'Section', 38 => $after('Section')];
        $sheet[6] = [38 => 'ABSENT', 40 => 'PRESENT'];
        foreach ($dateCells as $index => $date) {
            $sheet[5][$columns[$index]] = $date['text'];
            $weekdays = array_values(array_filter($cells, fn ($cell) => abs($cell['y'] - $absent['y']) < $tolerance && $cell['x'] >= $date['x'] - $spacing / 3 && $cell['x'] < $date['x'] + $spacing * 2 / 3 && preg_match('/^(M|T|W|TH|F|S|SU)$/', $cell['text'])));
            if (count($weekdays) > 1) {
                throw new Sf2ImportException('The weekdays in the SF2 PDF are ambiguous.');
            }
            $sheet[6][$columns[$index]] = $weekdays[0]['text'] ?? '';
        }
        $combined = $find('Combined TOTAL Per Day');
        $numberHeading = $find('No.');
        $rowAnchors = array_values(array_filter($cells, fn ($cell) => $cell['y'] < $absent['y'] - $tolerance && $cell['y'] >= $combined['y'] - $tolerance && abs($cell['x'] - $numberHeading['x']) < $spacing / 2 && ctype_digit($cell['text'])));
        usort($rowAnchors, fn ($a, $b) => $b['y'] <=> $a['y']);
        foreach ($rowAnchors as $anchor) {
            $line = array_values(array_filter($cells, fn ($cell) => abs($cell['y'] - $anchor['y']) < $tolerance));
            usort($line, fn ($a, $b) => $a['x'] <=> $b['x']);
            $names = array_values(array_filter($line, fn ($cell) => $cell['x'] > $numberHeading['x'] + $spacing / 2 && $cell['x'] < $dateCells[0]['x'] - $spacing / 2));
            if (count($names) !== 1) {
                throw new Sf2ImportException('A learner name in the SF2 PDF could not be read reliably.');
            }
            $row = [0 => $anchor['text'], 2 => $names[0]['text']];
            if (preg_match('/TOTAL/i', $row[2])) {
                $sheet[] = $row;

                continue;
            }
            foreach ($line as $cell) {
                if ($cell['x'] < $dateCells[0]['x'] - $spacing / 2) {
                    continue;
                }
                if ($cell['x'] >= $absent['x'] - $spacing / 2) {
                    $column = $cell['x'] < $present['x'] - $spacing / 2 ? 38 : ($cell['x'] < $present['x'] + $width ? 40 : 42);
                } else {
                    $distances = array_map(fn ($date) => abs($date['x'] - $cell['x']), $dateCells);
                    if (min($distances) > $spacing / 2) {
                        throw new Sf2ImportException('An attendance mark has no readable date column in the SF2 PDF.');
                    }
                    $column = $columns[array_search(min($distances), $distances, true)];
                }
                if (isset($row[$column]) && $column !== 42) {
                    throw new Sf2ImportException('The SF2 PDF has overlapping attendance cells.');
                }
                $row[$column] = trim(($row[$column] ?? '').' '.$cell['text']);
            }
            $sheet[] = $row;
        }
        $names = array_filter($cells, fn ($cell) => $cell['y'] < $absent['y'] - $tolerance && $cell['y'] > $combined['y'] && $cell['x'] > $numberHeading['x'] + $spacing / 2 && $cell['x'] < $dateCells[0]['x'] - $spacing / 2 && str_contains($cell['text'], ','));
        $report = (new Sf2AttendanceXls)->parseRows($sheet);
        if (count($names) !== count($report['rows'])) {
            throw new Sf2ImportException('A learner row in the SF2 PDF has a missing or unreadable number.');
        }
        foreach ($cells as $cell) {
            if (preg_match('/^Class days:\s*(\d+)$/i', $cell['text'], $days) && (int) $days[1] !== $report['school_days']) {
                throw new Sf2ImportException('The printed number of class days does not agree with the SF2 date columns.');
            }
        }
        foreach (['average_daily_attendance' => 'Average Daily Attendance', 'attendance_percentage' => 'Percentage of Attendance for the month'] as $key => $label) {
            $headings = array_values(array_filter($cells, fn ($cell) => strcasecmp($cell['text'], $label) === 0 && $cell['x'] >= $absent['x'] - $tolerance));
            if (count($headings) !== 1) {
                continue;
            }
            $heading = $headings[0];
            $numbers = array_values(array_filter($cells, fn ($cell) => $cell['x'] > $heading['x'] && abs($cell['y'] - $heading['y']) < $tolerance / 2 && is_numeric($cell['text'])));
            usort($numbers, fn ($a, $b) => $a['x'] <=> $b['x']);
            if (count($numbers) === 3) {
                $report['layout']['summary'][$key] = array_column($numbers, 'text');
            }
        }

        return $report;
    }
}
