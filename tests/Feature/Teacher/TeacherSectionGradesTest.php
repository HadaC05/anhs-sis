<?php

use App\Jobs\ProcessAdvisoryClassListImport;
use App\Models\AcademicYear;
use App\Models\AdvisoryClassListImport;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;

function createTeacherSectionGradeFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.grades',
        'password' => Hash::make('password'),
        'first_name' => 'Grade',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

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

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();
    $cluster = Cluster::query()->create(['name' => 'Core']);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'ENG11',
        'title' => 'English 11',
        'type' => 'core',
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => 'grade_11',
        'semester' => 'first',
    ]);

    $section = Section::query()->create([
        'name' => 'Newton',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'cluster_ID' => $cluster->cluster_ID,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'subject_ID' => $curriculumSubject->subject_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'semester' => 'first',
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('teacher', 'assignment', 'enrollment');
}

function teacherSectionGradeField(int $enrollmentId): string
{
    return "grades.{$enrollmentId}.shs_sem1_term_1.grade";
}

test('class record import fills only the open term and can then be saved as a draft', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment))->assertOk()->assertSee('Import Class Record');
    $response = $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1',
        'class_record' => \Tests\Support\EClassRecordFixture::upload(),
    ])->assertOk()->assertJsonPath('period', 'shs_sem1_term_1')
        ->assertJsonPath('grades.0.enrollment_id', $enrollment->enrollment_ID)
        ->assertJsonPath('grades.0.grade', 87)->assertJsonCount(1, 'grades')->assertJsonCount(0, 'issues');
    expect(StudentSubjectGrade::count())->toBe(0);
    $this->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [$enrollment->enrollment_ID => [$response->json('period') => ['grade' => $response->json('grades.0.grade')]]],
    ])->assertSessionHasNoErrors();
    expect(StudentSubjectGrade::sole()->numeric_grade)->toEqual(87)
        ->and(StudentSubjectGrade::sole()->status)->toBe('draft');
});

test('class record import supports the junior high open term', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $assignment->section->update(['grade_ID' => GradeLevel::idForValue('grade_7')]);
    $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'term_1', 'class_record' => \Tests\Support\EClassRecordFixture::upload(),
    ])->assertOk()->assertJsonPath('grades.0.grade', 87)->assertJsonPath('period', 'term_1');
});

test('class record import matches a workbook middle initial when the enrolled learner has no middle name', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $enrollment->student->update(['first_name' => 'Adrian Miguel', 'last_name' => 'Abad', 'middle_name' => null]);
    $assignment->section->update(['grade_ID' => GradeLevel::idForValue('grade_7')]);

    $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'term_1',
        'class_record' => \Tests\Support\EClassRecordFixture::upload(['TERM 1' => [['Abad, Adrian Miguel P.', '66']]]),
    ])->assertOk()
        ->assertJsonPath('grades.0.enrollment_id', $enrollment->enrollment_ID)
        ->assertJsonPath('grades.0.grade', 66)
        ->assertJsonCount(0, 'issues');
});

test('class record import skips an initial-only name when two enrolled learners share the same name', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $enrollment->student->update(['first_name' => 'Adrian Miguel', 'last_name' => 'Abad', 'middle_name' => null]);
    $duplicateStudent = $enrollment->student->replicate();
    $duplicateStudent->lrn = '555555555555';
    $duplicateStudent->save();
    $duplicateEnrollment = $enrollment->replicate();
    $duplicateEnrollment->student_ID = $duplicateStudent->id;
    $duplicateEnrollment->save();

    $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1',
        'class_record' => \Tests\Support\EClassRecordFixture::upload(['TERM 1' => [['Abad, Adrian Miguel P.', '66']]]),
    ])->assertOk()->assertJsonCount(0, 'grades')->assertJsonPath('issues.0', 'Row 18 — Abad, Adrian Miguel P.: name matches more than one learner.');
});

