@php
    $attendance = $card['attendance'] ?? \App\Support\Sf9AttendanceSummary::empty();
    $attendanceMonths = $card['attendance_months'] ?? \App\Support\Sf9ReportCardBuilder::seniorHighAttendanceMonthKeys();
    $isSeniorHigh = (bool) ($card['is_senior_high'] ?? false);
    $formPeriods = $isSeniorHigh ? ($card['senior_high_terms'] ?? []) : $periods;
    $gradeValueKey = $isSeniorHigh ? 'terms' : 'quarters';
    $gradeRows = $isSeniorHigh ? $card['subjects'] : \App\Support\Sf9ReportCardBuilder::updatedJuniorHighRows($card['subjects']);
    $division = trim($card['school_division'] ?? '');
    $divisionHeading = $division !== '' && ! preg_match('/^(?:schools?\s+)?division\b/i', $division) ? 'SCHOOLS DIVISION OF '.$division : $division;
    $displayedSchoolDays = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['school_days'][$month] ?? 0));
    $displayedPresent = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['days_present'][$month] ?? 0));
    $displayedAbsent = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['days_absent'][$month] ?? 0));
@endphp
<section class="sheet jhs-updated" data-school-level="{{ $isSeniorHigh ? 'senior-high' : 'junior-high' }}">
    <div class="shs-grid jhs-panels">
        <div>
            <header class="jhs-report-header">
                <div class="jhs-school-heading">
                    <img src="{{ asset('images/deped_logo.png') }}" alt="DepEd logo" class="jhs-deped-logo">
                    <div class="jhs-school-details">
                        <div>Republic of the Philippines</div>
                        <div>Department of Education</div>
                        <div class="jhs-region">{{ $card['school_region'] ?? '' }}</div>
                        <div class="bold">{{ $divisionHeading }}</div>
                        <div>{{ $card['school_district'] ?? '' }}</div>
                    </div>
                    @if ($card['school_logo'] ?? null)
                        <img src="{{ $card['school_logo'] }}" alt="School logo" class="jhs-school-logo">
                    @endif
                </div>
                <div class="jhs-school-name">{{ $card['school_name'] ?? '' }}</div>
                <div class="jhs-school-id">School ID: {{ $card['school_id'] ?? '' }}</div>
                <div class="shs-title">Learner's Performance Report</div>
                <div class="shs-year">School Year {{ $card['school_year'] }}</div>
            </header>

            <div class="jhs-learner-details">
                <div class="jhs-learner-row">
                    <div class="jhs-field"><span>Name:</span><span class="jhs-entry">{{ $card['name'] }}</span></div>
                    <div class="jhs-field"><span>Age:</span><span class="jhs-entry">{{ $card['age'] }}</span></div>
                    <div class="jhs-field"><span>Sex:</span><span class="jhs-entry">{{ $card['sex'] }}</span></div>
                </div>
                <div class="jhs-learner-row">
                    <div class="jhs-field"><span>LRN:</span><span class="jhs-entry">{{ $card['lrn'] }}</span></div>
                    <div class="jhs-field"><span>Grade:</span><span class="jhs-entry">{{ preg_replace('/^grade\s+/i', '', $card['grade']) }}</span></div>
                    <div class="jhs-field"><span>Section:</span><span class="jhs-entry">{{ $card['section_name'] }}</span></div>
                </div>
                <div class="jhs-field jhs-track"><span>Track (SHS only):</span><span class="jhs-entry">{{ $card['shs_track'] ?? '' }}</span></div>
            </div>

            <div class="shs-letter">
                <div class="salutation">Dear Parents,</div>
                <p>This Performance Report presents your child's progress and achievement in the different learning areas.</p>
                <p>The school welcomes you to reach out should you wish to know more about your child's learning and performance.</p>
            </div>

            <div class="jhs-signatures"><div><span>{{ $card['principal'] ?? '' }}</span>School Head</div><div><span>{{ $card['adviser'] ?? '' }}</span>Adviser</div></div>
            <div class="shs-section-title">Learning Progress and Achievement</div>
            <table class="shs-grades" style="--jhs-grade-row-height: {{ count($gradeRows) > 10 ? '4.35mm' : '5.22mm' }};">
                <thead>
                    <tr>
                        <th rowspan="2" class="learning-area">Learning Areas</th>
                        <th colspan="{{ count($formPeriods) }}">TERM</th>
                        <th rowspan="2">Final Grade</th>
                        <th rowspan="2">Remarks</th>
                    </tr>
                    <tr>
                        @foreach ($formPeriods as $term)
                            <th>{{ $isSeniorHigh ? $loop->iteration : (preg_match('/^term\s+\d+$/i', $term['label']) ? 'T'.\App\Models\GradingTerm::periodColumnLabel($term['label']) : \App\Models\GradingTerm::periodColumnLabel($term['label'])) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($gradeRows as $row)
                        @if ($row['category'] ?? false)
                            <tr class="shs-category">
                                <td>{{ $row['label'] }}</td>
                                @foreach ($formPeriods as $term)
                                    <td></td>
                                @endforeach
                                <td></td>
                                <td></td>
                            </tr>
                        @else
                            <tr class="{{ ($row['child'] ?? false) ? 'shs-child' : '' }}">
                                <td>{{ $row['label'] !== '' ? $row['label'] : ' ' }}</td>
                                @foreach ($formPeriods as $term)
                                    <td class="center">{{ $row[$gradeValueKey][$term['key']] ?? '' }}</td>
                                @endforeach
                                <td class="center bold">{{ $row['final'] ?? '' }}</td>
                                <td class="center">{{ $row['remarks'] ?? '' }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr>
                        <td colspan="{{ count($formPeriods) + 1 }}" class="bold center">General Average</td>
                        <td class="center bold">{{ $card['general_average'] ?? '' }}</td>
                        <td class="center">{{ $card['general_remarks'] ?? '' }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="shs-section-title jhs-descriptor-title">Performance Descriptors</div>
            <table class="shs-descriptors">
                <thead>
                    <tr>
                        <th>Grading Scale</th>
                        <th>Descriptors</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach (\App\Support\Sf9ReportCardBuilder::seniorHighPerformanceDescriptors() as $descriptor)
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

            <div class="shs-section-title jhs-comments-title">Teacher's Comments / Remarks</div>
            <table class="shs-comments">
                <tbody>
                    @foreach($formPeriods as $period)
                        <tr><td>{{ $period['label'] }}<div class="jhs-comment-text">{{ $card['teacher_comments'][$period['key']] ?? '' }}</div></td></tr>
                    @endforeach
                </tbody>
            </table>

            <div class="shs-section-title jhs-parent-title">Parent/s Guardian's Signature</div>
            <div class="jhs-parent-signatures">
                @foreach ($formPeriods as $period)
                    <div class="jhs-field"><span>{{ $period['label'] }}</span><span class="jhs-entry"></span></div>
                @endforeach
            </div>

            <div class="shs-section-title jhs-transfer-title">Certificate of Transfer</div>
            <div class="jhs-transfer">
                <p class="jhs-certification">This is to certify that the above-named learner has satisfactorily completed the requirements for the grade level indicated.</p>
                <div class="jhs-admission">
                    <div class="jhs-field"><span>Admitted to Grade:</span><span class="jhs-entry jhs-admitted-grade"></span></div>
                    <div class="jhs-field"><span>Eligible for Admission to Grade:</span><span class="jhs-entry jhs-eligible-grade"></span></div>
                </div>
                <div class="jhs-approval">
                    <div>Approved:</div>
                    <div class="jhs-approval-head"><span class="jhs-entry">{{ $card['principal'] ?? '' }}</span><span>School Head</span></div>
                    <div class="jhs-approval-adviser"><span class="jhs-entry">{{ $card['adviser'] ?? '' }}</span><span>Adviser</span></div>
                </div>
            </div>

            <div class="shs-section-title jhs-cancellation-title">Cancellation of Eligibility to Transfer</div>
            <div class="jhs-cancellation">
                <div class="jhs-cancellation-fields">
                    <div class="jhs-field"><span>Admitted in:</span><span class="jhs-entry"></span></div>
                    <div class="jhs-field"><span>Date:</span><span class="jhs-entry"></span></div>
                </div>
                <div class="jhs-cancellation-head"><span class="jhs-entry">{{ $card['principal'] ?? '' }}</span><span>School Head</span></div>
            </div>
        </div>
    </div>
</section>
