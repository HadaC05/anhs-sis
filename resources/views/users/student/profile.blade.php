@extends('users.student.layout')

@section('title', 'Student Profile')

@section('content')
@php
    $enrollment = $currentEnrollment;
    $showSeniorHighHistoryColumns = collect($enrollments ?? [])->contains(fn ($item): bool => $item->isSeniorHigh());
    $currentAddress = $student?->addresses?->firstWhere('address_type', 'current');
    $permanentAddress = $student?->addresses?->firstWhere('address_type', 'permanent');
    $father = $student?->guardians?->firstWhere('relationship', 'father');
    $mother = $student?->guardians?->firstWhere('relationship', 'mother');
    $guardian = $student?->guardians?->firstWhere('relationship', 'guardian');
    $selectedGender = old('gender', $student?->sex ? ucfirst($student->sex) : '');
    $fourPsValue = old('four_ps_beneficiary', $profile?->is_4ps ? 'Yes' : 'No');
    $ipValue = old('ip_community', $profile?->is_ip ? 'Yes' : 'No');
    $pwdValue = old('pwd', $profile?->has_disability ? 'Yes' : 'No');
    $profileBirthdateValue = old('birthdate', optional($application?->birthdate)->format('Y-m-d'));
    $profileComputedAge = $profileBirthdateValue ? \Illuminate\Support\Carbon::parse($profileBirthdateValue)->age : '';
    $sameAddressDefault = $currentAddress && $permanentAddress
        && (string) $currentAddress->house_no === (string) $permanentAddress->house_no
        && (string) $currentAddress->street_name === (string) $permanentAddress->street_name
        && (string) $currentAddress->barangay === (string) $permanentAddress->barangay
        && (string) $currentAddress->municipality === (string) $permanentAddress->municipality
        && (string) $currentAddress->province === (string) $permanentAddress->province
        && (string) $currentAddress->zip_code === (string) $permanentAddress->zip_code;
    $fieldClass = 'w-full rounded-md border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-800 shadow-sm outline-none transition focus:border-[#296374] focus:ring-2 focus:ring-[#296374]/15';
    $readonlyClass = 'w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm font-semibold text-[#296374] shadow-sm';
    $labelClass = 'mb-1.5 block text-sm text-gray-500';
    $requiredMark = '<span class="text-red-600" aria-hidden="true">*</span>';
@endphp

