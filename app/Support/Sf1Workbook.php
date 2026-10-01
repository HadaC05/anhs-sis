<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class Sf1Workbook
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    public static function create(Collection $learners, array $header): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sf1_');
        $zip = new ZipArchive;
        $opened = false;
        try {
            if (! $path || ! copy(resource_path('templates/sf1.xlsx'), $path) || $zip->open($path) !== true) {
                throw new RuntimeException('Unable to open the SF1 template.');
            }
            $opened = true;
            $sheet = new DOMDocument;
            $sheet->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'), LIBXML_NONET);
            $data = $sheet->getElementsByTagName('sheetData')->item(0);
            $original = [];
            foreach ($data->childNodes as $row) {
                if ($row instanceof DOMElement) {
                    $original[(int) $row->getAttribute('r')] = $row->cloneNode(true);
                }
            }
            while ($data->firstChild) {
                $data->removeChild($data->firstChild);
            }
            $males = $learners->where('sex', 'male')->values();
            $females = $learners->where('sex', 'female')->values();
            $unknown = $learners->where('sex', 'unspecified')->values();
            $maleCapacity = max(40, $males->count());
            $femaleCapacity = max(40, $females->count());
            $maleTotal = 11 + $maleCapacity;
            $femaleStart = $maleTotal + 1;
            $femaleTotal = $femaleStart + $femaleCapacity;
            $combined = $femaleTotal + 1 + ($unknown->isEmpty() ? 0 : $unknown->count() + 2);
            $map = fn (int $row): int => match (true) {
                $row <= 50 => $row,
                $row === 51 => $maleTotal,
                $row <= 91 => $femaleStart + $row - 52,
                $row === 92 => $femaleTotal,
                default => $combined + $row - 93,
            };
            $append = function (int $templateRow, int $number) use ($data, $original): void {
                $row = $original[$templateRow]->cloneNode(true);
                $row->setAttribute('r', (string) $number);
                foreach ($row->getElementsByTagName('c') as $cell) {
                    $cell->setAttribute('r', preg_replace('/\d+/', (string) $number, $cell->getAttribute('r')));
                }
                $data->appendChild($row);
            };
            for ($row = 1; $row <= 10; $row++) {
                if (isset($original[$row])) {
                    $append($row, $row);
                }
            }
            $dataRows = [];
            $cells = [];
            foreach ($header as $ref => $value) {
                $cells[preg_replace_callback('/\d+/', fn ($m) => (string) $map((int) $m[0]), $ref)] = $value;
            }
            foreach ([[$males, 11, $maleCapacity, 11], [$females, $femaleStart, $femaleCapacity, 52]] as [$group, $start, $capacity, $templateRow]) {
                for ($index = 0; $index < $capacity; $index++) {
                    $number = $start + $index;
                    $append($templateRow, $number);
                    $dataRows[] = $number;
                    foreach (($group[$index]['cells'] ?? []) as $column => $value) {
                        $cells[$column.$number] = $value;
                    }
                }
                $total = $start + $capacity;
                $append($templateRow === 11 ? 51 : 92, $total);
                $cells['A'.$total] = $group->count();
            }
            $extraMerges = [];
            if ($unknown->isNotEmpty()) {
                $start = $femaleTotal + 1;
                $append(51, $start);
                $cells['C'.$start] = 'SEX UNSPECIFIED';
                foreach ($unknown as $index => $learner) {
                    $number = $start + $index + 1;
                    $append(11, $number);
                    $dataRows[] = $number;
                    foreach ($learner['cells'] as $column => $value) {
                        $cells[$column.$number] = $value;
                    }
                }
                $end = $combined - 1;
                $append(51, $end);
                $cells['A'.$end] = $unknown->count();
                $cells['C'.$end] = '<=== TOTAL SEX UNSPECIFIED';
                foreach ([$start, $end] as $number) {
                    $extraMerges[] = 'A'.$number.':B'.$number;
                    $extraMerges[] = 'C'.$number.':AF'.$number;
                }
            }
            for ($row = 93; $row <= 100; $row++) {
                if (isset($original[$row])) {
                    $append($row, $map($row));
                }
            }
            $cells['A'.$combined] = $learners->count();
            $cells['U'.$map(96)] = $males->count();
            $cells['U'.$map(97)] = $females->count();
            $cells['U'.$map(99)] = $learners->count();
            if ($unknown->isNotEmpty()) {
                $cells['A'.$map(100)] .= ' Sex unspecified: '.$unknown->count().'.';
            }

            $merges = $sheet->getElementsByTagName('mergeCells')->item(0);
            $ranges = [];
            foreach ($merges->getElementsByTagName('mergeCell') as $merge) {
                $ref = $merge->getAttribute('ref');
                preg_match('/\d+/', $ref, $number);
                if (((int) $number[0] >= 11 && (int) $number[0] <= 50) || ((int) $number[0] >= 52 && (int) $number[0] <= 91)) {
                    continue;
                }
                $ranges[] = preg_replace_callback('/\d+/', fn ($m) => (string) $map((int) $m[0]), $ref);
            }
            foreach ($dataRows as $number) {
                foreach (['A:B', 'C:F', 'H:I', 'J:K', 'N:Q', 'R:T', 'U:V', 'X:Y', 'Z:AB', 'AE:AF'] as $range) {
                    [$first, $last] = explode(':', $range);
                    $ranges[] = $first.$number.':'.$last.$number;
                }
            }
            while ($merges->firstChild) {
                $merges->removeChild($merges->firstChild);
            }
            foreach (array_merge($ranges, $extraMerges) as $range) {
                $merge = $sheet->createElementNS(self::NS, 'mergeCell');
                $merge->setAttribute('ref', $range);
                $merges->appendChild($merge);
            }
            $merges->setAttribute('count', (string) $merges->childNodes->length);
            foreach ($cells as $ref => $value) {
                self::cell($sheet, $ref, $value);
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet->saveXML());
            $lastRow = $map(100);
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="SF1" sheetId="1" r:id="sheet1"/></sheets><definedNames><definedName name="_xlnm.Print_Area" localSheetId="0">SF1!$A$1:$AI$'.$lastRow.'</definedName><definedName name="_xlnm.Print_Titles" localSheetId="0">SF1!$1:$10</definedName></definedNames></workbook>');
            $saved = $zip->close();
            $opened = false;
            if (! $saved) {
                throw new RuntimeException('Unable to save SF1 workbook.');
            }

            return $path;
        } catch (\Throwable $exception) {
            if ($opened) {
                $zip->close();
            }
            if ($path && is_file($path)) {
                unlink($path);
            }
            throw $exception;
        }
    }

    private static function cell(DOMDocument $sheet, string $ref, string|int $value): void
    {
        $xpath = new DOMXPath($sheet);
        $xpath->registerNamespace('s', self::NS);
        $cell = $xpath->query("//s:c[@r='{$ref}']")->item(0);
        if (! $cell) {
            $number = (int) preg_replace('/\D/', '', $ref);
            $row = $xpath->query("//s:row[@r='{$number}']")->item(0);
            $cell = $sheet->createElementNS(self::NS, 'c');
            $cell->setAttribute('r', $ref);
            $row->appendChild($cell);
        }
        while ($cell->firstChild) {
            $cell->removeChild($cell->firstChild);
        }
        // Explicit strings preserve leading zeroes and prevent names becoming formulas.
        if (is_int($value)) {
            $cell->removeAttribute('t');
            $cell->appendChild($sheet->createElementNS(self::NS, 'v', (string) $value));
        } else {
            $cell->setAttribute('t', 'inlineStr');
            $inline = $sheet->createElementNS(self::NS, 'is');
            $text = $sheet->createElementNS(self::NS, 't');
            $text->appendChild($sheet->createTextNode($value));
            $inline->appendChild($text);
            $cell->appendChild($inline);
        }
    }
}
