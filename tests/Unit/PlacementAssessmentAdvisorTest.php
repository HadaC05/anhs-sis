<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Support\PlacementAssessmentAdvisor;
use Illuminate\Support\Carbon;

test('summarize age alignment groups students by grade and status', function () {
    $academicYear = new AcademicYear([
        'school_year' => '2026-2027',
        'start_date' => Carbon::parse('2026-06-01'),
        'end_date' => Carbon::parse('2027-03-31'),
        'status' => true,
    ]);

    $gradeSeven = new GradeLevel([
        'grade_ID' => 1,
        'grade_label' => 'Grade 7',
        'category' => 'Junior High School',
    ]);

    $gradeEight = new GradeLevel([
        'grade_ID' => 2,
        'grade_label' => 'Grade 8',
        'category' => 'Junior High School',
    ]);

    $enrollments = [
        buildEnrollmentForAgeTest($academicYear, $gradeSeven, '2014-03-01'),
        buildEnrollmentForAgeTest($academicYear, $gradeSeven, '2010-01-01'),
        buildEnrollmentForAgeTest($academicYear, $gradeEight, '2014-06-01'),
    ];

    $summary = PlacementAssessmentAdvisor::summarizeAgeAlignment($enrollments);

    expect($summary['reviewed'])->toBe(3);
    expect($summary['appropriate'])->toBe(1);
    expect($summary['overage'])->toBe(1);
    expect($summary['underage'])->toBe(1);
    expect($summary['by_grade'])->toHaveCount(2);
    expect($summary['by_grade'][0]['label'])->toBe('Grade 7');
    expect($summary['by_grade'][0]['reviewed'])->toBe(2);
});

test('expected ranges lists the age band for each grade', function () {
    $ranges = PlacementAssessmentAdvisor::expectedRanges();

    expect($ranges)->toHaveCount(6)
        ->and($ranges[0]['label'])->toBe('Grade 7')
        ->and($ranges[0]['minimum_age'])->toBe(12)
        ->and($ranges[0]['maximum_age'])->toBe(13)
        ->and($ranges[5]['label'])->toBe('Grade 12')
        ->and($ranges[5]['minimum_age'])->toBe(17)
        ->and($ranges[5]['maximum_age'])->toBe(18);
});

test('placement assessment recommendation is null for age appropriate students', function () {
    $academicYear = new AcademicYear([
        'start_date' => Carbon::parse('2026-06-01'),
    ]);

    $gradeSeven = new GradeLevel([
        'grade_ID' => 1,
        'grade_label' => 'Grade 7',
    ]);

    $enrollment = buildEnrollmentForAgeTest($academicYear, $gradeSeven, '2014-03-01');

    expect($enrollment->placementAssessmentRecommendation())->toBeNull();
    expect($enrollment->requiresPlacementAssessment())->toBeFalse();
});

test('placement assessment is not recommended for underage students', function () {
    $academicYear = new AcademicYear([
        'start_date' => Carbon::parse('2026-06-01'),
    ]);

    $gradeSeven = new GradeLevel([
        'grade_ID' => 1,
        'grade_label' => 'Grade 7',
    ]);

    $enrollment = buildEnrollmentForAgeTest($academicYear, $gradeSeven, '2015-07-01');
    $assessment = PlacementAssessmentAdvisor::assessmentForEnrollment($enrollment);

    expect($assessment['status'])->toBe('underage');
    expect($enrollment->placementAssessmentRecommendation())->toBeNull();
    expect($enrollment->requiresPlacementAssessment())->toBeFalse();
});

test('placement assessment is recommended only for overage students', function () {
    $academicYear = new AcademicYear([
        'start_date' => Carbon::parse('2026-06-01'),
    ]);

    $gradeSeven = new GradeLevel([
        'grade_ID' => 1,
        'grade_label' => 'Grade 7',
    ]);

    $enrollment = buildEnrollmentForAgeTest($academicYear, $gradeSeven, '2010-01-01');
    $recommendation = $enrollment->placementAssessmentRecommendation();

    expect(PlacementAssessmentAdvisor::assessmentForEnrollment($enrollment)['status'])->toBe('overage');
    expect($enrollment->requiresPlacementAssessment())->toBeTrue();
    expect($recommendation)->not->toBeNull()
        ->and($recommendation['status'])->toBe('overage')
        ->and($recommendation['message'])->toContain('Recommend placement assessment');
});

function buildEnrollmentForAgeTest(AcademicYear $academicYear, GradeLevel $gradeLevel, string $birthdate): Enrollment
{
    $student = new Student([
        'first_name' => 'Test',
        'last_name' => 'Student',
        'birthdate' => Carbon::parse($birthdate),
        'sex' => 'male',
    ]);

    $enrollment = new Enrollment([
        'enrollment_status' => 'pending',
    ]);

    $enrollment->setRelation('student', $student);
    $enrollment->setRelation('academicYear', $academicYear);
    $enrollment->setRelation('gradeLevel', $gradeLevel);

    return $enrollment;
}