test('class record import rejects a closed or forged term', function (string $period) {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => $period, 'class_record' => \Tests\Support\EClassRecordFixture::upload(),
    ])->assertUnprocessable()->assertJsonValidationErrors('period');
    expect(StudentSubjectGrade::count())->toBe(0);
})->with(['shs_sem1_term_2', 'shs_sem2_term_1', 'term_1', 'invalid']);

test('class record import requires the assigned teacher', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $other = $teacher->replicate();
    $other->username = 'another.teacher';
    $other->save();
    $this->actingAs($other)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1', 'class_record' => \Tests\Support\EClassRecordFixture::upload(),
    ])->assertForbidden();
});

test('class record import leaves locked and missing grades unchanged', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $this->actingAs($teacher)->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [$enrollment->enrollment_ID => ['shs_sem1_term_1' => ['grade' => 80]]],
    ]);
    StudentSubjectGrade::sole()->update(['status' => 'submitted']);
    $this->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1', 'class_record' => \Tests\Support\EClassRecordFixture::upload(),
    ])->assertOk()->assertJsonCount(0, 'grades')->assertJsonPath('unchanged', 1)
        ->assertJsonPath('issues.0', 'Row 18 — Santos, Ana: grade is locked.');
    expect(StudentSubjectGrade::sole()->numeric_grade)->toEqual(80);
});

test('class record import reports invalid blank duplicate and unmatched learner records', function (array $rows, string $issue) {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $response = $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1', 'class_record' => \Tests\Support\EClassRecordFixture::upload(['TERM 1' => $rows]),
    ])->assertOk()->assertJsonCount(0, 'grades');
    expect(implode(' ', $response->json('issues')))->toContain($issue);
})->with([
    'blank cached formula' => [[['Santos, Ana', '']], 'Term Grade is blank'],
    'formula error' => [[['Santos, Ana', '#VALUE!']], 'not a valid number'],
    'out of range' => [[['Santos, Ana', '101']], 'between 0 and 100'],
    'duplicate' => [[['Santos, Ana', '87'], ['Santos, Ana', '90']], 'more than once'],
    'unmatched' => [[['Unknown, Learner', '88']], 'no matching learner'],
]);

test('class record import refuses names shared by multiple enrolled learners', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $student = $enrollment->student->replicate();
    $student->lrn = '555555555555';
    $student->save();
    $duplicate = $enrollment->replicate();
    $duplicate->student_ID = $student->id;
    $duplicate->save();
    $response = $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1', 'class_record' => \Tests\Support\EClassRecordFixture::upload(),
    ])->assertOk()->assertJsonCount(0, 'grades');
    expect($response->json('issues.0'))->toContain('more than one learner');
});

test('class record import does not fall back to another sheet when the term is missing', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $this->actingAs($teacher)->postJson(route('teacher.sections.grades.import', $assignment), [
        'period' => 'shs_sem1_term_1',
        'class_record' => \Tests\Support\EClassRecordFixture::upload(['TERM 2' => [['Santos, Ana', '99']]]),
    ])->assertUnprocessable()->assertJsonValidationErrors('class_record');
});

test('teacher subject list identifies subjects with no grade records as ungraded', function () {
    ['teacher' => $teacher] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.sections.index'));

    $response->assertOk()
        ->assertSee('ENG11 - English 11')
        ->assertSee('Grade status: Ungraded');
});

test('subject class lists no longer provide an import route', function () {
    expect(Route::has('teacher.sections.class-list.import'))->toBeFalse()
        ->and(Route::has('teacher.advisory.class-list.import'))->toBeTrue();
});

