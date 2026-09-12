<?php

use App\Models\Cluster;
use App\Models\PreferredCourse;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Support\Facades\Hash;

function createSubjectPageAdmin(string $username): Staff
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
 * @return array{admin: Staff, cluster: Cluster, subject: Subject, preferredCourse: PreferredCourse, preferredCluster: Cluster}
 */
function createSubjectPageFixtures(string $username): array
{
    $admin = createSubjectPageAdmin($username);
    $cluster = Cluster::query()->create(['name' => 'General Academic']);
    $preferredCluster = Cluster::query()->create([
        'name' => 'Science, Technology, Engineering, and Mathematics',
    ]);

    $subject = Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'ENG7',
        'title' => 'English 7',
        'type' => 'core',
        'status' => 'active',
    ]);

    $preferredCourse = PreferredCourse::query()->create([
        'cluster_ID' => $preferredCluster->cluster_ID,
        'name' => 'Engineering',
        'description' => 'Engineering-related careers',
    ]);

    return compact('admin', 'cluster', 'subject', 'preferredCourse', 'preferredCluster');
}

test('admin can view the restyled subjects page', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.page');

    $response = $this->actingAs($admin)->get(route('admin.subject-config.index'));

    $response->assertOk();
    $response->assertSee('Subjects');
    $response->assertSee('Add Subject');
    $response->assertSee('Manage the subject masterfile and preferred courses.');
    $response->assertSee('Total Subjects');
    $response->assertSee('Preferred Courses');
    $response->assertSee('ENG7');
    $response->assertSee('English 7');
    $response->assertSee('General Academic');
    $response->assertSee('>Subjects</span>', false);
    $response->assertSee('id="subjectModal"', false);
    $response->assertSee('id="preferredCourseModal"', false);
    $response->assertSee('openSubjectModal', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Archive"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertSee('M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', false);
    $response->assertDontSee('Subject Configuration');
    $response->assertDontSee('>Edit</button>', false);
    $response->assertDontSee('>Archive</button>', false);
});

test('admin can view preferred courses on the restyled subjects page', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.courses');

    $response = $this->actingAs($admin)->get(route('admin.subject-config.index', ['tab' => 'preferred_courses']));

    $response->assertOk();
    $response->assertSee('Add Preferred Course');
    $response->assertSee('Engineering');
    $response->assertSee('Engineering-related careers');
    $response->assertSee('Science, Technology, Engineering, and Mathematics');
    $response->assertSee('title="Delete"', false);
    $response->assertDontSee('>Edit</button>', false);
    $response->assertDontSee('>Delete</button>', false);
});

test('admin can filter subjects by search and type', function () {
    ['admin' => $admin, 'cluster' => $cluster] = createSubjectPageFixtures('admin.subjects.filter');

    Subject::query()->create([
        'cluster_ID' => $cluster->cluster_ID,
        'code' => 'RES01',
        'title' => 'Practical Research',
        'type' => 'applied',
        'status' => 'active',
    ]);

    $search = $this->actingAs($admin)->get(route('admin.subject-config.index', ['search' => 'ENG7']));
    $search->assertOk();
    $search->assertSee('ENG7');
    $search->assertDontSee('Practical Research');

    $type = $this->actingAs($admin)->get(route('admin.subject-config.index', ['type' => 'applied']));
    $type->assertOk();
    $type->assertSee('Practical Research');
    $type->assertDontSee('English 7');
});

test('admin can create a subject', function () {
    ['admin' => $admin, 'cluster' => $cluster] = createSubjectPageFixtures('admin.subjects.create');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->post(route('admin.subject-config.store'), [
            '_form' => 'subject',
            'cluster_ID' => $cluster->cluster_ID,
            'code' => 'MATH7',
            'title' => 'Mathematics 7',
            'type' => 'core',
        ])
        ->assertRedirect(route('admin.subject-config.index'))
        ->assertSessionHasNoErrors();

    expect(Subject::query()->where('code', 'MATH7')->first())
        ->not->toBeNull()
        ->title->toBe('Mathematics 7')
        ->type->toBe('core')
        ->cluster_ID->toBe($cluster->cluster_ID);
});

test('admin can create a junior high subject without a cluster', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.create.jhs');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->post(route('admin.subject-config.store'), [
            '_form' => 'subject',
            'code' => 'SCI8',
            'title' => 'Science 8',
            'type' => 'core',
        ])
        ->assertRedirect(route('admin.subject-config.index'))
        ->assertSessionHasNoErrors();

    expect(Subject::query()->where('code', 'SCI8')->first())
        ->not->toBeNull()
        ->title->toBe('Science 8')
        ->cluster_ID->toBeNull();
});

test('admin can archive and restore a subject', function () {
    ['admin' => $admin, 'subject' => $subject] = createSubjectPageFixtures('admin.subjects.archive');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->delete(route('admin.subject-config.delete', $subject))
        ->assertRedirect(route('admin.subject-config.index'));

    expect($subject->fresh()->status)->toBe('archived');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index', ['status' => 'archived']))
        ->delete(route('admin.subject-config.delete', $subject))
        ->assertRedirect(route('admin.subject-config.index', ['status' => 'archived']));

    expect($subject->fresh()->status)->toBe('active');
});

test('admin can create a preferred course', function () {
    ['admin' => $admin, 'preferredCluster' => $preferredCluster] = createSubjectPageFixtures('admin.subjects.course.create');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index', ['tab' => 'preferred_courses']))
        ->post(route('admin.subject-config.preferred-courses.store'), [
            '_form' => 'preferred_course',
            'cluster_ID' => $preferredCluster->cluster_ID,
            'name' => 'Architecture',
            'description' => 'Architecture-related careers',
        ])
        ->assertRedirect(route('admin.subject-config.index', ['tab' => 'preferred_courses']))
        ->assertSessionHasNoErrors();

    expect(PreferredCourse::query()->where('name', 'Architecture')->first())
        ->not->toBeNull()
        ->description->toBe('Architecture-related careers');
});
