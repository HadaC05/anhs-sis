<?php

namespace Tests\Support;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class EClassRecordFixture
{
    public static function upload(array $sheets = ['INPUT DATA' => [], 'TERM 2' => [['Santos, Ana', '99']], 'TERM 1' => [['Santos, Ana', '87']], 'FINAL GRADES' => [['Santos, Ana', '95']]]): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ecr-test-');
        try {
            $zip = new ZipArchive;
            $zip->open($path, ZipArchive::OVERWRITE);
            $sheetList = $relations = $types = '';
            $n = 0;
            foreach ($sheets as $name => $records) {
                $n++;
                $sheetList .= '<sheet name="'.self::escape($name).'" sheetId="'.$n.'" r:id="rId'.$n.'"/>';
                $relations .= '<Relationship Id="rId'.$n.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$n.'.xml"/>';
                $types .= '<Override PartName="/xl/worksheets/sheet'.$n.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
                $rows = '<row r="12"><c r="AB12" t="inlineStr"><is><t>Initial Grade</t></is></c><c r="AC12" t="s"><v>0</v></c></row>';
                $rows .= '<row r="16"><c r="B16" t="inlineStr"><is><t>LEARNERS\' NAMES</t></is></c></row><row r="17"><c r="B17" t="inlineStr"><is><t>MALE</t></is></c></row>';
                foreach ($records as $index => [$learner, $grade]) {
                    $r = 18 + $index;
                    $rows .= '<row r="'.$r.'"><c r="B'.$r.'"><v>'.($index + 1).'</v></c><c r="C'.$r.'" t="str"><f>IF(A1="","",A1)</f><v>'.self::escape($learner).'</v></c><c r="AB'.$r.'"><v>62</v></c><c r="AC'.$r.'"><f>XLOOKUP(AB'.$r.',HELPER!C8:C48,HELPER!D8:D48)</f><v>'.self::escape($grade).'</v></c><c r="AD'.$r.'" t="inlineStr"><is><t>Very Satisfactory</t></is></c></row>';
                }
                $zip->addFromString('xl/worksheets/sheet'.$n.'.xml', '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$rows.'</sheetData><mergeCells><mergeCell ref="B16:AD16"/></mergeCells></worksheet>');
            }
            $zip->addFromString('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'.$types.'</Types>');
            $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'.$sheetList.'</sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'.$relations.'</Relationships>');
            $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><r><t>Term </t></r><r><t>Grade</t></r></si></sst>');
            $zip->close();

            return UploadedFile::fake()->createWithContent('class-record.xlsx', file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
