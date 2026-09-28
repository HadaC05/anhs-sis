import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import '../../public/js/student-postal.js';

const records = JSON.parse(readFileSync(new URL('../../public/data/phlpost/zipcodes.json', import.meta.url)));
const { findZipCode, updateZip } = globalThis.StudentPostal;

test('same municipality names resolve within the selected province', () => {
    assert.equal(findZipCode(records, 'Agusan del Norte', 'Carmen', ''), '8603');
    assert.equal(findZipCode(records, 'Cebu', 'Carmen', ''), '6005');
    assert.equal(findZipCode(records, 'Agusan del Norte', 'Buenavista', ''), '8601');
    assert.equal(findZipCode(records, 'Guimaras', 'Buenavista', ''), '5044');
});

test('city labels, accents and province aliases are normalized', () => {
    assert.equal(findZipCode(records, 'AGUSAN DEL NORTE', 'CITY OF BUTUAN', 'Doongan'), '8600');
    assert.equal(findZipCode(records, 'Agusan del Norte', 'City of Cabadbaran', ''), '8605');
    assert.equal(findZipCode(records, 'Compostela Valley', 'Nabunturan', ''), '8800');
});

test('blank, partial and unknown locations never guess a ZIP', () => {
    for (const [province, city, barangay] of [
        ['', '', ''], ['Agusan del Norte', '', 'Carmen'],
        ['Unknown', 'Carmen', ''], ['Agusan del Norte', 'Buen', ''],
        ['Agusan del Norte', 'Unknown', 'Butuan'],
    ]) assert.equal(findZipCode(records, province, city, barangay), '');
});

test('NCR postal districts are scoped to their city', () => {
    assert.equal(findZipCode(records, 'NCR, FIRST DISTRICT', 'City of Manila', 'Pandacan'), '1011');
    assert.equal(findZipCode(records, 'Metro Manila', 'Quezon City', 'Pandacan'), '');
    assert.equal(findZipCode(records, 'Metro Manila', 'City of Manila', ''), '');
});

test('ambiguous codes require manual entry', () => {
    const rows = ['1111', '2222'].map(zip => ({ province: 'Test', municipality: 'Town', locality: '', zip }));
    assert.equal(findZipCode(rows, 'Test', 'Town', ''), '');
});

test('saved and corrected values survive hydration, but changed addresses clear stale codes', () => {
    const input = { value: '9999' };
    updateZip(input, records, 'Agusan del Norte', 'Butuan City', 'Doongan', true);
    assert.equal(input.value, '9999');
    updateZip(input, records, 'Agusan del Norte', 'Carmen', '', false);
    assert.equal(input.value, '8603');
    updateZip(input, records, 'Agusan del Norte', 'Unknown', '', false);
    assert.equal(input.value, '');
    updateZip(input, records, 'Agusan del Norte', 'Butuan City', '', true);
    assert.equal(input.value, '8600');
    updateZip(input, [], 'Agusan del Norte', 'Butuan City', '', false);
    assert.equal(input.value, '');
});

for (const page of ['enrollment', 'profile']) {
    const blade = readFileSync(new URL(`../../resources/views/users/student/${page}.blade.php`, import.meta.url), 'utf8');
    test(`${page} copies a manual ZIP and unlocks it when same-address is unchecked`, () => {
        const source = blade.match(/function copyCurrentToPermanent\(\) \{[\s\S]*?(?=\n    (?:    )?(?:async )?function )/)[0];
        const field = value => ({ value, readOnly: false,
            setAttribute() { this.readOnly = true; }, removeAttribute() { this.readOnly = false; } });
        const checkbox = { checked: true };
        const context = {
            sameAddress: checkbox, sameAddressCheckbox: checkbox,
            currentFields: { curr_zip_code: field('1234') },
            permanentFields: { perm_zip_code: field('5678') },
            addressControls: { perm: { provinceSelect: {}, municipalitySelect: {}, barangaySelect: {} } },
            addressData: { provinces: [1] }, syncAddressSelectsFromValues() {},
        };
        vm.createContext(context);
        vm.runInContext(source + '\ncopyCurrentToPermanent();', context);
        assert.equal(context.permanentFields.perm_zip_code.value, '1234');
        assert.equal(context.permanentFields.perm_zip_code.readOnly, true);
        checkbox.checked = false;
        vm.runInContext('copyCurrentToPermanent();', context);
        assert.equal(context.permanentFields.perm_zip_code.readOnly, false);
    });
}
