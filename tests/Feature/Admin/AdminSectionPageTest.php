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
    $curriculum->update(['grade_ID' => $gradeLevel->grade_ID, 'cluster_ID' => $cluster->cluster_ID]);

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

test('management can copy sections into a previous year with optional advisers', function (string $role, string $mode) {
    ['admin' => $admin, 'section' => $source, 'academicYear' => $current] = createSectionPageFixtures('copy.'.$role.$mode);
    $admin->update(['role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id]);
    $previous = AcademicYear::query()->create([
        'school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false,
    ]);
    $source->update(['status' => false]);
    $otherGrade = $source->replicate();
    $otherGrade->fill(['name' => 'Newton', 'grade_ID' => GradeLevel::idForValue('grade_8')])->save();

    $this->actingAs($admin)->get(route($role.'.section-config.index'))
        ->assertOk()->assertSee('Copy Sections')->assertSee(route($role.'.section-config.copy'));

    $payload = ['source_SY_ID' => $current->SY_ID, 'target_SY_ID' => $previous->SY_ID, 'copy_grade_level' => 'grade_7', 'adviser_mode' => $mode];
    $this->post(route($role.'.section-config.copy'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route($role.'.section-config.index', ['SY_ID' => $previous->SY_ID]));

    $copy = Section::query()->where('SY_ID', $previous->SY_ID)->sole();
    foreach (['name', 'grade_ID', 'cluster_ID', 'curriculum_grade_level_ID', 'room', 'capacity'] as $field) {
        expect($copy->$field)->toBe($source->$field);
    }
    expect($copy->staff_ID)->toBe($mode === 'keep' ? $source->staff_ID : null)
        ->and($copy->status)->toBeTrue()
        ->and($source->fresh()->status)->toBeFalse()
        ->and($current->fresh()->status)->toBeTrue()
        ->and($previous->fresh()->status)->toBeFalse();

    $copy->update(['room' => 'Keep this room', 'status' => false]);
    $this->post(route($role.'.section-config.copy'), array_merge($payload, ['copy_grade_level' => 'all']))
        ->assertSessionHasNoErrors()->assertSessionHas('success', 'Copied 1 section(s). Skipped 1 section(s) already present in the destination school year.');
    expect(Section::query()->where('SY_ID', $previous->SY_ID)->count())->toBe(2)
        ->and($copy->fresh()->room)->toBe('Keep this room')
        ->and($copy->fresh()->status)->toBeFalse();
})->with(['admin', 'principal'])->with(['keep', 'empty']);

test('copy sections rejects invalid selections and reopens only the copy modal', function () {
    ['admin' => $admin, 'academicYear' => $year] = createSectionPageFixtures('copy.invalid');
    $url = route('admin.section-config.index');
    $this->actingAs($admin)->from($url)->post(route('admin.section-config.copy'), [
        'source_SY_ID' => $year->SY_ID, 'target_SY_ID' => $year->SY_ID,
        'copy_grade_level' => 'grade_13', 'adviser_mode' => 'invalid',
    ])->assertSessionHasErrorsIn('copySections', ['target_SY_ID', 'copy_grade_level', 'adviser_mode']);
    $this->get($url)->assertSee('id="copySectionsModal" role="dialog" aria-modal="true" aria-labelledby="copySectionsTitle" data-open="true"', false)
        ->assertSee('id="sectionModal" role="dialog" aria-modal="true" aria-labelledby="sectionModalTitle" data-open="false"', false);
    expect(Section::query()->count())->toBe(1);
});

test('copy sections reports an empty source selection and rejects missing years', function () {
    ['admin' => $admin, 'academicYear' => $year] = createSectionPageFixtures('copy.empty');
    $other = AcademicYear::query()->create([
        'school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31', 'status' => false,
    ]);
    $payload = ['source_SY_ID' => $year->SY_ID, 'target_SY_ID' => $other->SY_ID, 'copy_grade_level' => 'grade_12', 'adviser_mode' => 'empty'];
    $this->actingAs($admin)->post(route('admin.section-config.copy'), $payload)
        ->assertSessionHasErrorsIn('copySections', ['source_SY_ID']);
    $this->post(route('admin.section-config.copy'), array_merge($payload, ['source_SY_ID' => 99999, 'target_SY_ID' => 99998]))
        ->assertSessionHasErrorsIn('copySections', ['source_SY_ID', 'target_SY_ID']);
    expect(Section::query()->count())->toBe(1);
});

test('teachers cannot copy sections through management routes', function () {
    ['admin' => $teacher] = createSectionPageFixtures('copy.forbidden');
    $teacher->update(['role_id' => Role::query()->firstOrCreate(['role_name' => 'teacher'])->id]);
    foreach (['admin', 'principal'] as $role) {
        $this->actingAs($teacher)->post(route($role.'.section-config.copy'), [])->assertForbidden();
    }
});
