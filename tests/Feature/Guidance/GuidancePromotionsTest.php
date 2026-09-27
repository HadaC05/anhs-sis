<?php

use App\Models\AcademicYear;
use App\Models\Curricula;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use App\Models\PromotionStatus;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;

test('guidance counselor can open the promotions page', function (bool $hasEligibleLearner) {
    $role = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $role->id,
        'username' => 'guidance.promotions',
        'password' => 'password',
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    if ($hasEligibleLearner) {
        $year = AcademicYear::query()->create([
            'school_year' => '2026-2027',
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => true,
        ]);
        $student = Student::query()->create([
            'lrn' => '777777777777',
            'first_name' => 'Maria',
            'last_name' => 'Reyes',
            'status' => 'pending',
        ]);
        PromotionStatus::clearCache();
        $grade = GradeLevel::query()->firstOrCreate(
            ['grade_label' => 'Grade 7'],
            ['category' => 'Junior High School'],
        );
        $offering = Curriculum::query()->create([
            'name' => 'Grade 7 Curriculum',
            'curricula_ID' => Curricula::query()->firstOrFail()->curricula_ID,
            'grade_ID' => $grade->grade_ID,
            'semester_ID' => GradingSemester::idFor(GradingSemester::FULL_YEAR),
        ]);
        Enrollment::query()->create([
            'student_ID' => $student->id,
            'curriculum_grade_level_ID' => $offering->curriculum_ID,
            'SY_ID' => $year->SY_ID,
            'enrollment_status' => 'pending',
            'promotion_status' => PromotionStatus::ELIGIBLE,
        ]);
    }

    $response = $this->actingAs($user)->get(route('guidance.promotions.index'))
        ->assertOk()
        ->assertSee('Promotion Confirmation');

    if ($hasEligibleLearner) {
        $response->assertSee('Reyes, Maria')
            ->assertSee('Grade 7')
            ->assertSee('2026-2027')
            ->assertSee('Next school year')
            ->assertSee(route('guidance.promotions.confirm', Enrollment::query()->firstOrFail()), false);
    } else {
        $response->assertSee('No learners are currently eligible for promotion.');
    }
})->with([false, true]);
