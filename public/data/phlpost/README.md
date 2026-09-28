# Philippine postal lookup

Downloaded on 2026-09-28 from official PHLPost sources:

- Current locator: https://phlpost.gov.ph/zip-code-locator/
- Nationwide workbook released by PHLPost on 2023-01-24: https://www.foi.gov.ph/requests/list-of-zipcodes/
- Workbook download: https://www.foi.gov.ph/documents/195440/zip_code_1_OMLmmHf.xlsx

The live locator contains only 960 populated rows, with gaps including most of NCR.
The workbook supplies broader coverage and NCR city/district relationships. Current
locator entries replace workbook entries for matching province/municipality pairs.
Each record retains its source. This is a retrieved snapshot, not a claim that
PHLPost revised every entry in 2026. No external service is called by student forms.

To refresh, download both sources and run:

    python scripts/build-postal-data.py locator.html master.xlsx

Review the resulting diff and run `node --test tests/js/student-postal.test.js`.
The existing address dropdown dataset is retained for compatibility. Postal and
administrative areas do not always align; unmatched or ambiguous areas require
manual entry. Matching never searches another province or guesses using substrings.
Existing saved ZIP values are preserved on load; changing an address recalculates
the suggestion. ZIP fields remain editable and retain existing server validation.

The historical AddressPinas ZIP file is no longer used by the forms.
