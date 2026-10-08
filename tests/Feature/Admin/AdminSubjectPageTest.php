<?php

use App\Models\Cluster;
use App\Models\PreferredCourse;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Track;
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
 * @return array{admin: Staff, track: Track, cluster: Cluster, subject: Subject, preferredCourse: PreferredCourse, preferredCluster: Cluster}
 */
function createSubjectPageFixtures(string $username): array
{
    $admin = createSubjectPageAdmin($username);
    $track = Track::query()->firstOrCreate(['name' => 'Academic Track']);
    $cluster = Cluster::query()->create(['track_ID' => $track->track_ID, 'name' => 'General Academic']);
    $preferredCluster = Cluster::query()->create([
        'track_ID' => $track->track_ID,
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

    return compact('admin', 'track', 'cluster', 'subject', 'preferredCourse', 'preferredCluster');
}

test('admin can view the restyled subjects page', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.page');

    $response = $this->actingAs($admin)->get(route('admin.subject-config.index'));

    $response->assertOk();
    $response->assertSee('Tracks, Clusters &amp; Subjects', false);
    $response->assertSee('Add Subject');
    $response->assertSee('View the Senior High structure and manage the subject masterfile.');
    $response->assertSee('Active subjects');
    $response->assertSeeInOrder(['tracksTabBtn', 'clustersTabBtn', 'subjectsTabBtn'], false);
    $response->assertSee('ENG7');
    $response->assertSee('English 7');
    $response->assertSee('General Academic');
    $response->assertSee('Tracks &amp; Subjects', false);
    $response->assertSee('id="subjectModal"', false);
    $response->assertSeeInOrder(['School Level', 'Subject Type', 'Code', 'Name']);
    $response->assertSee('id="subject_cluster_field"', false);
    $response->assertSee('syncSubjectFields', false);
    $response->assertDontSee('Preferred Courses');
    $response->assertSee('openSubjectModal', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Archive"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertSee('M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', false);
    $response->assertDontSee('Subject Configuration');
    $response->assertDontSee('>Edit</button>', false);
    $response->assertDontSee('>Archive</button>', false);
});

test('admin can view tracks and clusters before subjects', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.structure');

    $tracks = $this->actingAs($admin)->get(route('admin.subject-config.index'));
    $clusters = $this->actingAs($admin)->get(route('admin.subject-config.index', ['tab' => 'clusters']));

    $tracks->assertOk();
    $tracks->assertSee('Academic Track');
    $tracks->assertSee('id="tracksTabPanel" class=""', false);

    $clusters->assertOk();
    $clusters->assertSee('General Academic');
    $clusters->assertSee('Science, Technology, Engineering, and Mathematics');
    $clusters->assertSee('id="clustersTabPanel" class=""', false);
    $clusters->assertDontSee('Preferred Courses');
});

test('admin can search tracks and filter clusters by track', function () {
    ['admin' => $admin, 'track' => $academicTrack] = createSubjectPageFixtures('admin.subjects.structure.filters');
    $technicalTrack = Track::query()->firstOrCreate(['name' => 'Technical Professional Track']);
    Cluster::query()->create(['track_ID' => $technicalTrack->track_ID, 'name' => 'ICT Technologies']);

    $tracks = $this->actingAs($admin)->get(route('admin.subject-config.index', [
        'tab' => 'tracks',
        'track_search' => 'Technical',
    ]));
    $tracks->assertOk()->assertSee('Technical Professional Track');
    $tracks->assertViewHas('tracks', fn ($items) => $items->pluck('name')->all() === ['Technical Professional Track']);

    $clusters = $this->actingAs($admin)->get(route('admin.subject-config.index', [
        'tab' => 'clusters',
        'cluster_search' => 'ICT',
        'cluster_track_ID' => $technicalTrack->track_ID,
    ]));
    $clusters->assertOk()->assertSee('ICT Technologies');
    $clusters->assertViewHas('clusters', fn ($items) => $items->pluck('name')->all() === ['ICT Technologies']);
    $clusters->assertSee('option value="'.$technicalTrack->track_ID.'" selected', false);
    expect($academicTrack->track_ID)->not->toBe($technicalTrack->track_ID);
});

test('admin can create and edit tracks and clusters', function () {
    ['admin' => $admin, 'track' => $academicTrack] = createSubjectPageFixtures('admin.subjects.structure.manage');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index', ['tab' => 'tracks']))
        ->post(route('admin.subject-config.tracks.store'), [
            '_form' => 'track',
            'name' => 'Arts and Design Track',
        ])
        ->assertRedirect(route('admin.subject-config.index', ['tab' => 'tracks']))
        ->assertSessionHasNoErrors();

    $track = Track::query()->where('name', 'Arts and Design Track')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index', ['tab' => 'tracks']))
        ->put(route('admin.subject-config.tracks.update', $track), [
            '_form' => 'track',
            'name' => 'Creative Industries Track',
        ])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index', ['tab' => 'clusters']))
        ->post(route('admin.subject-config.clusters.store'), [
            '_form' => 'cluster',
            'track_ID' => $academicTrack->track_ID,
            'name' => 'New Academic Cluster',
        ])
        ->assertRedirect(route('admin.subject-config.index', ['tab' => 'clusters']))
        ->assertSessionHasNoErrors();

    $cluster = Cluster::query()->where('name', 'New Academic Cluster')->firstOrFail();
    $this->actingAs($admin)
        ->from(route('admin.subject-config.index', ['tab' => 'clusters']))
        ->put(route('admin.subject-config.clusters.update', $cluster), [
            '_form' => 'cluster',
            'track_ID' => $track->track_ID,
            'name' => 'Creative Academic Cluster',
        ])
        ->assertSessionHasNoErrors();

    expect($track->fresh()->name)->toBe('Creative Industries Track')
        ->and($cluster->fresh()->name)->toBe('Creative Academic Cluster')
        ->and($cluster->fresh()->track_ID)->toBe($track->track_ID);
});

test('admin can filter subjects by search and type', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.filter');
    $electiveCluster = Cluster::query()->create(['name' => 'Elective Cluster']);

    Subject::query()->create([
        'school_level' => 'Senior High School',
        'cluster_ID' => $electiveCluster->cluster_ID,
        'code' => 'RES01',
        'title' => 'Practical Research',
        'type' => 'elective',
        'status' => 'active',
    ]);

    $search = $this->actingAs($admin)->get(route('admin.subject-config.index', ['search' => 'ENG7']));
    $search->assertOk();
    $search->assertSee('ENG7');
    $search->assertDontSee('Practical Research');

    $type = $this->actingAs($admin)->get(route('admin.subject-config.index', ['type' => 'elective']));
    $type->assertOk();
    $type->assertSee('Practical Research');
    $type->assertDontSee('English 7');

    $cluster = $this->actingAs($admin)->get(route('admin.subject-config.index', [
        'cluster_ID' => $electiveCluster->cluster_ID,
    ]));
    $cluster->assertOk();
    $cluster->assertSee('Practical Research');
    $cluster->assertDontSee('English 7');
    $cluster->assertSee('option value="'.$electiveCluster->cluster_ID.'" selected', false);
});

