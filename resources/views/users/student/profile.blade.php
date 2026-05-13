@extends('users.student.layout')

@section('title', 'My Information')

@section('content')
@php
    $studentName = optional($application)->last_name
        ? optional($application)->last_name . ', ' . optional($application)->first_name
        : Auth::user()->name;
    $gradeLabel = $currentEnrollment?->grade_level ? strtoupper(str_replace('grade_', 'Grade ', $currentEnrollment->grade_level)) : 'N/A';
    $documentLabels = [
        'birth_certificate' => 'Birth Certificate',
        'form_137' => 'Form 137 / SF9',
        'good_moral' => 'Good Moral Certificate',
        'id_photo' => '2x2 ID Photo',
        'other' => 'Other Supporting Document',
    ];
@endphp

<div class="space-y-6">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-2 tracking-tight">My Information</h1>
        <p class="text-gray-600 text-sm md:text-base">Review the student information currently saved in your account.</p>
    </div>

    @if(!$student)
        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-100 p-8 text-center">
            <h2 class="text-xl font-bold text-gray-700">Student profile not found</h2>
            <p class="text-sm text-gray-500 mt-2">Your account does not have a complete student record yet.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="shadow-xl rounded-2xl p-6 md:col-span-2" style="background-color: #296374;">
                <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Student Name</p>
                <p class="text-xl font-bold text-white">{{ $studentName }}</p>
            </div>
            <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #10b981;">
                <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">LRN</p>
                <p class="text-xl font-bold text-white">{{ $student->lrn ?? 'Not Set' }}</p>
            </div>
            <div class="shadow-xl rounded-2xl p-6 border-l-4" style="background-color: #296374; border-left-color: #f59e0b;">
                <p class="text-xs font-bold text-white/80 uppercase tracking-wider mb-2">Active School Year</p>
                <p class="text-xl font-bold text-white">{{ $activeYear?->school_year ?? 'Not Set' }}</p>
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
            <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider mb-4">Personal Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500">Last Name</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->last_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">First Name</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->first_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Middle Name</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->middle_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Suffix</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->suffix ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Birthdate</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->birthdate?->format('M d, Y') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Sex</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $student->sex ? ucfirst($student->sex) : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Email</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->email ?? Auth::user()->email ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Contact Number</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->contact_no ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Birthplace</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $student->birthplace ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Religion</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $student->religion ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Mother Tongue</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $student->mother_tongue ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Application Status</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $application?->status ? ucfirst($application->status) : '-' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
            <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider mb-4">Current Enrollment</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs font-semibold text-gray-500">School Year</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $activeYear?->school_year ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Grade Level</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $gradeLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Enrollment Status</p>
                    <p class="text-sm font-semibold text-gray-800">
                        {{ $currentEnrollment?->enrollment_status ? ucwords(str_replace('_', ' ', $currentEnrollment->enrollment_status)) : 'Not enrolled' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Section</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment?->section?->name ?? 'Not assigned' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Cluster</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment?->cluster?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Preferred Course</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment?->preferredCourse?->name ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500">Semester</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $currentEnrollment?->semester ? ucfirst($currentEnrollment->semester) . ' Semester' : '-' }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
            <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider mb-4">4Ps / IP / PWD Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">4Ps Beneficiary</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $profile?->is_4ps ? 'Yes' : 'No' }}</p>
                    <p class="text-xs text-gray-600 mt-1">{{ $profile?->four_ps_household_id ?: '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Indigenous People (IP)</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $profile?->is_ip ? 'Yes' : 'No' }}</p>
                    <p class="text-xs text-gray-600 mt-1">{{ $profile?->ip_community ?: '-' }}</p>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Person with Disability (PWD)</p>
                    <p class="text-sm font-semibold text-gray-800">{{ $profile?->has_disability ? 'Yes' : 'No' }}</p>
                    <p class="text-xs text-gray-600 mt-1">{{ $profile?->disability_name ?: '-' }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
                <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider mb-4">Addresses</h2>
                @if($student->addresses->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($student->addresses as $address)
                            <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $address->address_type ?? 'Address' }}</p>
                                <p class="text-sm font-semibold text-gray-800">
                                    {{ implode(', ', array_filter([$address->house_no, $address->street_name, $address->barangay, $address->municipality, $address->province, $address->country, $address->zip_code])) ?: '-' }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No address information saved yet.</p>
                @endif
            </div>

            <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
                <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider mb-4">Parents / Guardians</h2>
                @if($student->guardians->isNotEmpty())
                    <div class="space-y-4">
                        @foreach($student->guardians as $guardian)
                            @php
                                $fullName = trim(implode(' ', array_filter([
                                    $guardian->first_name,
                                    $guardian->middle_name,
                                    $guardian->last_name,
                                    $guardian->suffix,
                                ])));
                            @endphp
                            <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">{{ $guardian->relationship ?? 'Guardian' }}</p>
                                <p class="text-sm font-semibold text-gray-800">{{ $fullName ?: '-' }}</p>
                                <p class="text-xs text-gray-600 mt-1">{{ $guardian->contact_no ?: 'No contact number' }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No guardian information saved yet.</p>
                @endif
            </div>
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider">Uploaded Documents</h2>
                    <p class="text-sm text-gray-500 mt-1">These are the files currently attached to your student record.</p>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold bg-slate-100 text-slate-700">
                    {{ $documents->count() }} uploaded
                </span>
            </div>

            @if($documents->isNotEmpty())
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($documents as $document)
                        <div class="rounded-lg border border-gray-200 bg-gray-50/80 p-5">
                            <p class="text-xs font-bold text-[#296374] uppercase tracking-wider">
                                {{ $documentLabels[$document->doc_type] ?? ucwords(str_replace('_', ' ', $document->doc_type ?? 'document')) }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Uploaded {{ $document->date_uploaded?->format('M d, Y h:i A') ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-1">Status: {{ ucfirst($document->status ?? 'pending') }}</p>
                            @if($document->file_path)
                                <a href="{{ route('student.documents.view', $document) }}" target="_blank" class="inline-flex items-center gap-2 text-sm font-semibold text-[#296374] hover:underline mt-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    View document
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No documents uploaded yet.</p>
            @endif
        </div>

        <div class="bg-white/95 backdrop-blur-sm shadow-xl rounded-lg border border-white/20 p-8">
            <h2 class="text-xs font-bold text-[#296374] uppercase tracking-wider mb-4">Enrollment History</h2>
            @if($enrollments->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">School Year</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Grade Level</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Cluster</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Preferred Course</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($enrollments as $enrollment)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enrollment->academicYear?->school_year ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level ?? '')) ?: '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enrollment->section?->name ?? 'Not assigned' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enrollment->cluster?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enrollment->preferredCourse?->name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $enrollment->enrollment_status ? ucwords(str_replace('_', ' ', $enrollment->enrollment_status)) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">No enrollment history found yet.</p>
            @endif
        </div>
    @endif
</div>
@endsection