test('advisory class list uploads are queued and then imported by the worker', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $section = $assignment->section;
    $csv = "LRN,Name,Sex,Birth Date,Ethnic Group,Barangay,Municipality,Province\n987654321098,\"Cruz, Juan\",M,2010-01-01,Sample Group,Sample Barangay,Sample City,Sample Province\n";

    Queue::fake();

    $response = $this->actingAs($teacher)
        ->from(route('teacher.advisory.class-list.index', $section))
        ->post(route('teacher.advisory.class-list.import', $section), [
            'class_list' => UploadedFile::fake()->createWithContent('students.csv', $csv),
        ]);

    $response->assertRedirect(route('teacher.advisory.class-list.index', $section))
        ->assertSessionHas('status', 'Student import queued. You can keep using the system; this page will show the result when it finishes.');

    $import = AdvisoryClassListImport::query()->sole();

    expect($import->status)->toBe('queued')
        ->and(Student::query()->where('lrn', '987654321098')->exists())->toBeFalse();
    Queue::assertPushed(ProcessAdvisoryClassListImport::class, fn (ProcessAdvisoryClassListImport $job): bool => $job->importId === $import->id);

    expect(\App\Models\AuditLog::sole()->action)->toBe('Queued');
    $originalName = $teacher->name;
    $teacher->update(['first_name' => 'Renamed']);

    (new ProcessAdvisoryClassListImport($import->id))->handle(app(\App\Http\Controllers\Teacher\TeacherSectionController::class));

    $import->refresh();
    expect($import->status)->toBe('completed')
        ->and($import->total_students)->toBe(1)
        ->and($import->processed_students)->toBe(1)
        ->and($import->result['createdStudents'])->toBe(1)
        ->and($import->result['createdEnrollments'])->toBe(1)
        ->and($import->file_contents)->toBeNull()
        ->and(Student::query()->where('lrn', '987654321098')->exists())->toBeTrue();
    $audit = \App\Models\AuditLog::where('action', 'Imported')->sole();
    expect($audit->user_name)->toBe($originalName)
        ->and($audit->user_id)->toBe('staff:'.$teacher->staff_id)
        ->and($audit->role)->toBe('teacher')
        ->and($audit->status)->toBe('Success')
        ->and($audit->reference)->toContain('AdvisoryClassListImport:'.$import->id)
        ->and($audit->toJson())->not->toContain('987654321098', 'Sample Barangay');
    $student = Student::query()->where('lrn', '987654321098')->firstOrFail();
    expect($student->addresses()->exists())->toBeFalse()
        ->and($student->profile()->exists())->toBeFalse();
});

test('import progress reports saved learners including skipped enrollments', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $section = $assignment->section;
    $section->update(['capacity' => 2]);
    $controller = app(\App\Http\Controllers\Teacher\TeacherSectionController::class);
    $records = $controller->advisoryClassListRecords([
        ['LRN', 'Name'],
        ['987654321098', 'Cruz, Juan'],
        ['987654321099', 'Reyes, Maria'],
    ]);
    $snapshots = [];
    $level = \Illuminate\Support\Facades\DB::transactionLevel();
    $controller->importAdvisoryClassListRecords($records, $section, $teacher->staff_id,
        function (int $processed, array $result) use (&$snapshots, $level): void {
            // Progress runs after the learner transaction, not inside it.
            expect(\Illuminate\Support\Facades\DB::transactionLevel())->toBe($level);
            $snapshots[] = [$processed, $result['createdEnrollments'], $result['skippedStudents']];
        });

    expect($snapshots)->toBe([[1, 1, 0], [2, 1, 1]]);
});

test('import status is private and reports when a worker has not started', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $section = $assignment->section;
    $import = AdvisoryClassListImport::query()->create([
        'section_ID' => $section->section_ID,
        'requested_by' => $teacher->staff_id,
        'original_filename' => 'students.csv',
        'file_contents' => base64_encode('private file contents'),
        'status' => 'queued',
    ]);
    $this->travel(2)->minutes();
    $url = route('teacher.advisory.class-list.import-status', [$section, $import]);
    $this->actingAs($teacher)->getJson($url)->assertOk()
        ->assertJsonPath('processed_students', 0)
        ->assertJsonPath('total_students', null)
        ->assertJsonPath('waiting_for_worker', true)
        ->assertJsonMissingPath('file_contents');

    $import->update(['status' => 'processing', 'total_students' => 10, 'processed_students' => 3,
        'result' => ['createdEnrollments' => 1, 'existingEnrollments' => 1, 'skippedStudents' => 1]]);
    $this->getJson($url)->assertOk()->assertJsonPath('processed_students', 3)
        ->assertJsonPath('enrolled_students', 2)->assertJsonPath('skipped_students', 1);
    $this->get(route('teacher.advisory.class-list.index', $section))->assertOk()
        ->assertSee('3 / 10 students processed')->assertSee('import-progress-bar');

    $other = $teacher->replicate();
    $other->username = 'other.teacher';
    $other->save();
    $this->actingAs($other)->getJson($url)->assertForbidden();
    $import->update(['requested_by' => $other->staff_id]);
    $this->actingAs($teacher)->getJson($url)->assertNotFound();
});

