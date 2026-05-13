@extends('users.registrar.layout')

@section('title', 'Student Details')

@section('content')
@php
    $application = $student->application;
    $fullName = $application?->last_name
        ? ($application->last_name . ', ' . $application->first_name . ($application->middle_name ? ' ' . $application->middle_name : '') . ($application->suffix ? ' ' . $application->suffix : ''))
        : ($student->user?->name ?? '—');
    $profile = $student->profile;
    $addresses = $student->addresses ?? collect();
    $currentAddress = $addresses->firstWhere('address_type', 'current');
    $permanentAddress = $addresses->firstWhere('address_type', 'permanent');
    $guardians = $student->guardians ?? collect();
    $father = $guardians->firstWhere('relationship', 'father');
    $mother = $guardians->firstWhere('relationship', 'mother');
    $guardian = $guardians->firstWhere('relationship', 'guardian');
@endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-2xl md:text-3xl font-bold text-gray-700 mb-1 tracking-tight">Student Details</h1>
        <p class="text-gray-600 text-sm md:text-base">Full student record and academic history.</p>
    </div>
    <a href="{{ route('registrar.students') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold border border-gray-300 text-gray-600 bg-white hover:bg-gray-50">
        Back to Masterlist
    </a>
</div>

<div class="mb-6">
    <div class="inline-flex rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <button type="button" class="tab-btn px-5 py-2.5 text-sm font-semibold text-white" style="background-color: #296374;" data-tab="info">Student Information</button>
        <button type="button" class="tab-btn px-5 py-2.5 text-sm font-semibold text-gray-600 bg-white" data-tab="history">Academic History</button>
    </div>
</div>

