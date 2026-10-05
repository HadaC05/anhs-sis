            @php
                $attendance = $card['attendance'] ?? \App\Support\Sf9AttendanceSummary::empty();
                $attendanceMonths = $card['attendance_months'] ?? \App\Support\Sf9ReportCardBuilder::seniorHighAttendanceMonthKeys();
                $displayedSchoolDays = collect($attendanceMonths)->sum(fn ($month) => (int) ($attendance['school_days'][$month] ?? 0));
            @endphp
            <section class="sheet jhs-updated">
                <div class="shs-grid">
                    <div>
                        <div class="shs-header">
                            <img src="{{ asset('images/deped_logo.png') }}" alt="DepEd logo" class="logo-image">
                            <div>
                                <div>Republic of the Philippines</div>
                                <div>Department of Education</div>
                                <div>{{ $card['school_region'] ?? 'Region XIII' }}</div>
                                <div>SCHOOLS DIVISION OF <span class="line" style="min-width: 28mm;">{{ $card['school_division'] ?? '' }}</span></div>
                                <div>District of <span class="line" style="min-width: 24mm;">{{ $card['school_district'] ?? '' }}</span></div>
                                <div>{{ $card['school_name'] ?? config('app.name') }}</div>
                                <div>School ID: {{ $card['school_id'] ?? '' }}</div>
                            </div>
                            @if ($card['school_logo'] ?? null)
                                <img src="{{ $card['school_logo'] }}" alt="School logo" class="logo-image">
                            @else
                                <div class="logo-placeholder">School<br>Logo</div>
                            @endif
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
                            <p>This Performance Report presents your child's progress and achievement in the different learning areas.</p>
                            <p>The school welcomes you to reach out should you wish to know more about your child's learning and performance.</p>
                        </div>

                        <div class="jhs-signatures"><div><span>{{ $card['principal'] ?? '' }}</span>School Head</div><div><span>{{ $card['adviser'] ?? '' }}</span>Adviser</div></div>
                        <div class="shs-section-title">Learning Progress and Achievement</div>
                        <table class="shs-grades">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="learning-area">Learning Areas</th>
                                    <th colspan="{{ count($periods) }}">TERM</th>
                                    <th rowspan="2">Final Grade</th>
                                    <th rowspan="2">Remarks</th>
                                </tr>
                                <tr>
                                    @foreach ($periods as $term)
                                        <th>{{ \App\Models\GradingTerm::periodColumnLabel($term['label']) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach (\App\Support\Sf9ReportCardBuilder::updatedJuniorHighRows($card['subjects']) as $row)
                                    @if ($row['category'] ?? false)
                                        <tr class="shs-category">
                                            <td>{{ $row['label'] }}</td>
                                            @foreach ($periods as $term)
                                                <td></td>
                                            @endforeach
                                            <td></td>
                                            <td></td>
                                        </tr>
                                    @else
                                        <tr class="{{ ($row['child'] ?? false) ? 'shs-child' : '' }}">
                                            <td>{{ $row['label'] !== '' ? $row['label'] : ' ' }}</td>
                                            @foreach ($periods as $term)
                                                <td class="center">{{ $row['quarters'][$term['key']] ?? '' }}</td>
                                            @endforeach
                                            <td class="center bold">{{ $row['final'] ?? '' }}</td>
                                            <td class="center">{{ $row['remarks'] ?? '' }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                                <tr>
                                    <td colspan="{{ count($periods) + 1 }}" class="bold center">General Average</td>
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
                                    <td class="center">{{ $attendance['total_present'] ?? '' }}</td>
                                </tr>
                                <tr>
                                    <td>No. of Days Absent</td>
                                    @foreach ($attendanceMonths as $monthKey)
                                        <td class="center">{{ $attendance['days_absent'][$monthKey] ?? '' }}</td>
                                    @endforeach
                                    <td class="center">{{ $attendance['total_absent'] ?? '' }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="shs-section-title">Teacher's Comments/ Remarks</div>
                        <table class="shs-comments">
                            <tbody>
                                @foreach($periods as $period)
                                    <tr><td>{{ $period['label'] }}</td></tr>
                                @endforeach
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
                            <p>This is to certify that the above-named learner has satisfactorily completed the requirements for the grade level indicated.</p>
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
