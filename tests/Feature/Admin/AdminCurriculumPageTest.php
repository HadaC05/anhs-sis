<?php

use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Support\Facades\Hash;

function createCurriculumPageAdmin(string $username): Staff
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

/**
 * @return array{admin: Staff, cluster: Cluster, curriculum: Curriculum, subject: Subject, curriculumSubject: CurriculumSubject}
 */
function createCurriculumPageFixtures(string $username): array
{
    $admin = createCurriculumPageAdmin($username);
    $cluster = Cluster::query()->create(['name' => 'STEM']);

    $curriculum = Curriculum::query()->create([
        'name' => 'DepEd SHS - STEM',
        'description' => 'Science, Technology, Engineering, and Mathematics',
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

    return compact('admin', 'cluster', 'curriculum', 'subject', 'curriculumSubject');
}

test('admin can view the restyled curriculum page', function () {
    ['admin' => $admin] = createCurriculumPageFixtures('admin.curriculum.page');

    $response = $this->actingAs($admin)->get(route('admin.curriculum-config.index'));

    $response->assertOk();
    $response->assertSee('Curriculum');
    $response->assertSee('Add Curriculum');
    $response->assertDontSee('Configure curricula and assign subjects by grade level and semester.');
    $response->assertSee('Total Curricula');
    $response->assertSee('Assigned Subjects');
    $response->assertSee('DepEd SHS - STEM');
    $response->assertSee('>Curriculum</span>', false);
    $response->assertSee('id="curriculumModal"', false);
    $response->assertSee('id="curriculumSubjectModal"', false);
    $response->assertSee('openCurriculumModal', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Archive"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertSee('M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', false);
    $response->assertDontSee('Curriculum Configuration');
    $response->assertSee('>Edit</button>', false);
    $response->assertDontSee('>Archive</button>', false);
});

test('admin can view curriculum subjects on the restyled page', function () {
    ['admin' => $admin] = createCurriculumPageFixtures('admin.curriculum.subjects');

    $response = $this->actingAs($admin)->get(route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']));

    $response->assertOk();
    $response->assertSee('Assign Subject');
    $response->assertSee('PRECAL11');
    $response->assertSee('Pre-Calculus');
    $response->assertSee('Grade 11');
    $response->assertSee('First');
    $response->assertSee('>Edit</button>', false);
    $response->assertDontSee('>Remove</button>', false);
    $response->assertSee('Curriculum Subjects');
});

test('admin can view the curriculum overview tab', function () {
    ['admin' => $admin] = createCurriculumPageFixtures('admin.curriculum.overview');

    $response = $this->actingAs($admin)->get(route('admin.curriculum-config.index', ['tab' => 'curriculum_overview']));

    $response->assertOk();
    $response->assertSee('DepEd SHS - STEM');
    $response->assertSee('Grade 11 · First Semester');
    $response->assertSee('PRECAL11');
    $response->assertSee('No subjects assigned.');
    $response->assertDontSee('Curriculum Subject Overview');
});

test('admin can create a curriculum', function () {
    $admin = createCurriculumPageAdmin('admin.curriculum.create');

    $this->actingAs($admin)
        ->from(route('admin.curriculum-config.index'))
        ->post(route('admin.curriculum-config.store'), [
            '_form' => 'curriculum',
            'name' => 'DepEd SHS - HUMSS',
            'description' => 'Humanities and Social Sciences',
        ])
        ->assertRedirect(route('admin.curriculum-config.index'))
        ->assertSessionHasNoErrors();

    $created = Curriculum::query()->where('name', 'DepEd SHS - HUMSS')->first();

    expect($created)->not->toBeNull();
    expect((bool) $created->status)->toBeTrue();
});

test('admin can archive and activate a curriculum', function () {
    ['admin' => $admin, 'curriculum' => $curriculum] = createCurriculumPageFixtures('admin.curriculum.archive');

    $this->actingAs($admin)
        ->from(route('admin.curriculum-config.index'))
        ->patch(route('admin.curriculum-config.toggle-status', $curriculum))
        ->assertRedirect(route('admin.curriculum-config.index'));

    expect((bool) $curriculum->fresh()->status)->toBeFalse();

    $this->actingAs($admin)
        ->from(route('admin.curriculum-config.index'))
        ->patch(route('admin.curriculum-config.toggle-status', $curriculum))
        ->assertRedirect(route('admin.curriculum-config.index'));

    expect((bool) $curriculum->fresh()->status)->toBeTrue();
});

test('admin can assign a junior high subject without a cluster', function () {
    ['admin' => $admin] = createCurriculumPageFixtures('admin.curriculum.jhs.subject');

    $curriculum = Curriculum::query()->create([
        'name' => 'Grade 7',
        'description' => 'Junior High School Grade 7 curriculum',
        'status' => true,
    ]);

    $subject = Subject::query()->create([
        'cluster_ID' => null,
        'code' => 'MATH7',
        'title' => 'Mathematics 7',
        'type' => 'core',
        'status' => 'active',
    ]);

    $science = Subject::query()->create([
        'cluster_ID' => null,
        'code' => 'SCI7',
        'title' => 'Science 7',
        'type' => 'core',
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->from(route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']))
        ->post(route('admin.curriculum-config.subjects.store'), [
            '_form' => 'curriculum_subject',
            'curriculum_ID' => $curriculum->curriculum_ID,
            'subject_ID' => [$subject->subject_ID, $science->subject_ID],
            'grade_level' => 'grade_7',
            'semester' => 'first',
        ])
        ->assertRedirect(route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']))
        ->assertSessionHasNoErrors();

    $assigned = CurriculumSubject::query()
        ->where('curriculum_ID', $curriculum->curriculum_ID)
        ->where('subject_ID', $subject->subject_ID)
        ->first();

    expect($assigned)->not->toBeNull()
        ->and($assigned->cluster_ID)->toBeNull()
        ->and($assigned->grade_level)->toBe('grade_7')
        ->and(CurriculumSubject::query()->where('curriculum_ID', $curriculum->curriculum_ID)->count())->toBe(2);
});

test('admin can assign multiple senior high subjects for a selected cluster and semester', function () {
    ['admin' => $admin, 'cluster' => $cluster, 'curriculum' => $curriculum] = createCurriculumPageFixtures('admin.curriculum.shs.subjects');

    $secondSubject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'GENMATH11',
        'title' => 'General Mathematics',
        'type' => 'core',
        'status' => 'active',
    ]);

    $secondSemesterId = \App\Models\GradingSemester::idFor('second');
    $grade11Id = \App\Models\GradeLevel::idForValue('grade_11');
    $firstSubjectId = Subject::query()->where('code', 'PRECAL11')->value('subject_ID');

    $this->actingAs($admin)
        ->from(route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']))
        ->post(route('admin.curriculum-config.subjects.store'), [
            '_form' => 'curriculum_subject',
            'curriculum_ID' => $curriculum->curriculum_ID,
            'subject_ID' => [$firstSubjectId, $secondSubject->subject_ID],
            'cluster_ID' => $cluster->cluster_ID,
            'grade_ID' => $grade11Id,
            'semester_ID' => $secondSemesterId,
        ])
        ->assertRedirect(route('admin.curriculum-config.index', ['tab' => 'curriculum_subjects']))
        ->assertSessionHasNoErrors();

    expect(CurriculumSubject::query()
        ->where('curriculum_ID', $curriculum->curriculum_ID)
        ->whereIn('subject_ID', [$firstSubjectId, $secondSubject->subject_ID])
        ->where('semester_ID', $secondSemesterId)
        ->count())->toBe(2);
});