<div id="tab-info" class="tab-panel">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Student Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Full Name</p>
                        <p class="text-sm font-bold text-gray-800">{{ $fullName }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">LRN</p>
                        <p class="text-sm font-bold text-gray-800 font-mono">{{ $student->lrn ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Birthdate</p>
                        <p class="text-sm text-gray-700">{{ $application?->birthdate?->format('M d, Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Sex</p>
                        <p class="text-sm text-gray-700">{{ $student->sex ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Birthplace</p>
                        <p class="text-sm text-gray-700">{{ $student->birthplace ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Mother Tongue</p>
                        <p class="text-sm text-gray-700">{{ $student->mother_tongue ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Religion</p>
                        <p class="text-sm text-gray-700">{{ $student->religion ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Email</p>
                        <p class="text-sm text-gray-700">{{ $application?->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Contact No.</p>
                        <p class="text-sm text-gray-700">{{ $application?->contact_no ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Profile Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500">4Ps Beneficiary</p>
                        <p class="text-sm text-gray-700">{{ $profile?->is_4ps ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">4Ps Household ID</p>
                        <p class="text-sm text-gray-700">{{ $profile?->four_ps_household_id ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Indigenous People (IP)</p>
                        <p class="text-sm text-gray-700">{{ $profile?->is_ip ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">IP Community</p>
                        <p class="text-sm text-gray-700">{{ $profile?->ip_community ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">With Disability</p>
                        <p class="text-sm text-gray-700">{{ $profile?->has_disability ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500">Disability Name</p>
                        <p class="text-sm text-gray-700">{{ $profile?->disability_name ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Addresses</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2">Current Address</p>
                        <div class="text-sm text-gray-700 space-y-1">
                            <p>{{ $currentAddress?->house_no ?? '—' }} {{ $currentAddress?->street_name ?? '' }}</p>
                            <p>{{ $currentAddress?->barangay ?? '' }}</p>
                            <p>{{ $currentAddress?->municipality ?? '' }} {{ $currentAddress?->province ?? '' }}</p>
                            <p>{{ $currentAddress?->country ?? '' }} {{ $currentAddress?->zip_code ?? '' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2">Permanent Address</p>
                        <div class="text-sm text-gray-700 space-y-1">
                            <p>{{ $permanentAddress?->house_no ?? '—' }} {{ $permanentAddress?->street_name ?? '' }}</p>
                            <p>{{ $permanentAddress?->barangay ?? '' }}</p>
                            <p>{{ $permanentAddress?->municipality ?? '' }} {{ $permanentAddress?->province ?? '' }}</p>
                            <p>{{ $permanentAddress?->country ?? '' }} {{ $permanentAddress?->zip_code ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Parents / Guardian</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-1">Father</p>
                        <p class="text-sm text-gray-700">{{ $father?->last_name ?? '—' }}, {{ $father?->first_name ?? '' }}</p>
                        <p class="text-xs text-gray-500">{{ $father?->contact_no ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-1">Mother</p>
                        <p class="text-sm text-gray-700">{{ $mother?->last_name ?? '—' }}, {{ $mother?->first_name ?? '' }}</p>
                        <p class="text-xs text-gray-500">{{ $mother?->contact_no ?? '' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-1">Guardian</p>
                        <p class="text-sm text-gray-700">{{ $guardian?->last_name ?? '—' }}, {{ $guardian?->first_name ?? '' }}</p>
                        <p class="text-xs text-gray-500">{{ $guardian?->contact_no ?? '' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Documents</h2>
                <div class="space-y-3">
                    @forelse($student->documents as $doc)
                        <div class="border border-gray-200 rounded-lg px-4 py-3">
                            <p class="text-sm font-semibold text-gray-700">{{ strtoupper(str_replace('_', ' ', $doc->doc_type ?? 'Document')) }}</p>
                            <p class="text-xs text-gray-500">Status: {{ ucfirst(str_replace('_', ' ', $doc->status ?? 'pending')) }}</p>
                            <p class="text-xs text-gray-500">Uploaded: {{ $doc->date_uploaded?->format('M d, Y') ?? '—' }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No documents uploaded.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div id="tab-history" class="tab-panel hidden">
    <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-100 p-6">
        <h2 class="text-sm font-bold uppercase tracking-widest text-gray-500 mb-4">Academic History</h2>

        @forelse($student->enrollments as $enrollment)
            @php
                $gradeLabel = strtoupper(str_replace('grade_', 'Grade ', $enrollment->grade_level));
                $grades = $enrollment->grades ?? collect();
            @endphp
            <div class="mb-6 rounded-xl border border-gray-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $enrollment->academicYear?->school_year ?? 'School Year' }} • {{ $gradeLabel }}</p>
                        <p class="text-xs text-gray-500">Section: {{ $enrollment->section?->section_name ?? '—' }} | Cluster: {{ $enrollment->cluster?->cluster_name ?? '—' }} | Semester: {{ $enrollment->semester ? ucfirst($enrollment->semester) : '—' }}</p>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold text-white" style="background-color: #296374;">
                        {{ ucfirst(str_replace('_', ' ', $enrollment->enrollment_status ?? '')) }}
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-xs font-bold text-[#296374] uppercase tracking-wider border-b border-white/20 bg-white/30">
                                <th class="px-4 py-2">Subject</th>
                                <th class="px-4 py-2">Period</th>
                                <th class="px-4 py-2">Grade</th>
                                <th class="px-4 py-2">Remarks</th>
                                <th class="px-4 py-2">Teacher</th>
                                <th class="px-4 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/20">
                            @forelse($grades as $grade)
                                @php
                                    $subject = $grade->assignment?->curriculumSubject?->subject?->subject_name ?? '—';
                                    $teacher = $grade->assignment?->staff;
                                    $teacherName = $teacher
                                        ? ($teacher->last_name . ', ' . $teacher->first_name)
                                        : '—';
                                @endphp
                                <tr class="hover:bg-white/30 transition-all bg-white/10">
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $subject }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ strtoupper($grade->grading_period ?? '') }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $grade->numeric_grade ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $grade->remarks ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $teacherName }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700">{{ ucfirst($grade->status ?? 'draft') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-sm text-gray-500">No grades recorded for this enrollment.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No enrollment history found.</p>
        @endforelse
    </div>
</div>

<script>
    (function () {
        var buttons = Array.from(document.querySelectorAll('.tab-btn'));
        var panels = {
            info: document.getElementById('tab-info'),
            history: document.getElementById('tab-history')
        };

        function activate(tab) {
            buttons.forEach(function (btn) {
                var isActive = btn.getAttribute('data-tab') === tab;
                btn.classList.toggle('text-white', isActive);
                btn.classList.toggle('text-gray-600', !isActive);
                btn.style.backgroundColor = isActive ? '#296374' : 'white';
            });
            Object.keys(panels).forEach(function (key) {
                panels[key].classList.toggle('hidden', key !== tab);
            });
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                activate(btn.getAttribute('data-tab'));
            });
        });

        activate('info');
    })();
</script>
@endsection
