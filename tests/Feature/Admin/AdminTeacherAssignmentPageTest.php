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
use Illuminate\Support\Facades\Hash;

function createTeacherAssignmentPageAdmin(string $username): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'admin']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'email' => $username.'@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => 'System',
        'last_name' => 'Admin',
        'status' => 'active',
    ]);
}

function createTeacherAssignmentPageTeacher(string $username, string $firstName, string $lastName): Staff
{
    $role = Role::query()->firstOrCreate(['role_name' => 'teacher']);

    return Staff::query()->create([
        'role_id' => $role->id,
        'username' => $username,
        'email' => $username.'@anhs.local',
        'password' => Hash::make('password'),
        'first_name' => $firstName,
        'last_name' => $lastName,
        'status' => 'active',
    ]);
}

/**
 * @return array{
 *     admin: Staff,
 *     teacher: Staff,
 *     section: Section,
 *     assignment: TeacherSubjectAssignment,
 *     curriculumSubject: CurriculumSubject,
 *     unassignedSection: Section
 * }
 */
function createTeacherAssignmentPageFixtures(string $username): array
{
    $admin = createTeacherAssignmentPageAdmin($username);
    $teacher = createTeacherAssignmentPageTeacher($username.'.teacher', 'Ana', 'Reyes');

    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'DepEd SHS - STEM',
        'description' => 'STEM curriculum',
        'status' => true,
    ]);

    $cluster = Cluster::query()->create(['name' => 'STEM']);
    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();

    $section = Section::query()->create([
        'name' => 'Newton',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'room' => 'Room 301',
        'capacity' => 40,
        'status' => true,
    ]);

    $unassignedSection = Section::query()->create([
        'name' => 'Kepler',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => null,
        'room' => 'Room 302',
        'capacity' => 40,
        'status' => true,
    ]);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'PRECAL11',
        'title' => 'Pre-Calculus',
        'type' => 'specialized',
        'status' => 'active',
    ]);

    $curriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $curriculum->curriculum_ID,
        'subject_ID' => $subject->subject_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_level' => 'grade_11',
        'semester' => 'first',
    ]);

    $assignment = TeacherSubjectAssignment::query()->create([
        'section_ID' => $section->section_ID,
        'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $academicYear->SY_ID,
    ]);

    return compact('admin', 'teacher', 'section', 'assignment', 'curriculumSubject', 'unassignedSection');
}

test('admin can view the restyled teacher assignments page', function () {
    ['admin' => $admin] = createTeacherAssignmentPageFixtures('admin.assignments.page');

    $response = $this->actingAs($admin)->get(route('admin.teacher-assignments.index'));

    $response->assertOk();
    $response->assertSee('Teacher Assignments');
    $response->assertSee('Assign teachers to advisory sections and subjects.');
    $response->assertSee('Assign Adviser');
    $response->assertSee('Advisory Sections');
    $response->assertSee('Unassigned');
    $response->assertSee('Subject Assignments');
    $response->assertSee('Newton');
    $response->assertSee('Reyes, Ana');
    $response->assertSee('2026-2027');
    $response->assertSee('>Teacher Assignments</span>', false);
    $response->assertSee('id="advisoryModal"', false);
    $response->assertSee('id="assignmentModal"', false);
    $response->assertSee('id="bulkModal"', false);
    $response->assertSee('id="reassignModal"', false);
    $response->assertSee('title="Remove"', false);
    $response->assertSee('M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16', false);
    $response->assertDontSee('id="modal-backdrop"', false);
    $response->assertDontSee('id="open-single"', false);
    $response->assertDontSee('Subject Teacher Assignments');
    $response->assertDontSee('>Remove</button>', false);
});