test('completed import notification appears once per session and appears again for a new import', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $section = $assignment->section;
    $import = AdvisoryClassListImport::query()->create([
        'section_ID' => $section->section_ID,
        'requested_by' => $teacher->staff_id,
        'original_filename' => 'students.csv',
        'status' => 'processing',
    ]);
    $url = route('teacher.advisory.class-list.index', $section);

    $this->actingAs($teacher)->get($url)->assertOk()->assertDontSee('Student import complete.');
    $import->update(['status' => 'completed', 'completed_at' => now()]);
    $this->get($url)->assertOk()->assertSee('Student import complete.');
    $this->get($url)->assertOk()->assertDontSee('Student import complete.');
    $this->get($url.'?search=Ana')->assertOk()->assertDontSee('Student import complete.');

    $nextImport = $import->replicate();
    $nextImport->save();
    $this->get($url)->assertOk()->assertSee('Student import complete.');
    $this->get($url)->assertOk()->assertDontSee('Student import complete.');
});

test('worker failure releases an import and preserves its progress', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $import = AdvisoryClassListImport::query()->create([
        'section_ID' => $assignment->section_ID,
        'requested_by' => $teacher->staff_id,
        'original_filename' => 'students.csv',
        'file_contents' => 'payload',
        'status' => 'processing',
        'total_students' => 10,
        'processed_students' => 3,
    ]);
    (new ProcessAdvisoryClassListImport($import->id))->failed(new RuntimeException('Worker timed out'));
    (new ProcessAdvisoryClassListImport($import->id))->failed(new RuntimeException('Duplicate callback'));
    expect(\App\Models\AuditLog::where('action', 'Imported')->sole()->status)->toBe('Failed');
    $import->refresh();
    expect($import->status)->toBe('failed')
        ->and($import->processed_students)->toBe(3)
        ->and($import->file_contents)->toBeNull()
        ->and($import->completed_at)->not->toBeNull();
});

test('unreadable imports fail without remaining stuck in processing', function () {
    \Illuminate\Support\Facades\Exceptions::fake();
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $import = AdvisoryClassListImport::query()->create([
        'section_ID' => $assignment->section_ID,
        'requested_by' => $teacher->staff_id,
        'original_filename' => 'students.csv',
        'file_contents' => '!!!invalid base64!!!',
        'status' => 'queued',
    ]);
    (new ProcessAdvisoryClassListImport($import->id))->handle(app(\App\Http\Controllers\Teacher\TeacherSectionController::class));
    expect($import->refresh()->status)->toBe('failed')
        ->and($import->file_contents)->toBeNull();
});

test('teacher subject list displays the configured grade status from its status ID', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $studentSubject = \App\Models\StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'subject_ID' => $assignment->subject_ID,
    ]);

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_1'),
        'numeric_grade' => 90,
        'grade_status_ID' => GradeStatus::idFor(GradeStatus::SUBMITTED),
        'posted_by' => $teacher->staff_id,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.sections.index'));

    $response->assertOk()
        ->assertSee('Grade status: Submitted');
});

