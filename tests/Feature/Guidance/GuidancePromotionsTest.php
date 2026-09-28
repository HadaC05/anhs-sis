<?php

use App\Models\AcademicYear;
use App\Models\Curricula;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\PromotionStatus;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubject;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Support\PromotionEligibility;
use App\Support\PromotionRegistrar;

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
            ->assertSee('Promote')
            ->assertDontSee('name="SY_ID"', false)
            ->assertSee(route('guidance.promotions.confirm', Enrollment::query()->firstOrFail()), false);
    } else {
        $response->assertSee('No learners are currently eligible for promotion.');
    }
})->with([false, true]);

function guidancePromotionFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $role->id, 'username' => 'guidance.promote', 'password' => 'password',
        'first_name' => 'Guidance', 'last_name' => 'Counselor', 'status' => 'active',
    ]);
    $year = AcademicYear::query()->create([
        'school_year' => '2026-2027', 'start_date' => '2026-06-01',
        'end_date' => '2027-03-31', 'status' => false,
    ]);
    $student = Student::query()->create([
        'lrn' => '777777777778', 'first_name' => 'Ana', 'last_name' => 'Reyes', 'status' => 'active',
    ]);
    $offerings = collect([7, 8])->mapWithKeys(function (int $number): array {
        $grade = GradeLevel::query()->where('grade_label', "Grade {$number}")->firstOrFail();

        return [$number => Curriculum::query()->create([
            'name' => "Grade {$number} Curriculum",
            'curricula_ID' => Curricula::query()->firstOrFail()->curricula_ID,
            'grade_ID' => $grade->grade_ID,
            'semester_ID' => GradingSemester::idFor(GradingSemester::FULL_YEAR),
        ])];
    });
    $section = Section::query()->create([
        'name' => 'Grade 7 A', 'grade_ID' => $offerings[7]->grade_ID,
        'SY_ID' => $year->SY_ID, 'curriculum_grade_level_ID' => $offerings[7]->curriculum_ID,
        'capacity' => 40,
    ]);
    $subject = Subject::query()->create([
        'code' => 'ENG7', 'title' => 'English 7', 'type' => 'core', 'status' => 'active',
    ]);
    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_grade_level_ID' => $offerings[7]->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
    ]);
    $teacher = Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id,
        'username' => 'promotion.teacher', 'password' => 'password',
        'first_name' => 'Test', 'last_name' => 'Teacher', 'status' => 'active',
    ]);
    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID, 'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id, 'SY_ID' => $year->SY_ID,
    ]);
    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id, 'section_ID' => $section->section_ID,
        'curriculum_grade_level_ID' => $offerings[7]->curriculum_ID,
        'SY_ID' => $year->SY_ID, 'enrollment_status' => 'enrolled',
        'promotion_status' => PromotionStatus::ELIGIBLE,
    ]);
    GradingTerm::query()->update([
        'junior_high_grading_period_status_ID' => GradingPeriodStatus::closedId(),
    ]);
    $studentSubject = StudentSubject::query()->where('enrollment_ID', $enrollment->enrollment_ID)->firstOrFail();
    foreach (GradingTerm::configuredPeriods() as $period) {
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $studentSubject->student_subject_ID,
            'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period['key']),
            'numeric_grade' => 85, 'status' => GradeStatus::RELEASED,
            'posted_by' => $teacher->staff_id,
        ]);
    }

    return compact('user', 'enrollment', 'year', 'offerings');
}

