<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PreferredCourse;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentSubjectGrade;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Facades\Hash;

function createAdvisorySf5Fixtures(bool $isSeniorHigh = true): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => $isSeniorHigh ? 'teacher.sf5.shs' : 'teacher.sf5.jhs',
        'password' => Hash::make('password'),
        'first_name' => 'Ada',
        'last_name' => 'Adviser',
        'status' => 'active',
    ]);

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => $isSeniorHigh ? 'DepEd SHS - STEM' : 'Junior High Curriculum',
        'description' => 'Test curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()
        ->where('grade_label', $isSeniorHigh ? 'Grade 11' : 'Grade 7')
        ->firstOrFail();
    $cluster = Cluster::query()->create([
        'name' => $isSeniorHigh ? 'Science, Technology, Engineering and Mathematics' : 'General',
    ]);
    $course = PreferredCourse::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'name' => 'STEM',
    ]);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => $isSeniorHigh ? 'ORALCOM' : 'ENG7',
        'title' => $isSeniorHigh ? 'Oral Communication' : 'English',
        'type' => 'core',
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => $isSeniorHigh ? 'grade_11' : 'grade_7',
        'semester' => 'first',
    ]);

    $section = Section::query()->create([
        'name' => 'Rizal',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'cluster_ID' => $isSeniorHigh ? $cluster->cluster_ID : null,
        'room' => 'Room 101',
        'capacity' => 40,
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'subject_ID' => $curriculumSubject->subject_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    if ($isSeniorHigh) {
        $specializedSubject = Subject::query()->create([
            'cluster_ID' => $cluster->cluster_ID,
            'code' => 'PRECALC',
            'title' => 'Pre-Calculus',
            'type' => 'elective',
            'status' => 'active',
        ]);

        $specializedCurriculumSubject = CurriculumSubject::query()->create([
            'curriculum_ID' => $curriculum->curriculum_ID,
            'subject_ID' => $specializedSubject->subject_ID,
            'cluster_ID' => $cluster->cluster_ID,
            'grade_level' => 'grade_11',
            'semester' => 'second',
        ]);

        TeacherSubjectAssignment::query()->create([
            'section_ID' => $section->section_ID,
            'subject_ID' => $specializedCurriculumSubject->subject_ID,
            'staff_ID' => $teacher->staff_id,
            'SY_ID' => $academicYear->SY_ID,
        ]);
    }

    $student = Student::query()->create([
        'lrn' => '123456789012',
        'first_name' => 'Ana',
        'middle_name' => 'Cruz',
        'last_name' => 'Santos',
        'sex' => 'female',
        'birthdate' => '2009-06-15',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'cluster_ID' => $isSeniorHigh ? $cluster->cluster_ID : null,
        'course_ID' => $isSeniorHigh ? $course->course_ID : null,
        'semester' => $isSeniorHigh ? 'first' : null,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('teacher', 'section', 'enrollment');
}

test('adviser can generate a draft SF5 from the uploaded template', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf5Fixtures(false);
    \App\Models\SchoolInformation::query()->create([
        'name' => 'Test & School', 'school_id' => '012345',
        'region' => 'Region VII', 'division' => 'Saved Division', 'district' => 'Saved District',
    ]);
    $this->actingAs($teacher)->get(route('teacher.advisory.promotions.index', $section))
        ->assertOk()->assertSee('Generate SF 5 (.xlsx)')->assertDontSee('name="school_name"', false);
    $response = $this->post(route('teacher.advisory.promotions.sf5', $section));
    $response->assertOk()->assertDownload('SF5-rizal-2026-2027.xlsx');
    $path = $response->baseResponse->getFile()->getPathname();
    try {
        $zip = new ZipArchive;
        expect($zip->open($path))->toBeTrue();
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($xml)->toContain('DRAFT - School Form 5', 'Santos, Ana Cruz', '123456789012', 'Test &amp; School', 'Pending complete released grades');
        expect($xml)->toContain('012345', 'Region VII', 'Saved Division', 'Saved District');
        expect($zip->getFromName('xl/media/image1.png'))->not->toBeFalse();
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('SF5 uses released grades and the template action categories', function (int $grade, string $action) {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf5Fixtures(false);
    $assignment = TeacherSubjectAssignment::query()->firstOrFail();
    StudentSubjectGrade::query()->delete();
    foreach (\App\Models\GradingTerm::configuredPeriods() as $period) {
        StudentSubjectGrade::query()->create([
            'student_subject_ID' => $enrollment->studentSubjects()->firstOrFail()->student_subject_ID,
            'assignment_ID' => $assignment->assignment_ID,
            'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period['key']),
            'numeric_grade' => $grade,
            'status' => \App\Models\GradeStatus::RELEASED,
            'posted_by' => $teacher->staff_id,
        ]);
    }
    $row = \App\Support\Sf5ReportBuilder::rows($section)->first();
    expect($row['average'])->toBe($grade)->and($row['action'])->toBe($action);
    $enrollment->update(['enrollment_status' => 'transferred_out']);
    expect(fn () => \App\Support\Sf5ReportBuilder::rows($section))->toThrow(\Illuminate\Validation\ValidationException::class);
})->with([[85, 'PROMOTED'], [70, 'CONDITIONAL']]);

test('teachers cannot export another advisers class', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf5Fixtures(false);
    $section->update(['staff_ID' => null]);
    $this->actingAs($teacher)->post(route('teacher.advisory.promotions.sf5', $section), ['school_name' => 'School'])->assertForbidden();
});

test('SF5 retains learners with three failed learning areas and omits unreleased results', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisorySf5Fixtures(false);
    $original = TeacherSubjectAssignment::query()->firstOrFail();
    foreach (['Mathematics', 'Science'] as $title) {
        $subject = Subject::query()->create(['code' => $title, 'title' => $title, 'type' => 'core', 'status' => 'active']);
        $curriculumSubject = $original->curriculumSubject->replicate();
        $curriculumSubject->subject_ID = $subject->subject_ID;
        $curriculumSubject->save();
        $assignment = $original->replicate();
        $assignment->subject_ID = $curriculumSubject->subject_ID;
        $assignment->save();
        \App\Models\StudentSubject::query()->firstOrCreate([
            'enrollment_ID' => $enrollment->enrollment_ID, 'subject_ID' => $curriculumSubject->subject_ID,
        ]);
    }
    foreach (TeacherSubjectAssignment::query()->get() as $assignment) {
        foreach (\App\Models\GradingTerm::configuredPeriods() as $period) {
            StudentSubjectGrade::query()->create([
                'student_subject_ID' => $enrollment->studentSubjects()->where('subject_ID', $assignment->subject_ID)->firstOrFail()->student_subject_ID,
                'assignment_ID' => $assignment->assignment_ID,
                'term_ID' => StudentSubjectGrade::termIdForPeriodKey($period['key']),
                'numeric_grade' => 70, 'status' => \App\Models\GradeStatus::RELEASED,
                'posted_by' => $teacher->staff_id,
            ]);
        }
    }
    $row = \App\Support\Sf5ReportBuilder::rows($section)->first();
    expect($row['action'])->toBe('RETAINED')->and($row['failed'])->toContain('English', 'Mathematics', 'Science');
    StudentSubjectGrade::query()->firstOrFail()->update(['status' => \App\Models\GradeStatus::DRAFT]);
    $row = \App\Support\Sf5ReportBuilder::rows($section)->first();
    expect($row['average'])->toBeNull()->and($row['action'])->toBe('');
});

test('SF5 paginates without dropping learners and preserves LRNs as text', function () {
    $rows = collect(range(1, 55))->map(fn ($i) => [
        'lrn' => str_pad((string) $i, 12, '0', STR_PAD_LEFT),
        'name' => '=Learner '.$i,
        'sex' => $i <= 25 ? 'male' : 'female',
        'average' => 85, 'action' => 'PROMOTED', 'failed' => '',
    ]);
    $path = \App\Support\Sf5Workbook::create($rows, []);
    try {
        $zip = new ZipArchive;
        expect($zip->open($path))->toBeTrue();
        foreach ([1, 2] as $page) {
            $xml = simplexml_load_string($zip->getFromName("xl/worksheets/sheet{$page}.xml"));
            $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            expect((string) $xml->xpath('//s:c[@r="O15"]/s:v')[0])->toBe('55');
        }
        $first = $zip->getFromName('xl/worksheets/sheet1.xml');
        $second = $zip->getFromName('xl/worksheets/sheet2.xml');
        expect($first)->toContain('000000000001', '=Learner 1')->not->toContain('<f>');
        expect($second)->toContain('000000000025', '000000000055', 'Page 2 of 2');
        expect($zip->getFromName('xl/workbook.xml'))->toContain('SF5 Page 2', '_xlnm.Print_Area');
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('adviser SF5 respects learner and status filters', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisorySf5Fixtures(false);
    $filters = ['search' => '123456789012', 'eligibility' => 'pending'];
    $this->actingAs($teacher)->get(route('teacher.advisory.promotions.index', [$section, ...$filters]))
        ->assertOk()->assertSee('Santos, Ana');
    $response = $this->post(route('teacher.advisory.promotions.sf5', $section), $filters)->assertOk()->assertDownload();
    unlink($response->baseResponse->getFile()->getPathname());
    $this->post(route('teacher.advisory.promotions.sf5', $section), ['eligibility' => 'eligible'])->assertSessionHasErrors('sf5');
    $this->post(route('teacher.advisory.promotions.sf5', $section), ['search' => 'NoMatch'])->assertSessionHasErrors('sf5');
    $this->post(route('principal.promotions.sf5'))->assertForbidden();
    $this->post(route('guidance.promotions.sf5'))->assertForbidden();
});
