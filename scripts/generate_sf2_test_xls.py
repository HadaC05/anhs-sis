"""Generate an anonymous BIFF8/OLE SF2 fixture matching the supplied LIS columns."""
from pathlib import Path
import struct
import sys
import zipfile
from xml.sax.saxutils import escape

pack = struct.pack
def record(kind, data=b''):
    return pack('<HH', kind, len(data)) + data

def bof(kind):
    return record(0x809, pack('<HHHHII', 0x600, kind, 0x0DBB, 0x07CC, 0x41, 6))

cells = {
    (0, 0): 'School Form 2 (SF2) Daily Attendance Report of Learners',
    (2, 0): 'School ID', (2, 5): '123456', (2, 9): 'School Year',
    (2, 12): '2026 - 2027', (2, 18): 'Report for the Month of', (2, 26): 'September',
    (3, 0): 'Name of School', (3, 5): 'Sample School', (3, 18): 'Grade Level',
    (3, 26): 'Grade 7 (Year I)', (3, 34): 'Section', (3, 38): 'Einstein',
    (4, 2): 'NAME (Last Name, First Name, Middle Name)',
    (6, 38): 'ABSENT', (6, 40): 'PRESENT',
    (7, 0): '1', (7, 2): 'Dela,Juan', (7, 7): 'X', (7, 8): 'T', (7, 38): '1', (7, 40): '3',
    (8, 2): '<=== MALE | TOTAL Per Day ===>',
    (9, 0): '1', (9, 2): 'Santos,Maria', (9, 38): '0', (9, 40): '4',
    (10, 2): '<=== FEMALE | TOTAL Per Day ===>', (11, 2): 'Combined TOTAL Per Day',
}
for column, day, weekday in [(7, 1, 'T'), (8, 2, 'W'), (9, 3, 'TH'), (10, 4, 'F')]:
    cells[5, column] = str(day)
    cells[6, column] = weekday
legacy = '--legacy' in sys.argv
if legacy:
    cells[6, 40] = 'TARDY'
    cells[7, 40] = '1'
    cells[9, 40] = '0'
strings = list(dict.fromkeys(cells.values()))
sst = pack('<II', len(cells), len(strings))
for text in strings:
    sst += pack('<HB', len(text), 1) + text.encode('utf-16le')
global_start = bof(5) + record(0x42, pack('<H', 1200)) + record(0xFC, sst)
sheet_name = b'SF2'
bound_size = 4 + 8 + len(sheet_name)
sheet_offset = len(global_start) + bound_size + 4
stream = global_start + record(0x85, pack('<IBBBB', sheet_offset, 0, 0, len(sheet_name), 0) + sheet_name) + record(0x0A)
stream += bof(0x10) + record(0x200, pack('<IIHHH', 0, 12, 0, 46, 0))
for (row, col), value in sorted(cells.items()):
    stream += record(0xFD, pack('<HHHI', row, col, 0, strings.index(value)))
stream += record(0x0A)
stream = stream.ljust(max(4096, ((len(stream)+511)//512)*512), b'\0')
sectors = len(stream)//512
# One FAT sector, then one directory sector, followed by the Workbook stream.
header = bytearray(512)
header[:8] = bytes.fromhex('D0CF11E0A1B11AE1')
struct.pack_into('<HHHHH', header, 24, 0x3e, 3, 0xfffe, 9, 6)
struct.pack_into('<IIIIIIIII', header, 40, 0, 1, 1, 0, 4096, 0xfffffffe, 0, 0xfffffffe, 0)
struct.pack_into('<109I', header, 76, 0, *([0xffffffff]*108))
fat = [0xfffffffd, 0xfffffffe] + [i+1 for i in range(2, 2+sectors)]
fat[-1] = 0xfffffffe
fat += [0xffffffff]*(128-len(fat))
def entry(name, kind, start, size, child=0xffffffff):
    result = bytearray(128)
    encoded = (name+'\0').encode('utf-16le')
    result[:len(encoded)] = encoded
    struct.pack_into('<HBBIII', result, 64, len(encoded), kind, 1, 0xffffffff, 0xffffffff, child)
    struct.pack_into('<IQ', result, 116, start, size)
    return result
folder = entry('Root Entry', 5, 0xfffffffe, 0, 1) + entry('Workbook', 2, 2, len(stream)) + bytes(256)
Path('tests/Fixtures/sf2-'+('legacy' if legacy else 'lis')+'-september-2026.xls').write_bytes(header + pack('<128I', *fat) + folder + stream)

# Also exercise the same old-form cells through an actual XLSX file.
if legacy:
    ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
    def column_name(index):
        result = ''
        index += 1
        while index:
            index, digit = divmod(index-1, 26)
            result = chr(65+digit) + result
        return result
    rows = []
    for row in range(12):
        xml_cells = ''.join(f'<c r="{column_name(col)}{row+1}" t="inlineStr"><is><t>{escape(value)}</t></is></c>' for (r, col), value in sorted(cells.items()) if r == row)
        rows.append(f'<row r="{row+1}">{xml_cells}</row>')
    with zipfile.ZipFile('tests/Fixtures/sf2-legacy-september-2026.xlsx', 'w', zipfile.ZIP_DEFLATED) as archive:
        archive.writestr('[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>')
        archive.writestr('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>')
        archive.writestr('xl/workbook.xml', f'<workbook xmlns="{ns}" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="SF2" sheetId="1" r:id="rId1"/></sheets></workbook>')
        archive.writestr('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>')
        archive.writestr('xl/worksheets/sheet1.xml', f'<worksheet xmlns="{ns}"><sheetData>{"".join(rows)}</sheetData></worksheet>')
