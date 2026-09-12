<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

function createSectionPageAdmin(string $username): Staff
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
 * @param  array<string, mixed>  $sectionOverrides
 * @return array{admin: Staff, section: Section, academicYear: AcademicYear, curriculum: Curriculum, cluster: Cluster, gradeLevel: GradeLevel}
 */
function createSectionPageFixtures(string $username, array $sectionOverrides = []): array
{
    $admin = createSectionPageAdmin($username);
    $adviser = Staff::query()->create([
        'role_id' => $admin->role_id,
        'username' => $username.'.adviser',
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
        'name' => 'DepEd SHS - GAS',
        'description' => 'General Academic Strand curriculum',
        'status' => true,
    ]);

    $cluster = Cluster::query()->create(['name' => 'General']);
    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();

    $section = Section::query()->create(array_merge([
        'name' => 'Einstein',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $adviser->staff_id,
        'room' => 'Room 201',
        'capacity' => 40,
        'status' => true,
    ], $sectionOverrides));

    return compact('admin', 'section', 'academicYear', 'curriculum', 'cluster', 'gradeLevel');
}

test('admin can view the restyled sections page', function () {
    ['admin' => $admin] = createSectionPageFixtures('admin.sections.page');

    $response = $this->actingAs($admin)->get(route('admin.section-config.index'));

    $response->assertOk();
    $response->assertSee('Sections');
    $response->assertSee('Add Section');
    $response->assertSee('Einstein');
    $response->assertSee('Grade 7');
    $response->assertSee('Adviser, Ada');
    $response->assertSee('2026-2027');
    $response->assertSee('Room 201');
    $response->assertSee('Active');
    $response->assertSee('>Sections</span>', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('title="Archive"', false);
    $response->assertSee('maxlength="3"', false);
    $response->assertSee('M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z', false);
    $response->assertSee('M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', false);
    $response->assertSee('id="sectionModal"', false);
    $response->assertSee('openSectionModal', false);
    $response->assertDontSee('Section Configuration');
    $response->assertDontSee('>Curriculum</th>', false);
    $response->assertDontSee('title="Delete"', false);
    $response->assertDontSee('Delete this section?');
    $response->assertDontSee('>Edit</button>', false);
    $response->assertDontSee('>Delete</button>', false);
});

test('inactive sections are hidden until the inactive or all filter is applied', function () {
    ['admin' => $admin, 'academicYear' => $academicYear, 'curriculum' => $curriculum, 'cluster' => $cluster, 'gradeLevel' => $gradeLevel] = createSectionPageFixtures('admin.sections.hidden');

    $inactive = Section::query()->create([
        'name' => 'Newton',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 202',
        'capacity' => 35,
        'status' => false,
    ]);

    $defaultList = $this->actingAs($admin)->get(route('admin.section-config.index'));
    $defaultList->assertOk();
    $defaultList->assertSee('Einstein');
    $defaultList->assertDontSee('Newton');
    $defaultList->assertDontSee('bg-amber-50 text-amber-700', false);

    $inactiveList = $this->actingAs($admin)->get(route('admin.section-config.index', ['status' => 'inactive']));
    $inactiveList->assertOk();
    $inactiveList->assertSee('Newton');
    $inactiveList->assertSee('bg-amber-50 text-amber-700', false);
    $inactiveList->assertDontSee('Einstein');

    $allList = $this->actingAs($admin)->get(route('admin.section-config.index', ['status' => 'all']));
    $allList->assertOk();
    $allList->assertSeeInOrder(['Einstein', 'Newton']);
    $allList->assertSee('Active');
    $allList->assertSee('Inactive');

    expect($inactive->fresh()->exists)->toBeTrue();
});

test('admin can archive and activate a section without deleting it', function () {
    ['admin' => $admin, 'section' => $section] = createSectionPageFixtures('admin.sections.archive');

    $this->actingAs($admin)
        ->from(route('admin.section-config.index'))
        ->patch(route('admin.section-config.toggle-status', $section))
        ->assertRedirect(route('admin.section-config.index'));

    expect($section->fresh())
        ->not->toBeNull()
        ->and($section->fresh()->status)->toBeFalse();

    $this->actingAs($admin)
        ->get(route('admin.section-config.index'))
        ->assertDontSee('Einstein');

    $this->actingAs($admin)
        ->from(route('admin.section-config.index', ['status' => 'inactive']))
        ->patch(route('admin.section-config.toggle-status', $section))
        ->assertRedirect(route('admin.section-config.index', ['status' => 'inactive']));

    expect($section->fresh()->status)->toBeTrue();
});

test('section capacity must be a number from 1 to 100', function (mixed $capacity, bool $passes) {
    [
        'admin' => $admin,
        'academicYear' => $academicYear,
        'curriculum' => $curriculum,
        'gradeLevel' => $gradeLevel,
    ] = createSectionPageFixtures('admin.sections.capacity.'.md5((string) json_encode($capacity)));

    $payload = [
        'name' => 'Faraday',
        'grade_level' => 'grade_7',
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'capacity' => $capacity,
    ];

    $response = $this->actingAs($admin)
        ->from(route('admin.section-config.index'))
        ->post(route('admin.section-config.store'), $payload);

    if ($passes) {
        $response->assertRedirect(route('admin.section-config.index'));
        $response->assertSessionHasNoErrors();
        expect(Section::query()->where('name', 'Faraday')->value('capacity'))->toBe((int) $capacity);
    } else {
        $response->assertRedirect(route('admin.section-config.index'));
        $response->assertSessionHasErrors('capacity');
        expect(Section::query()->where('name', 'Faraday')->exists())->toBeFalse();
    }
})->with([
    'one' => [1, true],
    'one hundred' => [100, true],
    'zero' => [0, false],
    'over one hundred' => [101, false],
    'four digits' => [1000, false],
    'letters' => ['abc', false],
]);

test('updating a section also rejects capacity above 100', function () {
    ['admin' => $admin, 'section' => $section, 'academicYear' => $academicYear, 'curriculum' => $curriculum] = createSectionPageFixtures('admin.sections.capacity.update');

    $this->actingAs($admin)
        ->from(route('admin.section-config.index'))
        ->put(route('admin.section-config.update', $section), [
            'name' => $section->name,
            'grade_level' => 'grade_7',
            'SY_ID' => $academicYear->SY_ID,
            'curriculum_ID' => $curriculum->curriculum_ID,
            'room' => $section->room,
            'capacity' => 101,
        ])
        ->assertRedirect(route('admin.section-config.index'))
        ->assertSessionHasErrors('capacity');

    expect($section->fresh()->capacity)->toBe(40);
});
