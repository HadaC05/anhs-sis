<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;
use Smalot\PdfParser\Parser;
use ZipArchive;

class ClassListSpreadsheet
{
    public static function rowsFromUpload(UploadedFile $file): array
    {
        $extension = Str::lower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => self::rowsFromCsv($file->getRealPath()),
            'xlsx' => self::rowsFromXlsx($file),
            'pdf' => self::rowsFromPdf($file),
            default => throw new RuntimeException('Only CSV, XLSX, and text-based PDF class list files are supported.'),
        };
    }

    /**
     * Turn an exported SF-1 PDF into the same small tabular shape used by the
     * CSV/XLSX importer.  SF-1 lists a learner's LRN, sex, birth date, and
     * name together, even when the PDF has no machine-readable table grid.
     */
    private static function rowsFromPdf(UploadedFile $file): array
    {
        try {
            $text = (new Parser)->parseFile($file->getRealPath())->getText();
        } catch (\Throwable) {
            throw new RuntimeException('The PDF could not be read. Upload the original text-based SF-1 export, not a scanned image.');
        }

        $text = preg_replace('/\s+/', ' ', $text) ?? '';
        preg_match_all('/(?<!\d)(\d{12})(?!\d)(.*?)(?=(?<!\d)\d{12}(?!\d)|$)/', $text, $matches, PREG_SET_ORDER);

        $rows = [['LRN', 'Name', 'Sex', 'Birth Date']];
        foreach ($matches as $match) {
            $details = $match[2];
            $name = '';
            if (preg_match('/([[:alpha:] .\'-]+),\s*([[:alpha:] .\'-]+)(?:,\s*([[:alpha:] .\'-]+))?/', $details, $nameMatch)) {
                $name = trim($nameMatch[1]).', '.trim($nameMatch[2]).(isset($nameMatch[3]) && trim($nameMatch[3]) !== '' ? ', '.trim($nameMatch[3]) : '');
            }

            preg_match('/\b([MF])\b/i', $details, $sexMatch);
            preg_match('/\b(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4}|\d{4}[\/-]\d{1,2}[\/-]\d{1,2})\b/', $details, $birthdateMatch);
            $rows[] = [$match[1], $name, $sexMatch[1] ?? '', $birthdateMatch[1] ?? ''];
        }

        if (count($rows) === 1) {
            throw new RuntimeException('This PDF contains only the SF-1 template and no learner records. Export or print the completed class list with student rows, then upload that file. An XLSX or CSV export is also supported.');
        }

        return $rows;
    }

    private static function rowsFromCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (! $handle) {
            throw new RuntimeException('The uploaded class list could not be opened.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        fclose($handle);

        return $rows;
    }

    private static function rowsFromXlsx(UploadedFile $file): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('XLSX import requires the PHP zip extension.');
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
            throw new RuntimeException('The uploaded XLSX file could not be opened.');
        }

        $sharedStrings = self::sharedStrings($zip);
        $sheetPath = self::firstWorksheetPath($zip);
        $xml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('No worksheet was found in the uploaded XLSX file.');
        }

        $sheet = simplexml_load_string($xml);
        if (! $sheet) {
            throw new RuntimeException('The worksheet could not be read.');
        }

        $rows = [];
        foreach ($sheet->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $reference = (string) $cell['r'];
                $columnIndex = self::columnIndex($reference);
                $values[$columnIndex] = self::cellValue($cell, $sharedStrings);
            }

            if ($values !== []) {
                ksort($values);
                $max = max(array_keys($values));
                $normalized = [];
                for ($index = 0; $index <= $max; $index++) {
                    $normalized[] = trim((string) ($values[$index] ?? ''));
                }
                $rows[] = $normalized;
            }
        }

        return $rows;
    }

    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $strings = [];
        $shared = simplexml_load_string($xml);
        foreach ($shared->si ?? [] as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;

                continue;
            }

            $text = '';
            foreach ($item->r ?? [] as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private static function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relationsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relationsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $relations = simplexml_load_string($relationsXml);
        $workbook->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');

        $firstSheet = $workbook->sheets->sheet[0] ?? null;
        $relationId = $firstSheet ? (string) $firstSheet->attributes('r', true)->id : '';

        foreach ($relations->Relationship ?? [] as $relation) {
            if ((string) $relation['Id'] === $relationId) {
                $target = (string) $relation['Target'];

                return str_starts_with($target, 'worksheets/')
                    ? 'xl/'.$target
                    : 'xl/worksheets/'.basename($target);
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function cellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) $cell['t'];

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        $value = (string) ($cell->v ?? '');
        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        return $value;
    }

    private static function columnIndex(string $reference): int
    {
        $letters = preg_replace('/[^A-Z]/', '', strtoupper($reference));
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return max(0, $index - 1);
    }
}