<div class="space-y-5">
    @if (session('profile_completion_required'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800">
            {{ session('profile_completion_required') }}
        </div>
    @endif

    @if (session('status'))
        <div id="studentProfileSuccessToast" role="status" aria-live="polite" class="fixed right-5 top-5 z-[120] flex w-[calc(100%-2.5rem)] max-w-sm items-start gap-3 rounded-xl border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 shadow-xl">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('status') }}</span>
            <button type="button" class="ml-auto -mr-1 -mt-1 rounded p-1 text-emerald-700/70 transition hover:bg-emerald-50 hover:text-emerald-800" data-dismiss-profile-toast aria-label="Close notification">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @if (! $student)
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="px-6 py-16 text-center">
                <h3 class="text-lg font-semibold text-gray-700">Student profile not found</h3>
                <p class="mt-2 text-sm text-gray-500">Your account does not have a complete student record yet.</p>
            </div>
        </div>
    @else
        @include('users.student.partials.enrollment-summary', [
            'student' => $student,
            'application' => $application,
            'enrollment' => $enrollment,
            'activeYear' => $activeYear,
        ])

        <form method="POST" action="{{ route('student.profile.update') }}" class="space-y-5" id="studentProfileForm" data-restrict-unsafe-input>
            @csrf
            @method('PUT')

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-5">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-800">Student Profile</h1>
                    <p class="mt-1 text-sm text-gray-500">You can update your contact and family details. Your name cannot be changed here.</p>
                    <p class="mt-2 text-xs font-medium text-gray-600"><span class="text-red-600" aria-hidden="true">*</span> Required fields</p>
                </div>

                <div class="space-y-8 px-6 py-6">
                    <div>
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]">Personal Information</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="{{ $labelClass }}">Last Name</label>
                                <input type="text" value="{{ $application?->last_name }}" class="{{ $readonlyClass }}" readonly>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">First Name</label>
                                <input type="text" value="{{ $application?->first_name }}" class="{{ $readonlyClass }}" readonly>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Middle Name</label>
                                <input type="text" value="{{ $application?->middle_name }}" class="{{ $readonlyClass }}" readonly>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Suffix</label>
                                <input type="text" value="{{ $application?->suffix ?: 'None' }}" class="{{ $readonlyClass }}" readonly>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="birthdate">Birthdate {!! $requiredMark !!}</label>
                                <input id="birthdate" type="date" name="birthdate" min="{{ $earliestBirthdate }}" value="{{ $profileBirthdateValue }}" class="{{ $fieldClass }}" required>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="ageDisplay">Age</label>
                                <input id="ageDisplay" type="text" value="{{ $profileComputedAge }}" placeholder="Auto-computed" readonly tabindex="-1" class="{{ $readonlyClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="birthplace">Birthplace {!! $requiredMark !!}</label>
                                <input id="birthplace" type="text" name="birthplace" value="{{ old('birthplace', $student->birthplace) }}" class="{{ $fieldClass }}" data-capitalize required autocapitalize="words">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Sex {!! $requiredMark !!}</label>
                                <div class="flex gap-4 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="gender" value="Male" class="accent-[#296374]" @checked($selectedGender === 'Male') required> Male
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="gender" value="Female" class="accent-[#296374]" @checked($selectedGender === 'Female') required> Female
                                    </label>
                                </div>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="contact_no">Contact Number {!! $requiredMark !!}</label>
                                <input id="contact_no" type="tel" name="contact_no" value="{{ old('contact_no', $application?->contact_no) }}" maxlength="13" inputmode="numeric" pattern="\+63\d{10}" placeholder="+639XXXXXXXXX" class="{{ $fieldClass }}" required>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="email">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $application?->email) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="religion">Religion {!! $requiredMark !!}</label>
                                <select id="religion" name="religion" class="{{ $fieldClass }}" required>
                                    <option value="">Select Religion</option>
                                    @foreach ($religions as $religion)
                                        <option value="{{ $religion }}" @selected(old('religion', $student->religion) === $religion)>{{ $religion }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="mother_tongue">Mother Tongue {!! $requiredMark !!}</label>
                                <input id="mother_tongue" type="text" name="mother_tongue" value="{{ old('mother_tongue', $student->mother_tongue) }}" class="{{ $fieldClass }}" data-capitalize required autocapitalize="words">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]">4Ps / IP / PWD</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <label class="{{ $labelClass }}">4Ps Beneficiary {!! $requiredMark !!}</label>
                                <div class="flex gap-4 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="four_ps_beneficiary" value="No" class="accent-[#296374]" @checked($fourPsValue === 'No') required> No
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="four_ps_beneficiary" value="Yes" class="accent-[#296374]" @checked($fourPsValue === 'Yes')> Yes
                                    </label>
                                </div>
                                <div class="mt-2 {{ $fourPsValue === 'Yes' ? '' : 'hidden' }}" id="four_ps_details_container">
                                    <input type="text" name="four_ps_details" value="{{ old('four_ps_details', $profile?->four_ps_household_id) }}" minlength="17" maxlength="21" placeholder="4Ps Household ID Number" class="{{ $fieldClass }}">
                                    <p class="mt-1 text-xs text-gray-500">Must be 17 to 21 characters.</p>
                                </div>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Indigenous People (IP) {!! $requiredMark !!}</label>
                                <div class="flex gap-4 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="ip_community" value="No" class="accent-[#296374]" @checked($ipValue === 'No') required> No
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="ip_community" value="Yes" class="accent-[#296374]" @checked($ipValue === 'Yes')> Yes
                                    </label>
                                </div>
                                <div class="mt-2 {{ $ipValue === 'Yes' ? '' : 'hidden' }}" id="ip_details_container">
                                    <input type="text" name="ip_details" value="{{ old('ip_details', $profile?->ip_community) }}" placeholder="Specify IP/Community" class="{{ $fieldClass }}">
                                </div>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Person with Disability (PWD) {!! $requiredMark !!}</label>
                                <div class="flex gap-4 rounded-md border border-gray-200 bg-gray-50 px-3 py-2.5">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="pwd" value="No" class="accent-[#296374]" @checked($pwdValue === 'No') required> No
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="pwd" value="Yes" class="accent-[#296374]" @checked($pwdValue === 'Yes')> Yes
                                    </label>
                                </div>
                                <div class="mt-2 {{ $pwdValue === 'Yes' ? '' : 'hidden' }}" id="pwd_details_container">
                                    <input type="text" name="pwd_details" value="{{ old('pwd_details', $profile?->disability_name) }}" placeholder="Specify Disability" class="{{ $fieldClass }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]">Current Address</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <input type="hidden" name="curr_country" value="{{ old('curr_country', $currentAddress?->country ?? 'Philippines') }}">
                            <div>
                                <label class="{{ $labelClass }}" for="curr_province_select">Province {!! $requiredMark !!}</label>
                                <select id="curr_province_select" data-address-role="province" data-address-prefix="curr" class="{{ $fieldClass }}" required></select>
                                <input type="hidden" name="curr_province" value="{{ old('curr_province', $currentAddress?->province) }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="curr_municipality_city_select">Municipality / City {!! $requiredMark !!}</label>
                                <select id="curr_municipality_city_select" data-address-role="municipality" data-address-prefix="curr" class="{{ $fieldClass }}" required disabled></select>
                                <input type="hidden" name="curr_municipality_city" value="{{ old('curr_municipality_city', $currentAddress?->municipality) }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="curr_barangay_select">Barangay {!! $requiredMark !!}</label>
                                <select id="curr_barangay_select" data-address-role="barangay" data-address-prefix="curr" class="{{ $fieldClass }}" required disabled></select>
                                <input type="hidden" name="curr_barangay" value="{{ old('curr_barangay', $currentAddress?->barangay) }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Zip Code {!! $requiredMark !!}</label>
                                <input type="text" name="curr_zip_code" value="{{ old('curr_zip_code', $currentAddress?->zip_code) }}" placeholder="Auto-generated" readonly required class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">House No.</label>
                                <input type="text" name="curr_house_no" value="{{ old('curr_house_no', $currentAddress?->house_no) }}" class="{{ $fieldClass }}">
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Street/Sitio</label>
                                <input type="text" name="curr_street_name" value="{{ old('curr_street_name', $currentAddress?->street_name) }}" class="{{ $fieldClass }}">
                            </div>
                        </div>

                        <label class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
                            <input id="same_address" type="checkbox" name="same_address" value="1" class="h-4 w-4 accent-[#296374]" @checked(old('same_address', $sameAddressDefault))>
                            Permanent address is the same as current address
                        </label>
                    </div>

                    <div>
                        <h2 class="mb-4 text-sm font-bold uppercase tracking-wide text-[#296374]">Permanent Address</h2>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <input type="hidden" name="perm_country" value="{{ old('perm_country', $permanentAddress?->country ?? 'Philippines') }}" data-perm-field>
                            <div>
                                <label class="{{ $labelClass }}" for="perm_province_select">Province {!! $requiredMark !!}</label>
                                <select id="perm_province_select" data-address-role="province" data-address-prefix="perm" class="{{ $fieldClass }}" data-perm-field required></select>
                                <input type="hidden" name="perm_province" value="{{ old('perm_province', $permanentAddress?->province) }}" data-perm-field>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="perm_municipality_city_select">Municipality / City {!! $requiredMark !!}</label>
                                <select id="perm_municipality_city_select" data-address-role="municipality" data-address-prefix="perm" class="{{ $fieldClass }}" data-perm-field required disabled></select>
                                <input type="hidden" name="perm_municipality_city" value="{{ old('perm_municipality_city', $permanentAddress?->municipality) }}" data-perm-field>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}" for="perm_barangay_select">Barangay {!! $requiredMark !!}</label>
                                <select id="perm_barangay_select" data-address-role="barangay" data-address-prefix="perm" class="{{ $fieldClass }}" data-perm-field required disabled></select>
                                <input type="hidden" name="perm_barangay" value="{{ old('perm_barangay', $permanentAddress?->barangay) }}" data-perm-field>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Zip Code {!! $requiredMark !!}</label>
                                <input type="text" name="perm_zip_code" value="{{ old('perm_zip_code', $permanentAddress?->zip_code) }}" placeholder="Auto-generated" readonly required class="{{ $fieldClass }}" data-perm-field>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">House No.</label>
                                <input type="text" name="perm_house_no" value="{{ old('perm_house_no', $permanentAddress?->house_no) }}" class="{{ $fieldClass }}" data-perm-field>
                            </div>
                            <div>
                                <label class="{{ $labelClass }}">Street/Sitio</label>
                                <input type="text" name="perm_street_name" value="{{ old('perm_street_name', $permanentAddress?->street_name) }}" class="{{ $fieldClass }}" data-perm-field>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="mb-1 text-sm font-bold uppercase tracking-wide text-[#296374]">Parent's / Guardian's Information</h2>
                        <p class="mb-4 text-xs text-gray-500">Enter <span class="font-semibold">N/A</span> in the name fields if not applicable. Mark the checkbox if a parent or guardian is deceased.</p>

                        <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-600">Father's Full Name {!! $requiredMark !!}</p>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                                <input type="checkbox" name="father_is_deceased" value="1" class="h-4 w-4 accent-[#296374]" data-deceased-toggle="father_contact_no" @checked(old('father_is_deceased', $father?->is_deceased))>
                                Deceased
                            </label>
                        </div>
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                            <input type="text" name="father_lname" placeholder="Last Name" value="{{ old('father_lname', $father?->last_name) }}" class="{{ $fieldClass }}" data-capitalize required autocapitalize="words">
                            <input type="text" name="father_fname" placeholder="Given Name" value="{{ old('father_fname', $father?->first_name) }}" class="{{ $fieldClass }}" data-capitalize required autocapitalize="words">
                            <input type="text" name="father_mname" placeholder="Middle Name" value="{{ old('father_mname', $father?->middle_name) }}" class="{{ $fieldClass }}" data-capitalize autocapitalize="words">
                            <select name="father_suffix" class="{{ $fieldClass }}">
                                <option value="">None</option>
                                @foreach ($suffixOptions as $suffixOption)
                                    <option value="{{ $suffixOption }}" @selected(old('father_suffix', $father?->suffix) === $suffixOption)>{{ $suffixOption }}</option>
                                @endforeach
                            </select>
                            <input type="tel" name="father_contact_no" placeholder="+639XXXXXXXXX" value="{{ old('father_contact_no', $father?->is_deceased ? '' : $father?->contact_no) }}" maxlength="13" class="{{ $fieldClass }}">
                        </div>

                        <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-600">Mother's Full Name {!! $requiredMark !!}</p>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                                <input type="checkbox" name="mother_is_deceased" value="1" class="h-4 w-4 accent-[#296374]" data-deceased-toggle="mother_contact_no" @checked(old('mother_is_deceased', $mother?->is_deceased))>
                                Deceased
                            </label>
                        </div>
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-5">
                            <input type="text" name="mother_lname" placeholder="Last Name" value="{{ old('mother_lname', $mother?->last_name) }}" class="{{ $fieldClass }}" data-capitalize required autocapitalize="words">
                            <input type="text" name="mother_fname" placeholder="Given Name" value="{{ old('mother_fname', $mother?->first_name) }}" class="{{ $fieldClass }}" data-capitalize required autocapitalize="words">
                            <input type="text" name="mother_mname" placeholder="Middle Name" value="{{ old('mother_mname', $mother?->middle_name) }}" class="{{ $fieldClass }}" data-capitalize autocapitalize="words">
                            <select name="mother_suffix" class="{{ $fieldClass }}">
                                <option value="">None</option>
                                @foreach ($suffixOptions as $suffixOption)
                                    <option value="{{ $suffixOption }}" @selected(old('mother_suffix', $mother?->suffix) === $suffixOption)>{{ $suffixOption }}</option>
                                @endforeach
                            </select>
                            <input type="tel" name="mother_contact_no" placeholder="+639XXXXXXXXX" value="{{ old('mother_contact_no', $mother?->is_deceased ? '' : $mother?->contact_no) }}" maxlength="13" class="{{ $fieldClass }}">
                        </div>

                        <div class="mb-2 flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-gray-600">Guardian's Full Name</p>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-gray-600">
                                <input type="checkbox" name="guardian_is_deceased" value="1" class="h-4 w-4 accent-[#296374]" data-deceased-toggle="guardian_contact_no" @checked(old('guardian_is_deceased', $guardian?->is_deceased))>
                                Deceased
                            </label>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                            <input type="text" name="guardian_lname" placeholder="Last Name" value="{{ old('guardian_lname', $guardian?->last_name) }}" class="{{ $fieldClass }}" data-capitalize autocapitalize="words">
                            <input type="text" name="guardian_fname" placeholder="Given Name" value="{{ old('guardian_fname', $guardian?->first_name) }}" class="{{ $fieldClass }}" data-capitalize autocapitalize="words">
                            <input type="text" name="guardian_mname" placeholder="Middle Name" value="{{ old('guardian_mname', $guardian?->middle_name) }}" class="{{ $fieldClass }}" data-capitalize autocapitalize="words">
                            <select name="guardian_suffix" class="{{ $fieldClass }}">
                                <option value="">None</option>
                                @foreach ($suffixOptions as $suffixOption)
                                    <option value="{{ $suffixOption }}" @selected(old('guardian_suffix', $guardian?->suffix) === $suffixOption)>{{ $suffixOption }}</option>
                                @endforeach
                            </select>
                            <input type="tel" name="guardian_contact_no" placeholder="+639XXXXXXXXX" value="{{ old('guardian_contact_no', $guardian?->is_deceased ? '' : $guardian?->contact_no) }}" maxlength="13" class="{{ $fieldClass }}">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end border-t border-gray-200 px-6 py-4">
                    <button type="submit" class="inline-flex items-center justify-center rounded-md px-6 py-2.5 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-2xl font-bold tracking-tight text-gray-800">Enrollment History</h2>
            </div>
            @if ($enrollments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse text-sm text-gray-800">
                        <thead>
                            <tr class="bg-[#dbeaf1] text-left text-xs font-bold uppercase tracking-wide text-gray-700">
                                <th class="border border-gray-200 px-3 py-3">School Year</th>
                                <th class="border border-gray-200 px-3 py-3">Grade Level</th>
                                <th class="border border-gray-200 px-3 py-3">Section</th>
                                @if ($showSeniorHighHistoryColumns)
                                    <th class="border border-gray-200 px-3 py-3">Cluster</th>
                                    <th class="border border-gray-200 px-3 py-3">Preferred Course</th>
                                @endif
                                <th class="border border-gray-200 px-3 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($enrollments as $index => $item)
                                <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                    <td class="border border-gray-200 px-3 py-2.5">{{ $item->academicYear?->school_year ?? '—' }}</td>
                                    <td class="border border-gray-200 px-3 py-2.5">{{ $item->gradeLevel?->grade_label ?? ($item->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $item->grade_level)) : '—') }}</td>
                                    <td class="border border-gray-200 px-3 py-2.5">{{ $item->section?->name ?? 'Not assigned' }}</td>
                                    @if ($showSeniorHighHistoryColumns)
                                        <td class="border border-gray-200 px-3 py-2.5">{{ $item->isSeniorHigh() ? ($item->cluster?->name ?? '—') : '—' }}</td>
                                        <td class="border border-gray-200 px-3 py-2.5">{{ $item->isSeniorHigh() ? ($item->preferredCourse?->name ?? '—') : '—' }}</td>
                                    @endif
                                    <td class="border border-gray-200 px-3 py-2.5">{{ $item->enrollment_status_label ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-16 text-center">
                    <h3 class="text-lg font-semibold text-gray-700">No Enrollment History</h3>
                    <p class="mt-2 text-sm text-gray-500">Your previous and current enrollments will appear here.</p>
                </div>
            @endif
        </div>
    @endif

    <div class="flex justify-center pt-1">
        <a href="{{ route('student.dashboard') }}" class="inline-flex items-center gap-2 rounded-lg px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-md transition hover:opacity-90" style="background-color: #296374;">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Dashboard
        </a>
    </div>
</div>

@if ($student)
<script>
    (function () {
        const successToast = document.getElementById('studentProfileSuccessToast');
        const dismissSuccessToast = () => successToast?.remove();

        successToast?.querySelector('[data-dismiss-profile-toast]')?.addEventListener('click', dismissSuccessToast);
        if (successToast) {
            window.setTimeout(dismissSuccessToast, 4000);
        }

        const form = document.getElementById('studentProfileForm');
        if (!form) {
            return;
        }

        const sameAddress = document.getElementById('same_address');
        const addressDataBaseUrl = @json(asset('data/addresspinas'));
        const currentFields = {
            curr_house_no: form.querySelector('[name="curr_house_no"]'),
            curr_street_name: form.querySelector('[name="curr_street_name"]'),
            curr_barangay: form.querySelector('[name="curr_barangay"]'),
            curr_municipality_city: form.querySelector('[name="curr_municipality_city"]'),
            curr_province: form.querySelector('[name="curr_province"]'),
            curr_country: form.querySelector('[name="curr_country"]'),
            curr_zip_code: form.querySelector('[name="curr_zip_code"]'),
        };
        const permanentFields = {
            perm_house_no: form.querySelector('[name="perm_house_no"]'),
            perm_street_name: form.querySelector('[name="perm_street_name"]'),
            perm_barangay: form.querySelector('[name="perm_barangay"]'),
            perm_municipality_city: form.querySelector('[name="perm_municipality_city"]'),
            perm_province: form.querySelector('[name="perm_province"]'),
            perm_country: form.querySelector('[name="perm_country"]'),
            perm_zip_code: form.querySelector('[name="perm_zip_code"]'),
        };
        const addressControls = {
            curr: {
                provinceSelect: document.getElementById('curr_province_select'),
                municipalitySelect: document.getElementById('curr_municipality_city_select'),
                barangaySelect: document.getElementById('curr_barangay_select'),
                provinceInput: currentFields.curr_province,
                municipalityInput: currentFields.curr_municipality_city,
                barangayInput: currentFields.curr_barangay,
                zipInput: currentFields.curr_zip_code,
            },
            perm: {
                provinceSelect: document.getElementById('perm_province_select'),
                municipalitySelect: document.getElementById('perm_municipality_city_select'),
                barangaySelect: document.getElementById('perm_barangay_select'),
                provinceInput: permanentFields.perm_province,
                municipalityInput: permanentFields.perm_municipality_city,
                barangayInput: permanentFields.perm_barangay,
                zipInput: permanentFields.perm_zip_code,
            },
        };
        const addressData = {
            provinces: [],
            municipalities: [],
            barangays: [],
            zipcodes: {},
            zipEntries: [],
        };

        function toggleDetails(name, containerId) {
            const selected = form.querySelector(`input[name="${name}"]:checked`);
            const container = document.getElementById(containerId);
            if (!container) {
                return;
            }
            const isYes = selected && selected.value === 'Yes';
            container.classList.toggle('hidden', !isYes);
            container.querySelectorAll('input').forEach((input) => {
                input.required = isYes;
            });
        }

        function subscriberDigitsFromContact(value) {
            const trimmed = String(value || '').trim();

            if (
                trimmed === ''
                || trimmed === '+'
                || trimmed === '+6'
                || trimmed === '+63'
                || trimmed === '6'
                || trimmed === '63'
            ) {
                return '';
            }

            let digits = trimmed.replace(/\D/g, '');

            if (digits.startsWith('63')) {
                digits = digits.slice(2);
            }

            if (digits.startsWith('0')) {
                digits = digits.slice(1);
            }

            return digits.slice(0, 10);
        }

        function setContactValidity(input, digits) {
            if (digits === '') {
                input.setCustomValidity(input.required ? 'Must enter 10 digits.' : '');
                return;
            }

            input.setCustomValidity(digits.length === 10 ? '' : 'Must enter 10 digits.');
        }

        function normalizeContact(input) {
            const digits = subscriberDigitsFromContact(input.value);

            if (digits === '') {
                input.value = '';
                setContactValidity(input, digits);
                return;
            }

            input.value = `+63${digits}`;
            setContactValidity(input, digits);
        }

        function capitalizeWords(value) {
            return value.replace(/(\p{L})(\p{L}*)/gu, (_, first, rest) => {
                return first.toLocaleUpperCase('en-US') + rest.toLocaleLowerCase('en-US');
            });
        }

        function capitalizeTextInput(input) {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            input.value = capitalizeWords(input.value);

            if (document.activeElement === input && start !== null && end !== null) {
                input.setSelectionRange(start, end);
            }
        }

        function restrictUnsafeCharacters(input) {
            if (!input || input.readOnly) {
                return;
            }

            const sanitized = String(input.value || '').replace(/[<>]/g, '');
            if (sanitized === input.value) {
                return;
            }

            const start = input.selectionStart;
            const end = input.selectionEnd;
            input.value = sanitized;

            if (document.activeElement === input && start !== null && end !== null) {
                const cursor = Math.max(0, start - 1);
                input.setSelectionRange(cursor, Math.max(0, (end ?? start) - 1));
            }
        }

        function restrictUnsafeFormInputs(target) {
            target.querySelectorAll('input, textarea').forEach((input) => {
                const type = (input.getAttribute('type') || 'text').toLowerCase();
                if (['hidden', 'checkbox', 'radio', 'date', 'file', 'submit', 'button'].includes(type)) {
                    return;
                }

                input.addEventListener('keydown', (event) => {
                    if (event.key === '<' || event.key === '>') {
                        event.preventDefault();
                    }
                });
                input.addEventListener('input', () => restrictUnsafeCharacters(input));

                if (type === 'text' && !input.hasAttribute('pattern')) {
                    input.setAttribute('pattern', '[^<>]*');
                    input.setAttribute('title', 'Characters < and > are not allowed.');
                }
            });
        }

        function bindDeceasedToggles(target) {
            target.querySelectorAll('[data-deceased-toggle]').forEach((checkbox) => {
                const contact = target.querySelector(`[name="${checkbox.getAttribute('data-deceased-toggle')}"]`);
                if (!contact) {
                    return;
                }

                const sync = () => {
                    contact.disabled = checkbox.checked;
                    if (checkbox.checked) {
                        contact.value = '';
                    }
                };

                checkbox.addEventListener('change', sync);
                sync();
            });
        }

        function updateAge() {
            const birthdateInput = document.getElementById('birthdate');
            const ageInput = document.getElementById('ageDisplay');
            if (!birthdateInput || !ageInput) {
                return;
            }

            if (!birthdateInput.value) {
                ageInput.value = '';
                return;
            }

            const birth = new Date(`${birthdateInput.value}T00:00:00`);
            if (Number.isNaN(birth.getTime())) {
                ageInput.value = '';
                return;
            }

            const today = new Date();
            let age = today.getFullYear() - birth.getFullYear();
            const monthDiff = today.getMonth() - birth.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                age -= 1;
            }

            ageInput.value = age >= 0 ? String(age) : '';
        }

        function normalizeAddressText(value) {
            return String(value || '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\([^)]*\)/g, '')
                .replace(/^city of\s+/i, '')
                .replace(/\bcity\b/gi, '')
                .replace(/[^a-z0-9]+/gi, ' ')
                .trim()
                .toLowerCase();
        }

        function findAddressItem(items, selectedName) {
            const normalized = normalizeAddressText(selectedName);
            if (!normalized) {
                return null;
            }

            return items.find((item) => normalizeAddressText(item.name) === normalized)
                || items.find((item) => {
                    const itemName = normalizeAddressText(item.name);

                    return itemName.includes(normalized) || normalized.includes(itemName);
                })
                || null;
        }

        function makeOption(value, label) {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;

            return option;
        }

        function populateSelect(select, placeholder, items, valueKey = 'value', labelKey = 'name') {
            if (!select) {
                return;
            }

            select.innerHTML = '';
            select.appendChild(makeOption('', placeholder));
            items.forEach((item) => {
                select.appendChild(makeOption(item[valueKey], item[labelKey]));
            });
            select.disabled = items.length === 0;
        }

        function selectedOptionText(select) {
            if (!select?.value) {
                return '';
            }

            return select.selectedOptions?.[0]?.textContent || '';
        }

        function findZipCode(municipalityName, barangayName) {
            const municipality = normalizeAddressText(municipalityName);
            const barangay = normalizeAddressText(barangayName);

            const municipalityMatch = addressData.zipEntries.find((entry) => {
                return entry.places.some((place) => {
                    const normalized = normalizeAddressText(place);

                    return normalized === municipality || normalized.includes(municipality) || municipality.includes(normalized);
                });
            });

            if (municipalityMatch) {
                return municipalityMatch.zip;
            }

            if (!barangay) {
                return '';
            }

            const barangayMatch = addressData.zipEntries.find((entry) => {
                return entry.places.some((place) => normalizeAddressText(place).includes(barangay));
            });

            return barangayMatch?.zip || '';
        }

        function updateAddressZip(prefix) {
            const controls = addressControls[prefix];
            if (!controls?.zipInput) {
                return;
            }

            controls.zipInput.value = findZipCode(
                controls.municipalityInput?.value,
                controls.barangayInput?.value
            ) || controls.zipInput.value;
        }

        function syncAddressValues(prefix) {
            const controls = addressControls[prefix];
            if (!controls) {
                return;
            }

            if (controls.provinceInput) {
                controls.provinceInput.value = selectedOptionText(controls.provinceSelect);
            }
            if (controls.municipalityInput) {
                controls.municipalityInput.value = selectedOptionText(controls.municipalitySelect);
            }
            if (controls.barangayInput) {
                controls.barangayInput.value = selectedOptionText(controls.barangaySelect);
            }

            updateAddressZip(prefix);
        }

        function fillMunicipalities(prefix, selectedMunicipality = '') {
            const controls = addressControls[prefix];
            const provinceCode = controls?.provinceSelect?.value || '';
            const municipalities = provinceCode
                ? addressData.municipalities
                    .filter((municipality) => municipality.prov_code === provinceCode)
                    .sort((a, b) => a.name.localeCompare(b.name))
                : [];

            populateSelect(controls?.municipalitySelect, 'Select municipality / city', municipalities, 'mun_code');
            if (selectedMunicipality) {
                const match = findAddressItem(municipalities, selectedMunicipality);
                controls.municipalitySelect.value = match?.mun_code || '';
            }
        }

        function fillBarangays(prefix, selectedBarangay = '') {
            const controls = addressControls[prefix];
            const municipalityCode = controls?.municipalitySelect?.value || '';
            const barangays = municipalityCode
                ? addressData.barangays
                    .filter((barangay) => barangay.mun_code === municipalityCode)
                    .sort((a, b) => a.name.localeCompare(b.name))
                : [];

            populateSelect(controls?.barangaySelect, 'Select barangay', barangays, 'name');
            if (selectedBarangay) {
                const match = findAddressItem(barangays, selectedBarangay);
                controls.barangaySelect.value = match?.name || '';
            }
        }

        function syncAddressSelectsFromValues(prefix) {
            const controls = addressControls[prefix];
            if (!controls?.provinceSelect) {
                return;
            }

            const selectedProvince = controls.provinceInput?.value || '';
            const province = findAddressItem(addressData.provinces, selectedProvince);
            controls.provinceSelect.value = province?.prov_code || '';
            fillMunicipalities(prefix, controls.municipalityInput?.value || '');
            fillBarangays(prefix, controls.barangayInput?.value || '');
            updateAddressZip(prefix);
        }

        function resetAddressBelow(prefix, role) {
            const controls = addressControls[prefix];

            if (role === 'province') {
                if (controls.municipalityInput) {
                    controls.municipalityInput.value = '';
                }
                if (controls.barangayInput) {
                    controls.barangayInput.value = '';
                }
                if (controls.zipInput) {
                    controls.zipInput.value = '';
                }
                populateSelect(controls.municipalitySelect, 'Select municipality / city', [], 'mun_code');
                populateSelect(controls.barangaySelect, 'Select barangay', [], 'name');
            }

            if (role === 'province' || role === 'municipality') {
                if (controls.barangayInput) {
                    controls.barangayInput.value = '';
                }
                if (controls.zipInput) {
                    controls.zipInput.value = '';
                }
                populateSelect(controls.barangaySelect, 'Select barangay', [], 'name');
            }
        }

        function copyCurrentToPermanent() {
            const isSameAddress = Boolean(sameAddress?.checked);

            if (!isSameAddress) {
                [permanentFields.perm_house_no, permanentFields.perm_street_name].forEach((field) => {
                    field?.removeAttribute('readonly');
                });
                syncAddressSelectsFromValues('perm');
                if (addressControls.perm.provinceSelect) {
                    addressControls.perm.provinceSelect.disabled = addressData.provinces.length === 0;
                }
                return;
            }

            Object.keys(currentFields).forEach((key) => {
                const currentField = currentFields[key];
                const permKey = key.replace('curr_', 'perm_');
                const permField = permanentFields[permKey];
                if (currentField && permField) {
                    permField.value = currentField.value;
                }
            });

            [permanentFields.perm_house_no, permanentFields.perm_street_name].forEach((field) => {
                field?.setAttribute('readonly', 'readonly');
            });
            syncAddressSelectsFromValues('perm');
            ['provinceSelect', 'municipalitySelect', 'barangaySelect'].forEach((key) => {
                if (addressControls.perm[key]) {
                    addressControls.perm[key].disabled = true;
                }
            });
        }

        async function initializeAddressSelectors() {
            if (!addressControls.curr.provinceSelect || !addressControls.perm.provinceSelect) {
                return;
            }

            try {
                const [provinces, municipalities, barangays, zipcodes] = await Promise.all([
                    fetch(`${addressDataBaseUrl}/provinces.json`).then((response) => response.json()),
                    fetch(`${addressDataBaseUrl}/city-mun.json`).then((response) => response.json()),
                    fetch(`${addressDataBaseUrl}/barangays.json`).then((response) => response.json()),
                    fetch(`${addressDataBaseUrl}/zipcodes.json`).then((response) => response.json()),
                ]);

                addressData.provinces = provinces.sort((a, b) => a.name.localeCompare(b.name));
                addressData.municipalities = municipalities;
                addressData.barangays = barangays;
                addressData.zipcodes = zipcodes;
                addressData.zipEntries = Object.entries(zipcodes).map(([zip, places]) => ({
                    zip,
                    places: Array.isArray(places) ? places : [places],
                }));

                Object.values(addressControls).forEach((controls) => {
                    populateSelect(controls.provinceSelect, 'Select province', addressData.provinces, 'prov_code');
                    populateSelect(controls.municipalitySelect, 'Select municipality / city', [], 'mun_code');
                    populateSelect(controls.barangaySelect, 'Select barangay', [], 'name');
                });

                syncAddressSelectsFromValues('curr');
                syncAddressSelectsFromValues('perm');
                copyCurrentToPermanent();
            } catch (error) {
                Object.values(addressControls).forEach((controls) => {
                    [controls.provinceSelect, controls.municipalitySelect, controls.barangaySelect].forEach((select) => {
                        if (select) {
                            select.innerHTML = '';
                            select.appendChild(makeOption('', 'Address list unavailable'));
                            select.disabled = true;
                        }
                    });
                });
            }
        }

        form.querySelectorAll('input[name="four_ps_beneficiary"]').forEach((input) => {
            input.addEventListener('change', () => toggleDetails('four_ps_beneficiary', 'four_ps_details_container'));
        });
        form.querySelectorAll('input[name="ip_community"]').forEach((input) => {
            input.addEventListener('change', () => toggleDetails('ip_community', 'ip_details_container'));
        });
        form.querySelectorAll('input[name="pwd"]').forEach((input) => {
            input.addEventListener('change', () => toggleDetails('pwd', 'pwd_details_container'));
        });
        sameAddress?.addEventListener('change', copyCurrentToPermanent);
        Object.entries(addressControls).forEach(([prefix, controls]) => {
            controls.provinceSelect?.addEventListener('change', () => {
                resetAddressBelow(prefix, 'province');
                fillMunicipalities(prefix);
                syncAddressValues(prefix);
                copyCurrentToPermanent();
            });
            controls.municipalitySelect?.addEventListener('change', () => {
                resetAddressBelow(prefix, 'municipality');
                fillBarangays(prefix);
                syncAddressValues(prefix);
                copyCurrentToPermanent();
            });
            controls.barangaySelect?.addEventListener('change', () => {
                syncAddressValues(prefix);
                copyCurrentToPermanent();
            });
        });
        Object.values(currentFields).forEach((field) => {
            field?.addEventListener('input', copyCurrentToPermanent);
        });
        form.querySelectorAll('input[name="contact_no"], input[name="father_contact_no"], input[name="mother_contact_no"], input[name="guardian_contact_no"]').forEach((input) => {
            input.addEventListener('focus', () => {
                if (input.required && input.value.trim() === '') {
                    input.value = '+63';
                }
            });
            input.addEventListener('keydown', (event) => {
                if (event.key !== 'Backspace' && event.key !== 'Delete') {
                    return;
                }

                const value = input.value.trim();
                if (value === '+63' || value === '+6' || value === '+' || value === '63' || value === '6') {
                    event.preventDefault();
                    input.value = '';
                    setContactValidity(input, '');
                }
            });
            input.addEventListener('input', () => normalizeContact(input));
            input.addEventListener('blur', () => {
                const digits = subscriberDigitsFromContact(input.value);
                if (digits === '') {
                    input.value = '';
                    setContactValidity(input, '');
                    return;
                }

                normalizeContact(input);
            });
        });
        form.querySelectorAll('[data-capitalize]').forEach((input) => {
            capitalizeTextInput(input);
            input.addEventListener('input', () => capitalizeTextInput(input));
            input.addEventListener('blur', () => capitalizeTextInput(input));
        });
        restrictUnsafeFormInputs(form);
        bindDeceasedToggles(form);
        document.getElementById('birthdate')?.addEventListener('input', updateAge);
        document.getElementById('birthdate')?.addEventListener('change', updateAge);
        updateAge();

        toggleDetails('four_ps_beneficiary', 'four_ps_details_container');
        toggleDetails('ip_community', 'ip_details_container');
        toggleDetails('pwd', 'pwd_details_container');
        initializeAddressSelectors();
    })();
</script>
@endif
@endsection
