<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\NotificationType;
use App\Models\PlacementStatus;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use App\Models\User;
use App\Notifications\EnrollmentStatusUpdated;
use App\Notifications\GradesApproved;
use App\Notifications\GradesReleased;
use App\Notifications\PlacementStatusUpdated;
use App\Notifications\StudentGradesReleased;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('students are notified when their enrollment status is updated', function () {
    Notification::fake();

    ['user' => $user, 'enrollment' => $enrollment, 'student' => $student] = createInAppNotificationEnrollment();

    $this->actingAs($user)->post(route('guidance.enrollments.approve', $enrollment), [
        'status' => EnrollmentStatus::ENROLLED,
    ])->assertRedirect();

    Notification::assertSentTo($student, EnrollmentStatusUpdated::class, function (EnrollmentStatusUpdated $notification) use ($enrollment, $student): bool {
        $data = $notification->toArray($student);

        return $notification->status === EnrollmentStatus::ENROLLED
            && $notification->enrollment->is($enrollment)
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::ENROLLMENT_STATUS);
    });
});

test('students are notified when enrollment is changed to a non-active status', function () {
    Notification::fake();

    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section, 'student' => $student] = createInAppNotificationEnrollment();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => EnrollmentStatus::ENROLLED,
    ]);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.status', $enrollment), [
            'status' => EnrollmentStatus::WITHDRAWN,
        ])
        ->assertRedirect();

    Notification::assertSentTo($student, EnrollmentStatusUpdated::class, function (EnrollmentStatusUpdated $notification): bool {
        return $notification->status === EnrollmentStatus::WITHDRAWN;
    });
});

test('overage registration notifies the student about enrollment and the placement test', function () {
    Notification::fake();
    createInAppNotificationAcademicYear();

    $this->post(route('register.store'), inAppNotificationRegistrationPayload([
        'LRN' => '321321321321',
        'email' => 'overage.notify@example.com',
        'birthdate' => '2010-01-01',
    ]))->assertRedirect(route('register'));

    $student = Student::query()->where('lrn', '321321321321')->first();

    expect($student)->not->toBeNull();

    Notification::assertSentTo($student, EnrollmentStatusUpdated::class);
    Notification::assertSentTo($student, PlacementStatusUpdated::class, function (PlacementStatusUpdated $notification) use ($student): bool {
        $data = $notification->toArray($student);

        return $notification->status === PlacementStatus::RECOMMENDED
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::PLACEMENT_TEST_RECOMMENDED);
    });
});

test('age appropriate registration notifies enrollment status but not placement', function () {
    Notification::fake();
    createInAppNotificationAcademicYear();

    $this->post(route('register.store'), inAppNotificationRegistrationPayload([
        'LRN' => '322322322322',
        'email' => 'age.ok.notify@example.com',
        'birthdate' => '2014-03-01',
    ]))->assertRedirect(route('register'));

    $student = Student::query()->where('lrn', '322322322322')->first();

    expect($student)->not->toBeNull();

    Notification::assertSentTo($student, EnrollmentStatusUpdated::class);
    Notification::assertNotSentTo($student, PlacementStatusUpdated::class);
});

test('students are notified when guidance recommends them for a placement test', function () {
    Notification::fake();

    ['user' => $user, 'enrollment' => $enrollment, 'student' => $student] = createInAppNotificationEnrollment();

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::RECOMMENDED,
        ])
        ->assertRedirect();

    Notification::assertSentTo($student, PlacementStatusUpdated::class, function (PlacementStatusUpdated $notification) use ($student): bool {
        $data = $notification->toArray($student);

        return $notification->status === PlacementStatus::RECOMMENDED
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::PLACEMENT_TEST_RECOMMENDED);
    });
});

test('students are notified when their placement status changes after a recommendation', function () {
    Notification::fake();

    ['user' => $user, 'enrollment' => $enrollment, 'student' => $student] = createInAppNotificationEnrollment();
    $enrollment->update(['placement_status' => PlacementStatus::RECOMMENDED]);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::PASSED,
        ])
        ->assertRedirect();

    Notification::assertSentTo($student, PlacementStatusUpdated::class, function (PlacementStatusUpdated $notification) use ($student): bool {
        $data = $notification->toArray($student);

        return $notification->status === PlacementStatus::PASSED
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::PLACEMENT_STATUS);
    });
});

