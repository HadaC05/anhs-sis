<?php

namespace App\Support;

use DateTimeImmutable;
use Shuchkin\SimpleXLS;
use Shuchkin\SimpleXLSX;

class Sf2AttendanceXls
{
    public function read(string $path, ?int $calendarYear = null): array
    {
        try {
            $workbook = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'xlsx'
                ? SimpleXLSX::parseFile($path)
                : SimpleXLS::parseFile($path);
            if (! $workbook) {
                throw new \RuntimeException('Unreadable workbook');
            }
            $sheets = [];
            $continuations = [];
            foreach ($workbook->sheetNames() as $index => $name) {
                $cells = $workbook->rows($index);
                $title = strtoupper(implode(' ', $cells[0] ?? []));
                if (str_contains($title, 'SCHOOL FORM 2') && str_contains($title, 'CONTINUATION')) {
                    $continuations[] = $cells;
                } elseif (str_contains($title, 'SCHOOL FORM 2 (SF2)')) {
                    $sheets[] = $cells;
                }
            }
        } catch (\Throwable $exception) {
            throw new Sf2ImportException('The Excel file could not be read. Upload an original SF2 .xls or .xlsx workbook.');
        }
        if (count($sheets) !== 1) {
            throw new Sf2ImportException('Upload an Excel workbook containing exactly one SF2 attendance sheet.');
        }

        $report = $this->parseRows($sheets[0], $calendarYear);
        foreach ($continuations as $index => $cells) {
            $continuation = $this->parseContinuationRows($cells, $report, $index + 2);
            $report['rows'] = array_merge($report['rows'], $continuation['rows']);
            $report['layout']['rows'] = array_merge($report['layout']['rows'], $continuation['layout_rows']);
        }

        return $report;
    }

