<?php

use App\Models\Cluster;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradingSemester;
use App\Models\PromotionStatus;
use App\Models\Subject;
use App\Models\Track;
use Database\Seeders\DatabaseSeeder;

function secondSemesterRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'grade_level' => '11',
        'LRN' => '998877665544',
        'semester' => 'first',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan National High School',
        'first_name' => 'Second',
        'middle_name' => 'Semester',
        'last_name' => 'Learner',
        'birthdate' => '2009-05-01',
        'birthplace' => 'Butuan City',
        'gender' => 'Female',
        'contact_no' => '+639123456789',
        'email' => 'semester.two@example.com',
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
        'father_lname' => 'Learner',
        'father_fname' => 'Father',
        'mother_lname' => 'Learner',
        'mother_fname' => 'Mother',
    ], $overrides);
}

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

test('eligible grade 11 student continues to semester two by selecting only electives', function () {
    $track = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $electives = Subject::query()
        ->where('status', 'active')
        ->whereHas('subjectType', fn ($query) => $query->where('key', 'elective'))
        ->whereHas('cluster', fn ($query) => $query->where('track_ID', $track->track_ID))
        ->orderBy('subject_ID')
        ->take(4)
        ->get();

    expect($electives)->toHaveCount(4);

    $this->post(route('register.store'), secondSemesterRegistrationPayload([
        'track_ID' => $track->track_ID,
        'cluster_ID' => $stem->cluster_ID,
        'elective_ids' => $electives->take(2)->pluck('subject_ID')->all(),
    ]))->assertSessionHasNoErrors();

    $firstSemester = Enrollment::query()->with('studentSubjects')->latest('enrollment_ID')->firstOrFail();
    $firstSemester->student->update(['change_password' => false]);
    $firstSemester->update([
        'enrollment_status' => EnrollmentStatus::ENROLLED,
        'promotion_status' => PromotionStatus::ELIGIBLE,
    ]);
    $firstRoster = $firstSemester->studentSubjects->pluck('subject_ID')->sort()->values()->all();

    $this->actingAs($firstSemester->student)
        ->get(route('student.second-semester-enrollment.create'))
        ->assertOk()
        ->assertSee('Grade 11 Semester 2 Enrollment')
        ->assertSee('Choose your electives')
        ->assertSee('Semester 2 Enrollment');

    $selected = $electives->skip(2)->take(2)->pluck('subject_ID')->all();
    $this->actingAs($firstSemester->student)
        ->post(route('student.second-semester-enrollment.store'), ['elective_ids' => $selected])
        ->assertRedirect(route('student.second-semester-enrollment.create'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    $secondSemester = Enrollment::query()
        ->with(['gradingSemester', 'electives', 'studentSubjects.subject', 'section'])
        ->where('student_ID', $firstSemester->student_ID)
        ->where('enrollment_ID', '!=', $firstSemester->enrollment_ID)
        ->firstOrFail();

    expect($secondSemester->gradingSemester?->key)->toBe(GradingSemester::SECOND)
        ->and($secondSemester->section_ID)->not->toBeNull()
        ->and($secondSemester->enrollment_status)->toBe(EnrollmentStatus::ENROLLED)
        ->and($secondSemester->electives->pluck('subject_ID')->sort()->values()->all())
        ->toBe(collect($selected)->sort()->values()->all())
        ->and($secondSemester->studentSubjects->where('subject.type', 'core'))->toHaveCount(6)
        ->and($secondSemester->studentSubjects->where('subject.type', 'elective'))->toHaveCount(2)
        ->and($firstSemester->fresh()->studentSubjects()->pluck('subject_ID')->sort()->values()->all())
        ->toBe($firstRoster);

    foreach ($selected as $subjectId) {
        $this->assertDatabaseMissing('curriculum_subjects', [
            'curriculum_grade_level_ID' => $secondSemester->curriculum_grade_level_ID,
            'subject_ID' => $subjectId,
        ]);
        $this->assertDatabaseHas('enrollment_electives', [
            'enrollment_ID' => $secondSemester->enrollment_ID,
            'subject_ID' => $subjectId,
        ]);
    }

    $this->actingAs($firstSemester->student)
        ->post(route('student.second-semester-enrollment.store'), ['elective_ids' => $selected])
        ->assertSessionHasErrors('enrollment');

    expect(Enrollment::query()->where('student_ID', $firstSemester->student_ID)->count())->toBe(2);
});

test('grade 11 student cannot continue before school confirms eligibility', function () {
    $track = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $electives = Subject::query()
        ->where('status', 'active')
        ->whereHas('subjectType', fn ($query) => $query->where('key', 'elective'))
        ->whereHas('cluster', fn ($query) => $query->where('track_ID', $track->track_ID))
        ->take(2)
        ->get();

    $this->post(route('register.store'), secondSemesterRegistrationPayload([
        'LRN' => '998877665533',
        'email' => 'semester.pending@example.com',
        'track_ID' => $track->track_ID,
        'cluster_ID' => $stem->cluster_ID,
        'elective_ids' => $electives->pluck('subject_ID')->all(),
    ]));

    $enrollment = Enrollment::query()->latest('enrollment_ID')->firstOrFail();
    $enrollment->student->update(['change_password' => false]);
    $enrollment->update(['enrollment_status' => EnrollmentStatus::ENROLLED]);

    $this->actingAs($enrollment->student)
        ->get(route('student.second-semester-enrollment.create'))
        ->assertOk()
        ->assertSee('Enrollment is not available yet')
        ->assertSee('eligibility has not yet been confirmed');

    $this->actingAs($enrollment->student)
        ->post(route('student.second-semester-enrollment.store'), ['elective_ids' => $electives->pluck('subject_ID')->all()])
        ->assertSessionHasErrors('enrollment');

    expect(Enrollment::query()->where('student_ID', $enrollment->student_ID)->count())->toBe(1);
});
