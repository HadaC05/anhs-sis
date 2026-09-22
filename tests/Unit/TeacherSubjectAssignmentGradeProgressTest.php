<?php

use App\Models\TeacherSubjectAssignment;

it('labels assignments without grades as ungraded', function () {
    $assignment = new TeacherSubjectAssignment;
    $assignment->grades_count = 0;

    expect($assignment->gradeProgressLabel())->toBe('Ungraded');
});

it('labels a single status assignment with that status name', function () {
    $assignment = new TeacherSubjectAssignment;
    $assignment->grades_count = 3;
    $assignment->submitted_grades_count = 3;

    expect($assignment->gradeProgressLabel())->toBe('Submitted');
});

it('labels mixed statuses as in progress', function () {
    $assignment = new TeacherSubjectAssignment;
    $assignment->grades_count = 4;
    $assignment->submitted_grades_count = 2;
    $assignment->approved_grades_count = 2;

    expect($assignment->gradeProgressLabel())->toBe('In progress');
});
