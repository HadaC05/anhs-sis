@extends('users.student.layout')

@section('title', 'Enrollment')

@section('content')
@php
    $adviserName = optional(optional($currentEnrollment)->section?->adviser)->last_name
        ? optional($currentEnrollment->section->adviser)->last_name . ', ' . optional($currentEnrollment->section->adviser)->first_name
            . (optional($currentEnrollment->section->adviser)->middle_name ? ' ' . substr(optional($currentEnrollment->section->adviser)->middle_name, 0, 1) . '.' : '')
            . (optional($currentEnrollment->section->adviser)->suffix ? ' ' . optional($currentEnrollment->section->adviser)->suffix : '')
        : 'Not assigned';
    $enrollmentStatus = $currentEnrollment?->enrollment_status
        ? ucwords(str_replace('_', ' ', $currentEnrollment->enrollment_status))
        : 'Not enrolled';
    $gradeLevelLabel = $currentEnrollment?->grade_level
        ? strtoupper(str_replace('grade_', 'Grade ', $currentEnrollment->grade_level))
        : '-';
    $preferredCoursesByCluster = ($clusters ?? collect())->mapWithKeys(fn ($cluster) => [
        (string) $cluster->cluster_ID => $cluster->preferredCourses->map(fn ($course) => [
            'id' => (string) $course->course_ID,
            'name' => $course->name,
        ])->values(),
    ]);
    $selectedEnrollmentLevel = $selectedEnrollmentLevel ?? null;
    $availableGradeLevels = collect($gradeLevels ?? [])->filter(function ($level) use ($selectedEnrollmentLevel) {
        $number = str_replace('grade_', '', $level['value']);

        return $selectedEnrollmentLevel === 'senior'
            ? in_array($number, ['11', '12'], true)
            : in_array($number, ['7', '8', '9', '10'], true);
    });
    $levelLabel = $selectedEnrollmentLevel === 'senior' ? 'Senior High School' : 'Junior High School';
