@extends('users.guidance.enrollments.partials.print-layout')

@section('title', 'Enrollment Form - '.trim(($enrollment->student?->last_name ? $enrollment->student->last_name.', ' : '').($enrollment->student?->first_name ?? 'Learner')))

@section('toolbar')
    <span class="hint">Print preview of the official 2-page Enhanced Basic Education Enrollment Form (Annex 1)</span>
    <button type="button" onclick="window.print()">Print</button>
    <a href="{{ route('guidance.enrollments.show', $enrollment) }}" class="secondary">Back to details</a>
@endsection

@section('content')
    @include('users.guidance.enrollments.partials.form-body', ['enrollment' => $enrollment])
@endsection
