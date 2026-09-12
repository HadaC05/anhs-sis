@php
    $periodCount = count($periods);
    $periodGroupLabel = \App\Models\GradingTerm::periodGroupLabel($periods);
    $periodRatingLabel = \App\Models\GradingTerm::periodRatingLabel($periods);
    $periodColumnWidth = $periodCount > 0 ? round(28 / $periodCount, 2) : 7;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SF10 - Learner Permanent Academic Record</title>
    <style>
        @page {
            size: portrait;
            margin: 8mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            background: #e5e7eb;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 10px 14px;
            background: #fff;
            border-bottom: 1px solid #d1d5db;
        }

        .toolbar button {
            border: 0;
            border-radius: 6px;
            background: #296374;
            color: #fff;
            cursor: pointer;
            font-size: 12px;
            font-weight: 700;
            padding: 8px 14px;
            text-transform: uppercase;
        }

        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            padding: 8mm;
            background: #fff;
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 4mm;
        }

        .header .form-id {
            text-align: left;
            font-size: 9px;
            font-weight: 700;
        }

        .header .deped {
            font-size: 10px;
            line-height: 1.35;
        }

        .title {
            margin: 3mm 0;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            text-align: center;
        }

        .section-title {
            margin: 3mm 0 1.5mm;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th,
        td {
            border: 1px solid #111;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            font-size: 9px;
            font-weight: 700;
            text-align: center;
        }

        .info-table td {
            height: 7mm;
            font-size: 9px;
        }

        .line {
            display: inline-block;
            min-width: 24mm;
            border-bottom: 1px solid #111;
            line-height: 1.2;
            padding: 0 2px;
        }

        .line.short {
            min-width: 18mm;
        }

        .line.signature {
            min-width: 30mm;
        }

        .scholastic-block {
            margin-top: 3mm;
            page-break-inside: avoid;
        }

        .scholastic-meta td {
            border: 0;
            padding: 1px 2px;
            font-size: 8.5px;
        }

        .grades th,
        .grades td {
            font-size: 8.5px;
            height: 5.5mm;
        }

        .learning-area {
            width: 34%;
            text-align: left;
        }

        .period-col {
            width: {{ $periodColumnWidth }}%;
        }

        .final-col {
            width: 10%;
        }

        .remarks-col {
            width: 12%;
        }

        .child-row .child-label {
            padding-left: 5mm;
            font-size: 8px;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: 700;
        }

        .small {
            font-size: 8px;
        }

        .remedial th,
        .remedial td {
            font-size: 8px;
            height: 5mm;
        }

        .certification {
            margin-top: 4mm;
            font-size: 9px;
            line-height: 1.5;
        }

        .certification .line {
            min-width: 35mm;
        }

        .footer-note {
            margin-top: 2mm;
            font-size: 7px;
            font-style: italic;
            text-align: right;
        }

        @media print {
            body {
                background: #fff;
            }

            .toolbar {
                display: none;
            }

            .sheet {
                width: auto;
                min-height: auto;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print SF10</button>
    </div>

    @foreach($cards as $card)
        <section class="sheet">
            <div class="header">
                <div class="form-id">SF10-JHS</div>
                <div class="deped">
                    <div>Republic of the Philippines</div>
                    <div>Department of Education</div>
                </div>
                <div class="title">Learner Permanent Academic Record for Junior High School (SF10-JHS)</div>
                <div class="small">(Formerly Form 137)</div>
            </div>

            <div class="section-title">Learner's Information</div>
            <table class="info-table">
                <tr>
                    <td>Last Name: <span class="line">{{ $card['last_name'] }}</span></td>
                    <td>First Name: <span class="line">{{ $card['first_name'] }}</span></td>
                    <td>Name Ext. (Jr, I, II): <span class="line">{{ $card['suffix'] }}</span></td>
                </tr>
                <tr>
                    <td colspan="3">Middle Name: <span class="line">{{ $card['middle_name'] }}</span></td>
                </tr>
                <tr>
                    <td>Learner Reference Number (LRN): <span class="line">{{ $card['lrn'] }}</span></td>
                    <td>Birthdate (mm/dd/yyyy): <span class="line">{{ $card['birthdate'] }}</span></td>
                    <td>Sex: <span class="line">{{ $card['sex'] }}</span></td>
                </tr>
            </table>

            <div class="section-title">Eligibility for JHS Enrollment</div>
            <table class="info-table">
                <tr>
                    <td>Elementary School Completer General Average: <span class="line">{{ $card['eligibility']['elementary_average'] }}</span></td>
                    <td colspan="2">Citation (if Any): <span class="line">{{ $card['eligibility']['citation'] }}</span></td>
                </tr>
                <tr>
                    <td>Name of Elementary School: <span class="line">{{ $card['eligibility']['elementary_school'] }}</span></td>
                    <td>School ID: <span class="line">{{ $card['eligibility']['elementary_school_id'] }}</span></td>
                    <td>Address of School: <span class="line">{{ $card['eligibility']['elementary_school_address'] }}</span></td>
                </tr>
                <tr>
                    <td colspan="3" class="bold">Other Credential Presented</td>
                </tr>
                <tr>
                    <td>PEPT Passer Rating: <span class="line"></span></td>
                    <td>ALS A &amp; E Passer Rating: <span class="line"></span></td>
                    <td>Others (Pls. Specify): <span class="line"></span></td>
                </tr>
                <tr>
                    <td colspan="2">Date of Examination/Assessment (mm/dd/yyyy): <span class="line"></span></td>
                    <td>Name and Address of Testing Center: <span class="line"></span></td>
                </tr>
            </table>

            <div class="section-title">Scholastic Record</div>

            @foreach(array_slice($card['scholastic_records'], 0, 2) as $record)
                <div class="scholastic-block">
                    @include('users.teacher.advisory.partials.sf10-scholastic-record', ['record' => $record])
                </div>
            @endforeach

            <div class="certification">
                <div class="section-title">Certification</div>
                <p>
                    I CERTIFY that this is a true record of <span class="line">{{ $card['full_name'] }}</span> with LRN <span class="line">{{ $card['lrn'] }}</span>
                    and that he/she is eligible for admission to Grade <span class="line"></span>.
                </p>
                <p>
                    Name of School: <span class="line">{{ $card['school_meta']['name'] }}</span>
                    School ID: <span class="line">{{ $card['school_meta']['id'] }}</span>
                    Last School Year Attended: <span class="line">{{ $card['last_school_year'] }}</span>
                </p>
                <p>
                    Date: <span class="line"></span>
                    Signature of Principal/School Head over Printed Name <span class="line signature"></span>
                    (Affix School Seal Here)
                </p>
            </div>

            <div class="footer-note">Revised 2025 based on DepEd Order No. 10, s. 2024</div>
        </section>

        <section class="sheet">
            <div class="header">
                <div class="form-id">SF10-JHS Page 2 of 2</div>
            </div>

            @foreach(array_slice($card['scholastic_records'], 2, 4) as $record)
                <div class="scholastic-block">
                    @include('users.teacher.advisory.partials.sf10-scholastic-record', ['record' => $record])
                </div>
            @endforeach

            <div class="certification">
                <div class="section-title">For Transfer Out / JHS Completer Only</div>
                <div class="section-title">Certification</div>
                <p>
                    I CERTIFY that this is a true record of <span class="line">{{ $card['full_name'] }}</span> with LRN <span class="line">{{ $card['lrn'] }}</span>
                    and that he/she is eligible for admission to Grade <span class="line"></span>.
                </p>
                <p>
                    Name of School: <span class="line">{{ $card['school_meta']['name'] }}</span>
                    School ID: <span class="line">{{ $card['school_meta']['id'] }}</span>
                    Last School Year Attended: <span class="line">{{ $card['last_school_year'] }}</span>
                </p>
                <p>
                    Date: <span class="line"></span>
                    Signature of Principal/School Head over Printed Name <span class="line signature"></span>
                    (Affix School Seal Here)
                </p>
            </div>

            <div class="footer-note">(May add Certification box if needed) Revised 2025 based on DepEd Order No. 10, s. 2024</div>
        </section>
    @endforeach
</body>
</html>
