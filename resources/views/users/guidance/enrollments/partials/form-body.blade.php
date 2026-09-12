@php
    $student = $enrollment->student;
    $profile = $student?->profile;
    $schoolYear = (string) ($enrollment->academicYear?->school_year ?? '');
    $schoolYearParts = explode('-', $schoolYear);
    $syStart = $schoolYearParts[0] ?? '';
    $syEnd = $schoolYearParts[1] ?? '';
    $gradeDigits = preg_replace('/\D+/', '', (string) ($enrollment->gradeLevel?->grade_label ?? $enrollment->grade_level ?? '')) ?: '';
    $lrn = (string) ($student?->lrn ?? '');
    $returning = $enrollment->learner_type === \App\Models\LearnerType::BALIK_ARAL;
    $sex = strtolower((string) ($student?->sex ?? ''));
    $birthdate = $student?->birthdate;
    $birthMonth = $birthdate?->format('m') ?? '';
    $birthDay = $birthdate?->format('d') ?? '';
    $birthYear = $birthdate?->format('Y') ?? '';
    $ageReference = $enrollment->academicYear?->start_date ?? $enrollment->created_at ?? now();
    $age = $birthdate ? $birthdate->diff($ageReference)->y : '';
    $fill = static fn (?string $value): string => filled($value) ? mb_strtoupper(trim($value)) : '';
    $lastCompletedRaw = (string) ($enrollment->last_grade_level_completed ?? '');
    $lastCompleted = $lastCompletedRaw === ''
        ? ''
        : (str_starts_with($lastCompletedRaw, 'grade_')
            ? \App\Models\GradeLevel::valueToLabel($lastCompletedRaw)
            : 'Grade '.$lastCompletedRaw);
    $addressLines = $student?->addresses?->groupBy('address_type') ?? collect();
    $currentAddr = optional($addressLines->get('current'))->first();
    $permanentAddr = optional($addressLines->get('permanent'))->first();
    $sameAddress = $currentAddr && $permanentAddr
        && (string) $currentAddr->house_no === (string) $permanentAddr->house_no
        && (string) $currentAddr->street_name === (string) $permanentAddr->street_name
        && (string) $currentAddr->barangay === (string) $permanentAddr->barangay
        && (string) $currentAddr->municipality === (string) $permanentAddr->municipality
        && (string) $currentAddr->province === (string) $permanentAddr->province
        && (string) $currentAddr->zip_code === (string) $permanentAddr->zip_code;
    $father = ($student?->guardians ?? collect())->firstWhere('relationship', 'father');
    $mother = ($student?->guardians ?? collect())->firstWhere('relationship', 'mother');
    $guardian = ($student?->guardians ?? collect())->firstWhere('relationship', 'guardian');
    $fourPs = (string) ($profile?->four_ps_household_id ?? '');
    $schoolId = (string) ($enrollment->school_id_from_previous_school ?? '');
    $placementAssessment = $enrollment->placementAssessmentRecommendation();
    $parentName = trim(($guardian?->first_name ?? $mother?->first_name ?? $father?->first_name ?? '').' '.($guardian?->last_name ?? $mother?->last_name ?? $father?->last_name ?? ''));
@endphp

