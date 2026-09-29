<?php

use App\Models\GradingPeriodStatus;
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

test('admin can close all configured junior high terms at once', function () {
    $admin = createGradingTermAdmin('admin.grading.close.all.jhs');

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.junior-high.close-all'))
        ->assertRedirect(route('admin.grading-term-config.index'))
        ->assertSessionHas('success');

    expect(GradingTerm::areJuniorHighTermsClosed())->toBeTrue();
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
            'max_terms' => 4,
        ]);

    $response->assertRedirect(route('admin.grading-term-config.index'));
    $response->assertSessionHas('success');

    expect(GradingTermSetting::current()->max_terms)->toBe(4);
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
    $response->assertDontSee('Current Period');
    $response->assertSee('First Semester');
    $response->assertSee('Second Semester');
    $response->assertSee('Term 1');
    $response->assertSee('Term 2');
    $response->assertSee('Term 3');
    $response->assertSee('>Semester</h2>', false);
    $response->assertSee('Term Status');
    $response->assertSee('Active semester');
    $response->assertSee('Active term');
    $response->assertSee('Change status');
    $response->assertSee('Maximum Terms');
    $response->assertDontSee('Current Quarter');
    $response->assertDontSee('Quarter 1');
    $response->assertDontSee('Semesters and Quarters');
    $response->assertSee('>Terms</h2>', false);
});