test('keeping the same placement status does not send another notification', function () {
    Notification::fake();

    ['user' => $user, 'enrollment' => $enrollment, 'student' => $student] = createInAppNotificationEnrollment();
    $enrollment->update(['placement_status' => PlacementStatus::RECOMMENDED]);

    $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.placement-test', $enrollment), [
            'placement_status' => PlacementStatus::RECOMMENDED,
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($student, PlacementStatusUpdated::class);
});

test('teachers are notified when submitted grades are approved', function () {
    Notification::fake();

    ['registrar' => $registrar, 'teacher' => $teacher, 'assignment' => $assignment] = createInAppNotificationGradeAssignment(GradeStatus::SUBMITTED);

    $this->actingAs($registrar)
        ->post(route('registrar.grade-approvals.approve', $assignment))
        ->assertRedirect(route('registrar.grade-approvals'));

    Notification::assertSentTo($teacher, GradesApproved::class, function (GradesApproved $notification) use ($assignment, $teacher): bool {
        $data = $notification->toArray($teacher);

        return $notification->assignment->is($assignment)
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::GRADES_APPROVED);
    });
});

test('teachers are notified when approved grades are released', function () {
    Notification::fake();

    ['principal' => $principal, 'teacher' => $teacher, 'assignment' => $assignment] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);

    $this->actingAs($principal)
        ->post(route('principal.grade-releases.release', $assignment))
        ->assertRedirect(route('principal.grade-releases'));

    Notification::assertSentTo($teacher, GradesReleased::class, function (GradesReleased $notification) use ($assignment, $teacher): bool {
        $data = $notification->toArray($teacher);

        return $notification->assignment->is($assignment)
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::GRADES_RELEASED);
    });
});

test('students are notified when their approved grades are released', function () {
    Notification::fake();

    ['principal' => $principal, 'student' => $student, 'assignment' => $assignment] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);

    $this->actingAs($principal)
        ->post(route('principal.grade-releases.release', $assignment))
        ->assertRedirect(route('principal.grade-releases'));

    Notification::assertSentTo($student, StudentGradesReleased::class, function (StudentGradesReleased $notification) use ($assignment, $student): bool {
        $data = $notification->toArray($student);

        return $notification->assignment->is($assignment)
            && $data['notification_type_ID'] === NotificationType::idFor(NotificationType::GRADES_RELEASED)
            && $data['url'] === route('student.grades', [], false);
    });
});

test('student grade notices wait for every subject and are sent once per period', function () {
    ['principal' => $principal, 'student' => $student, 'assignment' => $assignment] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    $firstGrade = $assignment->grades()->firstOrFail();
    $enrollment = $firstGrade->studentSubject->enrollment;
    $subject = Subject::query()->create(['code' => 'MATH-NOTIFY', 'title' => 'Math', 'type' => 'core', 'status' => 'active']);
    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_grade_level_ID' => $enrollment->curriculum_grade_level_ID,
        'subject_ID' => $subject->getKey(),
    ]);
    \App\Support\StudentSubjectRoster::sync($enrollment);
    $second = TeacherSubjectAssignment::query()->create([
        'section_ID' => $assignment->section_ID,
        'curr_subj_ID' => $curriculumSubject->getKey(),
        'staff_ID' => $assignment->staff_ID,
        'SY_ID' => $assignment->SY_ID,
    ]);

    $this->actingAs($principal)->post(route('principal.grade-releases.release', $assignment))->assertRedirect();
    expect($student->notifications()->count())->toBe(0);

    $grade = StudentSubjectGrade::query()->create([
        'student_subject_ID' => $enrollment->studentSubjects()->where('curr_subj_ID', $curriculumSubject->getKey())->firstOrFail()->getKey(),
        'assignment_ID' => $second->getKey(),
        'term_ID' => $firstGrade->term_ID,
        'numeric_grade' => 85,
        'status' => GradeStatus::APPROVED,
        'posted_by' => $assignment->staff_ID,
    ]);
    \App\Support\StudentGradeNotifier::released($assignment, collect([$student]));
    expect($student->notifications()->count())->toBe(0);

    $this->post(route('principal.grade-releases.bulk-release'), ['assignment_ids' => [$assignment->getKey(), $second->getKey()]])->assertRedirect();
    \App\Support\StudentGradeNotifier::released($assignment, collect([$student]));
    expect($student->notifications()->count())->toBe(1)
        ->and($student->notifications()->first()->data['message'])->toContain('All your grades');

    $nextTerm = \App\Models\GradingTerm::query()->where('term_ID', '!=', $firstGrade->term_ID)->firstOrFail();
    foreach ([$firstGrade, $grade] as $record) {
        $copy = $record->replicate();
        $copy->term_ID = $nextTerm->getKey();
        $copy->status = GradeStatus::APPROVED;
        $copy->save();
    }
    $this->post(route('principal.grade-releases.bulk-release'), ['assignment_ids' => [$assignment->getKey(), $second->getKey()]])->assertRedirect();
    expect($student->notifications()->count())->toBe(2);
});