test('guidance promotion automatically uses the later active year and ignores submitted year selection', function (bool $submitYear) {
    ['user' => $user, 'enrollment' => $enrollment, 'offerings' => $offerings] = guidancePromotionFixtures();
    $inactiveYear = AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => false,
    ]);
    $activeYear = AcademicYear::query()->create([
        'school_year' => '2028-2029', 'start_date' => '2028-06-01',
        'end_date' => '2029-03-31', 'status' => true,
    ]);
    $payload = $submitYear ? ['SY_ID' => $inactiveYear->SY_ID] : [];
    $response = $this->actingAs($user)->post(route('guidance.promotions.confirm', $enrollment), $payload);
    $response->assertSessionHasNoErrors();
    $next = Enrollment::query()->where('student_ID', $enrollment->student_ID)->where('SY_ID', $activeYear->SY_ID)->firstOrFail();
    $response->assertRedirect(route('guidance.enrollments.show', $next));
    expect($next->curriculum_grade_level_ID)->toBe($offerings[8]->curriculum_ID)
        ->and($next->section_ID)->toBeNull()
        ->and($next->enrollment_status)->toBe('pending');
    expect($enrollment->fresh()->promotion_status)->toBe(PromotionStatus::PROMOTED);
    expect(PromotionEligibility::synchronize($enrollment->fresh())['status'])->toBe(PromotionStatus::PROMOTED);
    $this->assertDatabaseMissing('enrollments', ['student_ID' => $enrollment->student_ID, 'SY_ID' => $inactiveYear->SY_ID]);
    $this->post(route('guidance.promotions.confirm', $enrollment))->assertSessionHasNoErrors();
    expect(Enrollment::query()->where('student_ID', $enrollment->student_ID)->count())->toBe(2);
})->with([false, true]);

test('guidance promotion requires an active year after the current enrollment', function (string $activeYearPosition) {
    ['user' => $user, 'enrollment' => $enrollment, 'year' => $year] = guidancePromotionFixtures();
    AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => false,
    ]);
    if ($activeYearPosition === 'current') {
        $year->makeActive();
    } elseif ($activeYearPosition === 'past') {
        AcademicYear::query()->create([
            'school_year' => '2025-2026', 'start_date' => '2025-06-01',
            'end_date' => '2026-03-31', 'status' => true,
        ]);
    }
    $this->actingAs($user)->from(route('guidance.promotions.index'))
        ->post(route('guidance.promotions.confirm', $enrollment))
        ->assertRedirect(route('guidance.promotions.index'))
        ->assertSessionHasErrors('promotion');
    expect(Enrollment::query()->where('student_ID', $enrollment->student_ID)->count())->toBe(1);
})->with(['none', 'current', 'past']);

test('guidance promotion still rejects learners without complete released grades', function () {
    ['user' => $user, 'enrollment' => $enrollment] = guidancePromotionFixtures();
    AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => true,
    ]);
    StudentSubjectGrade::query()->delete();
    $this->actingAs($user)->post(route('guidance.promotions.confirm', $enrollment))
        ->assertSessionHasErrors('promotion');
    expect(Enrollment::query()->where('student_ID', $enrollment->student_ID)->count())->toBe(1);
});

test('default promotion year selection still permits the next configured inactive year', function () {
    ['enrollment' => $enrollment] = guidancePromotionFixtures();
    $nextYear = AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => false,
    ]);
    $next = PromotionRegistrar::promote($enrollment);
    expect($next->SY_ID)->toBe($nextYear->SY_ID);
});

