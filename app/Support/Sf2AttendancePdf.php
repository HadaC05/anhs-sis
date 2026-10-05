<?php

namespace App\Support;

use DateTimeImmutable;
use Smalot\PdfParser\Parser;

class Sf2AttendancePdf
{
    /** Read the text-based DepEd SF2 layout. Never infer attendance from unreadable cells. */
    public function read(string $path): array
    {
        try {
            $document = (new Parser)->parseFile($path);
            $pages = array_map(fn ($page) => $page->getText(), $document->getPages());
        } catch (\Throwable) {
            throw new Sf2ImportException('The PDF could not be read. Upload an original text-based SF2 PDF, not a scanned image.');
        }

        if (preg_match('/ABSENT\s+PRESENT/i', implode("\n", $pages))) {
            return (new Sf2AttendanceLisPdf)->read($document);
        }

        $report = $this->parsePages($pages);
        $report['layout'] = (new Sf2SheetLayout)->extract($document, $report);

        return $report;
    }

    public function parsePages(array $pages): array
    {
        $text = implode("\n", $pages);
        if (! preg_match('/School Form 2\s*\(SF2\)/i', $text)) {
            throw new Sf2ImportException('No readable SF2 attendance form was found. Scanned PDFs require a text-based export.');
        }
        preg_match_all('/\bPage\s+(\d+)\s+of\s+(\d+)\b/i', $text, $pageNumbers, PREG_SET_ORDER);
        foreach ($pageNumbers as $pageNumber) {
            if ((int) $pageNumber[2] !== count($pages)) {
                throw new Sf2ImportException('The SF2 is missing pages. Upload the complete report.');
            }
        }

        $monthPattern = 'January|February|March|April|May|June|July|August|September|October|November|December';
        preg_match_all('/\b('.$monthPattern.')\s+(20\d{2})\b/i', $text, $dates, PREG_SET_ORDER);
        $reports = [];
        foreach ($dates as $date) {
            $reports[strtolower($date[1]).' '.$date[2]] = [
                'month' => (int) (new DateTimeImmutable('1 '.$date[1].' '.$date[2]))->format('n'),
                'year' => (int) $date[2],
            ];
        }
        if (count($reports) !== 1) {
            throw new Sf2ImportException('The SF2 must identify one report month and year, for example September 2026.');
        }
        $report = array_values($reports)[0];

        preg_match_all('/\b(20\d{2})\s*[-–]\s*(20\d{2})\b/u', $text, $years, PREG_SET_ORDER);
        $schoolYears = array_unique(array_map(fn ($year) => $year[1].'-'.$year[2], $years));
        if (count($schoolYears) !== 1) {
            throw new Sf2ImportException('The SF2 school year could not be identified reliably.');
        }

        // In the standard export, school name, grade and section share the filled header line.
        if (! preg_match('/[^\r\n]+\t\s*(\d{1,2})\s*\t\s*([^\r\n]+)(?:\r?\n|$)/u', $pages[0] ?? '', $header)) {
            throw new Sf2ImportException('The SF2 grade and section header could not be read. Use the completed text-based DepEd SF2 layout.');
        }

        $rows = [];
        $classDays = null;
        foreach ($pages as $pageIndex => $page) {
            $pageRows = [];
            foreach (preg_split('/\R/u', $page) as $line) {
                // Numbered learner rows include the printed ABSENT and TARDY totals.
                if (! preg_match('/^\s*\d{1,3}\s*[\p{L}][^\d]*,/u', $line)) {
                    continue;
                }
                if (! preg_match('/^\s*(\d{1,3})\s*([\p{L}\p{M} .\x{2019}\x{0027},-]+?)\s+(?:([Xx\s]*)\s+)?(\d{1,2})\s+(\d{1,2})(?:\s+(.*))?$/u', $line, $match)) {
                    throw new Sf2ImportException('A learner row on page '.($pageIndex + 1).' has unreadable or unsupported totals. No attendance was changed.');
                }
                $absent = (int) $match[4];
                if (preg_match('/transfer|drop.?out|dropped|late enrol/i', $match[6] ?? '')) {
                    throw new Sf2ImportException('The attendance period for '.$match[2].' needs review because of a transfer, dropout or late enrollment. Present days cannot safely be calculated from this row.');
                }
                $marks = preg_replace('/\s+/', '', $match[3] ?? '');
                if ($marks !== '' && strlen($marks) !== $absent) {
                    throw new Sf2ImportException('The absence marks and printed total disagree for '.trim($match[2]).'.');
                }
                $pageRows[] = [
                    'name' => trim($match[2]),
                    'days_absent' => $absent,
                    'days_tardy' => (int) $match[5],
                    'remarks' => trim($match[6] ?? ''),
                    'page' => $pageIndex + 1,
                    'row_number' => (int) $match[1],
                    'raw_text' => trim($line),
                ];
            }
            if ($pageRows === []) {
                continue;
            }
            $rowNumbers = array_column($pageRows, 'row_number');
            if ($rowNumbers !== range($rowNumbers[0], $rowNumbers[0] + count($rowNumbers) - 1)) {
                throw new Sf2ImportException('Learner row numbers are missing or duplicated on page '.($pageIndex + 1).'. Check that every row has readable totals.');
            }

            // Date, weekday and daily class total repeat across the SF2 header.
            preg_match_all('/^\s*(\d{1,2})\s*\R\s*(M|T|W|TH|F|S|SU)\s*\R\s*\d+\s*$/m', $page, $columns, PREG_SET_ORDER);
            $pageDays = array_map(fn ($column) => (int) $column[1], $columns);
            if ($pageDays === [] || count(array_unique($pageDays)) !== count($pageDays)) {
                throw new Sf2ImportException('The class dates on page '.($pageIndex + 1).' could not be read reliably.');
            }
            $sorted = $pageDays;
            sort($sorted);
            if ($sorted !== $pageDays || ($classDays !== null && $classDays !== $pageDays)) {
                throw new Sf2ImportException('The SF2 pages have inconsistent class dates.');
            }
            $weekdayCodes = [1 => 'M', 2 => 'T', 3 => 'W', 4 => 'TH', 5 => 'F', 6 => 'S', 7 => 'SU'];
            foreach ($columns as $column) {
                if (! checkdate($report['month'], (int) $column[1], $report['year'])) {
                    throw new Sf2ImportException('The SF2 contains an invalid class date.');
                }
                $day = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $report['year'], $report['month'], $column[1]));
                if ($weekdayCodes[(int) $day->format('N')] !== $column[2]) {
                    throw new Sf2ImportException('The SF2 class dates do not agree with the report month and year.');
                }
            }
            $classDays = $pageDays;
            array_push($rows, ...$pageRows);
        }
        if ($rows === [] || $classDays === null) {
            throw new Sf2ImportException('No readable learner attendance rows were found. Use the completed text-based SF2 export.');
        }

        $schoolDays = count($classDays);
        // The summary's month is followed by its printed number of class days.
        if (preg_match('/\b(?:'.$monthPattern.')\s+20\d{2}\s*\R\s*(\d{1,2})\s*\R/i', $text, $summary) && (int) $summary[1] !== $schoolDays) {
            throw new Sf2ImportException('The printed number of class days does not agree with the SF2 date columns.');
        }
        foreach ($rows as &$row) {
            if ($row['days_absent'] > $schoolDays || $row['days_tardy'] > $schoolDays) {
                throw new Sf2ImportException('Attendance totals exceed the number of class days for '.$row['name'].'.');
            }
            $row['days_present'] = $schoolDays - $row['days_absent'];
        }
        unset($row);

        return $report + [
            'school_year' => array_values($schoolYears)[0],
            'school_name' => trim(explode("\t", $header[0])[0]),
            'grade' => (int) $header[1],
            'section' => trim($header[2]),
            'school_days' => $schoolDays,
            'class_dates' => array_map(fn ($day) => sprintf('%04d-%02d-%02d', $report['year'], $report['month'], $day), $classDays),
            'rows' => $rows,
        ];
    }
}