<section class="sheet">
    <div class="sheet-body">
        <div class="beef-header">
            <img src="{{ asset('images/deped_logo.png') }}" alt="Department of Education seal" class="beef-logo">
            <div class="beef-title">
                <h1>Enhanced Basic Education Enrollment Form</h1>
                <p class="not-for-sale">THIS FORM IS NOT FOR SALE.</p>
            </div>
            <div class="annex">ANNEX 1</div>
        </div>

        <table class="beef">
            <tr>
                <td style="width: 58%;">
                    <div class="inline-field">
                        <span>School Year</span>
                        <span class="boxes">@for ($i = 0; $i < 4; $i++)<span>{{ substr($syStart, $i, 1) }}</span>@endfor</span>
                        <span>-</span>
                        <span class="boxes">@for ($i = 0; $i < 4; $i++)<span>{{ substr($syEnd, $i, 1) }}</span>@endfor</span>
                    </div>
                    <div class="inline-field">
                        <span>Grade level to Enroll:</span>
                        <span class="boxes">@for ($i = 0; $i < 2; $i++)<span>{{ substr($gradeDigits, $i, 1) }}</span>@endfor</span>
                    </div>
                </td>
                <td>
                    <div class="checkbox-panel">
                        <div class="panel-title">Check the appropriate box only</div>
                        <div class="check-row">
                            <span>1. With LRN?</span>
                            <span class="check">{{ $lrn !== '' ? 'X' : '' }}</span> Yes
                            <span class="check">{{ $lrn === '' ? 'X' : '' }}</span> No
                        </div>
                        <div class="check-row">
                            <span>2. Returning (Balik-Aral)</span>
                            <span class="check">{{ $returning ? 'X' : '' }}</span> Yes
                            <span class="check">{{ $returning ? '' : 'X' }}</span> No
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="instructions">
                    INSTRUCTIONS: Print legibly all information required in CAPITAL letters. Submit accomplished form to the Person-in-Charge/Registrar/Class Adviser. Use black or blue pen only.
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="4" class="section-bar">Learner Information</td>
            </tr>
            <tr>
                <td colspan="2">
                    <span class="field-label">PSA Birth Certificate No. (if available upon registration)</span>
                    <div class="field-value"></div>
                </td>
                <td colspan="2">
                    <span class="field-label">Learner Reference No. (LRN)</span>
                    <span class="boxes">@for ($i = 0; $i < 12; $i++)<span>{{ substr($lrn, $i, 1) }}</span>@endfor</span>
                </td>
            </tr>
            <tr>
                <td class="name-col tall">
                    <span class="field-label">Last Name</span>
                    <div class="field-value">{{ $fill($student?->last_name) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Birthdate (mm/dd/yyyy)</span>
                    <div class="inline-field" style="margin-top: 2px;">
                        <span class="boxes">@for ($i = 0; $i < 2; $i++)<span>{{ substr($birthMonth, $i, 1) }}</span>@endfor</span>
                        <span>/</span>
                        <span class="boxes">@for ($i = 0; $i < 2; $i++)<span>{{ substr($birthDay, $i, 1) }}</span>@endfor</span>
                        <span>/</span>
                        <span class="boxes">@for ($i = 0; $i < 4; $i++)<span>{{ substr($birthYear, $i, 1) }}</span>@endfor</span>
                    </div>
                </td>
                <td colspan="2" class="tall">
                    <span class="field-label">Place of Birth (Municipality/City)</span>
                    <div class="field-value">{{ $fill($student?->birthplace) }}</div>
                </td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">First Name</span>
                    <div class="field-value">{{ $fill($student?->first_name) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Sex</span>
                    <div class="check-row" style="margin-top: 4px;">
                        <span class="check">{{ $sex === 'male' ? 'X' : '' }}</span> Male
                        <span class="check">{{ $sex === 'female' ? 'X' : '' }}</span> Female
                    </div>
                </td>
                <td colspan="2" class="tall">
                    <span class="field-label">Age</span>
                    <div class="field-value">{{ $age }}</div>
                </td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Middle Name</span>
                    <div class="field-value">{{ $fill($student?->middle_name) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Mother Tongue</span>
                    <div class="field-value">{{ $fill($student?->mother_tongue) }}</div>
                </td>
                <td colspan="2" class="tall">
                    <span class="field-label">Belonging to any Indigenous Peoples (IP) Community/Indigenous Cultural Community?</span>
                    <div class="check-row" style="margin-top: 4px;">
                        <span class="check">{{ $profile?->is_ip ? 'X' : '' }}</span> Yes
                        <span class="check">{{ $profile?->is_ip ? '' : 'X' }}</span> No
                        <span>If Yes, please specify:</span>
                        <span class="field-value" style="min-width: 24mm; display: inline-block;">{{ $fill($profile?->ip_community) }}</span>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Extension Name e.g. Jr., III (if applicable)</span>
                    <div class="field-value">{{ $fill($student?->suffix) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Is your family a beneficiary of 4Ps?</span>
                    <div class="check-row" style="margin-top: 4px;">
                        <span class="check">{{ $profile?->is_4ps ? 'X' : '' }}</span> Yes
                        <span class="check">{{ $profile?->is_4ps ? '' : 'X' }}</span> No
                    </div>
                </td>
                <td colspan="2" class="tall">
                    <span class="field-label">If Yes, write the 4Ps Household ID Number below</span>
                    <div class="field-value">{{ $fourPs }}</div>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="4" class="section-bar">Current Address</td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">House No./Street</span>
                    <div class="field-value">{{ $fill($currentAddr?->house_no) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Street Name</span>
                    <div class="field-value">{{ $fill($currentAddr?->street_name) }}</div>
                </td>
                <td colspan="2" class="tall">
                    <span class="field-label">Barangay</span>
                    <div class="field-value">{{ $fill($currentAddr?->barangay) }}</div>
                </td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Municipality/City</span>
                    <div class="field-value">{{ $fill($currentAddr?->municipality) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Province</span>
                    <div class="field-value">{{ $fill($currentAddr?->province) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Country</span>
                    <div class="field-value">{{ $fill($currentAddr?->country) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Zip Code</span>
                    <div class="field-value">{{ $fill($currentAddr?->zip_code) }}</div>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="4" class="section-bar">Permanent Address</td>
            </tr>
            <tr>
                <td colspan="4">
                    <div class="check-row">
                        <span>Same with your Current Address?</span>
                        <span class="check">{{ $sameAddress ? 'X' : '' }}</span> Yes
                        <span class="check">{{ $sameAddress ? '' : 'X' }}</span> No
                    </div>
                </td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">House No./Street</span>
                    <div class="field-value">{{ $fill($permanentAddr?->house_no) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Street Name</span>
                    <div class="field-value">{{ $fill($permanentAddr?->street_name) }}</div>
                </td>
                <td colspan="2" class="tall">
                    <span class="field-label">Barangay</span>
                    <div class="field-value">{{ $fill($permanentAddr?->barangay) }}</div>
                </td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Municipality/City</span>
                    <div class="field-value">{{ $fill($permanentAddr?->municipality) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Province</span>
                    <div class="field-value">{{ $fill($permanentAddr?->province) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Country</span>
                    <div class="field-value">{{ $fill($permanentAddr?->country) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Zip Code</span>
                    <div class="field-value">{{ $fill($permanentAddr?->zip_code) }}</div>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="5" class="section-bar">Parent's/Guardian's Information</td>
            </tr>
            @foreach ([
                ['label' => "Father's Name", 'person' => $father],
                ['label' => "Mother's Maiden Name", 'person' => $mother],
                ['label' => "Guardian's Name", 'person' => $guardian],
            ] as $row)
                <tr>
                    <td class="tall" style="width: 18%;">
                        <span class="field-label">{{ $row['label'] }}</span>
                        @if ($row['person']?->is_deceased)
                            <div class="muted">(Deceased)</div>
                        @endif
                    </td>
                    <td class="tall">
                        <span class="field-label">Last Name</span>
                        <div class="field-value">{{ $fill($row['person']?->last_name) }}</div>
                    </td>
                    <td class="tall">
                        <span class="field-label">First Name</span>
                        <div class="field-value">{{ $fill($row['person']?->first_name) }}</div>
                    </td>
                    <td class="tall">
                        <span class="field-label">Middle Name</span>
                        <div class="field-value">{{ $fill($row['person']?->middle_name) }}</div>
                    </td>
                    <td class="tall">
                        <span class="field-label">Contact Number</span>
                        <div class="field-value">{{ $row['person']?->is_deceased ? '' : $fill($row['person']?->contact_no) }}</div>
                    </td>
                </tr>
            @endforeach
        </table>

        <table class="beef">
            <tr>
                <td colspan="4" class="section-bar">For Returning Learner (Balik-Aral) and Those Who Will Transfer/Move In</td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Last Grade Level Completed</span>
                    <div class="field-value">{{ $fill($lastCompleted) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Last School Year Completed</span>
                    <div class="field-value">{{ $fill($enrollment->last_school_year_completed) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Last School Attended</span>
                    <div class="field-value">{{ $fill($enrollment->last_school_attended) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">School ID</span>
                    <span class="boxes">@for ($i = 0; $i < 6; $i++)<span>{{ substr($schoolId, $i, 1) }}</span>@endfor</span>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="3" class="section-bar">For Learners in Senior High School</td>
            </tr>
            <tr>
                <td class="tall" style="width: 32%;">
                    <span class="field-label">Semester</span>
                    <div class="check-row" style="margin-top: 4px;">
                        <span class="check">{{ $enrollment->semester === 'first' ? 'X' : '' }}</span> 1st Sem
                        <span class="check">{{ $enrollment->semester === 'second' ? 'X' : '' }}</span> 2nd Sem
                    </div>
                </td>
                <td class="tall">
                    <span class="field-label">Track</span>
                    <div class="field-value">{{ $fill($enrollment->cluster?->name) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Strand</span>
                    <div class="field-value">{{ $fill($enrollment->preferredCourse?->name) }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="page-foot">Page 1 of 2</div>
</section>

<section class="sheet">
    <div class="sheet-body">
        <div class="beef-header">
            <img src="{{ asset('images/deped_logo.png') }}" alt="Department of Education seal" class="beef-logo">
            <div class="beef-title">
                <h1>Enhanced Basic Education Enrollment Form</h1>
                <p class="not-for-sale">THIS FORM IS NOT FOR SALE.</p>
            </div>
            <div class="annex">ANNEX 1</div>
        </div>

        <table class="beef">
            <tr>
                <td colspan="2" class="certify">
                    I hereby certify that the above information given are true and correct to the best of my knowledge and I allow the Department of Education to use my child's details to create and/or update his/her learner profile in the Learner Information System. The information herein shall be treated as confidential in compliance with the Data Privacy Act of 2012.
                </td>
            </tr>
            <tr>
                <td class="sign-block" style="width: 65%;">
                    <div class="sign-line">{{ $fill($parentName) }}</div>
                    <div class="sign-caption">Signature Over Printed Name of Parent/Guardian</div>
                </td>
                <td class="sign-block">
                    <div class="sign-line">{{ $enrollment->created_at?->format('m/d/Y') }}</div>
                    <div class="sign-caption">Date</div>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td class="section-bar">Preferred Distance Learning Modality/ies</td>
            </tr>
            <tr>
                <td>
                    <div class="check-row" style="margin-bottom: 6px;">Choose all that applies.</div>
                    <div class="dlm-grid">
                        <div class="check-row"><span class="check"></span> Modular (Print)</div>
                        <div class="check-row"><span class="check"></span> Online</div>
                        <div class="check-row"><span class="check"></span> Radio-Based Instruction</div>
                        <div class="check-row"><span class="check"></span> Blended</div>
                        <div class="check-row"><span class="check"></span> Modular (Digital)</div>
                        <div class="check-row"><span class="check"></span> Educational Television</div>
                        <div class="check-row"><span class="check"></span> Homeschooling</div>
                        <div class="check-row"><span class="check"></span> Face to Face</div>
                    </div>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="2" class="section-bar">Additional Learner Information</td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Religion</span>
                    <div class="field-value">{{ $fill($student?->religion) }}</div>
                </td>
                <td class="tall">
                    <span class="field-label">Does the learner have special education needs / a disability?</span>
                    <div class="check-row" style="margin-top: 4px;">
                        <span class="check">{{ $profile?->has_disability ? 'X' : '' }}</span> Yes
                        <span class="check">{{ $profile?->has_disability ? '' : 'X' }}</span> No
                        <span>If Yes, please specify:</span>
                        <span class="field-value" style="min-width: 36mm; display: inline-block;">{{ $fill($profile?->disability_name) }}</span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="beef">
            <tr>
                <td colspan="2" class="section-bar">For School Use</td>
            </tr>
            <tr>
                <td class="tall">
                    <span class="field-label">Date of First Attendance</span>
                    <div class="field-value"></div>
                </td>
                <td class="tall">
                    <span class="field-label">Processed by / Date</span>
                    <div class="field-value"></div>
                </td>
            </tr>
            @if ($placementAssessment)
                <tr>
                    <td colspan="2">
                        <span class="field-label">Placement assessment</span>
                        <div class="field-value" style="text-transform: none;">
                            {{ $placementAssessment['summary'] ?? '' }}. Expected age range for Grade {{ $gradeDigits }} is {{ $placementAssessment['minimum_age'] }}–{{ $placementAssessment['maximum_age'] }} years.
                        </div>
                    </td>
                </tr>
            @endif
        </table>
    </div>
    <div class="page-foot">Page 2 of 2</div>
</section>
