<?php

use App\Models\StudentSubjectGrade;

it('treats submitted approved and released grades as teacher locked', function () {
    $grade = new StudentSubjectGrade(['status' => 'submitted']);
    expect($grade->isTeacherLocked())->toBeTrue();

    $grade->status = 'approved';
    expect($grade->isTeacherLocked())->toBeTrue();

    $grade->status = 'released';
    expect($grade->isTeacherLocked())->toBeTrue();
});

it('allows teachers to edit draft and rejected grades', function () {
    $grade = new StudentSubjectGrade(['status' => 'draft']);
    expect($grade->isTeacherLocked())->toBeFalse();

    $grade->status = 'rejected';
    expect($grade->isTeacherLocked())->toBeFalse();
});