test('admin can set senior high semester and term independently from their lookup tables', function () {
    $admin = createGradingTermAdmin('admin.grading.shs.separate');
    $secondSemester = GradingSemester::query()->where('key', 'second')->firstOrFail();
    $secondTerm = GradingTerm::query()->seniorHigh()->where('key', 'term_2')->firstOrFail();

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

test('closing a senior high semester also closes its terms', function () {
    $admin = createGradingTermAdmin('admin.grading.close.shs.semester');
    $semester = GradingSemester::query()->where('key', 'first')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->put(route('admin.grading-term-config.senior-high.semester.close', $semester))
        ->assertRedirect(route('admin.grading-term-config.index', ['tab' => 'senior_high']))
        ->assertSessionHas('success');

    expect($semester->fresh()->status?->slug)->toBe('closed')
        ->and(GradingTerm::query()
            ->where('senior_high_grading_period_status_ID', GradingPeriodStatus::closedId())
            ->count())->toBe(3);
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

test('senior high maximum is independent and extra periods keep semester order', function () {
    $admin = createGradingTermAdmin('admin.shs.maximum');
    $before = GradingTerm::query()->pluck('junior_high_grading_period_status_ID', 'term_ID')->all();
    $this->actingAs($admin)->from(route('admin.grading-term-config.index'))
        ->put(route('admin.grading-term-config.senior-high.settings.update'), ['senior_high_max_terms' => 4])
        ->assertSessionHasNoErrors()->assertSessionHas('success');
    expect(GradingTermSetting::current()->max_terms)->toBe(4)
        ->and(GradingTermSetting::current()->senior_high_max_terms)->toBe(4)
        ->and(GradingTerm::query()->pluck('junior_high_grading_period_status_ID', 'term_ID')->all())->toBe($before)
        ->and(GradingTerm::seniorHighPeriodPosition('second', 1))->toBe(5)
        ->and(GradingTerm::findSeniorHighPeriodByKey('shs_sem1_term_4')['term'])->toBe(4);
    $term = GradingTerm::query()->seniorHigh()->where('key', 'term_4')->firstOrFail();
    $this->put(route('admin.grading-term-config.senior-high-status.update', $term), ['status' => 'open'])->assertSessionHasNoErrors();
    expect(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem1_term_4')
        ->and(GradingTerm::lockedSeniorHighPeriodKeys())->toBe(['shs_sem1_term_1', 'shs_sem1_term_2', 'shs_sem1_term_3']);
    $this->put(route('admin.grading-term-config.senior-high.settings.update'), ['senior_high_max_terms' => 3])
        ->assertSessionHasErrors('senior_high_max_terms');
    expect(GradingTermSetting::current()->senior_high_max_terms)->toBe(4);
});

test('adding a senior high term preserves junior high and starts archived', function () {
    $admin = createGradingTermAdmin('admin.shs.add');
    $before = GradingTerm::configuredPeriods();
    $this->actingAs($admin)->from(route('admin.grading-term-config.index'))
        ->post(route('admin.grading-term-config.senior-high.store'), ['label' => 'Term 5'])
        ->assertSessionHasNoErrors()->assertSessionHas('success');
    $term = GradingTerm::query()->where('label', 'Term 5')->firstOrFail();
    expect($term->junior_high_grading_period_status_ID)->toBeNull()->and($term->isSeniorHighArchived())->toBeTrue()
        ->and(GradingTerm::configuredPeriods())->toBe($before);
    $this->put(route('admin.grading-term-config.senior-high-status.update', $term), ['status' => 'open'])
        ->assertSessionHasErrors('status');
    $this->put(route('admin.grading-term-config.senior-high.settings.update'), ['senior_high_max_terms' => 5])
        ->assertSessionHasNoErrors();
    $this->put(route('admin.grading-term-config.senior-high-status.update', $term), ['status' => 'open'])
        ->assertSessionHasNoErrors();
    expect(GradingTerm::currentSeniorHighPeriodKey())->toBe('shs_sem1_term_5')
        ->and(GradingTerm::configuredPeriods())->toBe($before);
});

test('senior high maximum rejects unavailable and out of range counts', function (int $maximum) {
    $admin = createGradingTermAdmin('admin.shs.invalid.maximum');
    $this->actingAs($admin)->put(route('admin.grading-term-config.senior-high.settings.update'), ['senior_high_max_terms' => $maximum])
        ->assertSessionHasErrors('senior_high_max_terms');
    expect(GradingTermSetting::current()->senior_high_max_terms)->toBe(3);
})->with([1, 5, 13]);

test('school levels keep independent labels keys and statuses', function () {
    $admin = createGradingTermAdmin('admin.independent.terms');
    $junior = GradingTerm::query()->juniorHigh()->where('key', 'term_1')->firstOrFail();
    $senior = GradingTerm::query()->seniorHigh()->where('key', 'term_1')->firstOrFail();
    expect($junior->term_ID)->not->toBe($senior->term_ID)
        ->and($junior->senior_high_grading_period_status_ID)->toBeNull()
        ->and($senior->junior_high_grading_period_status_ID)->toBeNull();
    $this->actingAs($admin)->put(route('admin.grading-term-config.update', $junior), ['label' => 'Quarter 1', 'sort_order' => 1])
        ->assertSessionHasNoErrors();
    expect($senior->fresh()->label)->toBe('Term 1');
    $this->put(route('admin.grading-term-config.update', $senior), ['label' => 'Quarter 1', 'sort_order' => 1])
        ->assertSessionHasNoErrors();
    expect($senior->fresh()->label)->toBe('Quarter 1');
    $this->put(route('admin.grading-term-config.senior-high-status.update', $junior), ['status' => 'closed'])->assertNotFound();
    $this->put(route('admin.grading-term-config.junior-high-status.update', $senior), ['status' => 'closed'])->assertNotFound();
    expect($junior->fresh()->isJuniorHighOpen())->toBeTrue()->and($senior->fresh()->isSeniorHighOpen())->toBeTrue();
});

test('adding the same named term to each school level creates independent records', function () {
    $admin = createGradingTermAdmin('admin.independent.add');
    $this->actingAs($admin)->post(route('admin.grading-term-config.store'), ['label' => 'Term 5'])->assertSessionHasNoErrors();
    $this->post(route('admin.grading-term-config.senior-high.store'), ['label' => 'Term 5'])->assertSessionHasNoErrors();
    expect(GradingTerm::query()->where('key', 'term_5')->count())->toBe(2)
        ->and(GradingTerm::query()->juniorHigh()->where('key', 'term_5')->first()->senior_high_grading_period_status_ID)->toBeNull()
        ->and(GradingTerm::query()->seniorHigh()->where('key', 'term_5')->first()->junior_high_grading_period_status_ID)->toBeNull();
});

test('grading closures render modal confirmations and return a single success toast', function () {
    $admin = createGradingTermAdmin('admin.close.modals');
    $url = route('admin.grading-term-config.index');
    $this->actingAs($admin)->get($url)->assertOk()
        ->assertSee('id="closeGradingConfirmation"', false)
        ->assertSee('data-confirm-title="Close all Junior High terms?"', false)
        ->assertSee('data-confirm-title="Close First Semester?"', false)
        ->assertDontSee("return confirm('Close", false);
    $this->from($url)->put(route('admin.grading-term-config.junior-high.close-all'))->assertSessionHas('success');
    $response = $this->get($url)->assertOk()->assertSee('data-test="academic-setup-status"', false);
    expect(substr_count($response->getContent(), 'data-test="academic-setup-status"'))->toBe(1);
    expect(GradingTerm::query()->seniorHigh()->where('key', 'term_1')->first()->isSeniorHighOpen())->toBeTrue();
});

test('opening junior high restores included terms and keeps only the selected term open', function () {
    $admin = createGradingTermAdmin('admin.jhs.reopen');
    GradingTermSetting::current()->update(['max_terms' => 3]);
    GradingTerm::syncActiveStatus();
    GradingTerm::closeAllJuniorHighTerms();
    $beforeSenior = GradingTerm::query()->seniorHigh()->pluck('senior_high_grading_period_status_ID', 'term_ID')->all();
    $term = GradingTerm::query()->juniorHigh()->where('key', 'term_2')->firstOrFail();
    foreach ([1, 2] as $attempt) {
        $this->actingAs($admin)->put(route('admin.grading-term-config.junior-high-status.update', $term), ['status' => 'open'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        expect(GradingTerm::query()->juniorHigh()->orderBy('sort_order')->get()->map(fn ($row) => $row->juniorHighStatus->slug)->all())
            ->toBe(['active', 'open', 'active', 'archived']);
    }
    expect(GradingTerm::query()->seniorHigh()->pluck('senior_high_grading_period_status_ID', 'term_ID')->all())->toBe($beforeSenior);
});

test('management can reopen a semester and restore its configured terms', function (string $role) {
    $staff = createGradingTermAdmin('management.shs.reopen');
    $staff->update(['role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id]);
    GradingTermSetting::current()->update(['senior_high_max_terms' => 2]);
    $semester = GradingSemester::query()->where('key', 'first')->firstOrFail();
    $juniorBefore = GradingTerm::query()->juniorHigh()->pluck('junior_high_grading_period_status_ID', 'term_ID')->all();
    $url = route($role.'.grading-term-config.senior-high.semester.status', $semester);
    $this->actingAs($staff)->from(route($role.'.grading-term-config.index'));
    $this->put($url, ['status' => 'closed'])->assertSessionHas('success');
    expect(GradingTerm::isCurrentSeniorHighPeriodOpen())->toBeFalse();
    $this->put($url, ['status' => 'open'])->assertSessionHasNoErrors()->assertSessionHas('success');
    expect($semester->fresh()->status->slug)->toBe('open')
        ->and($semester->fresh()->isActive())->toBeTrue()
        ->and(GradingTerm::isCurrentSeniorHighPeriodOpen())->toBeTrue()
        ->and(GradingTerm::query()->seniorHigh()->orderBy('sort_order')->get()->map(fn ($term) => $term->seniorHighStatus->slug)->all())
        ->toBe(['open', 'active', 'archived', 'archived'])
        ->and(GradingTerm::query()->juniorHigh()->pluck('junior_high_grading_period_status_ID', 'term_ID')->all())->toBe($juniorBefore);
    $this->get(route($role.'.grading-term-config.index'))->assertOk()->assertSee('data-test="academic-setup-status"', false);
    $this->put($url, ['status' => 'archived'])->assertSessionHasNoErrors();
    expect(GradingTerm::isCurrentSeniorHighPeriodOpen())->toBeFalse();
    $this->put($url, ['status' => 'open'])->assertSessionHasNoErrors();
    expect(GradingTerm::isCurrentSeniorHighPeriodOpen())->toBeTrue();
})->with(['admin', 'principal']);

test('closing another semester does not close the current semesters terms', function () {
    $admin = createGradingTermAdmin('admin.shs.other.close');
    $second = GradingSemester::query()->where('key', 'second')->firstOrFail();
    $this->actingAs($admin)->put(route('admin.grading-term-config.senior-high.semester.status', $second), ['status' => 'closed'])
        ->assertSessionHasNoErrors();
    expect(GradingTerm::isCurrentSeniorHighPeriodOpen())->toBeTrue()
        ->and(GradingTermSetting::current()->seniorHighSemester())->toBe('first');
    $this->put(route('admin.grading-term-config.senior-high.semester.status', $second), ['status' => 'open'])
        ->assertSessionHasNoErrors();
    expect(GradingTermSetting::current()->seniorHighSemester())->toBe('second')
        ->and(GradingTerm::isCurrentSeniorHighPeriodOpen())->toBeTrue();
    $this->put(route('admin.grading-term-config.senior-high.semester.status', $second), ['status' => 'invalid'])
        ->assertSessionHasErrors('status');
    $fullYear = GradingSemester::query()->where('key', 'full_year')->firstOrFail();
    $this->put(route('admin.grading-term-config.senior-high.semester.status', $fullYear), ['status' => 'open'])->assertNotFound();
});

test('management limits maximum terms and term order to whole numbers and existing school level terms', function (string $role) {
    $staff = createGradingTermAdmin('management.numeric.limits');
    $staff->update(['role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id]);
    $this->actingAs($staff)->from(route($role.'.grading-term-config.index'));
    $this->post(route($role.'.grading-term-config.store'), ['label' => 'Extra Junior Term'])
        ->assertSessionHasNoErrors();

    foreach (['junior_high', 'senior_high'] as $level) {
        $count = GradingTerm::query()->where('school_level', $level)->count();
        $term = GradingTerm::query()->where('school_level', $level)->firstOrFail();
        $field = $level === 'junior_high' ? 'max_terms' : 'senior_high_max_terms';
        $settingsRoute = $level === 'junior_high' ? 'settings.update' : 'senior-high.settings.update';
        $previous = GradingTermSetting::current()->getAttribute($field);

        foreach ([$count + 1, 'abc', '2.5', '2e0', '-2', '0'] as $invalid) {
            $this->put(route($role.'.grading-term-config.'.$settingsRoute), [$field => $invalid])
                ->assertSessionHasErrors($field);
            expect(GradingTermSetting::current()->getAttribute($field))->toBe($previous);
            $this->put(route($role.'.grading-term-config.update', $term), [
                'label' => $term->label, 'sort_order' => $invalid,
            ])->assertSessionHasErrors('sort_order');
            expect($term->fresh()->sort_order)->toBe($term->sort_order);
        }

        $this->put(route($role.'.grading-term-config.'.$settingsRoute), [$field => $count])
            ->assertSessionHasNoErrors();
        $this->put(route($role.'.grading-term-config.update', $term), [
            'label' => $term->label, 'sort_order' => $count,
        ])->assertSessionHasNoErrors();
    }

    $this->get(route($role.'.grading-term-config.index'))->assertOk()
        ->assertSee('Open semester')->assertSee('Close semester')
        ->assertSee('data-whole-number', false);
})->with(['admin', 'principal']);

test('management edit buttons preserve term data and modals sit outside the main page', function (string $role) {
    $staff = createGradingTermAdmin('management.modal.markup');
    $staff->update(['role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id]);
    $term = GradingTerm::query()->seniorHigh()->firstOrFail();
    $term->update(['label' => 'Term "One" & Teacher'.chr(39).'s']);
    $response = $this->actingAs($staff)->get(route($role.'.grading-term-config.index'))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $xpath = new DOMXPath($document);
    $buttons = $xpath->query('//button[@data-term]');
    expect($buttons->length)->toBe(GradingTerm::query()->count());
    foreach ($buttons as $button) {
        expect($button->getAttribute('onclick'))->toBe('openEditTermModal(JSON.parse(this.dataset.term))');
        $data = json_decode($button->getAttribute('data-term'), true, flags: JSON_THROW_ON_ERROR);
        expect($data['label'])->toBe(GradingTerm::findOrFail($data['term_ID'])->label);
    }
    foreach (['maxTermsModal', 'shsMaxTermsModal', 'editTermModal'] as $id) {
        expect($xpath->query('//*[@id="'.$id.'"]')->length)->toBe(1)
            ->and($xpath->query('//main//*[@id="'.$id.'"]')->length)->toBe(0);
    }
})->with(['admin', 'principal']);