test('guidance promotion filters combine search grade eligibility and source school year', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'year' => $year, 'offerings' => $offerings] = guidancePromotionFixtures();
    $other = Student::query()->create([
        'lrn' => '888888888888', 'first_name' => 'Ben', 'last_name' => 'Santos', 'status' => 'active',
    ]);
    $otherYear = AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01', 'end_date' => '2028-03-31', 'status' => true,
    ]);
    $otherEnrollment = Enrollment::query()->create([
        'student_ID' => $other->id, 'curriculum_grade_level_ID' => $offerings[8]->curriculum_ID,
        'SY_ID' => $otherYear->SY_ID, 'enrollment_status' => 'enrolled', 'promotion_status' => PromotionStatus::RETAINED,
    ]);
    $filters = ['search' => 'Reyes, Ana', 'grade_level' => $offerings[7]->grade_ID, 'eligibility' => 'eligible', 'academic_year_id' => $year->SY_ID];
    $assertIds = function (array $params, array $ids) use ($user): void {
        $this->actingAs($user)->get(route('guidance.promotions.index', $params))
            ->assertOk()->assertViewHas('enrollments', fn ($rows) => $rows->pluck('enrollment_ID')->all() === $ids);
    };
    $assertIds($filters, [$enrollment->enrollment_ID]);
    $assertIds([...$filters, 'search' => '777777777778'], [$enrollment->enrollment_ID]);
    $assertIds([...$filters, 'search' => 'missing'], []);
    $assertIds([...$filters, 'grade_level' => $offerings[8]->grade_ID], []);
    $assertIds([...$filters, 'academic_year_id' => $otherYear->SY_ID], []);
    $assertIds(['eligibility' => 'retained'], [$otherEnrollment->enrollment_ID]);
    $assertIds(['eligibility' => 'all'], [$otherEnrollment->enrollment_ID, $enrollment->enrollment_ID]);
    $otherEnrollment->update(['promotion_status' => PromotionStatus::PENDING]);
    $assertIds(['eligibility' => 'pending'], [$otherEnrollment->enrollment_ID]);
    $this->actingAs($user)->get(route('guidance.promotions.index', ['eligibility' => 'pending']))
        ->assertDontSee('data-promotion-checkbox aria-label=', false);
});

test('guidance bulk promotion creates enrollments for selected learners and rolls back an invalid batch', function (bool $validBatch) {
    ['user' => $user, 'enrollment' => $enrollment, 'offerings' => $offerings] = guidancePromotionFixtures();
    $year = AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01', 'end_date' => '2028-03-31', 'status' => true,
    ]);
    $student = Student::query()->create([
        'lrn' => '888888888888', 'first_name' => 'Ben', 'last_name' => 'Santos', 'status' => 'active',
    ]);
    $second = Enrollment::query()->create([
        'student_ID' => $student->id, 'section_ID' => $enrollment->section_ID,
        'curriculum_grade_level_ID' => $enrollment->curriculum_grade_level_ID,
        'SY_ID' => $enrollment->SY_ID, 'enrollment_status' => 'enrolled', 'promotion_status' => PromotionStatus::ELIGIBLE,
    ]);
    if ($validBatch) {
        $subject = StudentSubject::query()->where('enrollment_ID', $second->enrollment_ID)->firstOrFail();
        foreach (StudentSubjectGrade::query()->get() as $grade) {
            $copy = $grade->replicate();
            $copy->student_subject_ID = $subject->student_subject_ID;
            $copy->save();
        }
    }
    $payload = ['enrollment_ids' => [$enrollment->enrollment_ID, $second->enrollment_ID]];
    $response = $this->actingAs($user)->from(route('guidance.promotions.index'))
        ->post(route('guidance.promotions.bulk'), $payload);
    $response->assertRedirect(route('guidance.promotions.index'));
    if ($validBatch) {
        $response->assertSessionHasNoErrors()->assertSessionHas('status', '2 selected learner(s) promoted to the next active school year.');
        foreach ([$enrollment, $second] as $source) {
            $this->assertDatabaseHas('enrollments', [
                'student_ID' => $source->student_ID, 'SY_ID' => $year->SY_ID,
                'curriculum_grade_level_ID' => $offerings[8]->curriculum_ID, 'section_ID' => null,
            ]);
        }
        $this->post(route('guidance.promotions.bulk'), $payload)->assertSessionHasNoErrors();
        expect(Enrollment::query()->where('SY_ID', $year->SY_ID)->count())->toBe(2);
    } else {
        $response->assertSessionHasErrors('promotion');
        expect(Enrollment::query()->where('SY_ID', $year->SY_ID)->count())->toBe(0);
    }
})->with([true, false]);

test('guidance bulk promotion validates its selection', function (array $payload) {
    ['user' => $user] = guidancePromotionFixtures();
    $this->actingAs($user)->post(route('guidance.promotions.bulk'), $payload)->assertSessionHasErrors();
    expect(Enrollment::query()->count())->toBe(1);
})->with([
    [[]],
    [['enrollment_ids' => []]],
    [['enrollment_ids' => [999999]]],
    [['enrollment_ids' => [1, 1]]],
]);

