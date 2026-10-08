@if ($enrollment)
<div id="detail-enrollment" class="registrar-record-section space-y-8 px-6 py-6" data-record-section>
    <div>
        <h2 class="{{ $headingClass }}">Enrollment Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="{{ $labelClass }}">LRN</label>
                <div class="{{ $valueClass }}">{{ $display($student?->lrn) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Grade Level</label>
                <div class="{{ $valueClass }}">{{ $display($gradeLabel) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Learner Type</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->learner_type_label) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Status</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->enrollment_status_label) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">School Year</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->academicYear?->school_year) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Section</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->section?->name ?? 'Not Assigned') }}</div>
            </div>
            @if(!empty($enrollment->semester) || $enrollment->isSeniorHigh())
            <div>
                <label class="{{ $labelClass }}">Semester</label>
                <div class="{{ $valueClass }}">{{ $enrollment->semester ? ucfirst($enrollment->semester).' Semester' : '—' }}</div>
            </div>
            @endif
            @if($enrollment->cluster || $enrollment->isSeniorHigh())
            <div>
                <label class="{{ $labelClass }}">Track</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->track?->name ?? $enrollment->cluster?->track?->name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Cluster</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->cluster?->name) }}</div>
            </div>
            @endif
            @if($enrollment->electives->isNotEmpty() || $enrollment->isSeniorHigh())
            <div>
                <label class="{{ $labelClass }}">Electives</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->electives->pluck('title')->join(', ')) }}</div>
            </div>
            @endif
            <div>
                <label class="{{ $labelClass }}">Last School Attended</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->last_school_attended) }}</div>
            </div>
        </div>
    </div>
    @if($enrollment->requiresPreviousSchoolDetails())
    <div>
        <h3 class="{{ $headingClass }}">Previous School Details</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div>
                <label class="{{ $labelClass }}">Last Grade Level Completed</label>
                <div class="{{ $valueClass }}">{{ $formatGradeLevel($enrollment->last_grade_level_completed) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Last School Year Completed</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->last_school_year_completed) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Previous School ID</label>
                <div class="{{ $valueClass }}">{{ $display($enrollment->school_id_from_previous_school) }}</div>
            </div>
        </div>
    </div>
    @endif
</div>

@else
<section id="detail-enrollment" class="registrar-record-section p-6"><h2 class="{{ $headingClass }}">Enrollment Information</h2><p class="text-sm text-gray-500">No enrollment history found.</p></section>
@endif

@if($student)
<div id="detail-personal" class="registrar-record-section space-y-8 px-6 py-6" data-record-section>
    <div>
        <h2 class="{{ $headingClass }}">Personal Information</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="{{ $labelClass }}">Last Name</label>
                <div class="{{ $valueClass }}">{{ $display($application?->last_name ?? $student->last_name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">First Name</label>
                <div class="{{ $valueClass }}">{{ $display($application?->first_name ?? $student->first_name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Middle Name</label>
                <div class="{{ $valueClass }}">{{ $display($application?->middle_name ?? $student->middle_name) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Suffix</label>
                <div class="{{ $valueClass }}">{{ $display(($application?->suffix ?? $student->suffix) ?: 'None') }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Birthdate</label>
                <div class="{{ $valueClass }}">{{ $display($application?->birthdate?->format('M d, Y')) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Age at School Year Start</label>
                <div class="{{ $valueClass }} {{ $placementAssessment ? '!text-amber-700' : '' }}">
                    {{ $placementAssessment['age'] ?? ($application?->birthdate ? $application->birthdate->diff($enrollment?->academicYear?->start_date ?? $enrollment?->created_at ?? now())->y : '—') }}
                </div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Sex</label>
                <div class="{{ $valueClass }}">{{ $display($student->sex ? ucfirst($student->sex) : null) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Contact Number</label>
                <div class="{{ $valueClass }}">{{ $display($application?->contact_no) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Email Address</label>
                <div class="{{ $valueClass }}">{{ $display($application?->email ?? $student->email) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Religion</label>
                <div class="{{ $valueClass }}">{{ $display($student->religion) }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Mother Tongue</label>
                <div class="{{ $valueClass }}">{{ $display($student->mother_tongue) }}</div>
            </div>
        </div>
    </div>
    <div>
        <h3 class="{{ $headingClass }}">4Ps / IP / PWD</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="{{ $labelClass }}">4Ps Beneficiary</label>
                <div class="{{ $valueClass }}">{{ $profile?->is_4ps ? 'Yes' : 'No' }}</div>
                @if($profile?->is_4ps && $profile->four_ps_household_id)
                    <p class="mt-2 text-xs text-gray-500">Household ID: <span class="font-semibold text-[#296374]">{{ $profile->four_ps_household_id }}</span></p>
                @endif
            </div>
            <div>
                <label class="{{ $labelClass }}">Indigenous People (IP)</label>
                <div class="{{ $valueClass }}">{{ $profile?->is_ip ? ($profile->ip_community ?: 'Yes') : 'No' }}</div>
            </div>
            <div>
                <label class="{{ $labelClass }}">Person with Disability (PWD)</label>
                <div class="{{ $valueClass }}">{{ $profile?->has_disability ? ($profile->disability_name ?: 'Yes') : 'No' }}</div>
            </div>
        </div>
    </div>
</div>

<div id="detail-addresses" class="registrar-record-section space-y-8 px-6 py-6" data-record-section>
    @if($student?->addresses && $student->addresses->isNotEmpty())
        @foreach($addressFieldGroups as $addressGroup)
            @php
                $addr = $addressGroup['address'];
            @endphp
            <div>
                <h2 class="{{ $headingClass }}">{{ $addressGroup['title'] }}</h2>
                @if($addr)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="{{ $labelClass }}">Province</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->province) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Municipality / City</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->municipality) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Barangay</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->barangay) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Zip Code</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->zip_code) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">House No.</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->house_no) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Street/Sitio</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->street_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Country</label>
                            <div class="{{ $valueClass }}">{{ $display($addr->country) }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No {{ strtolower($addressGroup['title']) }} recorded.</p>
                @endif
            </div>
        @endforeach
    @else
        <h2 class="{{ $headingClass }}">Addresses</h2>
        <p class="text-sm text-gray-500">No addresses recorded.</p>
    @endif
</div>

<div id="detail-parents" class="registrar-record-section space-y-8 px-6 py-6" data-record-section>
    <div>
        <h2 class="{{ $headingClass }}">Parent's / Guardian's Information</h2>
        @if($guardians->isNotEmpty())
            @foreach ([
                ['record' => $father, 'title' => "Father's Full Name"],
                ['record' => $mother, 'title' => "Mother's Full Name"],
                ['record' => $guardian, 'title' => "Guardian's Full Name"],
            ] as $guardianGroup)
                @php
                    $record = $guardianGroup['record'];
                @endphp
                <div class="{{ ! $loop->last ? 'mb-6' : '' }}">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-600">{{ $guardianGroup['title'] }}</p>
                        @if($record?->is_deceased)
                            <span class="text-xs font-semibold text-gray-600">Deceased</span>
                        @endif
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                        <div>
                            <label class="{{ $labelClass }}">Last Name</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->last_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Given Name</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->first_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Middle Name</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->middle_name) }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Suffix</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->suffix ?: 'None') }}</div>
                        </div>
                        <div>
                            <label class="{{ $labelClass }}">Contact No.</label>
                            <div class="{{ $valueClass }}">{{ $display($record?->is_deceased ? null : $record?->contact_no) }}</div>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <p class="text-sm text-gray-500">No parent or guardian records found.</p>
        @endif
    </div>
</div>

@endif