test('grade releases default to all statuses and support status filters', function () {
    ['principal' => $principal, 'assignment' => $pending] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    ['assignment' => $released] = createInAppNotificationGradeAssignment(GradeStatus::RELEASED);
    ['assignment' => $submitted] = createInAppNotificationGradeAssignment(GradeStatus::SUBMITTED);

    $this->actingAs($principal)->get(route('principal.grade-releases', ['academic_year_id' => '']))
        ->assertOk()
        ->assertViewHas('filters', fn ($filters) => $filters['status'] === 'all')
        ->assertViewHas('assignments', fn ($rows) => $rows->count() === 2 && ! $rows->contains($submitted))
        ->assertSeeInOrder(['name="search"', 'name="grade_level"', 'name="subject_id"', 'name="academic_year_id"', 'name="status"'], false);

    $this->get(route('principal.grade-releases', ['status' => 'approved', 'academic_year_id' => '']))
        ->assertOk()
        ->assertViewHas('assignments', fn ($rows) => $rows->modelKeys() === [$pending->assignment_ID]);

    $this->get(route('principal.grade-releases', ['status' => 'released', 'academic_year_id' => '']))
        ->assertOk()
        ->assertViewHas('assignments', fn ($rows) => $rows->modelKeys() === [$released->assignment_ID]);

    $this->get(route('principal.grade-releases', ['status' => 'all', 'academic_year_id' => '']))
        ->assertOk()
        ->assertViewHas('assignments', fn ($rows) => $rows->count() === 2 && ! $rows->contains($submitted));

    $this->get(route('principal.grade-releases', ['status' => 'invalid']))
        ->assertSessionHasErrors('status');
});

test('registrar grade approvals include released records and filter all supported statuses', function () {
    ['registrar' => $registrar, 'assignment' => $released] = createInAppNotificationGradeAssignment(GradeStatus::RELEASED);
    ['assignment' => $approved] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    ['assignment' => $submitted] = createInAppNotificationGradeAssignment(GradeStatus::SUBMITTED);
    ['assignment' => $draft] = createInAppNotificationGradeAssignment(GradeStatus::DRAFT);
    $filters = ['academic_year_id' => '', 'term_id' => ''];

    $this->actingAs($registrar)->get(route('registrar.grade-approvals', $filters))->assertOk()
        ->assertSee('value="released"', false)
        ->assertViewHas('assignments', fn ($rows) => $rows->count() === 3 && ! $rows->contains($draft));

    foreach (['submitted' => $submitted, 'approved' => $approved, 'released' => $released] as $status => $assignment) {
        $this->get(route('registrar.grade-approvals', $filters + ['status' => $status]))->assertOk()
            ->assertViewHas('assignments', fn ($rows) => $rows->modelKeys() === [$assignment->assignment_ID])
            ->assertSee(route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => $status]));
    }
});