test('promotion pages automatically persist failing grade tags without advancing learners', function (int $failures, string $status) {
    ['user' => $user, 'enrollment' => $enrollment] = guidancePromotionFixtures();
    StudentSubjectGrade::query()->orderBy('grade_ID')->take($failures)->get()
        ->each(fn ($grade) => $grade->update(['numeric_grade' => 74]));

    $this->actingAs($user)->get(route('guidance.promotions.index', ['eligibility' => $status]))
        ->assertOk()
        ->assertViewHas('enrollments', fn ($rows) => $rows->pluck('enrollment_ID')->all() === [$enrollment->enrollment_ID]);
    expect($enrollment->fresh()->promotion_status)->toBe($status)
        ->and(Enrollment::query()->count())->toBe(1);
})->with([
    [0, PromotionStatus::ELIGIBLE],
    [1, PromotionStatus::CONDITIONALLY_PROMOTED],
    [2, PromotionStatus::CONDITIONALLY_PROMOTED],
    [3, PromotionStatus::RETAINED],
]);

test('conditional promotion blocks individual and bulk grade advancement', function (int $failures, bool $bulk) {
    ['user' => $user, 'enrollment' => $enrollment] = guidancePromotionFixtures();
    StudentSubjectGrade::query()->orderBy('grade_ID')->take($failures)->get()
        ->each(fn ($grade) => $grade->update(['numeric_grade' => 74]));
    AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => true,
    ]);
    $this->actingAs($user)->get(route('guidance.promotions.index', ['eligibility' => 'conditionally_promoted']))
        ->assertOk()->assertSee('Conditionally Promoted')
        ->assertDontSee('data-promotion-checkbox aria-label=', false);
    $this->post(
        $bulk ? route('guidance.promotions.bulk') : route('guidance.promotions.confirm', $enrollment),
        $bulk ? ['enrollment_ids' => [$enrollment->enrollment_ID]] : [],
    )->assertSessionHasErrors('promotion');
    expect(Enrollment::query()->count())->toBe(1)
        ->and($enrollment->fresh()->promotion_status)->toBe(PromotionStatus::CONDITIONALLY_PROMOTED);
})->with([1, 2])->with([false, true]);

test('incomplete grades stay pending and corrected failing grades become eligible', function () {
    ['enrollment' => $enrollment] = guidancePromotionFixtures();
    $grade = StudentSubjectGrade::query()->firstOrFail();
    $grade->update(['numeric_grade' => 74, 'status' => GradeStatus::DRAFT]);
    expect(PromotionEligibility::synchronize($enrollment)['status'])->toBe(PromotionStatus::PENDING);
    $grade->update(['status' => GradeStatus::RELEASED]);
    expect(PromotionEligibility::synchronize($enrollment)['status'])->toBe(PromotionStatus::CONDITIONALLY_PROMOTED);
    $grade->update(['numeric_grade' => 75]);
    expect(PromotionEligibility::synchronize($enrollment)['status'])->toBe(PromotionStatus::ELIGIBLE);
});

test('existing next grade enrollments are recognized as completed promotions', function () {
    ['user' => $user, 'enrollment' => $enrollment] = guidancePromotionFixtures();
    AcademicYear::query()->create([
        'school_year' => '2027-2028', 'start_date' => '2027-06-01',
        'end_date' => '2028-03-31', 'status' => true,
    ]);
    PromotionRegistrar::promote($enrollment);
    $enrollment->update(['promotion_status' => PromotionStatus::ELIGIBLE]);

    $this->actingAs($user)->get(route('guidance.promotions.index', ['eligibility' => 'promoted']))
        ->assertOk()->assertSee('Already promoted.')
        ->assertDontSee('data-promotion-checkbox aria-label=', false)
        ->assertViewHas('enrollments', fn ($rows) => $rows->pluck('enrollment_ID')->all() === [$enrollment->enrollment_ID]);
    expect($enrollment->fresh()->promotion_status)->toBe(PromotionStatus::PROMOTED);
});
