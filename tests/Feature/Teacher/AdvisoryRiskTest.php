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

function createAdvisoryRiskFixtures(bool $isSeniorHigh = true): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => $isSeniorHigh ? 'teacher.sf9.shs' : 'teacher.sf9.jhs',
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
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    if ($isSeniorHigh) {
        $specializedSubject = Subject::query()->create([
            'cluster_ID' => $cluster->cluster_ID,
            'code' => 'PRECALC',
            'title' => 'Pre-Calculus',
            'type' => 'specialized',
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
            'curr_subj_ID' => $specializedCurriculumSubject->curr_subj_ID,
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

    StudentSubjectGrade::query()->create([
        'student_subject_ID' => $enrollment->studentSubjects()->where('curr_subj_ID', $curriculumSubject->curr_subj_ID)->firstOrFail()->getKey(),
        'assignment_ID' => $assignment->assignment_ID,
        'term_ID' => StudentSubjectGrade::termIdForPeriodKey($isSeniorHigh ? 'shs_sem1_term_1' : 'term_1'),
        'numeric_grade' => 91,
        'posted_by' => $teacher->staff_id,
    ]);

    return compact('teacher', 'section', 'enrollment');
}

test('advisers see below passing recorded grades for the selected period', function (bool $seniorHigh, string $term) {
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryRiskFixtures($seniorHigh);
    $grade = StudentSubjectGrade::query()->firstOrFail();
    $grade->update(['numeric_grade' => 74]);
    $url = route('teacher.advisory.at-risk', [$section, 'term' => $term]);
    $this->actingAs($teacher)->get($url)->assertOk()->assertSee('Notify student')->assertSee('Back to Advisory')
        ->assertViewHas('rows', fn ($rows) => $rows->count() === 1);
    $grade->update(['numeric_grade' => 75]);
    $this->get($url)->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
    $grade->update(['numeric_grade' => null]);
    $this->get($url)->assertOk()->assertViewHas('rows', fn ($rows) => $rows->isEmpty());
})->with([[false, 'term_1'], [true, 'shs_sem1_term_1']]);

test('advisers can notify an at risk student with a duplicate reminder cooldown', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryRiskFixtures(false);
    StudentSubjectGrade::query()->firstOrFail()->update(['numeric_grade' => 74]);
    $url = route('teacher.advisory.at-risk.notify', [$section, $enrollment]);
    $this->actingAs($teacher)->post($url, ['term' => 'term_1'])->assertRedirect()->assertSessionHasNoErrors();
    $notification = $enrollment->student->notifications()->firstOrFail();
    expect($notification->data['enrollment_ID'])->toBe($enrollment->enrollment_ID)
        ->and($notification->data['period_key'])->toBe('term_1')
        ->and($notification->notification_type_ID)->toBe(\App\Models\NotificationType::idFor(\App\Models\NotificationType::ACADEMIC_SUPPORT));
    $this->post($url, ['term' => 'term_1'])->assertRedirect();
    expect($enrollment->student->notifications()->count())->toBe(1);
    $this->travel(25)->hours();
    $this->post($url, ['term' => 'term_1'])->assertRedirect();
    expect($enrollment->student->notifications()->count())->toBe(2);
});

test('advisory risk access is restricted and notification eligibility is rechecked', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryRiskFixtures(false);
    $url = route('teacher.advisory.at-risk.notify', [$section, $enrollment]);
    $this->actingAs($teacher)->post($url, ['term' => 'term_1'])->assertSessionHasErrors('student');
    StudentSubjectGrade::query()->firstOrFail()->update(['numeric_grade' => 74]);
    $enrollment->update(['enrollment_status' => 'withdrawn']);
    $this->post($url, ['term' => 'term_1'])->assertSessionHasErrors('student');
    $this->post($url, ['term' => 'invalid'])->assertSessionHasErrors('term');
    $section->update(['staff_ID' => null]);
    $this->get(route('teacher.advisory.at-risk', $section))->assertForbidden();
    $this->post($url, ['term' => 'term_1'])->assertForbidden();
    expect($enrollment->student->notifications()->count())->toBe(0);
});

