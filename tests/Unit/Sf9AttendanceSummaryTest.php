<?php

use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Support\Sf9AttendanceSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createAttendanceSummaryFixtures(): array
{
    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'DepEd SHS - GAS',
        'description' => 'General Academic Strand curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();

    $section = Section::query()->create([
        'name' => 'Curie',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'last_name' => 'Learner',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('section', 'enrollment');
}

it('loads academic year school days by month', function () {
    ['section' => $section] = createAttendanceSummaryFixtures();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $section->SY_ID,
        'month' => 6,
        'school_days' => 18,
    ]);

    $schoolDays = Sf9AttendanceSummary::schoolDaysForSection($section);

    expect($schoolDays)->toHaveCount(12)
        ->and(array_keys($schoolDays)[0])->toBe(1)
        ->and($schoolDays[6])->toBe(18)
        ->and($schoolDays[7])->toBe(0)
        ->and(Sf9AttendanceSummary::schoolDaysForAcademicYear((int) $section->SY_ID)[6])->toBe(18)
        ->and(Sf9AttendanceSummary::months()[1])->toBe('January')
        ->and(Sf9AttendanceSummary::months()[12])->toBe('December');
});

it('builds enrollment attendance totals for sf9', function () {
    ['section' => $section, 'enrollment' => $enrollment] = createAttendanceSummaryFixtures();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $section->SY_ID,
        'month' => 6,
        'school_days' => 20,
    ]);

    EnrollmentMonthlyAttendance::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'month' => 6,
        'days_present' => 18,
        'days_absent' => 2,
    ]);

    $summary = Sf9AttendanceSummary::forEnrollment(
        $enrollment->enrollment_ID,
        Sf9AttendanceSummary::schoolDaysForSection($section)
    );

    expect($summary['days_present'][6])->toBe(18)
        ->and($summary['days_absent'][6])->toBe(2)
        ->and($summary['total_school_days'])->toBe(20)
        ->and($summary['total_present'])->toBe(18)
        ->and($summary['total_absent'])->toBe(2);
});