test('teacher subject status follows the displayed term after it changes', function (bool $seniorHigh) {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    if (! $seniorHigh) {
        $assignment->section->update(['grade_ID' => GradeLevel::idForValue('grade_7')]);
    }
    $studentSubject = \App\Models\StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'subject_ID' => $assignment->subject_ID,
    ]);
    $periodPrefix = $seniorHigh ? 'shs_sem1_' : '';
    $attributes = [
        'student_subject_ID' => $studentSubject->student_subject_ID,
        'assignment_ID' => $assignment->assignment_ID,
        'numeric_grade' => 90,
        'posted_by' => $teacher->staff_id,
    ];
    StudentSubjectGrade::query()->create($attributes + [
        'term_ID' => StudentSubjectGrade::termIdForPeriodKey($periodPrefix.'term_1'),
        'status' => GradeStatus::SUBMITTED,
    ]);
    $this->actingAs($teacher)->get(route('teacher.sections.index'))
        ->assertOk()->assertSee('Grade status: Submitted');

    if ($seniorHigh) {
        GradingTermSetting::current()->setSeniorHighPeriod('first', 2);
    } else {
        GradingTerm::query()->juniorHigh()->where('key', 'term_1')
            ->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::closedId()]);
        GradingTerm::query()->juniorHigh()->where('key', 'term_2')
            ->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::openId()]);
    }
    $this->get(route('teacher.sections.index'))->assertOk()
        ->assertSee('Term 2')->assertSee('Grade status: Ungraded')->assertDontSee('Grade status: Submitted');

    $grade = StudentSubjectGrade::query()->create($attributes + [
        'term_ID' => StudentSubjectGrade::termIdForPeriodKey($periodPrefix.'term_2'),
        'status' => GradeStatus::DRAFT,
    ]);
    foreach ([GradeStatus::DRAFT, GradeStatus::SUBMITTED, GradeStatus::APPROVED, GradeStatus::REJECTED, GradeStatus::RELEASED] as $status) {
        $grade->update(['status' => $status]);
        $this->get(route('teacher.sections.index'))->assertOk()
            ->assertSee('Grade status: '.GradeStatus::nameFor($status))
            ->assertDontSee('Grade status: In progress');
    }
})->with(['junior high' => false, 'senior high' => true]);

test('teacher subject list uses term rather than semester for junior high sections', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();
    $juniorHighGrade = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();
    $assignment->section->update(['grade_ID' => $juniorHighGrade->grade_ID]);

    $response = $this->actingAs($teacher)->get(route('teacher.sections.index'));

    $response->assertOk()
        ->assertSee('Term 1')
        ->assertDontSee('Full year');
});

test('teacher grade input only accepts numeric values from 60 to 100', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment));

    $response->assertOk();
    $response->assertSee('Newton');
    $response->assertSee('ENG11 - English 11');
    $response->assertSee('Grade Sheet');
    $response->assertSee('type="number"', false);
    $response->assertSee('min="60"', false);
    $response->assertSee('max="100"', false);
    $response->assertSee('data-grade-input', false);
});

test('second-semester subjects use the section roster after the active semester changes', function () {
    ['teacher' => $teacher, 'assignment' => $assignment] = createTeacherSectionGradeFixtures();

    $assignment->curriculumSubject->update(['semester' => 'second']);
    GradingTermSetting::current()->setSeniorHighPeriod('second', 1);

    $response = $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment));

    $response->assertOk();
    $response->assertSee('Santos, Ana');
    $response->assertSee('Second Semester');
});

