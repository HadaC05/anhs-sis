<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Form 1 (SF 1){{ $activeYear ? ' - ' . $activeYear->school_year : '' }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 10px; line-height: 1.3; color: #000; background: #fff; margin: 0; padding: 16px; }
        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
            .sf-page { page-break-after: always; }
            .sf-page:last-child { page-break-after: auto; }
        }
        .btn { display: inline-block; padding: 8px 16px; background: #296374; color: #fff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-right: 8px; border: none; cursor: pointer; font-size: 12px; }
        .btn:hover { opacity: 0.9; }
        .btn-pdf { background: #059669; }
        .btn-back { background: #6b7280; }
        .title { text-align: center; font-weight: bold; font-size: 12px; margin-bottom: 2px; }
        .subtitle { text-align: center; font-size: 9px; margin-bottom: 8px; }
        .header-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 6px; }
        .header-grid .label { font-weight: bold; font-size: 9px; }
        .header-grid .value { border-bottom: 1px solid #000; min-height: 12px; }
        .section-title { border: 1px solid #000; padding: 3px 6px; font-weight: bold; text-transform: uppercase; text-align: center; margin-top: 6px; background: #e5e7eb; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 4px; vertical-align: top; }
        th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; }
        .small { font-size: 8px; }
        .remarks { font-size: 8px; }
        .legend { margin-top: 8px; font-size: 8px; }
        .legend h4 { margin: 0 0 4px; font-size: 9px; text-transform: uppercase; }
        .legend-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .legend-table th, .legend-table td { font-size: 8px; }
    </style>
</head>
<body>
    @if(empty($forPdf))
    <div class="no-print" style="margin-bottom: 12px;">
        <button type="button" onclick="window.print()" class="btn">Print</button>
        <a href="{{ route('guidance.sections.master-list.pdf') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" class="btn btn-pdf">Download PDF</a>
        <a href="{{ route('guidance.sections.index') . (request()->getQueryString() ? '?' . request()->getQueryString() : '') }}" class="btn btn-back">Back to Sectioning</a>
    </div>
    @endif

    @foreach($sections as $section)
        @php
            $ay = $section->academicYear ?? $activeYear;
            $startYear = $ay?->school_year ? explode('-', $ay->school_year)[0] : null;
            $firstFriday = null;
            if ($startYear) {
                $firstFriday = \Carbon\Carbon::create($startYear, 6, 1)->next(\Carbon\Carbon::FRIDAY);
            }
            $enrollments = $section->enrollments ?? collect();
            $adviser = $section->adviser ? trim($section->adviser->first_name . ' ' . $section->adviser->last_name) : '';
        @endphp
        <div class="sf-page">
            <div class="title">School Form 1 (SF 1) School Register</div>
            <div class="subtitle">(This replaces Form 1, Master List & STS Form 2 - Family Background and Profile)</div>

            <div class="header-grid">
                <div>
                    <div class="label">School ID</div>
                    <div class="value"></div>
                </div>
                <div>
                    <div class="label">School Name</div>
                    <div class="value">Agusan National High School</div>
                </div>
                <div>
                    <div class="label">Region</div>
                    <div class="value">VIII</div>
                </div>
                <div>
                    <div class="label">Division</div>
                    <div class="value"></div>
                </div>
                <div>
                    <div class="label">District</div>
                    <div class="value"></div>
                </div>
                <div>
                    <div class="label">School Year</div>
                    <div class="value">{{ $ay?->school_year ?? '' }}</div>
                </div>
                <div>
                    <div class="label">Grade Level</div>
                    <div class="value">{{ strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) }}</div>
                </div>
                <div>
                    <div class="label">Section</div>
                    <div class="value">{{ $section->name }}</div>
                </div>
                <div>
                    <div class="label">Adviser</div>
                    <div class="value">{{ $adviser }}</div>
                </div>
            </div>

            <div class="section-title">Learner Information</div>

            <table>
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Name (Last Name, First Name, Middle Name)</th>
                        <th>Sex (M/F)</th>
                        <th>Birth Date (mm/dd/yyyy)</th>
                        <th>Age as of 1st Friday June</th>
                        <th>Mother Tongue</th>
                        <th>IP (Ethnic Group)</th>
                        <th>Religion</th>
                        <th>House #/Street/Sitio/Purok</th>
                        <th>Barangay</th>
                        <th>Municipality/City</th>
                        <th>Province</th>
                        <th>Father's Name (Last, First, Middle)</th>
                        <th>Mother's Maiden Name (Last, First, Middle)</th>
                        <th>Guardian Name</th>
                        <th>Relationship</th>
                        <th>Contact No.</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($enrollments as $enrollment)
                        @php
                            $student = $enrollment->student;
                            $application = $student?->application;
                            $profile = $student?->profile;
                            $currentAddr = ($student?->addresses ?? collect())->firstWhere('address_type', 'current');
                            $father = ($student?->guardians ?? collect())->firstWhere('relationship', 'father');
                            $mother = ($student?->guardians ?? collect())->firstWhere('relationship', 'mother');
                            $guardian = ($student?->guardians ?? collect())->firstWhere('relationship', 'guardian');
                            $birthdate = $application?->birthdate ? $application->birthdate->format('m/d/Y') : '';
                            $age = ($application?->birthdate && $firstFriday) ? $application->birthdate->diffInYears($firstFriday) : '';
                            $fullName = $application ? trim($application->last_name . ', ' . $application->first_name . ' ' . ($application->middle_name ?? '')) : '';
                            $fatherName = $father ? trim($father->last_name . ', ' . $father->first_name . ' ' . ($father->middle_name ?? '')) : '';
                            $motherName = $mother ? trim($mother->last_name . ', ' . $mother->first_name . ' ' . ($mother->middle_name ?? '')) : '';
                        @endphp
                        <tr>
                            <td>{{ $student?->lrn ?? '' }}</td>
                            <td>{{ $fullName }}</td>
                            <td>{{ strtoupper(substr($student?->sex ?? '', 0, 1)) }}</td>
                            <td>{{ $birthdate }}</td>
                            <td>{{ $age }}</td>
                            <td>{{ $student?->mother_tongue ?? '' }}</td>
                            <td>{{ $profile?->ip_community ?? '' }}</td>
                            <td>{{ $student?->religion ?? '' }}</td>
                            <td>{{ $currentAddr?->house_no ?? '' }} {{ $currentAddr?->street_name ?? '' }}</td>
                            <td>{{ $currentAddr?->barangay ?? '' }}</td>
                            <td>{{ $currentAddr?->municipality ?? '' }}</td>
                            <td>{{ $currentAddr?->province ?? '' }}</td>
                            <td>{{ $fatherName }}</td>
                            <td>{{ $motherName }}</td>
                            <td>{{ $guardian ? trim($guardian->last_name . ', ' . $guardian->first_name . ' ' . ($guardian->middle_name ?? '')) : '' }}</td>
                            <td>{{ $guardian?->relationship ?? '' }}</td>
                            <td>{{ $guardian?->contact_no ?? $father?->contact_no ?? $mother?->contact_no ?? '' }}</td>
                            <td class="remarks"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="18" class="small" style="text-align:center;">No students found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="legend">
                <h4>List and Code of Indicators under Remarks column</h4>
                <div class="legend-grid">
                    <table class="legend-table">
                        <thead>
                            <tr><th>Code</th><th>Required Information</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>BoSY</td><td>Registered at Beginning of School Year</td></tr>
                            <tr><td>EoSY</td><td>Registered at End of School Year</td></tr>
                            <tr><td>T/O</td><td>Transferred Out (Name of school & effectivity date)</td></tr>
                            <tr><td>T/I</td><td>Transferred In (Name of school & effectivity date)</td></tr>
                            <tr><td>DRP</td><td>Dropped (Reason & effectivity date)</td></tr>
                            <tr><td>LE</td><td>Late Enrollment (reason)</td></tr>
                        </tbody>
                    </table>
                    <table class="legend-table">
                        <thead>
                            <tr><th>Code</th><th>Required Information</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>B/A</td><td>Balik-Aral (Name of school last attended & year)</td></tr>
                            <tr><td>CCT</td><td>CCT Control/Reference number & effectivity date</td></tr>
                            <tr><td>LWD</td><td>Specify</td></tr>
                            <tr><td>ACL</td><td>Specify level & effectivity date</td></tr>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:8px;">
                    <span class="small">Prepared by: _________________________</span>
                    <span style="display:inline-block; width:40px;"></span>
                    <span class="small">Certified Correct: _________________________</span>
                </div>
                <div style="margin-top:4px;">
                    <span class="small">(Signature of Adviser over Printed Name)</span>
                    <span style="display:inline-block; width:62px;"></span>
                    <span class="small">(Signature of School Head over Printed Name)</span>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
