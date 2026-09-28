<?php

use App\Support\ClassListSpreadsheet;

test('xlsx imports read default and prefixed spreadsheet namespaces', function (string $prefix) {
    $path = tempnam(sys_get_temp_dir(), 'class-list-test-');
    $tag = $prefix === '' ? '' : $prefix.':';
    $namespace = $prefix === '' ? 'xmlns' : 'xmlns:'.$prefix;
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('xl/workbook.xml', '<'.$tag.'workbook '.$namespace.'="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><'.$tag.'sheets><'.$tag.'sheet name="Students" sheetId="1" r:id="rId1"/></'.$tag.'sheets></'.$tag.'workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Target="worksheets/students.xml"/></Relationships>');
    $zip->addFromString('xl/sharedStrings.xml', '<'.$tag.'sst '.$namespace.'="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><'.$tag.'si><'.$tag.'t>Cruz, Juan</'.$tag.'t></'.$tag.'si></'.$tag.'sst>');
    $zip->addFromString('xl/worksheets/students.xml', '<'.$tag.'worksheet '.$namespace.'="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><'.$tag.'sheetData><'.$tag.'row r="1"><'.$tag.'c r="A1" t="inlineStr"><'.$tag.'is><'.$tag.'t>LRN</'.$tag.'t></'.$tag.'is></'.$tag.'c><'.$tag.'c r="B1" t="inlineStr"><'.$tag.'is><'.$tag.'t>Name</'.$tag.'t></'.$tag.'is></'.$tag.'c></'.$tag.'row><'.$tag.'row r="2"><'.$tag.'c r="A2"><'.$tag.'v>987654321098</'.$tag.'v></'.$tag.'c><'.$tag.'c r="B2" t="s"><'.$tag.'v>0</'.$tag.'v></'.$tag.'c></'.$tag.'row></'.$tag.'sheetData></'.$tag.'worksheet>');
    $zip->close();

    try {
        expect(ClassListSpreadsheet::rowsFromPath($path, 'xlsx'))->toBe([
            ['LRN', 'Name'],
            ['987654321098', 'Cruz, Juan'],
        ]);
    } finally {
        unlink($path);
    }
})->with(['', 'x']);
