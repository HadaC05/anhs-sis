/* The same A4 paper box and content geometry are used on screen and in print. */
@page jhs2026 { size: A4 landscape; margin: 9.4mm 9.2mm 14mm 11.8mm; }
.sheet.jhs-updated {
    page: jhs2026;
    width: 297mm;
    min-height: 210mm;
    padding: 9.4mm 9.2mm 15mm 11.8mm;
    font-family: "Bookman Old Style", "URW Bookman", Georgia, serif;
    font-size: 6.8pt;
    line-height: 1.2;
}
.jhs-updated .jhs-panels { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14.1mm; }
.jhs-updated .jhs-panels > div { border: .25mm solid #111; padding: 6.2mm 4.7mm 3mm; min-height: 185.6mm; }
.jhs-report-header { min-height: 37.5mm; text-align: center; }
.jhs-school-heading { position: relative; min-height: 21.5mm; }
.jhs-school-details { padding: 0 23mm; font-size: 6.8pt; line-height: 1.15; }
.jhs-region { margin-top: 3mm; }
.jhs-deped-logo, .jhs-school-logo { position: absolute; top: 0; width: 20mm; height: 20mm; object-fit: contain; }
.jhs-deped-logo { left: 0; }
.jhs-school-logo { right: 0; }
.jhs-school-name { font-weight: bold; margin-top: 1mm; }
.jhs-school-id { font-size: 6.5pt; }
.jhs-updated .shs-title { margin: 1mm 0 .5mm; font-size: 6.8pt; letter-spacing: 0; }
.jhs-updated .shs-year { margin: 0; font-size: 6.8pt; font-weight: normal; }
.jhs-field { display: flex; align-items: baseline; gap: 1mm; min-width: 0; }
.jhs-field > span:first-child { flex-shrink: 0; }
.jhs-entry { display: block; flex: 1; min-width: 0; min-height: 3mm; border-bottom: .2mm solid #111; text-align: center; overflow-wrap: anywhere; }
.jhs-learner-details { margin: 0 0 3mm; }
.jhs-learner-row { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, .5fr) minmax(0, .8fr); gap: 1mm; min-height: 3.25mm; }
.jhs-track { width: 47%; min-height: 3.25mm; }
.jhs-updated .shs-letter { margin: 0; min-height: 19mm; font-size: 6.8pt; line-height: 1.3; text-align: left; }
.jhs-updated .shs-letter .salutation { margin-bottom: 0; font-weight: normal; }
.jhs-updated .shs-letter p { margin: 0; text-indent: 13.3mm; }
.jhs-signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 10mm; margin: 2.5mm 0 2mm; text-align: center; }
.jhs-signatures span { display: block; min-height: 3.2mm; border-bottom: .2mm solid; }
.jhs-updated .shs-section-title { margin: 0; font-size: 6.8pt; line-height: 1.2; font-weight: bold; }
.jhs-updated .shs-grades { margin-top: .5mm; }
.jhs-updated .shs-grades th, .jhs-updated .shs-grades td { font-size: 6.8pt; padding: .3mm .5mm; border-width: .2mm; }
.jhs-updated .shs-grades th { height: 5.2mm; }
.jhs-updated .shs-grades td { height: var(--jhs-grade-row-height); }
.jhs-updated .shs-grades .learning-area { width: 38%; }
.jhs-updated .shs-grades th[rowspan]:nth-last-child(2) { width: 9%; }
.jhs-updated .shs-grades th[rowspan]:last-child { width: 25%; }
.jhs-updated .shs-child td:first-child { padding-left: .5mm; font-style: italic; font-size: 6.23pt; }
.jhs-updated .jhs-descriptor-title { text-align: left; margin-top: 3mm; }
.jhs-updated .shs-descriptors th, .jhs-updated .shs-descriptors td { border: 0; padding: 0; font-size: 6.8pt; line-height: 1.1; }
.jhs-updated .shs-attendance th, .jhs-updated .shs-attendance td { font-size: 6.8pt; padding: 0 .3mm; line-height: 1; text-align: center; border-width: .2mm; }
.jhs-updated .shs-attendance th:first-child, .jhs-updated .shs-attendance th:last-child { width: 15%; }
.jhs-updated .shs-attendance thead tr { height: 3.3mm; }
.jhs-updated .shs-attendance tbody tr { height: 6.4mm; }
.jhs-updated .shs-attendance td:first-child { font-weight: bold; }
.jhs-updated .jhs-comments-title { margin-top: 4.7mm; }
.jhs-updated .shs-comments td { height: 12.9mm; padding: .3mm; font-size: 6.8pt; font-weight: bold; }
.jhs-updated .jhs-parent-title { margin-top: 3.2mm; }
.jhs-parent-signatures { margin: 2.2mm 15.5mm 0 24mm; font-weight: bold; }
.jhs-parent-signatures .jhs-field { min-height: 5.2mm; }
.jhs-parent-signatures .jhs-field > span:first-child { width: 9mm; }
.jhs-updated .jhs-transfer-title { margin-top: 4.5mm; }
.jhs-certification { margin: 3mm 0 6mm; line-height: 2; }
.jhs-admission .jhs-field { min-height: 5.2mm; gap: 4mm; }
.jhs-admission .jhs-admitted-grade { flex: 0 0 16mm; }
.jhs-admission .jhs-eligible-grade { flex: 0 0 32mm; }
.jhs-approval { display: grid; grid-template-columns: 1fr 1fr; gap: 4mm 8mm; align-content: start; margin-top: 5mm; min-height: 17mm; }
.jhs-approval > div:first-child { grid-column: 1 / -1; }
.jhs-approval-adviser, .jhs-approval-head, .jhs-cancellation-head { text-align: center; }
.jhs-updated .jhs-cancellation-title { margin-top: 1mm; font-size: 6.8pt; }
.jhs-cancellation-fields { display: grid; grid-template-columns: 1.6fr 1fr; gap: 1mm; margin-top: 1mm; }
.jhs-cancellation-head { width: 48%; margin: 6mm auto 0; }
@media print {
    /* Print the content box inside the page margins, not a full-height paper box.
       The extra millimetre below the panels allows for printer rounding. */
    .sheet.jhs-updated {
        width: auto;
        min-height: 0;
        margin: 0;
        padding: 0;
        break-inside: avoid;
        break-after: auto;
        page-break-after: auto;
    }
    .sheet.jhs-updated ~ .sheet.jhs-updated { break-before: page; }
    .jhs-updated .jhs-panels { height: auto; }
}

.jhs-updated .jhs-comment-text { font-size: 6.2pt; line-height: 1.1; font-weight: normal; text-align: center; overflow-wrap: anywhere; white-space: normal; }
