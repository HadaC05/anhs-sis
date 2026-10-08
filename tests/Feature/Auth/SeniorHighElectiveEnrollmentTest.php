<?php

use App\Models\Cluster;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\Subject;
use App\Models\Track;
use Database\Seeders\DatabaseSeeder;

function seniorHighElectivePayload(array $overrides = []): array
{
    return array_merge([
        'grade_level' => '11',
        'LRN' => '987654321098',
        'semester' => 'first',
        'learner_type' => 'regular',
        'last_school_attended' => 'Agusan National High School',
        'first_name' => 'Senior',
        'middle_name' => 'High',
        'last_name' => 'Learner',
        'birthdate' => '2009-05-01',
        'birthplace' => 'Butuan City',
        'gender' => 'Female',
        'contact_no' => '+639123456789',
        'email' => 'senior.electives@example.com',
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

test('senior high enrollment chooses a track and two electives grouped across clusters', function () {
    $academicTrack = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $business = Cluster::query()->where('name', 'Business and Entrepreneurship')->firstOrFail();
    $stemElective = Subject::query()->where('cluster_ID', $stem->cluster_ID)->where('status', 'active')->firstOrFail();
    $businessElective = Subject::query()->where('cluster_ID', $business->cluster_ID)->where('status', 'active')->firstOrFail();

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('name="track_ID"', false)
        ->assertSee('name="elective_ids[]"', false)
        ->assertSee('Science, Technology, Engineering and Mathematics')
        ->assertSee('Business and Entrepreneurship')
        ->assertDontSee('Preferred Course');

    $response = $this->post(route('register.store'), seniorHighElectivePayload([
        'track_ID' => $academicTrack->track_ID,
        'cluster_ID' => $stem->cluster_ID,
        'elective_ids' => [$stemElective->subject_ID, $businessElective->subject_ID],
    ]));

    $response->assertRedirect(route('register'))->assertSessionHasNoErrors();

    $enrollment = Enrollment::query()->with(['electives', 'studentSubjects.subject'])->latest('enrollment_ID')->firstOrFail();

    expect($enrollment->track_ID)->toBe($academicTrack->track_ID)
        ->and($enrollment->track?->name)->toBe('Academic Track')
        ->and($enrollment->electives->pluck('subject_ID')->sort()->values()->all())
        ->toBe(collect([$stemElective->subject_ID, $businessElective->subject_ID])->sort()->values()->all())
        ->and($enrollment->studentSubjects->pluck('subject_ID')->sort()->values()->all())
        ->toContain($stemElective->subject_ID, $businessElective->subject_ID)
        ->and($enrollment->studentSubjects->filter(fn ($row) => $row->subject?->type === 'core'))->toHaveCount(6)
        ->and($enrollment->studentSubjects->filter(fn ($row) => $row->subject?->type === 'elective'))->toHaveCount(2);

    foreach ([$stemElective, $businessElective] as $elective) {
        expect(\App\Models\CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID)
            ->where('subject_ID', $elective->subject_ID)
            ->exists())->toBeFalse();
        $this->assertDatabaseHas('student_subjects', [
            'enrollment_ID' => $enrollment->enrollment_ID,
            'subject_ID' => $elective->subject_ID,
        ]);
        $this->assertDatabaseHas('teacher_subject_assignments', [
            'section_ID' => $enrollment->section_ID,
            'subject_ID' => $elective->subject_ID,
            'SY_ID' => $enrollment->SY_ID,
        ]);
    }

    $notEnlisted = Subject::query()
        ->where('cluster_ID', $stem->cluster_ID)
        ->whereNotIn('subject_ID', [$stemElective->subject_ID, $businessElective->subject_ID])
        ->where('status', 'active')
        ->firstOrFail();
    \App\Models\TeacherSubjectAssignment::query()->firstOrCreate([
        'section_ID' => $enrollment->section_ID,
        'subject_ID' => $notEnlisted->subject_ID,
        'SY_ID' => $enrollment->SY_ID,
    ]);

    $enrollment->load(['student', 'studentSubjects', 'track', 'cluster.track', 'academicYear']);
    $section = $enrollment->section->load(['academicYear', 'cluster.track', 'gradeLevel', 'adviser', 'curriculum']);
    $assignments = \App\Models\TeacherSubjectAssignment::query()
        ->with('subject.subjectType')
        ->where('section_ID', $section->section_ID)
        ->where('SY_ID', $enrollment->SY_ID)
        ->get();
    $periods = \App\Models\GradingTerm::periodsForSection($section);

    $sf9 = \App\Support\Sf9ReportCardBuilder::buildCard(
        $enrollment,
        $section,
        $assignments,
        collect(),
        collect(),
        $periods,
    );
    $sf10 = \App\Support\LearnerPermanentRecordBuilder::buildScholasticRecord(
        $enrollment,
        $section,
        $assignments,
        collect(),
        $periods,
    );
    $sf10Card = \App\Support\LearnerPermanentRecordBuilder::buildCardsForStudents(collect([$enrollment]))->first();
    $sf10Html = view('users.teacher.advisory.sf10-print', [
        'cards' => collect([$sf10Card]),
        'section' => $section,
        'periods' => $periods,
    ])->render();
    $sf9Labels = collect($sf9['subjects'])->pluck('label');
    $sf10Labels = collect($sf10['subjects'])->pluck('label');

    expect($sf9Labels)->toContain($stemElective->title, $businessElective->title)
        ->not->toContain($notEnlisted->title, 'Academic Elective 1', 'Mabisang Komunikasyon')
        ->and($sf10Labels)->toContain($stemElective->title, $businessElective->title)
        ->not->toContain($notEnlisted->title, 'Mathematics', 'MAPEH')
        ->and($sf10['is_senior_high'])->toBeTrue()
        ->and($sf10Html)->toContain('SF10-SHS', $stemElective->title, $businessElective->title)
        ->not->toContain($notEnlisted->title, 'SF10-JHS');

    // A later section assignment (for example after document verification)
    // must recreate the elective grade-book entries as well.
    \App\Models\TeacherSubjectAssignment::query()
        ->where('section_ID', $enrollment->section_ID)
        ->whereIn('subject_ID', $enrollment->studentSubjects
            ->filter(fn ($row) => $row->subject?->type === 'elective')
            ->pluck('subject_ID'))
        ->delete();
    $assignedSectionId = $enrollment->section_ID;
    $enrollment->update(['section_ID' => null]);
    $enrollment->update(['section_ID' => $assignedSectionId]);

    expect(\App\Models\TeacherSubjectAssignment::query()
        ->where('section_ID', $assignedSectionId)
        ->whereIn('subject_ID', $enrollment->studentSubjects
            ->filter(fn ($row) => $row->subject?->type === 'elective')
            ->pluck('subject_ID'))
        ->count())->toBe(2);
});

test('technical senior high enrollment stores its track and exactly one elective', function () {
    $technicalTrack = Track::query()->where('name', 'Technical Professional Track')->firstOrFail();
    $ict = Cluster::query()->where('name', 'ICT Support and Computer Programming Technologies')->firstOrFail();
    $elective = Subject::query()->where('cluster_ID', $ict->cluster_ID)->where('status', 'active')->firstOrFail();

    $response = $this->post(route('register.store'), seniorHighElectivePayload([
        'LRN' => '987654321097',
        'email' => 'technical.elective@example.com',
        'track_ID' => $technicalTrack->track_ID,
        'cluster_ID' => $ict->cluster_ID,
        // The browser posts the hidden second Academic elective control too.
        'elective_ids' => [$elective->subject_ID, ''],
    ]));

    $response->assertRedirect(route('register'))->assertSessionHasNoErrors();

    $enrollment = Enrollment::query()->with(['track', 'electives', 'studentSubjects.subject'])->latest('enrollment_ID')->firstOrFail();

    expect($enrollment->track_ID)->toBe($technicalTrack->track_ID)
        ->and($enrollment->track?->name)->toBe('Technical Professional Track')
        ->and($enrollment->electives)->toHaveCount(1)
        ->and($enrollment->electives->first()->subject_ID)->toBe($elective->subject_ID)
        ->and($enrollment->studentSubjects->filter(fn ($row) => $row->subject?->type === 'elective'))->toHaveCount(1)
        ->and(\App\Models\CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID)
            ->where('subject_ID', $elective->subject_ID)
            ->exists())->toBeFalse();

    $this->assertDatabaseHas('student_subjects', [
        'enrollment_ID' => $enrollment->enrollment_ID,
        'subject_ID' => $elective->subject_ID,
    ]);
});

test('grade 11 second semester continues the same core subjects with a separate semester grade book', function () {
    $academicTrack = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $electives = Subject::query()
        ->where('cluster_ID', $stem->cluster_ID)
        ->where('status', 'active')
        ->limit(2)
        ->get();

    $this->post(route('register.store'), seniorHighElectivePayload([
        'LRN' => '987654321096',
        'email' => 'second.semester.core@example.com',
        'semester' => 'second',
        'track_ID' => $academicTrack->track_ID,
        'cluster_ID' => $stem->cluster_ID,
        'elective_ids' => $electives->pluck('subject_ID')->all(),
    ]))->assertSessionHasNoErrors();

    $enrollment = Enrollment::query()
        ->with(['curriculumGradeLevel.gradingSemester', 'section.curriculum.gradingSemester', 'studentSubjects.subject.subjectType'])
        ->whereHas('student', fn ($query) => $query->where('email', 'second.semester.core@example.com'))
        ->firstOrFail();
    $coreSubjectIds = $enrollment->studentSubjects
        ->filter(fn ($row): bool => $row->subject?->subjectType?->key === 'core')
        ->pluck('subject_ID');

    expect($enrollment->curriculumGradeLevel?->gradingSemester?->key)->toBe('second')
        ->and($coreSubjectIds)->toHaveCount(6)
        ->and($enrollment->studentSubjects->filter(fn ($row): bool => $row->subject?->subjectType?->key === 'elective'))->toHaveCount(2)
        ->and(CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID)
            ->whereIn('subject_ID', $coreSubjectIds)
            ->count())->toBe(6)
        ->and(\App\Models\TeacherSubjectAssignment::query()
            ->where('section_ID', $enrollment->section_ID)
            ->whereIn('subject_ID', $coreSubjectIds)
            ->count())->toBe(6)
        ->and(collect(\App\Models\GradingTerm::periodsForSection($enrollment->section, $enrollment->semester))
            ->pluck('key')
            ->every(fn (string $key): bool => str_starts_with($key, 'shs_sem2_')))->toBeTrue();

    foreach ($electives as $elective) {
        expect(CurriculumSubject::query()
            ->where('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID)
            ->where('subject_ID', $elective->subject_ID)
            ->exists())->toBeFalse();
    }
});

test('learners in the same senior high section keep independent elective rosters', function () {
    $technicalTrack = Track::query()->where('name', 'Technical Professional Track')->firstOrFail();
    $ict = Cluster::query()->where('name', 'ICT Support and Computer Programming Technologies')->firstOrFail();
    $electives = Subject::query()
        ->where('cluster_ID', $ict->cluster_ID)
        ->where('status', 'active')
        ->limit(2)
        ->get();

    expect($electives)->toHaveCount(2);

    foreach ($electives as $index => $elective) {
        $this->post(route('register.store'), seniorHighElectivePayload([
            'LRN' => '9876543210'.(90 + $index),
            'email' => "isolated.elective.{$index}@example.com",
            'track_ID' => $technicalTrack->track_ID,
            'cluster_ID' => $ict->cluster_ID,
            'elective_ids' => [$elective->subject_ID],
        ]))->assertSessionHasNoErrors();
    }

    $enrollments = Enrollment::query()
        ->with(['electives', 'studentSubjects.subject'])
        ->whereHas('student', fn ($query) => $query->where('email', 'like', 'isolated.elective.%'))
        ->orderBy('enrollment_ID')
        ->get();

    expect($enrollments)->toHaveCount(2)
        ->and($enrollments->pluck('section_ID')->unique())->toHaveCount(1);

    foreach ($enrollments as $index => $enrollment) {
        $selectedId = $electives[$index]->subject_ID;
        $otherId = $electives[1 - $index]->subject_ID;

        expect($enrollment->electives->pluck('subject_ID')->all())->toBe([$selectedId])
            ->and($enrollment->studentSubjects->pluck('subject_ID'))->toContain($selectedId)
            ->not->toContain($otherId)
            ->and(CurriculumSubject::query()->where('subject_ID', $selectedId)->exists())->toBeFalse();
    }
});

test('junior high enrollment ignores hidden senior high elective fields', function () {
    $academicTrack = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $elective = Subject::query()->where('cluster_ID', $stem->cluster_ID)->where('status', 'active')->firstOrFail();

    foreach ([7, 8, 9, 10] as $grade) {
        $response = $this->post(route('register.store'), seniorHighElectivePayload([
            'grade_level' => (string) $grade,
            'LRN' => sprintf('7000000000%02d', $grade),
            'email' => "junior.high.{$grade}@example.com",
            // Match the values submitted by hidden controls and a stale form draft.
            'semester' => 'first',
            'track_ID' => $academicTrack->track_ID,
            'cluster_ID' => $stem->cluster_ID,
            'elective_ids' => ['', $elective->subject_ID],
        ]));

        $response->assertRedirect(route('register'))->assertSessionHasNoErrors();

        $enrollment = Enrollment::query()
            ->with(['electives', 'studentSubjects.subject.subjectType'])
            ->whereHas('student', fn ($query) => $query->where('lrn', sprintf('7000000000%02d', $grade)))
            ->firstOrFail();

        expect($enrollment->semester)->toBeNull()
            ->and($enrollment->track_ID)->toBeNull()
            ->and($enrollment->cluster_ID)->toBeNull()
            ->and($enrollment->electives)->toHaveCount(0)
            ->and($enrollment->studentSubjects)->toHaveCount(8)
            ->and($enrollment->studentSubjects->every(
                fn ($row): bool => $row->subject?->subjectType?->key === 'general'
            ))->toBeTrue();
    }
});

test('senior high enrollment rejects a cluster outside the selected track and duplicate electives', function () {
    $technicalTrack = Track::query()->where('name', 'Technical Professional Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $elective = Subject::query()->where('cluster_ID', $stem->cluster_ID)->where('status', 'active')->firstOrFail();

    $this->from(route('register'))->post(route('register.store'), seniorHighElectivePayload([
        'track_ID' => $technicalTrack->track_ID,
        'cluster_ID' => $stem->cluster_ID,
        'elective_ids' => [$elective->subject_ID, $elective->subject_ID],
    ]))->assertRedirect(route('register'))->assertSessionHasErrors(['cluster_ID', 'elective_ids.0', 'elective_ids.1']);
});

test('elective counts are enforced by track on the server', function () {
    $academicTrack = Track::query()->where('name', 'Academic Track')->firstOrFail();
    $technicalTrack = Track::query()->where('name', 'Technical Professional Track')->firstOrFail();
    $stem = Cluster::query()->where('name', 'Science, Technology, Engineering and Mathematics')->firstOrFail();
    $ict = Cluster::query()->where('name', 'ICT Support and Computer Programming Technologies')->firstOrFail();
    $academicElectives = Subject::query()->where('cluster_ID', $stem->cluster_ID)->where('status', 'active')->limit(2)->pluck('subject_ID')->all();
    $technicalElectives = Subject::query()->where('cluster_ID', $ict->cluster_ID)->where('status', 'active')->limit(2)->pluck('subject_ID')->all();

    $this->from(route('register'))->post(route('register.store'), seniorHighElectivePayload([
        'track_ID' => $academicTrack->track_ID,
        'cluster_ID' => $stem->cluster_ID,
        'elective_ids' => [$academicElectives[0]],
    ]))->assertSessionHasErrors('elective_ids');

    $this->from(route('register'))->post(route('register.store'), seniorHighElectivePayload([
        'track_ID' => $technicalTrack->track_ID,
        'cluster_ID' => $ict->cluster_ID,
        'elective_ids' => $technicalElectives,
    ]))->assertSessionHasErrors('elective_ids');
});