    /** Read the LIS SF2 layout with ABSENT / PRESENT monthly totals. */
    public function parseRows(array $cells, ?int $calendarYear = null): array
    {
        $value = fn (int $row, int $column) => trim((string) ($cells[$row][$column] ?? ''));
        $after = function (int $row, string $label) use ($cells, $value): string {
            foreach ($cells[$row] ?? [] as $column => $cell) {
                if (strcasecmp(trim((string) $cell), $label) === 0) {
                    foreach (array_keys($cells[$row]) as $next) {
                        if ($next > $column && $value($row, $next) !== '') {
                            return $value($row, $next);
                        }
                    }
                }
            }

            return '';
        };
        if (! str_contains($value(0, 0), 'School Form 2 (SF2)') || $value(6, 38) !== 'ABSENT' || ! in_array($value(6, 40), ['PRESENT', 'TARDY'], true)) {
            throw new Sf2ImportException('The Excel SF2 layout is not supported. Use the SF2 form with Absent and Present or Tardy totals.');
        }
        $isPresent = $value(6, 40) === 'PRESENT';
        $monthText = $after(2, 'Report for the Month of');
        if (! preg_match('/^(January|February|March|April|May|June|July|August|September|October|November|December)(?:\s+(20\d{2}))?$/i', $monthText, $date)) {
            throw new Sf2ImportException('Complete the report month, class dates, and attendance totals in the SF2 workbook before uploading.');
        }
        $year = isset($date[2]) ? (int) $date[2] : $calendarYear;
        if (! $year) {
            throw new Sf2ImportException('Include the report year beside the month in the SF2 workbook.');
        }
        $month = (int) (new DateTimeImmutable('1 '.$date[1].' '.$year))->format('n');
        if (! preg_match('/^(20\d{2})\s*[-–]\s*(20\d{2})$/u', $after(2, 'School Year'), $schoolYear)
            || ! preg_match('/^(?:Grade\s*)?(\d{1,2})\b/i', $after(3, 'Grade Level'), $grade)
            || ($section = $after(3, 'Section')) === '') {
            throw new Sf2ImportException('Complete the school year, grade level, and section in the SF2 workbook.');
        }
        $dates = [];
        $lastDay = 0;
        $weekdays = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'S', 7 => 'SU'];
        for ($column = 5; $column < 38; $column++) {
            $day = $value(5, $column);
            if (in_array($day, ['', '-'], true)) {
                continue;
            }
            if (! ctype_digit($day) || (int) $day <= $lastDay || ! checkdate($month, (int) $day, $year)) {
                throw new Sf2ImportException('The SF2 class dates are invalid, duplicated, or out of order.');
            }
            $dateString = sprintf('%04d-%02d-%02d', $year, $month, $day);
            if ($value(6, $column) !== $weekdays[(int) (new DateTimeImmutable($dateString))->format('N')]) {
                throw new Sf2ImportException('The SF2 class dates do not agree with the report month and year.');
            }
            $dates[$column] = $dateString;
            $lastDay = (int) $day;
        }
        $schoolDays = count($dates);
        if (! $schoolDays) {
            throw new Sf2ImportException('Fill in the class dates in the SF2 workbook before uploading.');
        }
        $rows = [];
        $layoutRows = [];
        $groupStart = 0;
        for ($r = 7; $r < count($cells); $r++) {
            $name = $value($r, 2);
            if (preg_match('/(FEMALE|MALE)\s*\|\s*TOTAL/i', $name, $group)) {
                for ($i = $groupStart; $i < count($rows); $i++) {
                    $layoutRows['1-'.$rows[$i]['row_number']]['sex'] = ucfirst(strtolower($group[1]));
                }
                $groupStart = count($rows);

                continue;
            }
            if (str_contains(strtoupper($name), 'COMBINED TOTAL')) {
                if (ctype_digit($value($r, 0)) && (int) $value($r, 0) !== count($rows)) {
                    throw new Sf2ImportException('The learner count does not agree with the combined SF2 total.');
                }
                break;
            }
            if ($name === '') {
                if ($value($r, 0) !== '') {
                    throw new Sf2ImportException('A numbered SF2 learner row has no name.');
                }

                continue;
            }
            if (! ctype_digit($value($r, 0)) || ! str_contains($name, ',')) {
                throw new Sf2ImportException('An Excel learner row could not be read. Check the learner names and row numbers.');
            }
            $absent = $this->total($value($r, 38), $name);
            $secondTotal = $this->total($value($r, 40), $name);
            $present = $isPresent ? $secondTotal : $schoolDays - $absent;
            if ($absent > $schoolDays || $secondTotal > $schoolDays || $absent + $present !== $schoolDays) {
                throw new Sf2ImportException('Absent and present totals must equal the number of class dates for '.$name.'.');
            }
            $remarks = $value($r, 42);
            if (preg_match('/transfer|drop.?out|dropped|late enrol|\bNLS\b/i', $remarks)) {
                throw new Sf2ImportException('The attendance period for '.$name.' needs review because of a transfer, dropout or late enrollment.');
            }
            $daily = [];
            for ($column = 5; $column < 38; $column++) {
                $mark = strtoupper($value($r, $column));
                if (! isset($dates[$column])) {
                    if ($mark !== '') {
                        throw new Sf2ImportException('An attendance mark has no class date for '.$name.'.');
                    }

                    continue;
                }
                if (! in_array($mark, ['', 'X', 'T', 'L', 'C'], true)) {
                    throw new Sf2ImportException('Unsupported attendance mark for '.$name.'. Use blank, X, T, L, or C.');
                }
                $daily[$dates[$column]] = $mark;
            }
            if (count(array_filter($daily, fn ($mark) => $mark === 'X')) !== $absent) {
                throw new Sf2ImportException('The absence marks and printed total disagree for '.$name.'.');
            }
            $tardy = count(array_filter($daily, fn ($mark) => in_array($mark, ['T', 'L', 'C'], true)));
            if (! $isPresent && $tardy > $secondTotal) {
                throw new Sf2ImportException('The tardy marks and printed total disagree for '.$name.'.');
            }
            $tardyComplete = $isPresent || $tardy === $secondTotal;
            $tardy = $isPresent ? $tardy : $secondTotal;
            // Keep row keys unique when numbering restarts for female learners.
            $rows[] = ['name' => $name, 'days_absent' => $absent, 'days_present' => $present, 'days_tardy' => $tardy, 'remarks' => $remarks, 'page' => 1, 'row_number' => count($rows) + 1, 'raw_text' => implode(' ', $cells[$r])];
            $layoutRows['1-'.count($rows)] = ['sex' => 'Unspecified', 'daily' => $daily, 'tardy_dates_complete' => $tardyComplete];
        }
        if ($rows === []) {
            throw new Sf2ImportException('No completed learner attendance rows were found in the SF2 workbook.');
        }
        $schoolName = $after(3, 'Name of School');
        $summary = [];
        foreach (array_combine(array_keys(Sf2SheetLayout::SUMMARY_LABELS), [37, 39, 43, 45, 47, 49, 50, 51, 53, 55]) as $key => $rowIndex) {
            $numbers = [$value($rowIndex, 43), $value($rowIndex, 44), $value($rowIndex, 45)];
            if (count(array_filter($numbers, 'is_numeric')) === 3) {
                $summary[$key] = array_map(fn ($number) => (string) round((float) $number, 2), $numbers);
            }
        }
        foreach ($cells[35] ?? [] as $cell) {
            if (preg_match('/Class days:\s*(\d+)/i', (string) $cell, $printed) && (int) $printed[1] !== $schoolDays) {
                throw new Sf2ImportException('The printed number of class days does not agree with the SF2 date columns.');
            }
        }