test('registrar can view released grades without approval or return actions', function () {
    ['registrar' => $registrar, 'assignment' => $assignment] = createInAppNotificationGradeAssignment(GradeStatus::RELEASED);
    $this->actingAs($registrar)->get(route('registrar.grade-approvals.show', ['assignment' => $assignment, 'status' => 'released']))
        ->assertOk()->assertViewHas('status', 'released')
        ->assertSee('Released Grade Record')->assertSee('Released Records')
        ->assertSee('These grades have been released by the principal.')
        ->assertSee('90.00')->assertDontSee('Approve Grades')->assertDontSee('Return to Teacher')
        ->assertDontSee('id="gradeReturnModal"', false);

    $this->post(route('registrar.grade-approvals.approve', $assignment))->assertRedirect();
    expect($assignment->grades()->first()->status)->toBe(GradeStatus::RELEASED);
});

test('grade filters include curriculum grade mappings in both staff portals', function (string $portal, string $page) {
    $fixtures = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    $subject = $fixtures['assignment']->curriculumSubject->subject;
    $gradeId = $fixtures['assignment']->curriculumSubject->curriculumGradeLevel->grade_ID;

    $this->actingAs($fixtures[$portal])->get(route($portal.'.'.$page))
        ->assertOk()
        ->assertSeeInOrder(['name="grade_level"', 'name="subject_id"'], false)
        ->assertSee('data-grade-ids="'.$gradeId.'" value="'.$subject->subject_ID.'"', false);
})->with([
    ['principal', 'grade-releases'],
    ['registrar', 'grade-approvals'],
]);

test('grade pages default to the current term and filter grade records by term', function (string $portal, string $page) {
    $fixtures = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    $grade = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();
    $terms = \App\Models\GradingTerm::query()->juniorHigh()->orderBy('sort_order')->get();
    $current = $terms[1];
    \App\Models\GradingTerm::query()->juniorHigh()->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::closedId()]);
    $current->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    $record = $fixtures['assignment']->grades()->firstOrFail()->replicate();
    $record->term_ID = $current->term_ID;
    $record->save();
    $query = ['grade_level' => $portal === 'principal' ? $grade->grade_ID : $grade->value];

    $this->actingAs($fixtures[$portal])->get(route($portal.'.'.$page, $query))
        ->assertOk()
        ->assertViewHas('filters', fn ($filters) => (int) $filters['term_id'] === $current->term_ID && (int) $filters['academic_year_id'] === $fixtures['assignment']->SY_ID)
        ->assertViewHas('assignments', fn ($rows) => $rows->count() === 1 && $rows->first()->grades->pluck('term_ID')->all() === [$current->term_ID])
        ->assertSeeInOrder(['name="grade_level"', 'name="semester"', 'name="term_id"', 'name="subject_id"'], false);

    $this->get(route($portal.'.'.$page, $query + ['term_id' => '', 'academic_year_id' => '']))
        ->assertOk()
        ->assertViewHas('filters', fn ($filters) => ! $filters['term_id'] && ! $filters['academic_year_id'])
        ->assertViewHas('assignments', fn ($rows) => $rows->first()->grades->count() === 2);
})->with([['principal', 'grade-releases'], ['registrar', 'grade-approvals']]);

