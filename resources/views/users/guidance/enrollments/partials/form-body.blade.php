@php
    $student = $enrollment->student;
    $application = $student?->application;
    $profile = $student?->profile;
    $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
    $schoolYear = $enrollment->academicYear?->school_year ?? '—';
    $schoolYearParts = explode('-', $schoolYear);
    $syStart = $schoolYearParts[0] ?? '';
    $syEnd = $schoolYearParts[1] ?? '';
    $lrn = $student?->lrn ?? '';
    $withLrn = $lrn !== '';
    $returning = in_array($enrollment->learner_type, ['returnee'], true);
    $sex = strtolower($student?->sex ?? '');
    $birthdate = $application?->birthdate ? $application->birthdate->format('m/d/Y') : '';
    $addressLines = $student?->addresses?->groupBy('address_type') ?? collect();
    $currentAddr = optional($addressLines->get('current'))->first();
    $permanentAddr = optional($addressLines->get('permanent'))->first();
@endphp

<div class="header-row">
    <div class="header-logo">
        <div class="seal">SEAL</div>
    </div>
    <div class="header-text">
        <p class="form-title">ENHANCED BASIC EDUCATION ENROLLMENT FORM</p>
        <p class="form-sub">THIS FORM IS NOT FOR SALE.</p>
    </div>
    <div class="header-annex">ANNEX 1</div>
</div>

<div class="top-grid">
    <div class="top-left">
        <div class="inline-field">
            <span class="label">School Year</span>
            <span class="boxes">@for ($i = 0; $i < 4; $i++)<span>{{ $syStart[$i] ?? '' }}</span>@endfor</span>
            <span class="dash">-</span>
            <span class="boxes">@for ($i = 0; $i < 4; $i++)<span>{{ $syEnd[$i] ?? '' }}</span>@endfor</span>
        </div>
        <div class="inline-field">
            <span class="label">Grade level to Enroll:</span>
            <span class="boxes">@for ($i = 0; $i < 2; $i++)<span>{{ str_replace('Grade ', '', $gradeLabel)[$i] ?? '' }}</span>@endfor</span>
        </div>
    </div>
    <div class="top-right">
        <div class="checkbox-box">
            <div class="checkbox-title">Check the appropriate box only</div>
            <div class="checkbox-row">
                <span>1. With LRN?</span>
                <span class="check">{{ $withLrn ? 'X' : '' }}</span> Yes
                <span class="check">{{ $withLrn ? '' : 'X' }}</span> No
                <span class="spacer"></span>
                <span>2. Returning (Balik-Aral)</span>
                <span class="check">{{ $returning ? 'X' : '' }}</span> Yes
                <span class="check">{{ $returning ? '' : 'X' }}</span> No
            </div>
        </div>
    </div>
</div>

<div class="instructions">
    INSTRUCTIONS: Print legibly all information required in CAPITAL letters. Submit accomplished form to the Person-in-Charge/Registrar/Class Adviser. Use black or blue pen only.
</div>

<div class="section-header">LEARNER INFORMATION</div>

<table class="form-table">
    <tr>
        <td class="label">PSA Birth Certificate No. (if available upon registration)</td>
        <td class="line"></td>
        <td class="label">Learner Reference No. (LRN)</td>
        <td>
            <span class="boxes">
                @for ($i = 0; $i < 12; $i++)
                    <span>{{ $lrn[$i] ?? '' }}</span>
                @endfor
            </span>
        </td>
    </tr>
</table>

