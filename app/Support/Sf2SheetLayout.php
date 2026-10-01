<?php

namespace App\Support;

use Smalot\PdfParser\Config;
use Smalot\PdfParser\Document;

class Sf2SheetLayout
{
    public const SUMMARY_LABELS = [
        'enrollment' => 'Enrolment as of the first Friday of the school year',
        'late_enrollment' => 'Late enrolment during the month (beyond cut-off)',
        'registered' => 'Registered learners as of end of the month',
        'enrollment_percentage' => 'Percentage of enrolment as of end of the month',
        'average_daily_attendance' => 'Average daily attendance',
        'attendance_percentage' => 'Percentage of attendance for the month',
        'consecutive_absences' => 'Learners with 5 consecutive days of absences',
        'dropouts' => 'Dropped out',
        'transferred_out' => 'Transferred out',
        'transferred_in' => 'Transferred in',
    ];

    public function extract(Document $document, array $report): array
    {
        $layout = ['rows' => [], 'summary' => null, 'school_name' => $report['school_name'] ?? '', 'grade' => $report['grade'], 'section' => $report['section']];
        foreach ($document->getPages() as $index => $original) {
            try {
                $page = new Sf2LayoutPdfPage($document, $original->getHeader(), $original->getContent(), new Config);
                $cells = collect($page->getDataTm())->map(fn ($item) => ['x' => (float) $item[0][4], 'y' => (float) $item[0][5], 'text' => trim($item[1])])->filter(fn ($cell) => $cell['text'] !== '')->values();
                $absent = $cells->firstWhere('text', 'ABSENT');
                $group = $cells->first(fn ($cell) => preg_match('/^(FEMALE|MALE)\s*\|\s*TOTAL/i', $cell['text']));
                $sex = $group ? (str_starts_with($group['text'], 'FEMALE') ? 'Female' : 'Male') : 'Unspecified';
                $triangles = $this->tardyTriangles($original);
                $columns = [];
                if ($absent) {
                    foreach ($report['class_dates'] as $date) {
                        $day = (string) (int) substr($date, -2);
                        $matches = $cells->filter(fn ($cell) => $cell['text'] === $day && $cell['x'] < $absent['x'] && $cell['y'] > $absent['y'] + 5 && $cell['y'] < $absent['y'] + 22);
                        if ($matches->count() === 1) {
                            $columns[$date] = $matches->first()['x'];
                        }
                    }
                }
                foreach ($report['rows'] as $row) {
                    if ($row['page'] !== $index + 1) {
                        continue;
                    }
                    $names = $cells->filter(fn ($cell) => Sf2AttendanceImporter::nameKey($cell['text']) === Sf2AttendanceImporter::nameKey($row['name']));
                    $daily = null;
                    if ($names->count() === 1 && count($columns) === count($report['class_dates'])) {
                        $name = $names->first();
                        $daily = array_fill_keys($report['class_dates'], '');
                        $spacing = count($columns) > 1 ? min(array_map(fn ($a, $b) => $b - $a, array_slice(array_values($columns), 0, -1), array_slice(array_values($columns), 1))) : 15;
                        $marks = $cells->filter(fn ($cell) => abs($cell['y'] - $name['y']) < 2 && $cell['x'] >= min($columns) - $spacing / 2 && $cell['x'] <= max($columns) + $spacing / 2);
                        foreach ($marks as $mark) {
                            $distances = array_map(fn ($x) => abs($mark['x'] - $x), $columns);
                            $date = array_search(min($distances), $distances, true);
                            if (min($distances) > $spacing / 2 || ! in_array(strtoupper($mark['text']), ['X', 'T'], true) || $daily[$date] !== '') {
                                $daily = null;
                                break;
                            }
                            $daily[$date] = strtoupper($mark['text']);
                        }
                        if ($daily !== null) {
                            foreach ($triangles as $triangle) {
                                if ($name['y'] < $triangle['bottom'] || $name['y'] > $triangle['top']) {
                                    continue;
                                }
                                $distances = array_map(fn ($x) => abs($triangle['x'] - $x), $columns);
                                if (min($distances) <= $spacing / 2) {
                                    $date = array_search(min($distances), $distances, true);
                                    if ($daily[$date] !== '') {
                                        $daily = null;
                                        break;
                                    }
                                    $daily[$date] = $triangle['mark'];
                                }
                            }
                        }
                        if ($daily !== null && count(array_filter($daily, fn ($mark) => $mark === 'X')) !== $row['days_absent']) {
                            $daily = null;
                        }
                    }
                    $layout['rows'][$row['page'].'-'.$row['row_number']] = [
                        'sex' => $sex,
                        'daily' => $daily,
                        'tardy_dates_complete' => $daily !== null && count(array_filter($daily, fn ($mark) => in_array($mark, ['T', 'L', 'C'], true))) === $row['days_tardy'],
                    ];
                }

                // The standard summary has ten numeric rows underneath M / F / TOTAL.
                $total = $cells->first(fn ($cell) => $cell['text'] === 'TOTAL' && $cell['x'] > 600);
                if ($total) {
                    $headings = $cells->filter(fn ($cell) => abs($cell['y'] - $total['y']) < 2 && in_array($cell['text'], ['M', 'F', 'TOTAL'], true))->sortBy('x')->values();
                    if ($headings->count() === 3) {
                        $numbers = $cells->filter(fn ($cell) => is_numeric($cell['text']) && $cell['x'] >= $headings[0]['x'] - 8 && $cell['x'] <= $total['x'] + 12 && $cell['y'] < $total['y'] - 5 && $cell['y'] > $total['y'] - 185)->groupBy(fn ($cell) => (string) round($cell['y']))->sortKeysDesc();
                        if ($numbers->count() === count(self::SUMMARY_LABELS) && $numbers->every(fn ($row) => $row->count() === 3)) {
                            $layout['summary'] = [];
                            foreach (array_keys(self::SUMMARY_LABELS) as $rowIndex => $key) {
                                $layout['summary'][$key] = $numbers->values()[$rowIndex]->sortBy('x')->pluck('text')->values()->all();
                            }
                        }
                    }
                }
            } catch (\Throwable $exception) {
                // Layout extraction is optional: never replace validated monthly totals with guesses.
                report($exception);
            }
        }

        return $layout;
    }

