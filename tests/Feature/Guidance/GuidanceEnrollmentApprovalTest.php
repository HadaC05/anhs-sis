<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Support\VacantSectionAssigner;
use Illuminate\Support\Facades\Hash;

function createEnrollmentApprovalFixtures(): array
{
    $guidanceRole = Role::query()->create(['role_name' => 'guidance counselor']);
    $user = User::query()->create([
        'role_id' => $guidanceRole->id,
        'username' => 'guidance.enrollment',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'Grade 7',
        'description' => 'Junior High School Grade 7 curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $section = Section::query()->create([
        'name' => 'Newton',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $student = Student::query()->create([
        'lrn' => '555555555555',
        'first_name' => 'Tete',
        'last_name' => 'Student',
        'status' => 'pending',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    return compact('user', 'enrollment', 'section', 'student');
}

test('guidance counselor enrollment approval returns structured enrollment result', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section, 'student' => $student] = createEnrollmentApprovalFixtures();

    $response = $this->actingAs($user)->post(route('guidance.enrollments.approve', $enrollment), [
        'status' => 'enrolled',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('enrollment_result');

    $result = session('enrollment_result');

    expect($result['type'])->toBe('single');
    expect($result['updated'])->toBe(1);
    expect($result['status'])->toBe('enrolled');
    expect($result['section_name'])->toBe($section->name);
    expect($result['accounts'])->toHaveCount(1);
    expect($result['accounts'][0]['username'])->toBe('555555555555');

    $enrollment->refresh();
    $student->refresh();

    expect($enrollment->enrollment_status)->toBe('enrolled');
    expect($enrollment->section_ID)->toBe($section->section_ID);
    expect($student->username)->toBe('555555555555');
});

test('guidance counselor can mark a temporarily enrolled student as enrolled', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $response = $this->actingAs($user)->post(route('guidance.enrollments.confirm', $enrollment));

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHas('enrollment_result');

    $result = session('enrollment_result');

    expect($result['type'])->toBe('single');
    expect($result['updated'])->toBe(1);
    expect($result['status'])->toBe('enrolled');
    expect($result['section_name'])->toBe($section->name);

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe('enrolled');
    expect($enrollment->section_ID)->toBe($section->section_ID);
});

test('guidance counselor cannot confirm enrollment unless the student is temporarily enrolled', function () {
    ['user' => $user, 'enrollment' => $enrollment] = createEnrollmentApprovalFixtures();

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->post(route('guidance.enrollments.confirm', $enrollment));

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHasErrors('status');

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe('pending');
});

test('guidance counselor bulk enroll converts temporarily enrolled students without changing section', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.index'))
        ->post(route('guidance.enrollments.bulk-approve'), [
            'status' => 'enrolled',
            'enrollment_ids' => [$enrollment->enrollment_ID],
        ]);

    $response->assertRedirect(route('guidance.enrollments.index'));
    $response->assertSessionHas('enrollment_result');

    $result = session('enrollment_result');

    expect($result['type'])->toBe('bulk');
    expect($result['updated'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect($result['status'])->toBe('enrolled');

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe('enrolled');
    expect($enrollment->section_ID)->toBe($section->section_ID);
});

test('guidance counselor can open enrollment settings to edit details or update status', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => 'temporarily_enrolled',
    ]);

    $response = $this->actingAs($user)->get(route('guidance.enrollments.show', $enrollment));

    $response->assertOk()
        ->assertDontSee('Mark as Enrolled')
        ->assertSee('Enrollment settings')
        ->assertSee('Edit details')
        ->assertSee('Update enrollment status')
        ->assertSee('enrollment-status-form', false)
        ->assertSee(route('guidance.enrollments.edit', $enrollment), false)
        ->assertSee(route('guidance.enrollments.status', $enrollment), false);
});

test('guidance counselor approval creates a new section when matching sections are full', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $section->update(['name' => 'G7-A', 'capacity' => 1]);

    $occupant = Student::query()->create([
        'lrn' => '555555555556',
        'first_name' => 'Occupied',
        'last_name' => 'Seat',
        'status' => 'approved',
    ]);

    Enrollment::query()->create([
        'student_ID' => $occupant->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $enrollment->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $enrollment->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $response = $this->actingAs($user)->post(route('guidance.enrollments.approve', $enrollment), [
        'status' => 'enrolled',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('enrollment_result');

    $result = session('enrollment_result');
    $createdSection = Section::query()->where('name', 'G7-B')->first();

    expect($result['updated'])->toBe(1)
        ->and($result['section_name'])->toBe('G7-B')
        ->and($result['created_sections'])->toBe(['G7-B']);

    $enrollment->refresh();

    expect($createdSection)->not->toBeNull()
        ->and($createdSection->capacity)->toBe(VacantSectionAssigner::DEFAULT_CAPACITY)
        ->and($enrollment->enrollment_status)->toBe('enrolled')
        ->and($enrollment->section_ID)->toBe($createdSection->section_ID)
        ->and($enrollment->section_ID)->not->toBe($section->section_ID);
});

test('guidance counselor bulk approval creates overflow sections as existing ones fill up', function () {
    ['user' => $user, 'enrollment' => $firstEnrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $section->update(['name' => 'G7-A', 'capacity' => 1]);

    $occupant = Student::query()->create([
        'lrn' => '555555555557',
        'first_name' => 'Occupied',
        'last_name' => 'Seat',
        'status' => 'approved',
    ]);

    Enrollment::query()->create([
        'student_ID' => $occupant->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $firstEnrollment->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $firstEnrollment->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $secondStudent = Student::query()->create([
        'lrn' => '555555555558',
        'first_name' => 'Second',
        'last_name' => 'Pending',
        'status' => 'pending',
    ]);

    $secondEnrollment = Enrollment::query()->create([
        'student_ID' => $secondStudent->id,
        'section_ID' => null,
        'SY_ID' => $firstEnrollment->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $firstEnrollment->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.index'))
        ->post(route('guidance.enrollments.bulk-approve'), [
            'status' => 'enrolled',
            'enrollment_ids' => [$firstEnrollment->enrollment_ID, $secondEnrollment->enrollment_ID],
        ]);

    $response->assertRedirect(route('guidance.enrollments.index'));
    $response->assertSessionHas('enrollment_result');

    $result = session('enrollment_result');
    $overflowB = Section::query()->where('name', 'G7-B')->first();
    $overflowC = Section::query()->where('name', 'G7-C')->first();

    expect($result['type'])->toBe('bulk')
        ->and($result['updated'])->toBe(2)
        ->and($result['failed'])->toBe(0)
        ->and($result['created_sections'])->toBe(['G7-B']);

    $firstEnrollment->refresh();
    $secondEnrollment->refresh();

    expect($overflowB)->not->toBeNull()
        ->and($overflowC)->toBeNull()
        ->and($overflowB->capacity)->toBe(VacantSectionAssigner::DEFAULT_CAPACITY)
        ->and($firstEnrollment->section_ID)->toBe($overflowB->section_ID)
        ->and($secondEnrollment->section_ID)->toBe($overflowB->section_ID);
});

test('guidance counselor enrollment details list every enrollment status', function () {
    ['user' => $user, 'enrollment' => $enrollment] = createEnrollmentApprovalFixtures();

    $response = $this->actingAs($user)->get(route('guidance.enrollments.show', $enrollment));

    $response->assertOk()
        ->assertSee('Update enrollment status')
        ->assertSee('Update status')
        ->assertSee(route('guidance.enrollments.status', $enrollment), false);

    foreach (EnrollmentStatus::options() as $slug => $label) {
        $response->assertSee('value="'.$slug.'"', false)
            ->assertSee($label, false);
    }
});

test('guidance counselor can change an enrolled student to another status', function (string $status) {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => EnrollmentStatus::ENROLLED,
    ]);

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.status', $enrollment), [
            'status' => $status,
        ]);

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHas('success');

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe($status)
        ->and($enrollment->section_ID)->toBe($section->section_ID);
})->with([
    EnrollmentStatus::TEMPORARILY_ENROLLED,
    EnrollmentStatus::TRANSFERRED_OUT,
    EnrollmentStatus::DROPPED_OUT,
    EnrollmentStatus::WITHDRAWN,
    EnrollmentStatus::CANCELLED,
    EnrollmentStatus::NO_SHOW,
]);

test('guidance counselor can cancel a pending enrollment without assigning a section', function () {
    ['user' => $user, 'enrollment' => $enrollment] = createEnrollmentApprovalFixtures();

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.status', $enrollment), [
            'status' => EnrollmentStatus::CANCELLED,
        ]);

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe(EnrollmentStatus::CANCELLED)
        ->and($enrollment->section_ID)->toBeNull();
});

test('guidance counselor can re-enroll a withdrawn student and keep the assigned section', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => EnrollmentStatus::WITHDRAWN,
    ]);

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.status', $enrollment), [
            'status' => EnrollmentStatus::ENROLLED,
        ]);

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHas('success');

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe(EnrollmentStatus::ENROLLED)
        ->and($enrollment->section_ID)->toBe($section->section_ID);
});