        return ['format' => $isPresent ? 'lis' : 'legacy', 'year' => $year, 'month' => $month, 'school_year' => $schoolYear[1].'-'.$schoolYear[2], 'school_name' => $schoolName, 'grade' => (int) $grade[1], 'section' => $section, 'school_days' => $schoolDays, 'class_dates' => array_values($dates), 'rows' => $rows, 'layout' => ['rows' => $layoutRows, 'summary' => $summary ?: null, 'school_name' => $schoolName, 'grade' => (int) $grade[1], 'section' => $section]];
    }

    /** Read learner rows from an SF2 continuation worksheet. */
    public function parseContinuationRows(array $cells, array $report, int $page = 2): array
    {
        $value = fn (int $row, int $column) => trim((string) ($cells[$row][$column] ?? ''));
        $title = strtoupper(implode(' ', $cells[0] ?? []));
        if (! str_contains($title, 'SCHOOL FORM 2') || ! str_contains($title, 'CONTINUATION')) {
            throw new Sf2ImportException('An Excel continuation sheet has an unsupported title or layout.');
        }

        $metadata = implode(' ', $cells[1] ?? []);
        $monthName = (new DateTimeImmutable(sprintf('%04d-%02d-01', $report['year'], $report['month'])))->format('F');
        if (! preg_match('/\bSY\s*(20\d{2})\s*-\s*(20\d{2})\b/i', $metadata, $schoolYear)
            || $report['school_year'] !== $schoolYear[1].'-'.$schoolYear[2]
            || ! preg_match('/\bGrade\s*(\d{1,2})\s+([^|]+)/i', $metadata, $gradeSection)
            || (int) $gradeSection[1] !== $report['grade']
            || $this->textKey($gradeSection[2]) !== $this->textKey($report['section'])
            || ! preg_match('/\b'.preg_quote($monthName, '/').'\s+'.$report['year'].'\b/i', $metadata)) {
            throw new Sf2ImportException('The Excel continuation sheet does not match the month, school year, grade, or section on the main SF2 sheet.');
        }

        $headerRow = null;
        $columns = [];
        foreach ($cells as $rowIndex => $row) {
            foreach ($row as $column => $cell) {
                $label = strtoupper(trim((string) $cell));
                $key = match ($label) {
                    'NO.', 'NO' => 'number',
                    'LEARNER NAME', 'NAME' => 'name',
                    'SEX' => 'sex',
                    'ABSENT' => 'absent',
                    'PRESENT' => 'present',
                    'TARDY' => 'tardy',
                    default => null,
                };
                if ($key) {
                    $columns[$key] = $column;
                }
            }
            if (isset($columns['number'], $columns['name'], $columns['sex'], $columns['absent'])
                && (isset($columns['present']) || isset($columns['tardy']))) {
                $headerRow = $rowIndex;
                break;
            }
            $columns = [];
        }
        $secondKey = $report['format'] === 'lis' ? 'present' : 'tardy';
        if ($headerRow === null || ! isset($columns[$secondKey])) {
            throw new Sf2ImportException('The Excel continuation sheet must include No., Learner name, Sex, Absent, and '.ucfirst($secondKey).' columns.');
        }

        $dates = [];
        $dateRow = $headerRow + 1;
        $dayRow = $headerRow + 2;
        $weekdays = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'S', 7 => 'SU'];
        for ($column = $columns['sex'] + 1; $column < $columns['absent']; $column++) {
            $day = $value($dateRow, $column);
            if (in_array($day, ['', '-'], true)) {
                continue;
            }
            if (! ctype_digit($day) || ! checkdate($report['month'], (int) $day, $report['year'])) {
                throw new Sf2ImportException('The SF2 continuation class dates are invalid.');
            }
            $date = sprintf('%04d-%02d-%02d', $report['year'], $report['month'], $day);
            if ($value($dayRow, $column) !== $weekdays[(int) (new DateTimeImmutable($date))->format('N')]) {
                throw new Sf2ImportException('The SF2 continuation class dates do not agree with the report month and year.');
            }
            $dates[$column] = $date;
        }
        if (array_values($dates) !== $report['class_dates']) {
            throw new Sf2ImportException('The class dates on the SF2 continuation sheet do not match the main sheet.');
        }

        $rows = [];
        $layoutRows = [];
        $nextNumber = count($report['rows']) + 1;
        for ($rowIndex = $headerRow + 3; $rowIndex < count($cells); $rowIndex++) {
            $number = $value($rowIndex, $columns['number']);
            $name = $value($rowIndex, $columns['name']);
            if ($number === '' && $name === '') {
                continue;
            }
            if ($number === '' && preg_match('/^(?:class\s+present|\d+\s+learners\b)/i', $name)) {
                continue;
            }
            if (! ctype_digit($number) || (int) $number !== $nextNumber || $name === '' || ! str_contains($name, ',')) {
                throw new Sf2ImportException('An Excel continuation learner row could not be read. Check the learner names and row numbers.');
            }
            $sex = strtoupper($value($rowIndex, $columns['sex']));
            if (! in_array($sex, ['M', 'F', 'MALE', 'FEMALE'], true)) {
                throw new Sf2ImportException('Complete the sex column for '.$name.' on the SF2 continuation sheet.');
            }
            $absent = $this->total($value($rowIndex, $columns['absent']), $name);
            $secondTotal = $this->total($value($rowIndex, $columns[$secondKey]), $name);
            $present = $report['format'] === 'lis' ? $secondTotal : $report['school_days'] - $absent;
            if ($absent + $present !== $report['school_days']) {
                throw new Sf2ImportException('Absent and present totals must equal the number of class dates for '.$name.'.');
            }

            $daily = [];
            foreach (range($columns['sex'] + 1, $columns['absent'] - 1) as $column) {
                $mark = strtoupper($value($rowIndex, $column));
                if (! isset($dates[$column])) {
                    if ($mark !== '') {
                        throw new Sf2ImportException('An attendance mark has no class date for '.$name.'.');
                    }

                    continue;
                }
                if (! in_array($mark, ['', 'X', 'T', 'L', 'C'], true)) {
                    throw new Sf2ImportException('Unsupported attendance mark for '.$name.'. Use blank, X, T, L, or C.');
                }
                $daily[$dates[$column]] = $mark;
            }
            if (count(array_filter($daily, fn ($mark) => $mark === 'X')) !== $absent) {
                throw new Sf2ImportException('The absence marks and printed total disagree for '.$name.'.');
            }
            $markedTardy = count(array_filter($daily, fn ($mark) => in_array($mark, ['T', 'L', 'C'], true)));
            if ($report['format'] === 'legacy' && $markedTardy !== $secondTotal) {
                throw new Sf2ImportException('The tardy marks and printed total disagree for '.$name.'.');
            }

            $row = ['name' => $name, 'days_absent' => $absent, 'days_present' => $present, 'days_tardy' => $report['format'] === 'lis' ? $markedTardy : $secondTotal, 'remarks' => '', 'page' => $page, 'row_number' => $nextNumber, 'raw_text' => implode(' ', $cells[$rowIndex])];
            $rows[] = $row;
            $layoutRows[$page.'-'.$nextNumber] = ['sex' => str_starts_with($sex, 'M') ? 'Male' : 'Female', 'daily' => $daily, 'tardy_dates_complete' => true];
            $nextNumber++;
        }
        if ($rows === []) {
            throw new Sf2ImportException('No completed learner attendance rows were found on an SF2 continuation sheet.');
        }

        return ['rows' => $rows, 'layout_rows' => $layoutRows];
    }

    private function textKey(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value));
    }

    private function total(string $value, string $name): int
    {
        if (! is_numeric($value) || (float) $value < 0 || (float) $value > 31 || floor((float) $value) !== (float) $value) {
            throw new Sf2ImportException('Complete whole-number Absent and Present totals for '.$name.'. Blank or fractional totals cannot be imported.');
        }

        return (int) $value;
    }
}