    private function tardyTriangles(\Smalot\PdfParser\Page $page): array
    {
        $triangles = [];
        $contents = $page->get('Contents')->getContent();
        foreach (is_array($contents) ? $contents : [$contents] as $stream) {
            $content = is_object($stream) ? $stream->getContent() : $stream;
            // Only simple, untransformed, filled triangular marks are supported.
            if (! is_string($content) || ! preg_match('/^\s*q\s+((?:[-\d.]+\s+[-\d.]+\s+[ml]\s+){3,4})h\s+0 0 0 rg\s+f\s+Q\s*$/', $content, $path)) {
                continue;
            }
            preg_match_all('/([-\d.]+)\s+([-\d.]+)\s+[ml]/', $path[1], $points, PREG_SET_ORDER);
            $vertices = array_unique(array_map(fn ($point) => $point[1].','.$point[2], $points));
            if (count($vertices) !== 3) {
                continue;
            }
            $xs = array_map(fn ($point) => (float) explode(',', $point)[0], $vertices);
            $ys = array_map(fn ($point) => (float) explode(',', $point)[1], $vertices);
            if (max($xs) - min($xs) < 4 || max($xs) - min($xs) > 25 || max($ys) - min($ys) < 4 || max($ys) - min($ys) > 20) {
                continue;
            }
            $triangles[] = ['x' => (min($xs) + max($xs)) / 2, 'bottom' => min($ys), 'top' => max($ys), 'mark' => count(array_filter($ys, fn ($y) => $y === max($ys))) === 2 ? 'L' : 'C'];
        }

        return $triangles;
    }
}
