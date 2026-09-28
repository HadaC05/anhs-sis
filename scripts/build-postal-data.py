"""Build the local postal snapshot from PHLPost's HTML locator and FOI workbook.

Usage: python scripts/build-postal-data.py locator.html master.xlsx
Only the standard library is needed. See public/data/phlpost/README.md.
"""
import html
import json
import re
import sys
import unicodedata
import xml.etree.ElementTree as ET
import zipfile
from pathlib import Path


def normalize(value):
    value = ''.join(c for c in unicodedata.normalize('NFD', value.lower()) if not unicodedata.combining(c))
    value = re.sub(r'\([^)]*\)', '', value)
    value = re.sub(r'\b(city of|city|province)\b', '', value)
    value = re.sub(r'[^a-z0-9]+', ' ', value).strip()
    return {'western samar': 'samar', 'north cotabato': 'cotabato',
            'compostela valley': 'davao de oro', 'dinagat island': 'dinagat islands'}.get(value, value)


records = {}
with zipfile.ZipFile(sys.argv[2]) as workbook:
    ns = {'m': 'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
    strings = [''.join(n.itertext()) for n in ET.fromstring(workbook.read('xl/sharedStrings.xml')).findall('m:si', ns)]
    for sheet in [1, 3, 5, 7]:
        area = ''
        for row in ET.fromstring(workbook.read(f'xl/worksheets/sheet{sheet}.xml')).findall('.//m:row', ns):
            cells = {}
            for cell in row.findall('m:c', ns):
                v = cell.find('m:v', ns)
                value = v.text if v is not None else ''
                cells[re.sub(r'\d', '', cell.get('r'))] = strings[int(value)] if cell.get('t') == 's' else value
            place, code = cells.get('A', '').strip(), cells.get('B', '').strip()
            if not place or place.startswith(('REGION', '***')):
                continue
            if not code:
                if place not in ['NORTH', 'SOUTH']:
                    area = place
                continue
            if not re.fullmatch(r'\d{4}', code):
                continue
            province, municipality, locality = (('Metro Manila', area, place) if sheet == 1 else (area, place, ''))
            key = (normalize(province), normalize(municipality), normalize(locality))
            records[key] = dict(province=province, municipality=municipality, locality=locality, zip=code, source='foi-2023')

page = Path(sys.argv[1]).read_text(encoding='utf-8')
for row in re.findall(r'<tr[^>]*>(.*?)</tr>', page, re.S):
    cells = [html.unescape(re.sub('<[^>]+>', '', c)).strip() for c in re.findall(r'<td[^>]*>(.*?)</td>', row, re.S)]
    if len(cells) != 4 or not re.fullmatch(r'\d{4}', cells[3]):
        continue
    _, province, municipality, code = cells
    # NCR locator rows contain districts without their parent city. Use the workbook hierarchy.
    if province == 'Metro Manila':
        continue
    key = (normalize(province), normalize(municipality), '')
    records[key] = dict(province=province, municipality=municipality, locality='', zip=code, source='locator')

target = Path(__file__).resolve().parents[1] / 'public/data/phlpost/zipcodes.json'
target.write_text(json.dumps(list(records.values()), ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
print(f'Wrote {len(records)} postal records')
