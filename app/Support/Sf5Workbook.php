<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

class Sf5Workbook
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /** Populate copies of the supplied form; each page holds 20 males and 26 females. */
    public static function create(Collection $rows, array $header): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sf5_');
        $zip = new ZipArchive;
        $opened = false;
        try {
            if ($path === false || ! copy(resource_path('templates/sf5.xlsx'), $path) || $zip->open($path) !== true) {
                throw new RuntimeException('Unable to open the SF 5 template.');
            }
            $opened = true;
            $template = $zip->getFromName('xl/worksheets/sheet1.xml');
            $sheetRels = $zip->getFromName('xl/worksheets/_rels/sheet1.xml.rels');
            $workbook = self::xml($zip->getFromName('xl/workbook.xml'));
            $rels = self::xml($zip->getFromName('xl/_rels/workbook.xml.rels'));
            $types = self::xml($zip->getFromName('[Content_Types].xml'));
            $males = $rows->where('sex', 'male')->values();
            $females = $rows->where('sex', 'female')->values();
            $pages = max(1, (int) ceil($males->count() / 20), (int) ceil($females->count() / 26));
            $draft = $rows->contains(fn ($row) => $row['average'] === null);

            for ($page = 1; $page <= $pages; $page++) {
                $sheet = self::xml($template);
                $merges = $sheet->getElementsByTagName('mergeCells')->item(0);
                foreach (['J3:O3', 'C5:D5', 'J5:O5', 'J7:K7', 'M7:O7', 'L36:O36', 'L41:O41'] as $range) {
                    $merge = $sheet->createElementNS(self::NS, 'mergeCell');
                    $merge->setAttribute('ref', $range);
                    $merges->appendChild($merge);
                }
                $merges->setAttribute('count', (string) $merges->getElementsByTagName('mergeCell')->length);
                $cells = $header;
                if ($draft) {
                    $cells['A1'] = 'DRAFT - School Form 5 (SF 5) Report on Promotion and Learning Progress & Achievement';
                }
                $pageMales = $males->slice(($page - 1) * 20, 20)->values();
                $pageFemales = $females->slice(($page - 1) * 26, 26)->values();
                foreach ([[$pageMales, 13], [$pageFemales, 34]] as [$learners, $start]) {
                    foreach ($learners as $index => $learner) {
                        $row = $start + $index;
                        foreach (['A' => 'lrn', 'B' => 'name', 'F' => 'average', 'G' => 'action', 'I' => 'failed'] as $column => $key) {
                            $cells[$column.$row] = $learner[$key] ?? '';
                        }
                    }
                }
                $cells['A33'] = $pageMales->count();
                $cells['A60'] = $pageFemales->count();
                $cells['A61'] = $pageMales->count() + $pageFemales->count();
                $cells['L13'] = 'SUMMARY TABLE (ENTIRE CLASS)';
                $cells['L61'] = "School Form 5: Page {$page} of {$pages}";
                foreach ([15 => 'PROMOTED', 17 => 'CONDITIONAL', 19 => 'RETAINED'] as $row => $action) {
                    self::counts($cells, $row, $rows->where('action', $action));
                }
                foreach ([24 => [0, 74], 26 => [75, 79], 28 => [80, 84], 30 => [85, 89], 32 => [90, 100]] as $row => [$min, $max]) {
                    self::counts($cells, $row, $rows->filter(fn ($learner) => $learner['average'] !== null && $learner['average'] >= $min && $learner['average'] <= $max));
                }
                foreach ($cells as $ref => $value) {
                    self::cell($sheet, $ref, $value);
                }
                $zip->addFromString("xl/worksheets/sheet{$page}.xml", $sheet->saveXML());
                if ($page > 1) {
                    $zip->addFromString("xl/worksheets/_rels/sheet{$page}.xml.rels", $sheetRels);
                    $node = $workbook->createElementNS(self::NS, 'sheet');
                    $node->setAttribute('name', "SF5 Page {$page}");
                    $node->setAttribute('sheetId', (string) $page);
                    $node->setAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'r:id', "sf5page{$page}");
                    $workbook->getElementsByTagName('sheets')->item(0)->appendChild($node);
                    self::element($rels, 'Relationship', ['Id' => "sf5page{$page}", 'Type' => 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet', 'Target' => "worksheets/sheet{$page}.xml"]);
                    self::element($types, 'Override', ['PartName' => "/xl/worksheets/sheet{$page}.xml", 'ContentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml']);
                }
                $sheetName = $page === 1 ? 'School Form 5 (SF5)' : "SF5 Page {$page}";
                $printArea = $workbook->createElementNS(self::NS, 'definedName');
                $printArea->setAttribute('name', '_xlnm.Print_Area');
                $printArea->setAttribute('localSheetId', (string) ($page - 1));
                $printArea->appendChild($workbook->createTextNode("'{$sheetName}'!".'$A$1:$O$62'));
                $workbook->getElementsByTagName('definedNames')->item(0)->appendChild($printArea);
            }
            $zip->addFromString('xl/workbook.xml', $workbook->saveXML());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $rels->saveXML());
            $zip->addFromString('[Content_Types].xml', $types->saveXML());
            $saved = $zip->close();
            $opened = false;
            if (! $saved) {
                throw new RuntimeException('Unable to save SF 5.');
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

    private static function counts(array &$cells, int $row, Collection $learners): void
    {
        $cells['M'.$row] = $learners->where('sex', 'male')->count();
        $cells['N'.$row] = $learners->where('sex', 'female')->count();
        $cells['O'.$row] = $learners->count();
    }

    private static function xml(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        $document->loadXML($xml, LIBXML_NONET);

        return $document;
    }

    private static function element(DOMDocument $document, string $name, array $attributes): void
    {
        $node = $document->createElementNS($document->documentElement->namespaceURI, $name);
        foreach ($attributes as $key => $value) {
            $node->setAttribute($key, $value);
        }
        $document->documentElement->appendChild($node);
    }

    private static function cell(DOMDocument $sheet, string $ref, string|int $value): void
    {
        $xpath = new DOMXPath($sheet);
        $xpath->registerNamespace('s', self::NS);
        /** @var DOMElement $cell */
        $cell = $xpath->query("//s:c[@r='{$ref}']")->item(0);
        if (! $cell) {
            $rowNumber = (int) preg_replace('/\D/', '', $ref);
            $row = $xpath->query("//s:row[@r='{$rowNumber}']")->item(0);
            if (! $row) {
                throw new RuntimeException("Missing SF 5 template row: {$rowNumber}");
            }
            $cell = $sheet->createElementNS(self::NS, 'c');
            $cell->setAttribute('r', $ref);
            $cell->setAttribute('s', $xpath->query('//s:c[@r="L37"]')->item(0)->getAttribute('s'));
            $next = null;
            $column = preg_replace('/\d/', '', $ref);
            foreach ($row->childNodes as $sibling) {
                if ($sibling instanceof DOMElement) {
                    $other = preg_replace('/\d/', '', $sibling->getAttribute('r'));
                    if (strlen($other) > strlen($column) || (strlen($other) === strlen($column) && strcmp($other, $column) > 0)) {
                        $next = $sibling;
                        break;
                    }
                }
            }
            $row->insertBefore($cell, $next);
        }
        while ($cell->firstChild) {
            $cell->removeChild($cell->firstChild);
        }
        $cell->removeAttribute('t');
        if (is_int($value)) {
            $cell->appendChild($sheet->createElementNS(self::NS, 'v', (string) $value));
        } else {
            // Inline strings preserve leading-zero LRNs and never execute formulas.
            $cell->setAttribute('t', 'inlineStr');
            $inline = $cell->appendChild($sheet->createElementNS(self::NS, 'is'));
            $text = $inline->appendChild($sheet->createElementNS(self::NS, 't'));
            $text->appendChild($sheet->createTextNode($value));
        }
    }
}