test('admin can view the restyled subject assignments tab', function () {
    ['admin' => $admin] = createTeacherAssignmentPageFixtures('admin.assignments.subjects');

    $response = $this->actingAs($admin)->get(route('admin.teacher-assignments.index', ['tab' => 'subjects']));

    $response->assertOk();
    $response->assertSee('New Assignment');
    $response->assertSee('Bulk Assign');
    $response->assertSee('PRECAL11');
    $response->assertSee('Pre-Calculus');
    $response->assertSee('Newton');
    $response->assertSee('First');
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Delete"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertDontSee('>Update</button>', false);
    $response->assertDontSee('>Delete</button>', false);
    $response->assertDontSee('id="open-bulk"', false);
});

test('admin can search subject assignments', function () {
    ['admin' => $admin, 'teacher' => $teacher, 'unassignedSection' => $unassignedSection] = createTeacherAssignmentPageFixtures('admin.assignments.search');

    $english = Subject::query()->create([
        'cluster_ID' => $unassignedSection->cluster_ID,
        'code' => 'ENG11',
        'title' => 'Oral Communication',
        'type' => 'core',
        'status' => 'active',
    ]);

    $englishCurriculumSubject = CurriculumSubject::query()->create([
        'curriculum_ID' => $unassignedSection->curriculum_ID,
        'subject_ID' => $english->subject_ID,
        'cluster_ID' => $unassignedSection->cluster_ID,
        'grade_level' => 'grade_11',
        'semester' => 'first',
    ]);

    TeacherSubjectAssignment::query()->create([
        'section_ID' => $unassignedSection->section_ID,
        'curr_subj_ID' => $englishCurriculumSubject->curr_subj_ID,
        'staff_ID' => $teacher->staff_id,
        'SY_ID' => $unassignedSection->SY_ID,
    ]);

    $search = $this->actingAs($admin)->get(route('admin.teacher-assignments.index', [
        'tab' => 'subjects',
        'search' => 'PRECAL11',
    ]));

    $search->assertOk();
    $search->assertSee('PRECAL11');
    $search->assertSee('Pre-Calculus');
    $search->assertSee('value="PRECAL11"', false);
});

test('admin can assign an adviser from the restyled page', function () {
    ['admin' => $admin, 'teacher' => $teacher, 'unassignedSection' => $unassignedSection] = createTeacherAssignmentPageFixtures('admin.assignments.advisory');

    $this->actingAs($admin)
        ->from(route('admin.teacher-assignments.index'))
        ->post(route('admin.teacher-assignments.advisory.assign'), [
            '_form' => 'advisory',
            'section_ID' => $unassignedSection->section_ID,
            'staff_ID' => $teacher->staff_id,
        ])
        ->assertRedirect(route('admin.teacher-assignments.index'))
        ->assertSessionHasErrors('staff_ID');

    $secondTeacher = createTeacherAssignmentPageTeacher('admin.assignments.advisory.two', 'Cara', 'Lim');

    $this->actingAs($admin)
        ->from(route('admin.teacher-assignments.index'))
        ->post(route('admin.teacher-assignments.advisory.assign'), [
            '_form' => 'advisory',
            'section_ID' => $unassignedSection->section_ID,
            'staff_ID' => $secondTeacher->staff_id,
        ])
        ->assertRedirect(route('admin.teacher-assignments.index'))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    expect($unassignedSection->fresh()->staff_ID)->toBe($secondTeacher->staff_id);
});

test('admin can create a subject assignment from the restyled page', function () {
    ['admin' => $admin, 'teacher' => $teacher, 'unassignedSection' => $unassignedSection, 'curriculumSubject' => $curriculumSubject] = createTeacherAssignmentPageFixtures('admin.assignments.create');

    $this->actingAs($admin)
        ->from(route('admin.teacher-assignments.index', ['tab' => 'subjects']))
        ->post(route('admin.teacher-assignments.store'), [
            '_form' => 'assignment',
            'section_ID' => $unassignedSection->section_ID,
            'curr_subj_ID' => $curriculumSubject->curr_subj_ID,
            'staff_ID' => $teacher->staff_id,
        ])
        ->assertRedirect(route('admin.teacher-assignments.index', ['tab' => 'subjects']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status');

    expect(TeacherSubjectAssignment::query()->where('section_ID', $unassignedSection->section_ID)->exists())->toBeTrue();
});
