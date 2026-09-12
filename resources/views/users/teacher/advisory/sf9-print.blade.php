@php
    $periodCount = count($periods);
    $periodGroupLabel = \App\Models\GradingTerm::periodGroupLabel($periods);
    $periodColumnWidth = $periodCount > 0 ? round(42 / $periodCount, 2) : 10.5;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SF9 - Learner's Progress Report Card</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111;
            font-family: Arial, Helvetica, sans-serif;
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
            width: 277mm;
            min-height: 190mm;
            margin: 12px auto;
            padding: 8mm;
            background: #fff;
            page-break-after: always;
        }

        .front-grid,
        .back-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8mm;
            height: 100%;
        }

        h2 {
            margin: 0 0 5mm;
            text-align: center;
            font-size: 14px;
            letter-spacing: .2px;
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
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            font-size: 11px;
            font-weight: 700;
            text-align: center;
        }

        td {
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: 700;
        }

        .small {
            font-size: 10px;
        }

        .no-border {
            border: 0;
        }

        .signature-line {
            display: inline-block;
            min-width: 44mm;
            border-bottom: 1px solid #111;
        }

        .line {
            display: inline-block;
            min-width: 42mm;
            border-bottom: 1px solid #111;
            line-height: 1.4;
            text-align: center;
        }

        .mini-heading {
            margin: 5mm 0 2mm;
            text-align: center;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .descriptor-grid {
            display: grid;
            grid-template-columns: 1.2fr .8fr .8fr;
            gap: 6mm;
            margin-top: 6mm;
            font-size: 11px;
        }

        .descriptor-grid p {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin: 0 0 3px;
        }

        .cover {
            padding: 6mm 4mm 0;
        }

        .deped {
            display: grid;
            grid-template-columns: 22mm 1fr;
            align-items: start;
            gap: 4mm;
            margin-bottom: 12mm;
            text-align: center;
        }

        .logo-placeholder {
            width: 20mm;
            height: 20mm;
            border: 1px solid #888;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
        }

        .report-title {
            margin: 6mm 0 12mm;
            text-align: center;
            font-size: 18px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .student-lines p,
        .transfer p {
            margin: 0 0 5mm;
            font-size: 12px;
            font-weight: 700;
        }

        .message {
            margin: 6mm 0 10mm;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
        }

        .attendance th,
        .attendance td {
            height: 8mm;
            padding: 2px;
            font-size: 9px;
        }

        .attendance .month {
            height: 18mm;
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            text-transform: uppercase;
        }

        .values td {
            height: 13mm;
        }

        .core {
            width: 19%;
            font-weight: 700;
            text-align: center;
        }

        .statement {
            width: 39%;
            line-height: 1.15;
        }

        .quarter,
        .period-col {
            text-align: center;
        }

        .period-col {
            width: {{ $periodColumnWidth }}%;
        }

        .mapeh-child td:first-child {
            padding-left: 9mm;
        }

        .muted-cell {
            background: #f3f4f6;
        }

        .semester-title {
            margin: 0 0 2mm;
            text-align: center;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .semester-title + table {
            margin-bottom: 4mm;
        }

        .category-row td {
            background: #f3f4f6;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        .shs-grades th,
        .shs-grades td {
            font-size: 10px;
            padding: 2px 4px;
        }

        .name-grid {
            width: 100%;
            margin-bottom: 4mm;
        }

        .name-grid td {
            border: 0;
            padding: 0 2mm 3mm 0;
            font-size: 11px;
            font-weight: 700;
            vertical-align: bottom;
        }

        .cover-meta {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 4mm;
            font-size: 11px;
            font-weight: 800;
        }

        .lrn-box {
            min-width: 42mm;
            border: 1px solid #111;
            padding: 2mm 3mm;
            text-align: center;
        }

        .logo-image {
            width: 20mm;
            height: 20mm;
            object-fit: contain;
        }

        .shs-sheet {
            min-height: 190mm;
            page-break-after: auto;
        }

        .shs-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 7mm;
            height: 100%;
        }

        .shs-header {
            display: grid;
            grid-template-columns: 18mm 1fr 18mm;
            align-items: center;
            gap: 3mm;
            margin-bottom: 3mm;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.25;
        }

        .shs-header .logo-image,
        .shs-header .logo-placeholder {
            width: 16mm;
            height: 16mm;
        }

        .shs-title {
            margin: 2mm 0 1mm;
            text-align: center;
            font-size: 13px;
            font-weight: 800;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        .shs-year {
            margin: 0 0 3mm;
            text-align: center;
            font-size: 11px;
            font-weight: 700;
        }

        .shs-meta p {
            margin: 0 0 2mm;
            font-size: 11px;
            font-weight: 700;
        }

        .shs-letter {
            margin: 3mm 0 4mm;
            font-size: 10px;
            line-height: 1.35;
            text-align: justify;
        }

        .shs-letter .salutation {
            margin-bottom: 2mm;
            font-weight: 700;
        }

        .shs-section-title {
            margin: 3mm 0 2mm;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .shs-grades th,
        .shs-grades td,
        .shs-descriptors th,
        .shs-descriptors td,
        .shs-attendance th,
        .shs-attendance td,
        .shs-comments td {
            font-size: 9px;
            padding: 2px 3px;
        }

        .shs-grades .learning-area {
            width: 40%;
        }

        .shs-child td:first-child {
            padding-left: 6mm;
        }

        .shs-category td {
            background: #fbe4d5;
            font-weight: 800;
        }

        .shs-comments td {
            height: 10mm;
            vertical-align: top;
        }

        .shs-sign p {
            margin: 0 0 2.5mm;
            font-size: 11px;
            font-weight: 700;
        }

        .shs-transfer {
            font-size: 10px;
            line-height: 1.45;
        }

        .shs-transfer p {
            margin: 0 0 2mm;
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
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print SF9</button>
    </div>

    @foreach($cards as $card)
        @php
            $isSeniorHigh = (bool) ($card['is_senior_high'] ?? false);
            $observedPeriods = $card['observed_periods'] ?? $periods;
            $observedPeriodCount = count($observedPeriods);
        @endphp
        @if ($isSeniorHigh)
            @php
                $attendance = $card['attendance'] ?? \App\Support\Sf9AttendanceSummary::empty();
                $attendanceMonths = $card['attendance_months'] ?? \App\Support\Sf9ReportCardBuilder::seniorHighAttendanceMonthKeys();
                $displayedSchoolDays = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['school_days'][$month] ?? 0));
                $displayedPresent = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['days_present'][$month] ?? 0));
                $displayedAbsent = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['days_absent'][$month] ?? 0));
            @endphp
            <section class="sheet shs-sheet">
                <div class="shs-grid">
                    <div>
                        <div class="shs-header">
                            <img src="{{ asset('images/deped_logo.png') }}" alt="DepEd logo" class="logo-image">
                            <div>
                                <div>Republic of the Philippines</div>
                                <div>Department of Education</div>
                                <div>{{ $card['school_region'] ?? 'Region XIII' }}</div>
                                <div>DIVISION OF <span class="line" style="min-width: 28mm;"></span></div>
                                <div>District of <span class="line" style="min-width: 24mm;"></span></div>
                                <div>{{ $card['school_name'] ?? config('app.name') }}</div>
                            </div>
                            <div class="logo-placeholder">School<br>Logo</div>
                        </div>

                        <div class="shs-title">Learner's Performance Report</div>
                        <div class="shs-year">School Year {{ $card['school_year'] }}</div>

                        <div class="shs-meta">
                            <p>Name: <span class="line" style="min-width: 42mm;">{{ $card['name'] }}</span> Age: <span class="line" style="min-width: 12mm;">{{ $card['age'] }}</span> Sex: <span class="line" style="min-width: 16mm;">{{ $card['sex'] }}</span></p>
                            <p>LRN: <span class="line" style="min-width: 42mm;">{{ $card['lrn'] }}</span> Grade: <span class="line" style="min-width: 14mm;">{{ $card['grade'] }}</span> Section: <span class="line" style="min-width: 18mm;">{{ $card['section_name'] }}</span></p>
                            <p>Track (SHS only): <span class="line" style="min-width: 28mm;">{{ $card['shs_track'] ?? '' }}</span></p>
                        </div>

                        <div class="shs-letter">
                            <div class="salutation">Dear Parents:</div>
                            <p>This report card shows the ability and progress your child has made in the different learning areas as well as his/her core values.</p>
                            <p>The school welcomes you should you desire to know more about your child's progress.</p>
                        </div>

                        <div class="shs-section-title">Learning Progress and Achievement</div>
                        <table class="shs-grades">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="learning-area">Learning Areas</th>
                                    <th colspan="3">TERM</th>
                                    <th rowspan="2">Final Grade</th>
                                    <th rowspan="2">Remarks</th>
                                </tr>
                                <tr>
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($card['subjects'] as $row)
                                    @if ($row['category'] ?? false)
                                        <tr class="shs-category">
                                            <td>{{ $row['label'] }}</td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @else
                                        <tr class="{{ ($row['child'] ?? false) ? 'shs-child' : '' }}">
                                            <td>{{ $row['label'] !== '' ? $row['label'] : ' ' }}</td>
                                            <td class="center">{{ $row['terms']['term_1'] ?? '' }}</td>
                                            <td class="center">{{ $row['terms']['term_2'] ?? '' }}</td>
                                            <td class="center">{{ $row['terms']['term_3'] ?? '' }}</td>
                                            <td class="center bold">{{ $row['final'] ?? '' }}</td>
                                            <td class="center">{{ $row['remarks'] ?? '' }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr>
                                    <td colspan="4" class="bold center">General Average</td>
                                    <td class="center bold">{{ $card['general_average'] ?? '' }}</td>
                                    <td class="center">{{ $card['general_remarks'] ?? '' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="shs-section-title">Performance Descriptors</div>
                        <table class="shs-descriptors">
                            <thead>
                                <tr>
                                    <th>Grading Scale</th>
                                    <th>Description</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($card['performance_descriptors'] ?? [] as $descriptor)
                                    <tr>
                                        <td class="center">{{ $descriptor['scale'] }}</td>
                                        <td class="center">{{ $descriptor['description'] }}</td>
                                        <td class="center">{{ $descriptor['remarks'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <div class="shs-section-title" style="margin-top: 0;">Attendance Record</div>
                        <table class="shs-attendance">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    @foreach ($attendanceMonths as $monthKey)
                                        <th>{{ \App\Support\Sf9ReportCardBuilder::attendanceMonthAbbreviation((int) $monthKey) }}</th>
                                    @endforeach
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>No. of Class Days</td>
                                    @foreach ($attendanceMonths as $monthKey)
                                        @php $schoolDayCount = (int) ($attendance['school_days'][$monthKey] ?? 0); @endphp
                                        <td class="center">{{ $schoolDayCount > 0 ? $schoolDayCount : '' }}</td>
                                    @endforeach
                                    <td class="center">{{ $displayedSchoolDays > 0 ? $displayedSchoolDays : '' }}</td>
                                </tr>
                                <tr>
                                    <td>No. of Days Present</td>
                                    @foreach ($attendanceMonths as $monthKey)
                                        <td class="center">{{ $attendance['days_present'][$monthKey] ?? '' }}</td>
                                    @endforeach
                                    <td class="center">{{ $displayedPresent > 0 ? $displayedPresent : '' }}</td>
                                </tr>
                                <tr>
                                    <td>No. of Days Absent</td>
                                    @foreach ($attendanceMonths as $monthKey)
                                        <td class="center">{{ $attendance['days_absent'][$monthKey] ?? '' }}</td>
                                    @endforeach
                                    <td class="center">{{ $displayedAbsent > 0 ? $displayedAbsent : '' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="shs-section-title">Teacher's Comments/ Remarks</div>
                        <table class="shs-comments">
                            <tbody>
                                <tr><td>Term 1</td></tr>
                                <tr><td>Term 2</td></tr>
                                <tr><td>Term 3</td></tr>
                            </tbody>
                        </table>

                        <div class="shs-section-title">Parent's/ Guardian's Signature</div>
                        <div class="shs-sign">
                            @foreach (($card['signature_labels'] ?? ['Term 1', 'Term 2', 'Term 3']) as $signatureLabel)
                                <p>{{ $signatureLabel }} <span class="signature-line"></span></p>
                            @endforeach
                        </div>

                        <div class="shs-section-title">Certificate of Transfer</div>
                        <div class="shs-transfer">
                            <p>This is to certify that the above-named learner has completed the requirements for the grade level indicated.</p>
                            <p>Admitted to Grade: <span class="line"></span> Eligible for Admission to Grade: <span class="line"></span></p>
                            <p>Approved:</p>
                            <p class="center">
                                <span class="line">{{ $card['principal'] ?? '' }}</span>
                                <span class="line">{{ $card['adviser'] ?? '' }}</span>
                            </p>
                            <p class="center small">
                                <span style="display:inline-block;width:45mm;">School Head</span>
                                <span style="display:inline-block;width:45mm;">Adviser</span>
                            </p>
                        </div>

                        <div class="shs-section-title">Cancellation of Eligibility to Transfer</div>
                        <div class="shs-transfer">
                            <p>Admitted in: <span class="line"></span> Date: <span class="line"></span></p>
                            <p class="center"><span class="line">{{ $card['principal'] ?? '' }}</span><br><span class="small">School Head</span></p>
                        </div>
                    </div>
                </div>
            </section>
        @else
        <section class="sheet">
            <div class="front-grid">
                <div>
                    <h2>Report on Learning Progress and Achievement</h2>
                    <table>
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 39%;">Learning Areas</th>
                                <th colspan="{{ max($periodCount, 1) }}">{{ $periodGroupLabel }}</th>
                                <th rowspan="2" style="width: 13%;">Final<br>Rating</th>
                                <th rowspan="2" style="width: 14%;">Remarks</th>
                            </tr>
                            <tr>
                                @foreach($periods as $period)
                                    <th class="period-col">{{ \App\Models\GradingTerm::periodColumnLabel($period['label']) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($card['subjects'] as $row)
                                <tr class="{{ $row['child'] ? 'mapeh-child' : '' }}">
                                    <td class="{{ $row['child'] ? 'small' : 'bold' }}">{{ $row['label'] }}</td>
                                    @foreach($periods as $period)
                                        <td class="center">{{ $row['quarters'][$period['key']] ?? '' }}</td>
                                    @endforeach
                                    <td class="center bold">{{ $row['final'] ?? '' }}</td>
                                    <td class="center">{{ $row['remarks'] }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="{{ 1 + max($periodCount, 1) }}" class="bold center">General Average</td>
                                <td class="center bold">{{ $card['general_average'] ?? '' }}</td>
                                <td class="center">{{ $card['general_remarks'] }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="descriptor-grid">
                        <div>
                            <p class="bold">Descriptors</p>
                            <p><span>Outstanding</span><span>90-100</span></p>
                            <p><span>Very Satisfactory</span><span>85-89</span></p>
                            <p><span>Satisfactory</span><span>80-84</span></p>
                            <p><span>Fairly Satisfactory</span><span>75-79</span></p>
                            <p><span>Did Not Meet Expectations</span><span>Below 75</span></p>
                        </div>
                        <div>
                            <p class="bold">Grading Scale</p>
                            <p><span>90-100</span><span>Passed</span></p>
                            <p><span>85-89</span><span>Passed</span></p>
                            <p><span>80-84</span><span>Passed</span></p>
                            <p><span>75-79</span><span>Passed</span></p>
                            <p><span>Below 75</span><span>Failed</span></p>
                        </div>
                        <div>
                            <p class="bold">Remarks</p>
                            <p><span>Passed</span></p>
                            <p><span>Passed</span></p>
                            <p><span>Passed</span></p>
                            <p><span>Passed</span></p>
                            <p><span>Failed</span></p>
                        </div>
                    </div>
                </div>

                <div>
                    <h2>Report on Learner's Observed Values</h2>
                    <table class="values">
                        <thead>
                            <tr>
                                <th rowspan="2" class="core">Core Values</th>
                                <th rowspan="2" class="statement">Behavior Statements</th>
                                <th colspan="{{ max($observedPeriodCount, 1) }}">{{ $isSeniorHigh ? 'Term' : $periodGroupLabel }}</th>
                            </tr>
                            <tr>
                                @foreach($observedPeriods as $period)
                                    <th class="quarter period-col">{{ $isSeniorHigh ? $period['label'] : \App\Models\GradingTerm::periodColumnLabel($period['label']) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $lastCore = null; @endphp
                            @foreach($card['observed_values'] as $valueRow)
                                @php
                                    $coreCount = collect($card['observed_values'])->where('core_value', $valueRow['core_value'])->count();
                                    $showCore = $lastCore !== $valueRow['core_value'];
                                    $lastCore = $valueRow['core_value'];
                                @endphp
                                <tr>
                                    @if($showCore)
                                        <td rowspan="{{ $coreCount }}" class="core">{{ $valueRow['core_value'] }}</td>
                                    @endif
                                    <td class="statement">{{ $valueRow['statement'] }}</td>
                                    @foreach($observedPeriods as $period)
                                        <td class="quarter bold">{{ $valueRow['quarters'][$period['key']] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="descriptor-grid" style="grid-template-columns: .6fr 1.4fr;">
                        <div>
                            <p class="bold">Marking</p>
                            @foreach($observedValueMarkings as $marking => $label)
                                <p><span>{{ $marking }}</span></p>
                            @endforeach
                        </div>
                        <div>
                            <p class="bold">Non-Numerical Rating</p>
                            @foreach($observedValueMarkings as $marking => $label)
                                <p><span>{{ $label }}</span></p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="sheet">
            <div class="back-grid">
                <div>
                    <h2 style="margin-top: 8mm;">Attendance Record</h2>
                    @php
                        $attendance = $card['attendance'] ?? \App\Support\Sf9AttendanceSummary::empty();
                        $attendanceMonths = \App\Support\Sf9AttendanceSummary::monthKeys();
                    @endphp
                    <table class="attendance">
                        <thead>
                            <tr>
                                <th></th>
                                @foreach($attendanceMonths as $monthKey)
                                    <th class="month">{{ \App\Support\Sf9AttendanceSummary::months()[$monthKey] ?? '' }}</th>
                                @endforeach
                                <th class="month">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>School<br>Days</td>
                                @foreach($attendanceMonths as $monthKey)
                                    @php $schoolDayCount = (int) ($attendance['school_days'][$monthKey] ?? 0); @endphp
                                    <td class="center">{{ $schoolDayCount > 0 ? $schoolDayCount : '' }}</td>
                                @endforeach
                                <td class="center">{{ $attendance['total_school_days'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td>No. of<br>Days<br>Present</td>
                                @foreach($attendanceMonths as $monthKey)
                                    <td class="center">{{ $attendance['days_present'][$monthKey] ?? '' }}</td>
                                @endforeach
                                <td class="center">{{ $attendance['total_present'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td>No. of<br>Days<br>Absent</td>
                                @foreach($attendanceMonths as $monthKey)
                                    <td class="center">{{ $attendance['days_absent'][$monthKey] ?? '' }}</td>
                                @endforeach
                                <td class="center">{{ $attendance['total_absent'] ?? '' }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mini-heading">Parent / Guardian's Signature</div>
                    @foreach(($card['signature_labels'] ?? []) as $signatureLabel)
                        <p>{{ $signatureLabel }} <span class="signature-line"></span></p>
                    @endforeach

                    <div class="mini-heading">Certificate of Transfer</div>
                    <div class="transfer">
                        <p>Admitted to Grade: <span class="line"></span> Section: <span class="line"></span></p>
                        <p>Eligibility for Admission to Grade: <span class="line"></span></p>
                        <p>Approved:</p>
                        <p><span class="line"></span> <span class="line"></span></p>
                        <p class="center small"><span style="display:inline-block;width:45mm;">Principal</span><span style="display:inline-block;width:45mm;">Teacher</span></p>
                    </div>

                    <div class="mini-heading">Cancellation of Eligibility to Transfer</div>
                    <div class="transfer">
                        <p>Admitted in: <span class="line"></span></p>
                        <p>Date: <span class="line"></span></p>
                        <p class="center"><span class="line"></span><br><span class="small">Principal</span></p>
                    </div>
                </div>

                <div class="cover">
                    <div class="deped">
                        <img src="{{ asset('images/deped_logo.png') }}" alt="DepEd logo" class="logo-image">
                        <div>
                            <div>Republic of the Philippines</div>
                            <div>Department of Education</div>
                            <div>Region XIII</div>
                            <div>Division of <span class="line" style="min-width: 28mm;"></span></div>
                            <div><span class="line"></span></div>
                            <div>District</div>
                            <div><span class="line"></span></div>
                            <div>{{ config('app.name') }}</div>
                        </div>
                    </div>

                    <div class="small bold">SF 9 - JHS</div>
                    <div class="report-title">Learner's Progress Report Card</div>

                    <div class="student-lines">
                        <p>Name: <span class="line" style="min-width: 76mm;">{{ $card['name'] }}</span></p>
                        <p>Learner's Reference Number: <span class="line" style="min-width: 54mm;">{{ $card['lrn'] }}</span></p>
                        <p>Age: <span class="line" style="min-width: 52mm;">{{ $card['age'] }}</span> Sex: <span class="line" style="min-width: 35mm;">{{ $card['sex'] }}</span></p>
                        <p>Grade: <span class="line" style="min-width: 45mm;">{{ $card['grade'] }}</span> Section: <span class="line" style="min-width: 45mm;">{{ $card['section_name'] }}</span></p>
                        <p>School Year: <span class="line" style="min-width: 54mm;">{{ $card['school_year'] }}</span></p>
                    </div>

                    <div style="font-size: 12px; font-weight: 700;">Dear Parent,</div>
                    <div class="message">
                        This report card shows the ability and progress your child has made in different learning areas as well as his/her core values.
                    </div>

                    <p class="center">
                        <span class="line"></span>
                        <span class="line"></span>
                    </p>
                    <p class="center bold small">
                        <span style="display:inline-block;width:45mm;">Principal</span>
                        <span style="display:inline-block;width:45mm;">Teacher</span>
                    </p>
                </div>
            </div>
        </section>
        @endif
    @endforeach
</body>
</html>
