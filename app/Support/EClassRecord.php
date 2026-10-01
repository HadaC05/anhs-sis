<?php

namespace App\Support;

use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class EClassRecord
{
    /** Read saved term grades, never the annual FINAL GRADES sheet or raw scores. */
    public static function read(string $path, int $term): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The class record could not be opened. Upload an XLSX workbook.');
        }

        try {
            $workbook = self::xml($zip, 'xl/workbook.xml');
            $relationships = self::xml($zip, 'xl/_rels/workbook.xml.rels');
            $ids = [];
            foreach ($workbook->xpath('//*[local-name()="sheet"]') as $sheet) {
                if (preg_match('/^TERM\s*0?'.$term.'$/i', trim((string) $sheet['name']))) {
                    $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $ids[] = (string) $attributes['id'];
                }
            }
            if (count($ids) !== 1) {
                throw new RuntimeException("The workbook must contain exactly one TERM {$term} sheet. No other term was read.");
            }
            $sheetPath = null;
            foreach ($relationships->xpath('//*[local-name()="Relationship"]') as $relationship) {
                if ((string) $relationship['Id'] === $ids[0] && (string) $relationship['TargetMode'] !== 'External') {
                    $target = (string) $relationship['Target'];
                    $sheetPath = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.$target;
                }
            }
            if (! $sheetPath || str_contains($sheetPath, '..')) {
                throw new RuntimeException('The selected term worksheet could not be found.');
            }

            $strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                foreach (self::xml($zip, 'xl/sharedStrings.xml')->xpath('//*[local-name()="si"]') as $item) {
                    $strings[] = self::text($item);
                }
            }
            $sheet = self::xml($zip, $sheetPath);
            $rows = [];
            $nameHeading = null;
            $gradeColumn = null;
            foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row) {
                $number = (int) $row['r'];
                foreach ($row->xpath('./*[local-name()="c"]') as $cell) {
                    $column = preg_replace('/\d/', '', (string) $cell['r']);
                    $value = (string) ($cell->xpath('./*[local-name()="v"]')[0] ?? '');
                    $value = match ((string) $cell['t']) {
                        's' => $strings[(int) $value] ?? '',
                        'inlineStr' => self::text($cell),
                        default => $value,
                    };
                    $rows[$number][$column] = trim($value);
                    if ($number <= 30 && self::normalize($value) === 'term grade') {
                        if ($gradeColumn !== null) {
                            throw new RuntimeException('The selected sheet has more than one Term Grade column.');
                        }
                        $gradeColumn = $column;
                    }
                    if ($number <= 30 && in_array(self::normalize($value), ['learners names', 'learner names'], true)) {
                        $nameHeading = [$column, $number];
                    }
                }
            }
            if (! $nameHeading || ! $gradeColumn) {
                throw new RuntimeException('The selected sheet must have LEARNERS’ NAMES and Term Grade headings, as in the three-term E-Class Record template.');
            }

            // The heading spans B:AD; learner rows use B for numbering and C:E for the name.
            [$nameStart, $headerRow] = $nameHeading;
            $records = [];
            foreach ($rows as $number => $cells) {
                if ($number <= $headerRow) {
                    continue;
                }
                $name = $cells[$nameStart] ?? '';
                if (is_numeric($name) || $name === '') {
                    $nextColumn = $nameStart;
                    $nextColumn++;
                    $name = $cells[$nextColumn] ?? '';
                }
                if ($name === '' || is_numeric($name) || in_array(self::normalize($name), ['male', 'female'], true)) {
                    continue;
                }
                $value = $cells[$gradeColumn] ?? '';
                $records[] = ['row' => $number, 'name' => $name, 'grade' => $value];
            }
            if ($records === []) {
                throw new RuntimeException("No learner names were found in TERM {$term}. Complete the workbook, recalculate it in Excel, and save it before uploading.");
            }

            return $records;
        } finally {
            $zip->close();
        }
    }

    public static function normalize(string $value): string
    {
        return Str::lower(Str::squish(preg_replace('/[^\pL\pN\s]/u', '', $value) ?? ''));
    }

    private static function text(SimpleXMLElement $node): string
    {
        return implode('', array_map(fn ($text) => (string) $text, $node->xpath('.//*[local-name()="t"]')));
    }

    private static function xml(ZipArchive $zip, string $path): SimpleXMLElement
    {
        $stat = $zip->statName($path);
        if (! $stat || $stat['size'] > 20 * 1024 * 1024) {
            throw new RuntimeException('The workbook is missing required data or is too large to read.');
        }
        $xml = $zip->getFromName($path);
        if ($xml === false || stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            throw new RuntimeException('The workbook contains unsupported XML.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $node = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET);
            if ($node === false) {
                throw new RuntimeException('The workbook contains unreadable XML.');
            }

            return $node;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