test('admin can create a senior high elective subject with a cluster', function () {
    ['admin' => $admin, 'cluster' => $cluster] = createSubjectPageFixtures('admin.subjects.create');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->post(route('admin.subject-config.store'), [
            '_form' => 'subject',
            'school_level' => 'Senior High School',
            'cluster_ID' => $cluster->cluster_ID,
            'code' => 'MATH7',
            'title' => 'Mathematics 7',
            'type' => 'elective',
        ])
        ->assertRedirect(route('admin.subject-config.index'))
        ->assertSessionHasNoErrors();

    expect(Subject::query()->where('code', 'MATH7')->first())
        ->not->toBeNull()
        ->title->toBe('Mathematics 7')
        ->school_level->toBe('Senior High School')
        ->type->toBe('elective')
        ->cluster_ID->toBe($cluster->cluster_ID);
});

test('subject creation success is rendered as a toast', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.create.toast');

    $response = $this->actingAs($admin)
        ->withSession(['success' => 'Subject created successfully.'])
        ->get(route('admin.subject-config.index'));

    $response->assertOk();
    $response->assertSee('data-test="subject-config-status"', false);
    $response->assertSee('Subject created successfully.');
    $response->assertDontSee('mb-6 rounded-lg border border-emerald-200 bg-emerald-50', false);
});

test('admin can create a junior high subject without a cluster', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.create.jhs');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->post(route('admin.subject-config.store'), [
            '_form' => 'subject',
            'school_level' => 'Junior High School',
            'code' => 'SCI8',
            'title' => 'Science 8',
            'type' => 'elective',
            'cluster_ID' => 999999,
        ])
        ->assertRedirect(route('admin.subject-config.index'))
        ->assertSessionHasNoErrors();

    expect(Subject::query()->where('code', 'SCI8')->first())
        ->not->toBeNull()
        ->title->toBe('Science 8')
        ->type->toBe('general')
        ->cluster_ID->toBeNull();
});

test('admin must choose a cluster for a senior high elective', function () {
    ['admin' => $admin] = createSubjectPageFixtures('admin.subjects.elective.cluster');

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->post(route('admin.subject-config.store'), [
            '_form' => 'subject',
            'school_level' => 'Senior High School',
            'code' => 'ELECTIVE1',
            'title' => 'Senior High Elective',
            'type' => 'elective',
        ])
        ->assertRedirect(route('admin.subject-config.index'))
        ->assertSessionHasErrors('cluster_ID');
});

test('admin editing a subject applies school level type and cluster rules', function () {
    ['admin' => $admin, 'cluster' => $cluster, 'subject' => $subject] = createSubjectPageFixtures('admin.subjects.edit');

    $subject->update([
        'school_level' => 'Senior High School',
        'type' => 'elective',
        'cluster_ID' => $cluster->cluster_ID,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.subject-config.index'))
        ->put(route('admin.subject-config.update', $subject), [
            '_form' => 'subject',
            'school_level' => 'Junior High School',
            'code' => 'ENG7',
            'title' => 'English 7',
            'type' => 'elective',
            'cluster_ID' => $cluster->cluster_ID,
        ])
        ->assertRedirect(route('admin.subject-config.index'))
        ->assertSessionHasNoErrors();

    expect($subject->fresh())
        ->school_level->toBe('Junior High School')
        ->type->toBe('general')
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