test('risk notifications cannot target an enrollment from another section', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryRiskFixtures(false);
    StudentSubjectGrade::query()->firstOrFail()->update(['numeric_grade' => 70]);
    $otherSection = $section->replicate();
    $otherSection->name = 'Other advisory';
    $otherSection->save();
    $this->actingAs($teacher)->post(route('teacher.advisory.at-risk.notify', [$otherSection, $enrollment]), ['term' => 'term_1'])->assertNotFound();
    expect($enrollment->student->notifications()->count())->toBe(0);
});

test('promotion filters and advisory navigation render', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryRiskFixtures(false);
    $this->actingAs($teacher)->get(route('teacher.advisory.promotions.index', $section))
        ->assertOk()->assertSee('Apply filters')->assertSee('promotion-search')->assertSee('promotion-status')
        ->assertSee('At-risk Students')->assertSee('Back to Advisory');
});

test('risk reminders send email when available and show a success toast', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryRiskFixtures(false);
    StudentSubjectGrade::query()->firstOrFail()->update(['numeric_grade' => 70]);
    $enrollment->student->update(['email' => 'student@example.com']);
    $transport = \Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport();
    $page = route('teacher.advisory.at-risk', [$section, 'term' => 'term_1']);
    $url = route('teacher.advisory.at-risk.notify', [$section, $enrollment]);
    $this->actingAs($teacher)->from($page)->post($url, ['term' => 'term_1'])
        ->assertSessionHas('status', 'In-app notification and email sent to the student.');
    expect($transport->messages())->toHaveCount(1);
    $email = $transport->messages()->first()->getOriginalMessage();
    expect($email->getTo()[0]->getAddress())->toBe('student@example.com')
        ->and($email->getSubject())->toBe('Academic support reminder');
    $this->get($page)->assertOk()->assertSee('data-test="advisory-risk-status"', false);
    $this->post($url, ['term' => 'term_1'])->assertSessionHas('warning');
    expect($transport->messages())->toHaveCount(1)
        ->and($enrollment->student->notifications()->count())->toBe(1);
});

test('risk reminders skip email with an explicit toast when no address is set', function (?string $email) {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryRiskFixtures(false);
    StudentSubjectGrade::query()->firstOrFail()->update(['numeric_grade' => 70]);
    $enrollment->student->update(['email' => $email]);
    $transport = \Illuminate\Support\Facades\Mail::mailer()->getSymfonyTransport();
    $this->actingAs($teacher)->post(route('teacher.advisory.at-risk.notify', [$section, $enrollment]), ['term' => 'term_1'])
        ->assertSessionHas('status', 'In-app notification sent. No email was sent because the student has no email address.');
    expect($transport->messages())->toHaveCount(0)
        ->and($enrollment->student->notifications()->count())->toBe(1);
})->with([null, '', '   ']);

test('email failure preserves the in app reminder and reports partial success', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryRiskFixtures(false);
    StudentSubjectGrade::query()->firstOrFail()->update(['numeric_grade' => 70]);
    $enrollment->student->update(['email' => 'student@example.com']);
    $channel = Mockery::mock(\Illuminate\Notifications\Channels\MailChannel::class);
    $channel->shouldReceive('send')->once()->andThrow(new RuntimeException('Email service unavailable'));
    $this->app->instance(\Illuminate\Notifications\Channels\MailChannel::class, $channel);
    $this->actingAs($teacher)->post(route('teacher.advisory.at-risk.notify', [$section, $enrollment]), ['term' => 'term_1'])
        ->assertSessionHas('warning', 'In-app notification sent, but the email could not be sent. Please check the email service.');
    expect($enrollment->student->notifications()->count())->toBe(1);
});