<table class="form-table">
    <tr>
        <td class="label">Last Name</td>
        <td class="line">{{ $application?->last_name ?? '' }}</td>
        <td class="label">Birthdate (mm/dd/yyyy)</td>
        <td class="line">{{ $birthdate }}</td>
        <td class="label">Place of Birth (Municipality/City)</td>
        <td class="line">{{ $student?->birthplace ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">First Name</td>
        <td class="line">{{ $application?->first_name ?? '' }}</td>
        <td class="label">Sex</td>
        <td class="line">
            <span class="check">{{ $sex === 'male' ? 'X' : '' }}</span> Male
            <span class="check">{{ $sex === 'female' ? 'X' : '' }}</span> Female
        </td>
        <td class="label">Age</td>
        <td class="line">{{ $application?->birthdate ? $application->birthdate->age : '' }}</td>
    </tr>
    <tr>
        <td class="label">Middle Name</td>
        <td class="line">{{ $application?->middle_name ?? '' }}</td>
        <td class="label">Mother Tongue</td>
        <td class="line">{{ $student?->mother_tongue ?? '' }}</td>
        <td class="label">Belonging to any Indigenous Peoples (IP) Community/Indigenous Cultural Community?</td>
        <td class="line">
            <span class="check">{{ $profile?->is_ip ? 'X' : '' }}</span> Yes
            <span class="check">{{ $profile?->is_ip ? '' : 'X' }}</span> No
            <span class="label" style="margin-left:8px;">If Yes, please specify:</span>
            <span class="line" style="min-width:140px; display:inline-block;">{{ $profile?->ip_community ?? '' }}</span>
        </td>
    </tr>
    <tr>
        <td class="label">Extension Name e.g. Jr., II (if applicable)</td>
        <td class="line">{{ $application?->suffix ?? '' }}</td>
        <td class="label">Is your family a beneficiary of 4Ps?</td>
        <td class="line">
            <span class="check">{{ $profile?->is_4ps ? 'X' : '' }}</span> Yes
            <span class="check">{{ $profile?->is_4ps ? '' : 'X' }}</span> No
        </td>
        <td class="label">If Yes, write the 4Ps Household ID Number below</td>
        <td>
            <span class="boxes">
                @php $fourPs = $profile?->four_ps_household_id ?? ''; @endphp
                @for ($i = 0; $i < 12; $i++)
                    <span>{{ $fourPs[$i] ?? '' }}</span>
                @endfor
            </span>
        </td>
    </tr>
</table>

<div class="section-header">CURRENT ADDRESS</div>
<table class="form-table">
    <tr>
        <td class="label">House No./Street</td>
        <td class="line">{{ $currentAddr?->house_no ?? '' }}</td>
        <td class="label">Street Name</td>
        <td class="line">{{ $currentAddr?->street_name ?? '' }}</td>
        <td class="label">Barangay</td>
        <td class="line">{{ $currentAddr?->barangay ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Municipality/City</td>
        <td class="line">{{ $currentAddr?->municipality ?? '' }}</td>
        <td class="label">Province</td>
        <td class="line">{{ $currentAddr?->province ?? '' }}</td>
        <td class="label">Country</td>
        <td class="line">{{ $currentAddr?->country ?? '' }}</td>
        <td class="label">Zip Code</td>
        <td class="line">{{ $currentAddr?->zip_code ?? '' }}</td>
    </tr>
</table>

<div class="section-header">PERMANENT ADDRESS</div>
<table class="form-table">
    <tr>
        <td class="label">House No./Street</td>
        <td class="line">{{ $permanentAddr?->house_no ?? '' }}</td>
        <td class="label">Street Name</td>
        <td class="line">{{ $permanentAddr?->street_name ?? '' }}</td>
        <td class="label">Barangay</td>
        <td class="line">{{ $permanentAddr?->barangay ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Municipality/City</td>
        <td class="line">{{ $permanentAddr?->municipality ?? '' }}</td>
        <td class="label">Province</td>
        <td class="line">{{ $permanentAddr?->province ?? '' }}</td>
        <td class="label">Country</td>
        <td class="line">{{ $permanentAddr?->country ?? '' }}</td>
        <td class="label">Zip Code</td>
        <td class="line">{{ $permanentAddr?->zip_code ?? '' }}</td>
    </tr>
</table>

<div class="section-header">PARENT'S / GUARDIAN'S INFORMATION</div>
<table class="form-table">
    @foreach(['father', 'mother', 'guardian'] as $rel)
        @php
            $g = ($student?->guardians ?? collect())->firstWhere('relationship', $rel);
        @endphp
        <tr>
            <td class="label">{{ ucfirst($rel) }}'s Name</td>
            <td class="line">{{ $g?->last_name ?? '' }}</td>
            <td class="label">First Name</td>
            <td class="line">{{ $g?->first_name ?? '' }}</td>
            <td class="label">Middle Name</td>
            <td class="line">{{ $g?->middle_name ?? '' }}</td>
            <td class="label">Contact Number</td>
            <td class="line">{{ $g?->contact_no ?? '' }}</td>
        </tr>
    @endforeach
</table>

<div class="section-header">FOR RETURNING LEARNER (BALIK-ARAL) AND THOSE WHO WILL TRANSFER/MOVE IN</div>
<table class="form-table">
    <tr>
        <td class="label">Last Grade Level Completed</td>
        <td class="line">{{ $enrollment->last_grade_level_completed ?? '' }}</td>
        <td class="label">Last School Year Completed</td>
        <td class="line">{{ $enrollment->last_school_year_completed ?? '' }}</td>
    </tr>
    <tr>
        <td class="label">Last School Attended</td>
        <td class="line">{{ $enrollment->last_school_attended ?? '' }}</td>
        <td class="label">School ID</td>
        <td class="line">{{ $enrollment->school_id_from_previous_school ?? '' }}</td>
    </tr>
</table>

<div class="section-header">FOR LEARNERS IN SENIOR HIGH SCHOOL</div>
<table class="form-table">
    <tr>
        <td class="label">Semester</td>
        <td class="line">
            <span class="check">{{ $enrollment->semester === 'first' ? 'X' : '' }}</span> 1st Sem
            <span class="check">{{ $enrollment->semester === 'second' ? 'X' : '' }}</span> 2nd Sem
        </td>
        <td class="label">Track</td>
        <td class="line">{{ $enrollment->cluster?->name ?? '' }}</td>
        <td class="label">Strand</td>
        <td class="line">{{ $enrollment->cluster?->name ?? '' }}</td>
    </tr>
</table>
