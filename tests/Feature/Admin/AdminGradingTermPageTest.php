<?php

use App\Models\GradingSemester;
use App\Models\GradingTerm;
use App\Models\GradingTermSetting;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Support\Facades\Hash;

function createGradingTermAdmin(string $username): Staff
{
    $role = Role::query()->create(['role_name' => 'admin']);

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

test('admin can view the restyled grading terms page', function () {
    $admin = createGradingTermAdmin('admin.grading.terms');

    $response = $this->actingAs($admin)->get(route('admin.grading-term-config.index'));

    $response->assertOk();
    $response->assertSee('Grading Terms');
    $response->assertSee('Junior High School');
    $response->assertSee('Senior High School');
    $response->assertSee('Current Term');
    $response->assertSee('Maximum Terms');
    $response->assertSee('Term settings');
    $response->assertSee('Add new term');
    $response->assertSee('Edit maximum terms');
    $response->assertSee('Change status');
    $response->assertSee('Current grading term');
    $response->assertSee('id="addTermModal"', false);
    $response->assertSee('id="maxTermsModal"', false);
    $response->assertSee('id="editTermModal"', false);
    $response->assertSee('openAddTermModal', false);
    $response->assertSee('openMaxTermsModal', false);
    $response->assertSee('openEditTermModal', false);
    $response->assertSee('title="Edit"', false);
    $response->assertSee('Term 1');
    $response->assertSee('Term 2');
    $response->assertDontSee('Set as Active');
    $response->assertDontSee('Active Grading Term');
    $response->assertDontSee('<select name="open_terms_count"', false);
    $response->assertDontSee('Open Through');
    $response->assertDontSee('form="update-term-', false);
    $response->assertSee('Archive');
});

test('admin can set the one open junior high term from the status action', function () {
    $admin = createGradingTermAdmin('admin.grading.set.active');
    $termTwo = GradingTerm::query()->where('key', 'term_2')->firstOrFail();

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.junior-high-status.update', $termTwo), [
            'status' => 'open',
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHas('success');

    expect(GradingTerm::currentEditablePeriodKey())->toBe($termTwo->key)
        ->and(GradingTerm::currentEditablePeriodLabel())->toBe('Term 2');
});

test('admin can close a junior high term', function () {
    $admin = createGradingTermAdmin('admin.grading.close.jhs');
    $term = GradingTerm::query()->where('key', 'term_1')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.junior-high-status.update', $term), [
            'status' => 'closed',
        ])
        ->assertRedirect(route('admin.grading-term-config.index'))
        ->assertSessionHas('success');

    expect($term->fresh()->isJuniorHighOpen())->toBeFalse()
        ->and($term->fresh()->juniorHighStatus?->name)->toBe('Closed');
});

test('admin can add a term from the settings modal', function () {
    GradingTermSetting::current()->update(['max_terms' => 5]);
    $admin = createGradingTermAdmin('admin.grading.add.term');

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->post(route('admin.grading-term-config.store'), [
            '_form' => 'add_term',
            'label' => 'Term 5',
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHas('success');

    expect(GradingTerm::query()->where('label', 'Term 5')->exists())->toBeTrue();
});

test('admin can update the maximum terms from the settings modal', function () {
    $admin = createGradingTermAdmin('admin.grading.max.terms');

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.settings.update'), [
            '_form' => 'max_terms',
            'max_terms' => 6,
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHas('success');

    expect(GradingTermSetting::current()->max_terms)->toBe(6);
});

test('admin can edit a term label and order from the modal', function () {
    $admin = createGradingTermAdmin('admin.grading.edit.term');
    $term = GradingTerm::query()->where('key', 'term_2')->firstOrFail();

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.update', $term), [
            '_form' => 'edit_term',
            'term_id' => $term->term_ID,
            'label' => 'Second Quarter',
            'sort_order' => 2,
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHas('success');

    $term->refresh();

    expect($term->label)->toBe('Second Quarter')
        ->and($term->sort_order)->toBe(2);
});

test('lowering the maximum terms archives extra terms and raising it restores them', function () {
    $admin = createGradingTermAdmin('admin.grading.sync.status');
    $fourthTerm = GradingTerm::query()->where('key', 'term_4')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.settings.update'), [
            '_form' => 'max_terms',
            'max_terms' => 3,
        ])
        ->assertRedirect(route('admin.grading-term-config.index'));

    expect(GradingTermSetting::current()->max_terms)->toBe(3)
        ->and($fourthTerm->fresh()->isJuniorHighArchived())->toBeTrue()
        ->and(GradingTerm::query()->juniorHighAvailable()->count())->toBe(3);

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.settings.update'), [
            '_form' => 'max_terms',
            'max_terms' => 4,
        ])
        ->assertRedirect(route('admin.grading-term-config.index'));

    expect(GradingTermSetting::current()->max_terms)->toBe(4)
        ->and($fourthTerm->fresh()->isJuniorHighActive())->toBeTrue()
        ->and(GradingTerm::query()->juniorHighAvailable()->count())->toBe(4);
});

