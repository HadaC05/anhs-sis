/* Shared by enrollment and profile editing. Data is served locally. */
(function (root) {
    function normalize(value) {
        return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/\([^)]*\)/g, '').toLowerCase()
            .replace(/\b(city of|city|province)\b/g, '')
            .replace(/\bsta\.?\s/g, 'santa ').replace(/\bsto\.?\s/g, 'santo ')
            .replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function provinceKey(value) {
        const key = normalize(value);
        if (key.startsWith('ncr') || key === 'metropolitan manila') return 'metro manila';
        return ({ 'western samar': 'samar', 'north cotabato': 'cotabato',
            'compostela valley': 'davao de oro', 'dinagat island': 'dinagat islands' })[key] || key;
    }

    function findZipCode(records, province, municipality, barangay) {
        const provinceName = provinceKey(province);
        const city = normalize(municipality);
        const locality = normalize(barangay);
        if (!provinceName || !city) return '';
        const matches = records.filter(row => provinceKey(row.province) === provinceName
            && normalize(row.municipality) === city);
        const specific = locality ? matches.filter(row => row.locality && normalize(row.locality) === locality) : [];
        const candidates = specific.length ? specific : matches.filter(row => !row.locality);
        const codes = [...new Set(candidates.map(row => row.zip))];
        return codes.length === 1 ? codes[0] : '';
    }

    function updateZip(input, records, province, municipality, barangay, preserveExisting = false) {
        if (preserveExisting && input.value) return;
        input.value = findZipCode(records, province, municipality, barangay);
    }

    root.StudentPostal = { findZipCode, updateZip };
})(globalThis);