test('guidance counselor cannot change enrollment to an unknown status', function () {
    ['user' => $user, 'enrollment' => $enrollment] = createEnrollmentApprovalFixtures();

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.show', $enrollment))
        ->patch(route('guidance.enrollments.status', $enrollment), [
            'status' => 'completed',
        ]);

    $response->assertRedirect(route('guidance.enrollments.show', $enrollment));
    $response->assertSessionHasErrors('status');

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe(EnrollmentStatus::PENDING);
});

test('guidance counselor bulk update can mark selected enrollments as no show', function () {
    ['user' => $user, 'enrollment' => $enrollment, 'section' => $section] = createEnrollmentApprovalFixtures();

    $enrollment->update([
        'section_ID' => $section->section_ID,
        'enrollment_status' => EnrollmentStatus::ENROLLED,
    ]);

    $response = $this->actingAs($user)
        ->from(route('guidance.enrollments.index'))
        ->post(route('guidance.enrollments.bulk-approve'), [
            'status' => EnrollmentStatus::NO_SHOW,
            'enrollment_ids' => [$enrollment->enrollment_ID],
        ]);

    $response->assertRedirect(route('guidance.enrollments.index'));
    $response->assertSessionHas('enrollment_result');

    $result = session('enrollment_result');

    expect($result['type'])->toBe('bulk')
        ->and($result['updated'])->toBe(1)
        ->and($result['skipped'])->toBe(0)
        ->and($result['status'])->toBe(EnrollmentStatus::NO_SHOW);

    $enrollment->refresh();

    expect($enrollment->enrollment_status)->toBe(EnrollmentStatus::NO_SHOW)
        ->and($enrollment->section_ID)->toBe($section->section_ID);
});