test('senior high grade filters default to the configured semester and open senior high term', function (string $portal, string $page) {
    $fixtures = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    $grade = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();
    $term = \App\Models\GradingTerm::query()->seniorHigh()->orderByDesc('sort_order')->firstOrFail();
    \App\Models\GradingTerm::query()->seniorHigh()->update(['senior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::closedId()]);
    $term->update(['senior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    \App\Models\GradingTermSetting::current()->update(['semester_ID' => \App\Models\GradingSemester::idFor('second'), 'term_ID' => $term->term_ID]);

    $this->actingAs($fixtures[$portal])->get(route($portal.'.'.$page, ['grade_level' => $portal === 'principal' ? $grade->grade_ID : $grade->value]))
        ->assertOk()
        ->assertViewHas('showSemesterFilter', true)
        ->assertViewHas('filters', fn ($filters) => (int) $filters['term_id'] === $term->term_ID && $filters['semester'] === 'second')
        ->assertViewHas('terms', fn ($terms) => $terms->every(fn ($term) => $term->school_level === 'senior_high'));
})->with([['principal', 'grade-releases'], ['registrar', 'grade-approvals']]);

test('registrar subject options include subjects without grade submissions', function () {
    $fixtures = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    $subject = Subject::query()->create(['code' => 'NO-GRADES', 'title' => 'Subject without submissions', 'type' => 'core', 'status' => 'active']);
    CurriculumSubject::query()->create([
        'curriculum_grade_level_ID' => $fixtures['assignment']->curriculumSubject->curriculum_grade_level_ID,
        'subject_ID' => $subject->subject_ID,
    ]);

    $this->actingAs($fixtures['registrar'])->get(route('registrar.grade-approvals'))
        ->assertOk()->assertSee('Subject without submissions')
        ->assertViewHas('subjects', fn ($subjects) => $subjects->contains('subject_ID', $subject->subject_ID));
});

test('grade release term options exclude archived terms for the selected school level', function () {
    ['principal' => $principal] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    $term = \App\Models\GradingTerm::query()->firstOrFail();
    $term->update([
        'junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::archivedId(),
        'senior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::activeId(),
    ]);

    $this->actingAs($principal)->get(route('principal.grade-releases', [
        'grade_level' => GradeLevel::query()->where('grade_label', 'Grade 7')->value('grade_ID'),
    ]))->assertOk()->assertViewHas('terms', fn ($terms) => ! $terms->contains($term));

    $this->get(route('principal.grade-releases', [
        'grade_level' => GradeLevel::query()->where('grade_label', 'Grade 11')->value('grade_ID'),
    ]))->assertOk()->assertViewHas('terms', fn ($terms) => $terms->contains($term));

    $term->update(['senior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::archivedId()]);
    $this->get(route('principal.grade-releases'))
        ->assertOk()->assertViewHas('terms', fn ($terms) => ! $terms->contains($term));
});

test('bulk grade release updates multiple selected assignments and leaves other grades unchanged', function () {
    Notification::fake();
    ['principal' => $principal, 'assignment' => $first] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    ['assignment' => $second] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    ['assignment' => $unselected] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);
    ['assignment' => $submitted] = createInAppNotificationGradeAssignment(GradeStatus::SUBMITTED);

    $this->actingAs($principal)->from(route('principal.grade-releases'))
        ->post(route('principal.grade-releases.bulk-release'), [
            'assignment_ids' => [$first->assignment_ID, $second->assignment_ID, $submitted->assignment_ID],
        ])->assertRedirect()->assertSessionHas('status', '2 grade record(s) released to students.');

    expect($first->grades()->first()->status)->toBe(GradeStatus::RELEASED)
        ->and($second->grades()->first()->status)->toBe(GradeStatus::RELEASED)
        ->and($unselected->grades()->first()->status)->toBe(GradeStatus::APPROVED)
        ->and($submitted->grades()->first()->status)->toBe(GradeStatus::SUBMITTED);

    $this->post(route('principal.grade-releases.bulk-release'), ['assignment_ids' => []])
        ->assertSessionHasErrors('assignment_ids');
});

test('teachers are notified when approved grades are bulk released', function () {
    Notification::fake();

    ['principal' => $principal, 'teacher' => $teacher, 'assignment' => $assignment] = createInAppNotificationGradeAssignment(GradeStatus::APPROVED);

    $this->actingAs($principal)
        ->from(route('principal.grade-releases'))
        ->post(route('principal.grade-releases.bulk-release'), [
            'assignment_ids' => [$assignment->assignment_ID],
        ])
        ->assertRedirect();

    Notification::assertSentTo($teacher, GradesReleased::class);
});

/**
 * @return array{user: User, enrollment: Enrollment, section: Section, student: Student}
 */
function createInAppNotificationEnrollment(): array
{
    $guidanceRole = Role::query()->firstOrCreate(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.inapp.notify.'.uniqid(),
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $academicYear = createInAppNotificationAcademicYear();
    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );
    $curriculum = Curriculum::query()->create([
        'name' => 'In-app Grade 7 '.uniqid(),
        'status' => true,
    ]);
    $section = Section::query()->create([
        'name' => 'Newton',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);
    $student = Student::query()->create([
        'username' => 'student.inapp.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Tete',
        'last_name' => 'Student',
        'email' => 'tete.notify@example.com',
        'status' => 'active',
    ]);
    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => EnrollmentStatus::PENDING,
    ]);

    return compact('user', 'enrollment', 'section', 'student');
}

function createInAppNotificationAcademicYear(): AcademicYear
{
    return AcademicYear::query()->firstOrCreate(
        ['school_year' => '2026-2027'],
        [
            'start_date' => '2026-06-01',
            'end_date' => '2027-03-31',
            'status' => true,
        ]
    );
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function inAppNotificationRegistrationPayload(array $overrides = []): array
{
    GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    return array_merge([
        'grade_level' => '7',
        'LRN' => '123456789012',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan Elementary School',
        'first_name' => 'Juan',
        'middle_name' => 'Dela',
        'last_name' => 'Cruz',
        'birthdate' => '2014-03-01',
        'birthplace' => 'Butuan City',
        'gender' => 'Male',
        'contact_no' => '+639123456789',
        'email' => 'juan.cruz@example.com',
        'religion' => 'Catholic',
        'mother_tongue' => 'Cebuano',
        'ip_community' => 'No',
        'four_ps_beneficiary' => 'No',
        'pwd' => 'No',
        'curr_barangay' => 'Doongan',
        'curr_municipality_city' => 'Butuan City',
        'curr_province' => 'Agusan del Norte',
        'curr_country' => 'Philippines',
        'curr_zip_code' => '8600',
        'perm_barangay' => 'Doongan',
        'perm_municipality_city' => 'Butuan City',
        'perm_province' => 'Agusan del Norte',
        'perm_country' => 'Philippines',
        'perm_zip_code' => '8600',
        'same_address' => '1',
        'father_lname' => 'Cruz',
        'father_fname' => 'Pedro',
        'mother_lname' => 'Santos',
        'mother_fname' => 'Maria',
    ], $overrides);
}

/**
 * @return array{registrar: Staff, principal: Staff, teacher: Staff, student: Student, assignment: TeacherSubjectAssignment}
 */
function createInAppNotificationGradeAssignment(string $gradeStatus): array
{
    $teacherRole = Role::query()->firstOrCreate(['role_name' => 'teacher']);
    $registrarRole = Role::query()->firstOrCreate(['role_name' => 'registrar']);
    $principalRole = Role::query()->firstOrCreate(['role_name' => 'principal']);

    $teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher.inapp.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'Grade',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);
    $registrar = Staff::query()->create([
        'role_id' => $registrarRole->id,
        'username' => 'registrar.inapp.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'School',
        'last_name' => 'Registrar',
        'status' => 'active',
    ]);
    $principal = Staff::query()->create([
        'role_id' => $principalRole->id,
        'username' => 'principal.inapp.'.uniqid(),
        'password' => Hash::make('password'),
        'change_password' => false,
        'first_name' => 'School',
        'last_name' => 'Principal',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027-'.uniqid(),
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);
    $curriculum = Curriculum::query()->create([
        'name' => 'In-app Curriculum '.uniqid(),
        'status' => true,
    ]);
    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();
    $subject = Subject::query()->create([
        'code' => 'ENG'.substr(uniqid(), -4),
        'title' => 'English 7',
        'type' => 'core',
        'status' => 'active',
    ]);
    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'grade_level' => 'grade_7',
        'semester' => 'first',
    ]);
    $section = Section::query()->create([
        'name' => 'Rizal',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'capacity' => 40,
    ]);
    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);
    $student = Student::query()->create([
        'lrn' => (string) fake()->unique()->numerify('############'),
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);
    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => EnrollmentStatus::ENROLLED,
    ]);

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $enrollment->studentSubjects()->firstOrFail()->getKey(),
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => \App\Models\GradingTerm::query()->where('key', 'term_1')->value('term_ID'),
        'numeric_grade' => 90,
        'status' => $gradeStatus,
        'posted_by' => $teacher->staff_id,
    ]);

    return compact('registrar', 'principal', 'teacher', 'student', 'assignment');
}
