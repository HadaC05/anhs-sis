<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\TeacherSubjectAssignment;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\DefaultNonStudentUsersSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SectionSeeder;
use Database\Seeders\SubjectSeeder;
use Database\Seeders\TeacherSubjectAssignmentSeeder;
use Illuminate\Support\Facades\Hash;

test('teacher subject assignment seeder assigns subjects to each section adviser', function () {
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);

    $grade11Teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher_11',
        'email' => 'teacher_11@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Kristine',
        'last_name' => 'Ramos',
        'status' => 'active',
    ]);

    $grade12Teacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher_12',
        'email' => 'teacher_12@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Miguel',
        'last_name' => 'Torres',
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

    $cluster = Cluster::query()->create(['name' => 'General']);
    $grade11 = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();
    $grade12 = GradeLevel::query()->where('grade_label', 'Grade 12')->firstOrFail();

    $grade11Section = Section::query()->create([
        'name' => 'G11-A',
        'grade_ID' => $grade11->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $grade11Teacher->staff_id,
        'capacity' => 45,
        'status' => true,
    ]);

    $grade12Section = Section::query()->create([
        'name' => 'G12-A',
        'grade_ID' => $grade12->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $grade12Teacher->staff_id,
        'capacity' => 45,
        'status' => true,
    ]);

    $oralComm = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'ORALCOM',
        'title' => 'Oral Communication',
        'type' => 'core',
        'status' => 'active',
    ]);

    $research = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'PRACTRESEARCH2',
        'title' => 'Practical Research 2',
        'type' => 'applied',
        'status' => 'active',
    ]);

    $grade11Subject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $oralComm->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => 'grade_11',
        'semester' => 'first',
    ]);

    $grade12Subject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $research->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => 'grade_12',
        'semester' => 'first',
    ]);

    $this->seed(TeacherSubjectAssignmentSeeder::class);

    expect(TeacherSubjectAssignment::query()->where('section_ID', $grade11Section->section_ID)->where('curr_subj_ID', $grade11Subject->curr_subj_ID)->value('staff_ID'))
        ->toBe($grade11Teacher->staff_id)
        ->and(TeacherSubjectAssignment::query()->where('section_ID', $grade12Section->section_ID)->where('curr_subj_ID', $grade12Subject->curr_subj_ID)->value('staff_ID'))
        ->toBe($grade12Teacher->staff_id);
});

test('teacher subject assignment seeder shares specialists across sections and keeps one advisory each', function () {
    $teacherRole = Role::query()->create(['role_name' => 'teacher']);

    $mathTeacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher_g7a',
        'email' => 'teacher_g7a@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'status' => 'active',
    ]);

    $scienceTeacher = Staff::query()->create([
        'role_id' => $teacherRole->id,
        'username' => 'teacher_g7b',
        'email' => 'teacher_g7b@example.com',
        'password' => Hash::make('password'),
        'first_name' => 'Rafael',
        'last_name' => 'Bautista',
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
        'description' => 'Junior High School Grade 7 subject offerings.',
        'status' => true,
    ]);

    $grade7 = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();

    $sectionA = Section::query()->create([
        'name' => 'G7-A',
        'grade_ID' => $grade7->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $mathTeacher->staff_id,
        'capacity' => 45,
        'status' => true,
    ]);

    $sectionB = Section::query()->create([
        'name' => 'G7-B',
        'grade_ID' => $grade7->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $scienceTeacher->staff_id,
        'capacity' => 45,
        'status' => true,
    ]);

    $math = Subject::query()->create([
        'code' => 'MATH7',
        'title' => 'Mathematics 7',
        'type' => 'core',
        'status' => 'active',
    ]);

    $science = Subject::query()->create([
        'code' => 'SCI7',
        'title' => 'Science 7',
        'type' => 'core',
        'status' => 'active',
    ]);

    $mathSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $math->subject_ID,
        'grade_level' => 'grade_7',
        'semester' => 'first',
    ]);

    $scienceSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $science->subject_ID,
        'grade_level' => 'grade_7',
        'semester' => 'first',
    ]);

    $this->seed(TeacherSubjectAssignmentSeeder::class);

    $mathTeacherIds = TeacherSubjectAssignment::query()
        ->where('curr_subj_ID', $mathSubject->curr_subj_ID)
        ->whereIn('section_ID', [$sectionA->section_ID, $sectionB->section_ID])
        ->pluck('staff_ID')
        ->unique()
        ->values();

    $scienceTeacherIds = TeacherSubjectAssignment::query()
        ->where('curr_subj_ID', $scienceSubject->curr_subj_ID)
        ->whereIn('section_ID', [$sectionA->section_ID, $sectionB->section_ID])
        ->pluck('staff_ID')
        ->unique()
        ->values();

    expect($mathTeacherIds)->toHaveCount(1)
        ->and($scienceTeacherIds)->toHaveCount(1)
        ->and($mathTeacherIds->first())->toBe($mathTeacher->staff_id)
        ->and($scienceTeacherIds->first())->toBe($scienceTeacher->staff_id)
        ->and($sectionA->fresh()->staff_ID)->toBe($mathTeacher->staff_id)
        ->and($sectionB->fresh()->staff_ID)->toBe($scienceTeacher->staff_id);
});

test('default seeders do not create curriculum subject or teacher assignments', function () {
    $this->seed([
        RoleSeeder::class,
        DefaultNonStudentUsersSeeder::class,
        ClusterSeeder::class,
        GradeLevelSeeder::class,
        SubjectSeeder::class,
        CurriculumSeeder::class,
        AcademicYearSeeder::class,
        SectionSeeder::class,
        TeacherSubjectAssignmentSeeder::class,
    ]);

    $academicYearId = AcademicYear::query()->where('status', true)->value('SY_ID');
    $sections = Section::query()->where('SY_ID', $academicYearId)->get();

    expect($sections->pluck('staff_ID')->filter()->unique())->toHaveCount(30)
        ->and(CurriculumSubject::query()->count())->toBe(0)
        ->and(TeacherSubjectAssignment::query()->count())->toBe(0);
});
