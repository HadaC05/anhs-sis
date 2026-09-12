@extends('users.guidance.enrollments.partials.print-layout')

@section('title', 'Enrollment Forms - '.count($enrollments).' student(s)')

@section('toolbar')
    <span class="hint">Print preview of the official 2-page Enhanced Basic Education Enrollment Form (Annex 1)</span>
    <button type="button" onclick="window.print()">Print all</button>
    <a href="{{ route('guidance.enrollments.index') }}" class="secondary">Back to enrollment list</a>
@endsection

@section('content')
    @forelse ($enrollments as $enrollment)
        @include('users.guidance.enrollments.partials.form-body', ['enrollment' => $enrollment])
    @empty
        <section class="sheet">
            <p>No enrollments selected. Go back and select at least one student.</p>
        </section>
    @endforelse
@endsection
