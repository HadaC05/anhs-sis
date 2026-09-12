@extends('users.teacher.layout')

@section('title', 'Student Profile')

@section('content')
@include('users.teacher.advisory.partials.header', ['section' => $section, 'active' => 'class-list'])

@php
    $profile = $student?->profile;
    $addresses = $student?->addresses ?? collect();
    $currentAddress = $addresses->firstWhere('address_type', 'current');
    $permanentAddress = $addresses->firstWhere('address_type', 'permanent');
    $guardians = $student?->guardians ?? collect();
    $father = $guardians->firstWhere('relationship', 'father');
    $mother = $guardians->firstWhere('relationship', 'mother');
    $guardian = $guardians->firstWhere('relationship', 'guardian');
    $fullName = trim(($student?->last_name ? $student->last_name.', ' : '').($student?->first_name ?? '').($student?->middle_name ? ' '.$student->middle_name : '').($student?->suffix ? ' '.$student->suffix : ''));
@endphp

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div><h2 class="text-xl font-bold text-gray-800">Student Profile</h2><p class="mt-1 text-sm text-gray-500">View-only learner information for this advisory class.</p></div>
    <a href="{{ $backUrl }}" class="inline-flex h-9 items-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-600 transition hover:bg-gray-50">Back to class list</a>
</div>

<div class="space-y-5">
    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#296374]">Learner Information</h3><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><div><p class="text-xs font-semibold text-gray-500">Full Name</p><p class="mt-1 text-sm font-semibold text-gray-800">{{ $fullName ?: '—' }}</p></div><div><p class="text-xs font-semibold text-gray-500">LRN</p><p class="mt-1 font-mono text-sm font-semibold text-gray-800">{{ $student?->lrn ?? '—' }}</p></div><div><p class="text-xs font-semibold text-gray-500">Birthdate</p><p class="mt-1 text-sm text-gray-700">{{ $student?->birthdate?->format('M d, Y') ?? '—' }}</p></div><div><p class="text-xs font-semibold text-gray-500">Birthplace</p><p class="mt-1 text-sm text-gray-700">{{ $student?->birthplace ?? '—' }}</p></div><div><p class="text-xs font-semibold text-gray-500">Mother Tongue</p><p class="mt-1 text-sm text-gray-700">{{ $student?->mother_tongue ?? '—' }}</p></div><div><p class="text-xs font-semibold text-gray-500">Religion</p><p class="mt-1 text-sm text-gray-700">{{ $student?->religion ?? '—' }}</p></div></div></section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#296374]">Profile Information</h3><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"><div><p class="text-xs font-semibold text-gray-500">4Ps Beneficiary</p><p class="mt-1 text-sm text-gray-700">{{ $profile?->is_4ps ? 'Yes' : 'No' }}</p></div><div><p class="text-xs font-semibold text-gray-500">IP Community</p><p class="mt-1 text-sm text-gray-700">{{ $profile?->ip_community ?? '—' }}</p></div><div><p class="text-xs font-semibold text-gray-500">Disability</p><p class="mt-1 text-sm text-gray-700">{{ $profile?->has_disability ? ($profile->disability_name ?: 'Yes') : 'No' }}</p></div></div></section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#296374]">Address</h3><div class="grid gap-5 md:grid-cols-2">@foreach (['Current Address' => $currentAddress, 'Permanent Address' => $permanentAddress] as $label => $address)<div><p class="text-xs font-semibold text-gray-500">{{ $label }}</p><p class="mt-1 text-sm leading-6 text-gray-700">{{ trim(implode(', ', array_filter([$address?->house_no, $address?->street_name, $address?->barangay, $address?->municipality, $address?->province, $address?->zip_code]))) ?: '—' }}</p></div>@endforeach</div></section>

    <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"><h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-[#296374]">Parents / Guardian</h3><div class="grid gap-4 md:grid-cols-3">@foreach (['Father' => $father, 'Mother' => $mother, 'Guardian' => $guardian] as $label => $person)<div><p class="text-xs font-semibold text-gray-500">{{ $label }}</p><p class="mt-1 text-sm font-semibold text-gray-800">{{ trim(($person?->last_name ? $person->last_name.', ' : '').($person?->first_name ?? '').($person?->middle_name ? ' '.$person->middle_name : '')) ?: '—' }}</p><p class="mt-1 text-xs text-gray-500">{{ $person?->contact_no ?? '' }}</p></div>@endforeach</div></section>
</div>
@endsection