@endphp
<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">Enrollment Entry</h1>
        <p class="text-gray-600 text-sm md:text-base">Complete your enrollment information.</p>
    </div>

    @if (session('status'))
    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
    </div>
    @endif

    @if ($errors->has('enrollment') || $errors->has('LRN') || $errors->any())
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ $errors->first('enrollment') ?: $errors->first('LRN') ?: $errors->first() }}
    </div>
    @endif

    @if (! $activeYear)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Enrollment is currently unavailable because no active school year is configured.
    </div>
    @endif

    @if ($hasEnrollment)
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        You already submitted an enrollment for the current school year.
    </div>
    @endif

    @if ($hasEnrollment && $currentEnrollment)
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="shadow-xl rounded-2xl p-6 md:col-span-2" style="background-color: #296374;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Enrollment Status</p>
            <p class="text-2xl font-bold text-white">{{ $enrollmentStatus }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #10b981;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">School Year</p>
            <p class="text-xl font-bold text-white">{{ $currentEnrollment->academicYear?->school_year ?? ($activeYear?->school_year ?? '-') }}</p>
        </div>
        <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #f59e0b;">
            <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Grade Level</p>
            <p class="text-xl font-bold text-white">{{ $gradeLevelLabel }}</p>
        </div>
    </div>

    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
        <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider mb-6">Enrollment Information</span>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Status</label>
                <p class="text-sm font-semibold text-gray-800">{{ $enrollmentStatus }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">School Year Enrolled</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->academicYear?->school_year ?? ($activeYear?->school_year ?? '-') }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Grade Level</label>
                <p class="text-sm font-semibold text-gray-800">{{ $gradeLevelLabel }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Section</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->section?->name ?? 'Not assigned' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Adviser</label>
                <p class="text-sm font-semibold text-gray-800">{{ $adviserName }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Semester Enrolled</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->semester ? ucfirst($currentEnrollment->semester) . ' Semester' : 'Not applicable' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Cluster</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->cluster?->name ?? 'Not applicable' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Preferred Course</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->preferredCourse?->name ?? 'Not applicable' }}</p>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Submitted On</label>
                <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment->created_at?->format('M d, Y h:i A') ?? '-' }}</p>
            </div>
        </div>
    </div>
    @else
    @if (! $selectedEnrollmentLevel)
    <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
        <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider mb-6">Choose Enrollment Level</span>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <a href="{{ route('student.enrollment', ['enrollment_level' => 'junior']) }}" class="group rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-[#296374] hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-[#296374]">Junior High School</p>
                <h2 class="mt-2 text-xl font-bold text-gray-800">Grades 7 to 10</h2>
                <p class="mt-3 text-sm leading-6 text-gray-600">Use this enrollment form for junior high school learners.</p>
                <span class="mt-5 inline-flex items-center rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition group-hover:bg-[#214e5c]">Continue</span>
            </a>
            <a href="{{ route('student.enrollment', ['enrollment_level' => 'senior']) }}" class="group rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:border-[#296374] hover:shadow-md">
                <p class="text-xs font-bold uppercase tracking-wider text-[#296374]">Senior High School</p>
                <h2 class="mt-2 text-xl font-bold text-gray-800">Grades 11 to 12</h2>
                <p class="mt-3 text-sm leading-6 text-gray-600">Use this enrollment form for senior high school learners with cluster and preferred course choices.</p>
                <span class="mt-5 inline-flex items-center rounded-lg bg-[#296374] px-4 py-2 text-sm font-bold text-white transition group-hover:bg-[#214e5c]">Continue</span>
            </a>
        </div>
    </div>
    @else
    <form action="{{ route('student.enrollment.store') }}" method="POST" class="space-y-6" id="enrollmentForm">
        @csrf
        <input type="hidden" name="enrollment_level" value="{{ $selectedEnrollmentLevel }}">
        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider">01. {{ $levelLabel }} Enrollment Details</span>
                <a href="{{ route('student.enrollment') }}" class="text-sm font-semibold text-[#296374] hover:underline">Change enrollment level</a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Grade Level to Enroll</label>
                    <select name="grade_level" id="grade_level" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required {{ ! $activeYear || $hasEnrollment ? 'disabled' : '' }}>
                        <option value="">Select Level</option>
                        @foreach ($availableGradeLevels as $level)
                            @php($number = str_replace('grade_', '', $level['value']))
                            <option value="{{ $number }}" {{ old('grade_level') == $number ? 'selected' : '' }}>{{ $level['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Learner Reference Number (LRN)</label>
                    <input type="text" name="LRN" placeholder="Enter your LRN" minlength="12" maxlength="12" inputmode="numeric" pattern="\d{12}" value="{{ old('LRN', optional($student)->lrn) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" required {{ ! $activeYear || $hasEnrollment ? 'disabled' : '' }}>
                </div>
            </div>

            <div id="semester_container" class="mt-6 hidden">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Semester</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="semester" value="first" class="mr-2 accent-[#296374]" {{ old('semester') === 'first' ? 'checked' : '' }}> 1st Semester
                    </label>
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="semester" value="second" class="mr-2 accent-[#296374]" {{ old('semester') === 'second' ? 'checked' : '' }}> 2nd Semester
                    </label>
                </div>
            </div>

            <div id="cluster_container" class="mt-6 hidden">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Cluster</label>
                <select name="cluster_ID" id="cluster_ID" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    <option value="">Select Cluster</option>
                    @foreach ($clusters as $cluster)
                    <option value="{{ $cluster->cluster_ID }}" {{ old('cluster_ID') == $cluster->cluster_ID ? 'selected' : '' }}>{{ $cluster->name }}</option>
                    @endforeach
                </select>
            </div>

            <div id="preferred_course_container" class="mt-6 hidden">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Preferred Course</label>
                <select name="course_ID" id="course_ID" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    <option value="">Select Preferred Course</option>
                </select>
            </div>

            <div class="mb-0 mt-6">
                <label class="block text-xs font-semibold text-gray-500 mb-2">Learner Type <span class="text-gray-400 font-normal text-xs">(Ignore if not applicable.)</span></label>
                <div class="flex gap-4 mt-2 mb-2">
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="learner_type" value="regular" class="mr-2 accent-[#296374]" checked> New
                    </label>
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="learner_type" value="transferee" class="mr-2 accent-[#296374]"> Transferee
                    </label>
                    <label class="flex items-center text-sm text-gray-600">
                        <input type="radio" name="learner_type" value="returnee" class="mr-2 accent-[#296374]"> Returning Learner
                    </label>
                </div>
                <div id="learner_details_container" class="mt-4 space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-2">Last Grade Level Completed</label>
                            <input type="text" name="last_grade_level_completed" placeholder="e.g., Grade 10" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-2">Last School Year Completed</label>
                            <input type="text" name="last_school_year_completed" placeholder="e.g., 2025 - 2026" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-2">Last School Attended</label>
                        <input type="text" name="last_school_attended" placeholder="Enter full name of last school attended" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-2">School ID from Previous School</label>
                        <input type="text" name="school_id_from_previous_school" placeholder="Enter school ID" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
            <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider mb-6">02. Personal Information</span>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Last Name</label>
                    <input type="text" name="last_name" placeholder="Enter last name" value="{{ old('last_name', optional($application)->last_name) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">First Name</label>
                    <input type="text" name="first_name" placeholder="Enter first name" value="{{ old('first_name', optional($application)->first_name) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Middle Name</label>
                    <input type="text" name="middle_name" placeholder="Enter middle name" value="{{ old('middle_name', optional($application)->middle_name) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Suffix</label>
                    <input type="text" name="suffix" placeholder="Jr., Sr., III, etc." value="{{ old('suffix', optional($application)->suffix) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Birthdate</label>
                    <input type="date" name="birthdate" value="{{ old('birthdate', optional(optional($application)->birthdate)->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Birthplace</label>
                    <input type="text" name="birthplace" placeholder="Enter birthplace" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Sex</label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="gender" value="Male" class="mr-2 accent-[#296374]"> Male
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="gender" value="Female" class="mr-2 accent-[#296374]"> Female
                        </label>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Contact Number</label>
                    <input type="text" name="contact_no" placeholder="Enter contact number" value="{{ old('contact_no', optional($application)->contact_no) }}" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Religion</label>
                    <input type="text" name="religion" placeholder="Enter religion" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Mother Tongue</label>
                    <input type="text" name="mother_tongue" placeholder="Enter mother tongue" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Belonging to any Indigenous People (IP) Community?</label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="ip_community" value="No" class="mr-2 accent-[#296374]" checked> No
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="ip_community" value="Yes" class="mr-2 accent-[#296374]"> Yes
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="ip_details_container">
                        <input type="text" name="ip_details" placeholder="Specify IP/Community" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Is your family a beneficiary of 4Ps?</label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="four_ps_beneficiary" value="No" class="mr-2 accent-[#296374]" checked> No
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="four_ps_beneficiary" value="Yes" class="mr-2 accent-[#296374]"> Yes
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="four_ps_details_container">
                        <input type="text" name="four_ps_details" placeholder="4Ps Household ID Number" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Is the learner a Person with Disability (PWD)?</label>
                    <div class="flex gap-4 mt-2">
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="pwd" value="No" class="mr-2 accent-[#296374]" checked> No
                        </label>
                        <label class="flex items-center text-sm text-gray-600">
                            <input type="radio" name="pwd" value="Yes" class="mr-2 accent-[#296374]"> Yes
                        </label>
                    </div>
                    <div class="mt-2 hidden" id="pwd_details_container">
                        <input type="text" name="pwd_details" placeholder="Specify Disability" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
            <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider mb-6">03. Address & Family Details</span>
            <div class="block text-xs font-semibold text-gray-800 mb-2">CURRENT ADDRESS</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">House No.</label>
                    <input type="text" name="curr_house_no" placeholder="House No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Street/Sitio</label>
                    <input type="text" name="curr_street_name" placeholder="Street/Sitio" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Barangay</label>
                    <input type="text" name="curr_barangay" placeholder="Barangay" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Municipality</label>
                    <input type="text" name="curr_municipality_city" placeholder="Municipality" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Province</label>
                    <input type="text" name="curr_province" placeholder="Province" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Country</label>
                    <input type="text" name="curr_country" placeholder="Country" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Zip Code</label>
                    <input type="text" name="curr_zip_code" placeholder="Zip Code" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                </div>
            </div>

            <div class="flex items-center gap-3 mb-4">
                <input id="same_address" name="same_address" type="checkbox" value="1" class="h-4 w-4 accent-[#296374]">
                <label for="same_address" class="text-xs font-semibold text-gray-700">Permanent address is the same as current address</label>
            </div>

            <div class="block text-xs font-semibold text-gray-800 mb-2">PERMANENT ADDRESS</div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">House No.</label>
                    <input type="text" name="perm_house_no" placeholder="House No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Street/Sitio</label>
                    <input type="text" name="perm_street_name" placeholder="Street/Sitio" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Barangay</label>
                    <input type="text" name="perm_barangay" placeholder="Barangay" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Municipality</label>
                    <input type="text" name="perm_municipality_city" placeholder="Municipality" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Province</label>
                    <input type="text" name="perm_province" placeholder="Province" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Country</label>
                    <input type="text" name="perm_country" placeholder="Country" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-2">Zip Code</label>
                    <input type="text" name="perm_zip_code" placeholder="Zip Code" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm" data-perm-field>
                </div>
            </div>

            <div class="block text-xs font-semibold text-gray-800 mb-2">PARENT'S/GUARDIAN'S INFORMATION</div>
            <span class="block text-xs font-semibold text-gray-500 mb-2">Father's Full Name</span>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-4">
                <input type="text" name="father_lname" placeholder="Last Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="father_fname" placeholder="Given Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="father_mname" placeholder="Middle Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="father_suffix" placeholder="Extension" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="father_contact_no" placeholder="Contact No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
            </div>

            <span class="block text-xs font-semibold text-gray-500 mb-2">Mother's Full Name</span>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-4">
                <input type="text" name="mother_lname" placeholder="Last Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="mother_fname" placeholder="Given Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="mother_mname" placeholder="Middle Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="mother_suffix" placeholder="Extension" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="mother_contact_no" placeholder="Contact No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
            </div>

            <span class="block text-xs font-semibold text-gray-500 mb-2">Guardian's Full Name</span>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-2">
                <input type="text" name="guardian_lname" placeholder="Last Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="guardian_fname" placeholder="Given Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="guardian_mname" placeholder="Middle Name" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="guardian_suffix" placeholder="Extension" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
                <input type="text" name="guardian_contact_no" placeholder="Contact No." class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg p-8 border border-white/20">
            <span class="block text-xs font-bold text-[#296374] uppercase tracking-wider mb-6">04. Educational Background</span>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-2">Last School Attended</label>
                <input type="text" name="last_school" placeholder="Enter full name of last school attended" class="w-full border border-gray-300 rounded-md px-4 py-3 focus:ring-2 focus:ring-[#296374]/20 focus:border-[#296374] outline-none transition-all text-gray-700 bg-white shadow-sm">
            </div>
        </div>

        <div class="flex justify-end pt-2">
            <button type="submit" class="text-white font-bold text-sm uppercase tracking-wide px-12 py-4 rounded-lg shadow-lg transition-all transform hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed" style="background-color: #296374;" {{ ! $activeYear || $hasEnrollment ? 'disabled' : '' }}>
                Submit Enrollment
            </button>
        </div>
    </form>
    @endif
    @endif
</div>

<script>
    (function () {
    const enrollmentForm = document.getElementById('enrollmentForm');
    if (!enrollmentForm) {
        return;
    }

    const gradeLevelSelect = document.getElementById('grade_level');
    const semesterContainer = document.getElementById('semester_container');
    const clusterContainer = document.getElementById('cluster_container');
    const preferredCourseContainer = document.getElementById('preferred_course_container');
    const clusterSelect = document.getElementById('cluster_ID');
    const preferredCourseSelect = document.getElementById('course_ID');
    const selectedEnrollmentLevel = @json($selectedEnrollmentLevel);
    const selectedPreferredCourse = @json((string) old('course_ID', ''));
    const preferredCoursesByCluster = @json($preferredCoursesByCluster);
    const learnerDetails = document.getElementById('learner_details_container');
    const ipDetails = document.getElementById('ip_details_container');
    const fourPsDetails = document.getElementById('four_ps_details_container');
    const pwdDetails = document.getElementById('pwd_details_container');
    const sameAddressCheckbox = document.getElementById('same_address');
    const currentFields = {
        curr_house_no: document.querySelector('input[name="curr_house_no"]'),
        curr_street_name: document.querySelector('input[name="curr_street_name"]'),
        curr_barangay: document.querySelector('input[name="curr_barangay"]'),
        curr_municipality_city: document.querySelector('input[name="curr_municipality_city"]'),
        curr_province: document.querySelector('input[name="curr_province"]'),
        curr_country: document.querySelector('input[name="curr_country"]'),
        curr_zip_code: document.querySelector('input[name="curr_zip_code"]'),
    };
    const permanentFields = {
        perm_house_no: document.querySelector('input[name="perm_house_no"]'),
        perm_street_name: document.querySelector('input[name="perm_street_name"]'),
        perm_barangay: document.querySelector('input[name="perm_barangay"]'),
        perm_municipality_city: document.querySelector('input[name="perm_municipality_city"]'),
        perm_province: document.querySelector('input[name="perm_province"]'),
        perm_country: document.querySelector('input[name="perm_country"]'),
        perm_zip_code: document.querySelector('input[name="perm_zip_code"]'),
    };

    function updatePreferredCourses() {
        if (!preferredCourseSelect || !clusterSelect) {
            return;
        }

        const currentValue = preferredCourseSelect.value || selectedPreferredCourse;
        const courses = preferredCoursesByCluster[clusterSelect.value] || [];
        preferredCourseSelect.innerHTML = '<option value="">Select Preferred Course</option>';

        courses.forEach((course) => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.name;
            option.selected = course.id === currentValue;
            preferredCourseSelect.appendChild(option);
        });
    }

    function toggleSeniorFields() {
        const isSenior = selectedEnrollmentLevel === 'senior';
        semesterContainer.classList.toggle('hidden', !isSenior);
        clusterContainer.classList.toggle('hidden', !isSenior);
        preferredCourseContainer.classList.toggle('hidden', !isSenior);
        const semesterInputs = document.querySelectorAll('input[name="semester"]');

        if (clusterSelect) {
            clusterSelect.required = isSenior;
        }
        if (preferredCourseSelect) {
            preferredCourseSelect.required = isSenior;
        }
        semesterInputs.forEach((input) => {
            input.required = isSenior;
        });
        if (!isSenior) {
            semesterInputs.forEach((input) => {
                input.checked = false;
            });
            if (clusterSelect) {
                clusterSelect.value = '';
            }
            if (preferredCourseSelect) {
                preferredCourseSelect.value = '';
            }
        } else {
            updatePreferredCourses();
        }
    }

    function toggleLearnerDetails() {
        const learnerType = document.querySelector('input[name="learner_type"]:checked');
        const showDetails = learnerType && ['transferee', 'returnee'].includes(learnerType.value);
        learnerDetails.classList.toggle('hidden', !showDetails);
        learnerDetails.querySelectorAll('input').forEach((input) => {
            input.required = showDetails;
        });
    }

    function toggleYesNoDetails(name, container) {
        const selected = document.querySelector(`input[name="${name}"]:checked`);
        container.classList.toggle('hidden', !selected || selected.value !== 'Yes');
        container.querySelectorAll('input').forEach((input) => {
            input.required = selected && selected.value === 'Yes';
        });
    }

    function copyCurrentToPermanent() {
        if (!sameAddressCheckbox.checked) {
            Object.values(permanentFields).forEach((field) => {
                field.removeAttribute('readonly');
            });
            return;
        }

        Object.keys(currentFields).forEach((key) => {
            const currentField = currentFields[key];
            const permKey = key.replace('curr_', 'perm_');
            const permField = permanentFields[permKey];
            if (currentField && permField) {
                permField.value = currentField.value;
                permField.setAttribute('readonly', 'readonly');
            }
        });
    }

    gradeLevelSelect?.addEventListener('change', toggleSeniorFields);
    clusterSelect?.addEventListener('change', () => {
        if (preferredCourseSelect) {
            preferredCourseSelect.value = '';
        }
        updatePreferredCourses();
    });
    document.querySelectorAll('input[name="learner_type"]').forEach((input) => {
        input.addEventListener('change', toggleLearnerDetails);
    });
    document.querySelectorAll('input[name="ip_community"]').forEach((input) => {
        input.addEventListener('change', () => toggleYesNoDetails('ip_community', ipDetails));
    });
    document.querySelectorAll('input[name="four_ps_beneficiary"]').forEach((input) => {
        input.addEventListener('change', () => toggleYesNoDetails('four_ps_beneficiary', fourPsDetails));
    });
    document.querySelectorAll('input[name="pwd"]').forEach((input) => {
        input.addEventListener('change', () => toggleYesNoDetails('pwd', pwdDetails));
    });
    sameAddressCheckbox?.addEventListener('change', copyCurrentToPermanent);
    Object.values(currentFields).forEach((field) => {
        field?.addEventListener('input', copyCurrentToPermanent);
    });

    toggleSeniorFields();
    toggleLearnerDetails();
    toggleYesNoDetails('ip_community', ipDetails);
    toggleYesNoDetails('four_ps_beneficiary', fourPsDetails);
    toggleYesNoDetails('pwd', pwdDetails);
    })();
</script>
@endsection