test('teacher can save a numeric grade that does not exceed 100', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [
            $enrollment->enrollment_ID => [
                'shs_sem1_term_1' => ['grade' => 88],
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status', 'Grades saved successfully.');

    $grade = StudentSubjectGrade::query()
        ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
        ->where('assignment_ID', $assignment->assignment_ID)
        ->forPeriodKey('shs_sem1_term_1')
        ->first();

    expect($grade?->numeric_grade)->toEqual(88)
        ->and($grade->isSeniorHigh())->toBeTrue()
        ->and($grade->term_ID)->not->toBeNull()
        ->and($grade->semester_ID)->not->toBeNull()
        ->and($grade->status)->toBe('draft');
});

test('teacher can save a grade at either boundary', function (int $numericGrade) {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [
            $enrollment->enrollment_ID => [
                'shs_sem1_term_1' => ['grade' => $numericGrade],
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');

    expect(
        StudentSubjectGrade::query()
            ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
            ->where('assignment_ID', $assignment->assignment_ID)
            ->value('numeric_grade')
    )->toEqual($numericGrade);
})->with([60, 100]);

it('rejects invalid teacher grade values', function (mixed $grade) {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();

    $response = $this->actingAs($teacher)->from(route('teacher.sections.show', $assignment))->post(route('teacher.sections.grades.store', $assignment), [
        'grades' => [
            $enrollment->enrollment_ID => [
                'shs_sem1_term_1' => ['grade' => $grade],
            ],
        ],
    ]);

    $response->assertRedirect(route('teacher.sections.show', $assignment));
    $response->assertSessionHasErrors(teacherSectionGradeField($enrollment->enrollment_ID));

    expect(
        StudentSubjectGrade::query()
            ->whereHas('studentSubject', fn ($query) => $query->where('enrollment_ID', $enrollment->enrollment_ID))
            ->where('assignment_ID', $assignment->assignment_ID)
            ->exists()
    )->toBeFalse();
})->with([
    'letters' => 'abc',
    'over one hundred' => 101,
    'negative' => -1,
    'zero' => 0,
    'below sixty' => 59,
    'below sixty decimal' => 59.99,
    'over one hundred decimal' => 100.01,
]);

function createGradeDigestRegistrar(string $username = 'digest.registrar', string $status = 'active'): Staff
{
    return Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => 'registrar'])->id,
        'username' => $username, 'password' => Hash::make('password'),
        'first_name' => 'Digest', 'last_name' => 'Registrar', 'status' => $status,
    ]);
}

function createPendingDigestGrades(TeacherSubjectAssignment $assignment, Enrollment $enrollment, int $terms = 1): void
{
    $roster = \App\Models\StudentSubject::query()->firstOrCreate([
        'enrollment_ID' => $enrollment->enrollment_ID, 'subject_ID' => $assignment->subject_ID,
    ]);
    for ($term = 1; $term <= $terms; $term++) {
        $termId = StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_'.$term);
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => $termId, 'numeric_grade' => 90, 'status' => 'submitted', 'submitted_at' => now(),
            'posted_by' => $assignment->staff_ID,
        ]);
        \App\Support\RegistrarGradeDigest::record($assignment->assignment_ID, [$termId]);
    }
}

test('registrar digest groups subject terms and sends only one alert to each active registrar', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-30 10:00:00', 'Asia/Manila'));
    \Illuminate\Support\Facades\DB::table('registrar_grade_digest_state')->update(['last_sent_at' => now()->subHours(2)]);
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $registrar = createGradeDigestRegistrar();
    $otherRegistrar = createGradeDigestRegistrar('digest.other');
    $inactive = createGradeDigestRegistrar('digest.inactive', 'inactive');
    createPendingDigestGrades($assignment, $enrollment, 3);
    $anotherStudent = Student::query()->create(['lrn' => '987654321012', 'first_name' => 'Other', 'last_name' => 'Student', 'status' => 'active']);
    $anotherEnrollment = $enrollment->replicate();
    $anotherEnrollment->student_ID = $anotherStudent->id;
    $anotherEnrollment->save();
    createPendingDigestGrades($assignment, $anotherEnrollment, 3);

    $this->artisan('registrar:grade-digest')->assertSuccessful();
    foreach ([$registrar, $otherRegistrar] as $recipient) {
        expect($recipient->notifications()->count())->toBe(1);
        $data = $recipient->notifications()->first()->data;
        expect($data['submission_count'])->toBe(3)
            ->and($data['message'])->toBe('There are 3 new grade submissions awaiting review.');
        $this->actingAs($recipient)->get($data['url'])->assertOk();
    }
    expect($inactive->notifications()->count())->toBe(0)
        ->and($teacher->notifications()->count())->toBe(0);
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    expect($registrar->notifications()->count())->toBe(1);
    $this->actingAs($registrar)->get(route('registrar.dashboard'))->assertOk()
        ->assertSee('data-test="notification-bell"', false)->assertSee('There are 3 new grade submissions awaiting review.');
});

