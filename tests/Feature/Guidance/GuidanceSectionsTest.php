<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function createGuidanceCounselor(): User
{
    $role = Role::query()->create(['role_name' => 'guidance counselor']);

    return User::query()->create([
        'role_id' => $role->id,
        'username' => 'guidance.test',
        'password' => Hash::make('password'),
        'first_name' => 'Guidance',
        'last_name' => 'Counselor',
        'status' => 'active',
    ]);
}

function createSectioningFixtures(): array
{
    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'Grade 7',
        'description' => 'Junior High School Grade 7 curriculum',
        'status' => true,
    ]);

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();

    Section::query()->create([
        'name' => 'G7-A',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'capacity' => 40,
        'status' => true,
    ]);

    return compact('academicYear', 'curriculum');
}

test('guidance counselor can view the sectioning index page', function () {
    $user = createGuidanceCounselor();
    createSectioningFixtures();

    $response = $this->actingAs($user)->get(route('guidance.sections.index'));

    $response->assertOk();
    $response->assertSee('Add Section');
    $response->assertSee('Apply');
    $response->assertSee('lg:grid-cols-4', false);
    $response->assertDontSee('master-list.pdf');
    $response->assertDontSee('>PDF<', false);
    $response->assertDontSee('>Enrolled<', false);
    $response->assertDontSee('Curriculum', false);
});

test('guidance counselor can create a section', function () {
    $user = createGuidanceCounselor();
    ['academicYear' => $academicYear] = createSectioningFixtures();

    $response = $this->actingAs($user)->post(route('guidance.sections.store'), [
        'name' => 'Mendeleev',
        'grade_level' => 'grade_7',
        'SY_ID' => $academicYear->SY_ID,
        'room' => 'Room 201',
        'capacity' => 40,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Section created successfully.');

    $section = Section::query()->where('name', 'Mendeleev')->first();

    expect($section)->not->toBeNull();
    expect($section->curriculum?->name)->toBe('Grade 7');
});

test('guidance counselor section capacity must be a number from 1 to 100', function (mixed $capacity, bool $passes) {
    $user = createGuidanceCounselor();
    ['academicYear' => $academicYear] = createSectioningFixtures();

    $response = $this->actingAs($user)
        ->from(route('guidance.sections.index'))
        ->post(route('guidance.sections.store'), [
            'name' => 'Faraday',
            'grade_level' => 'grade_7',
            'SY_ID' => $academicYear->SY_ID,
            'room' => 'Room 201',
            'capacity' => $capacity,
        ]);

    $response->assertRedirect(route('guidance.sections.index'));

    if ($passes) {
        $response->assertSessionHasNoErrors();
        expect(Section::query()->where('name', 'Faraday')->value('capacity'))->toBe((int) $capacity);
    } else {
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