test('adding a term reopens the add term modal when validation fails', function () {
    GradingTermSetting::current()->update(['max_terms' => 5]);
    $admin = createGradingTermAdmin('admin.grading.add.invalid');

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->post(route('admin.grading-term-config.store'), [
            '_form' => 'add_term',
            'label' => 'Term 1',
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHasErrors('label');

    $this->actingAs($admin)
        ->get(route('admin.grading-term-config.index'))
        ->assertSee('id="addTermModal"', false)
        ->assertSee('data-open="true"', false)
        ->assertSee('Add new term');
});

test('editing a term reopens the edit modal when validation fails', function () {
    $admin = createGradingTermAdmin('admin.grading.edit.invalid');
    $term = GradingTerm::query()->where('key', 'term_2')->firstOrFail();

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.update', $term), [
            '_form' => 'edit_term',
            'term_id' => $term->term_ID,
            'label' => 'Term 1',
            'sort_order' => 2,
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHasErrors('label');

    $this->actingAs($admin)
        ->get(route('admin.grading-term-config.index'))
        ->assertSee('id="editTermModal"', false)
        ->assertSee('data-open="true"', false)
        ->assertSee('Edit term');
});

test('admin can view the senior high school grading tab', function () {
    $admin = createGradingTermAdmin('admin.grading.shs.tab');

    $response = $this->actingAs($admin)->get(route('admin.grading-term-config.index', [
        'tab' => 'senior_high',
    ]));

    $response->assertOk();
    $response->assertSee('Junior High School');
    $response->assertSee('Senior High School');
    $response->assertSee('Current Semester');
    $response->assertSee('Current Term');
    $response->assertSee('Current Period');
    $response->assertSee('First Semester');
    $response->assertSee('Second Semester');
    $response->assertSee('Term 1');
    $response->assertSee('Term 2');
    $response->assertSee('Term 3');
    $response->assertSee('Active Semester');
    $response->assertSee('Term Status');
    $response->assertSee('Active semester');
    $response->assertSee('Active term');
    $response->assertSee('Set Active');
    $response->assertSee('Maximum Terms');
    $response->assertDontSee('Current Quarter');
    $response->assertDontSee('Quarter 1');
    $response->assertDontSee('Semesters and Quarters');
    $response->assertSee('>Terms</h2>', false);
});

test('admin can set senior high semester and term independently from their lookup tables', function () {
    $admin = createGradingTermAdmin('admin.grading.shs.separate');
    $secondSemester = GradingSemester::query()->where('key', 'second')->firstOrFail();
    $secondTerm = GradingTerm::query()->where('key', 'term_2')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->put(route('admin.grading-term-config.senior-high.semester.update'), [
            'semester_ID' => $secondSemester->semester_ID,
        ])
        ->assertRedirect(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->assertSessionHas('success');

    expect(GradingTermSetting::current()->semester_ID)->toBe($secondSemester->semester_ID)
        ->and(GradingTermSetting::current()->term?->key)->toBe('term_1');

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->put(route('admin.grading-term-config.senior-high.term.update'), [
            'term_ID' => $secondTerm->term_ID,
        ])
        ->assertRedirect(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->assertSessionHas('success');

    expect(GradingTermSetting::current()->semester?->key)->toBe('second')
        ->and(GradingTermSetting::current()->term_ID)->toBe($secondTerm->term_ID)
        ->and(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem2_term_2');
});

test('admin can set the current senior high semester and term', function () {
    $admin = createGradingTermAdmin('admin.grading.shs.set');

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->put(route('admin.grading-term-config.senior-high.update'), [
            'semester' => 'second',
            'term' => 1,
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index', ['tab' => 'senior_high']));
    $response->assertSessionHas('success');

    expect(GradingTermSetting::current()->seniorHighSemester())->toBe('second')
        ->and(GradingTermSetting::current()->seniorHighTerm())->toBe(1)
        ->and(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem2_term_1')
        ->and(GradingTerm::currentSeniorHighPeriodLabel())->toBe('Second Semester · Term 1')
        ->and(GradingTerm::lockedSeniorHighPeriodKeys())->toBe(['shs_sem1_term_1', 'shs_sem1_term_2', 'shs_sem1_term_3']);
});

test('admin can set the second term of the first senior high semester', function () {
    $admin = createGradingTermAdmin('admin.grading.shs.term2');

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->put(route('admin.grading-term-config.senior-high.update'), [
            'semester' => 'first',
            'term' => 2,
        ])
        ->assertRedirect(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->assertSessionHas('success');

    expect(GradingTermSetting::current()->seniorHighSemester())->toBe('first')
        ->and(GradingTermSetting::current()->seniorHighTerm())->toBe(2)
        ->and(GradingTerm::currentSeniorHighPeriodLabel())->toBe('First Semester · Term 2')
        ->and(GradingTerm::lockedSeniorHighPeriodKeys())->toBe(['shs_sem1_term_1']);
});

test('setting an invalid senior high period is rejected', function () {
    $admin = createGradingTermAdmin('admin.grading.shs.invalid');

    $response = $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->put(route('admin.grading-term-config.senior-high.update'), [
            'semester' => 'third',
            'term' => 4,
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index', ['tab' => 'senior_high']));
    $response->assertSessionHasErrors(['semester', 'term']);

    expect(GradingTermSetting::current()->seniorHighSemester())->toBe('first')
        ->and(GradingTermSetting::current()->seniorHighTerm())->toBe(1);
});