test('registrar digest waits two hours and retains overnight and weekend submissions', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-02 16:00:00', 'Asia/Manila'));
    \Illuminate\Support\Facades\DB::table('registrar_grade_digest_state')->update(['last_sent_at' => now()]);
    ['assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $registrar = createGradeDigestRegistrar();
    createPendingDigestGrades($assignment, $enrollment);
    $this->travel(119)->minutes();
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    $this->travel(1)->minutes();
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-03 10:00:00', 'Asia/Manila'));
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-10-05 08:00:00', 'Asia/Manila'));
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(1);
    expect($registrar->notifications()->first()->data['message'])->toBe('There is 1 new grade submission awaiting review.');
    \App\Support\RegistrarGradeDigest::record($assignment->assignment_ID, [StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_1')]);
    $this->travel(119)->minutes();
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    $this->travel(1)->minutes();
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(1);
    expect($registrar->notifications()->count())->toBe(2);
});

test('registrar digest skips reviewed grades and empty digests but preserves work when no registrar is active', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-30 10:00:00', 'Asia/Manila'));
    \Illuminate\Support\Facades\DB::table('registrar_grade_digest_state')->update(['last_sent_at' => now()->subHours(2)]);
    ['assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    createPendingDigestGrades($assignment, $enrollment);
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    $this->assertDatabaseCount('pending_grade_submissions', 1);
    $registrar = createGradeDigestRegistrar();
    StudentSubjectGrade::query()->update(['grade_status_ID' => GradeStatus::idFor('approved')]);
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    $this->assertDatabaseCount('pending_grade_submissions', 0);
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(0);
    expect($registrar->notifications()->count())->toBe(0);
});

test('teacher grade submission records one pending digest item and retries do not duplicate it', function (bool $saveAndSubmit) {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    GradingTermSetting::current()->setSeniorHighPeriod('first', 1);
    $assignment->curriculumSubject->curriculumGradeLevel->update(['semester_ID' => \App\Models\GradingSemester::idFor('first')]);
    $registrar = createGradeDigestRegistrar();
    $payload = ['grades' => [$enrollment->enrollment_ID => ['shs_sem1_term_1' => ['grade' => 90]]]];
    $this->actingAs($teacher)->post(route('teacher.sections.grades.store', $assignment), $payload)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('pending_grade_submissions', 0);
    $submitUrl = route($saveAndSubmit ? 'teacher.sections.grades.store' : 'teacher.sections.grades.submit', $assignment);
    $this->post($submitUrl, $saveAndSubmit ? $payload + ['submit' => true] : [])->assertSessionHasNoErrors();
    $this->assertDatabaseCount('pending_grade_submissions', 1);
    $this->assertDatabaseHas('pending_grade_submissions', ['assignment_ID' => $assignment->assignment_ID, 'term_ID' => StudentSubjectGrade::termIdForPeriodKey('shs_sem1_term_1')]);
    expect($registrar->notifications()->count())->toBe(0);
    $this->post(route('teacher.sections.grades.submit', $assignment));
    $this->assertDatabaseCount('pending_grade_submissions', 1);
})->with([false, true]);

test('registrar digest retries without partial delivery when notification persistence fails', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-09-30 10:00:00', 'Asia/Manila'));
    \Illuminate\Support\Facades\DB::table('registrar_grade_digest_state')->update(['last_sent_at' => now()->subHours(2)]);
    ['assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $registrar = createGradeDigestRegistrar();
    createPendingDigestGrades($assignment, $enrollment);
    \Illuminate\Support\Facades\Event::listen(\Illuminate\Notifications\Events\NotificationSent::class, function () {
        throw new RuntimeException('Simulated interrupted delivery');
    });
    try {
        expect(fn () => \App\Support\RegistrarGradeDigest::sendWhenDue())->toThrow(RuntimeException::class);
        expect($registrar->notifications()->count())->toBe(0);
        $this->assertDatabaseCount('pending_grade_submissions', 1);
    } finally {
        \Illuminate\Support\Facades\Event::forget(\Illuminate\Notifications\Events\NotificationSent::class);
    }
    expect(\App\Support\RegistrarGradeDigest::sendWhenDue())->toBe(1);
    expect($registrar->notifications()->count())->toBe(1);
    $this->assertDatabaseCount('pending_grade_submissions', 0);
});

test('subject summaries use the configured SF9 descriptors and expose class statistics', function (bool $senior, string $format, string $descriptor) {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    if (! $senior) {
        $assignment->section->update(['grade_ID' => GradeLevel::idForValue('grade_7')]);
    }
    \App\Models\Sf9Configuration::create(['junior_high' => $format, 'senior_high' => 'shs_current']);
    $assignment->curriculumSubject->curriculumGradeLevel->update(['grade_ID' => GradeLevel::idForValue($senior ? 'grade_11' : 'grade_7'), 'semester_ID' => \App\Models\GradingSemester::idFor($senior ? 'first' : \App\Models\GradingSemester::FULL_YEAR)]);
    $assignment->refresh();
    $periods = GradingTerm::periodsForSection($assignment->section->fresh(), $assignment->curriculumSubject->semester);
    $roster = \App\Models\StudentSubject::where('enrollment_ID', $enrollment->enrollment_ID)->firstOrFail();
    StudentSubjectGrade::create(['student_subject_ID' => $roster->student_subject_ID, 'assignment_ID' => $assignment->assignment_ID, 'term_ID' => StudentSubjectGrade::termIdForPeriodKey($periods[0]['key']), 'numeric_grade' => 85, 'posted_by' => $teacher->staff_id]);
    $this->actingAs($teacher)->get(route('teacher.sections.show', $assignment))->assertOk()->assertSee('Descriptor guide')->assertSee($descriptor)->assertSee('Class Statistics')->assertSee('Download PNG')->assertSee('data-summary-descriptor', false);
    $print = $this->get(route('teacher.sections.summary.print', $assignment))->assertOk()->assertSee($descriptor)->assertSee('Passed');
    foreach ($periods as $period) {
        $print->assertSee($period['label']);
    }
})->with([[false, 'jhs_legacy', 'Very Satisfactory'], [false, 'jhs_2026', 'Benchmarking'], [true, 'jhs_legacy', 'Benchmarking']]);

test('full subject summary includes saved archived terms and averages every recorded term', function () {
    ['teacher' => $teacher, 'assignment' => $assignment, 'enrollment' => $enrollment] = createTeacherSectionGradeFixtures();
    $assignment->section->update(['grade_ID' => GradeLevel::idForValue('grade_7')]);
    $periods = GradingTerm::configuredPeriods();
    $roster = \App\Models\StudentSubject::firstOrCreate(['enrollment_ID' => $enrollment->enrollment_ID, 'subject_ID' => $assignment->subject_ID]);
    foreach (array_slice($periods, 0, 2) as $index => $period) {
        StudentSubjectGrade::create([
            'student_subject_ID' => $roster->student_subject_ID,
            'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period['key']),
            'numeric_grade' => $index === 0 ? 80 : 90,
            'posted_by' => $teacher->staff_id,
        ]);
    }
    GradingTerm::where('key', $periods[0]['key'])->update(['junior_high_grading_period_status_ID' => \App\Models\GradingPeriodStatus::idFor('archived')]);
    $this->actingAs($teacher)->get(route('teacher.sections.summary.print', $assignment))
        ->assertOk()
        ->assertSee($periods[0]['label'])
        ->assertSee($periods[1]['label'])
        ->assertViewHas('summaries', fn ($summaries) => (float) $summaries[$enrollment->enrollment_ID]['average'] === 85.0);
});
