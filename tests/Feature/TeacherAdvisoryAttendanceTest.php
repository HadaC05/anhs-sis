<?php

use App\Models\AcademicYear;
use App\Models\AcademicYearAttendanceSetting;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentMonthlyAttendance;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionAttendanceSetting;
use App\Models\SectionSf2Upload;
use App\Models\Staff;
use App\Models\Student;
use App\Support\Sf2AttendancePdf;
use App\Support\Sf9AttendanceSummary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

function createAdvisoryAttendanceFixtures(): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.attendance',
        'password' => Hash::make('password'),
        'first_name' => 'Advisory',
        'last_name' => 'Teacher',
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

    $gradeLevel = GradeLevel::query()->where('grade_label', 'Grade 7')->firstOrFail();

    $section = Section::query()->create([
        'name' => 'Einstein',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'staff_ID' => $teacher->staff_id,
        'room' => 'Room 201',
        'capacity' => 40,
    ]);

    $student = Student::query()->create([
        'lrn' => '987654321098',
        'first_name' => 'Juan',
        'last_name' => 'Dela',
        'status' => 'active',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $section->section_ID,
        'SY_ID' => $academicYear->SY_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    return compact('teacher', 'section', 'enrollment');
}

test('teacher attendance uses configured months and excludes hidden months from totals without deleting records', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    $section->academicYear->update(['attendance_start_month' => 8, 'attendance_end_month' => 2]);

    foreach ([6, 8] as $month) {
        AcademicYearAttendanceSetting::factory()->create(['SY_ID' => $section->SY_ID, 'month' => $month, 'school_days' => 20]);
        EnrollmentMonthlyAttendance::query()->create([
            'enrollment_ID' => $enrollment->enrollment_ID,
            'month' => $month,
            'days_present' => 18,
            'days_absent' => 2,
        ]);
    }

    $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section))
        ->assertOk()
        ->assertViewHas('months', fn ($months) => array_keys($months) === [8, 9, 10, 11, 12, 1, 2])
        ->assertViewHas('schoolDays', fn ($days) => array_sum($days) === 20)
        ->assertViewHas('rows', fn ($rows) => $rows->first()['summary']['total_present'] === 18 && $rows->first()['summary']['total_absent'] === 2);

    expect(EnrollmentMonthlyAttendance::query()->where('enrollment_ID', $enrollment->enrollment_ID)->count())->toBe(2);
});

test('advisory teacher can view attendance record page', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertOk();
    $response->assertSee('Attendance Record');
    $response->assertViewHas('months', fn ($months) => array_keys($months) === [6, 7, 8, 9, 10, 11, 12, 1, 2, 3]);
    $response->assertSee('Monthly Attendance Summary');
    $response->assertSee('Upload SF2');
    $response->assertDontSee('Save Attendance');
});

test('attendance summary displays imported monthly counts', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $section->SY_ID,
        'month' => 6,
        'school_days' => 20,
    ]);

    EnrollmentMonthlyAttendance::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'month' => 6,
        'days_present' => 19,
        'days_absent' => 1,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertOk();
    $response->assertSee('19', false);
    $response->assertSee('20', false);
    $response->assertSee('School days are set by the administrator');
});

test('attendance overview shows zero total absences once every month has a record', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    $section->academicYear->update(['attendance_start_month' => 6, 'attendance_end_month' => 7]);

    EnrollmentMonthlyAttendance::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'month' => 6,
        'days_present' => 20,
        'days_absent' => 0,
    ]);

    $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section))
        ->assertOk()
        ->assertSee('data-test="attendance-total-absent">—</td>', false);

    EnrollmentMonthlyAttendance::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'month' => 7,
        'days_present' => 22,
        'days_absent' => 0,
    ]);

    $this->get(route('teacher.advisory.attendance', $section))
        ->assertOk()
        ->assertViewHas('rows', fn ($rows) => $rows->first()['has_complete_attendance'] === true)
        ->assertSee('data-test="attendance-total-absent">0</td>', false);
});

test('advisory attendance displays admin configured school days', function () {
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();

    AcademicYearAttendanceSetting::factory()->create([
        'SY_ID' => $section->SY_ID,
        'month' => 7,
        'school_days' => 23,
    ]);

    $response = $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertOk();
    $response->assertSee('23', false);
    $response->assertSee('School Days');
});

test('unreadable sf2 is saved with a failed import status', function () {
    Storage::fake('local');

    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();

    $response = $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 6,
        'sf2_file' => UploadedFile::fake()->create('SF2-June.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('attendance_import_error');
    $response->assertSessionHas('error');

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee('data-test="attendance-upload-error"', false)
        ->assertDontSee('data-test="attendance-upload-status"', false);

    $upload = SectionSf2Upload::query()->where('section_ID', $section->section_ID)->first();

    expect($upload)->not->toBeNull();
    expect($upload->status)->toBe('failed');
    expect($upload->report_month)->toBe(6);
    Storage::disk('local')->assertExists($upload->storage_path);
});

test('non adviser cannot access attendance record page', function () {
    ['section' => $section] = createAdvisoryAttendanceFixtures();

    $role = Role::query()->create(['role_name' => 'teacher-other']);
    $otherTeacher = Staff::query()->create([
        'role_id' => $role->id,
        'username' => 'teacher.other',
        'password' => Hash::make('password'),
        'first_name' => 'Other',
        'last_name' => 'Teacher',
        'status' => 'active',
    ]);

    $response = $this->actingAs($otherTeacher)->get(route('teacher.advisory.attendance', $section));

    $response->assertForbidden();
});

test('monthly detail shows saved counts and only reports for the selected month and section', function () {
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    EnrollmentMonthlyAttendance::query()->create([
        'enrollment_ID' => $enrollment->enrollment_ID,
        'month' => 6,
        'days_present' => 20,
        'days_absent' => 0,
    ]);
    foreach ([6 => 'June-source.pdf', 7 => 'July-source.pdf'] as $month => $filename) {
        SectionSf2Upload::query()->create([
            'section_ID' => $section->section_ID,
            'SY_ID' => $section->SY_ID,
            'report_month' => $month,
            'original_filename' => $filename,
            'storage_path' => 'sf2/'.$filename,
            'status' => 'pending',
        ]);
    }

    $this->actingAs($teacher)->get(route('teacher.advisory.attendance', [
        'section' => $section, 'view' => 'detailed', 'month' => 6,
    ]))->assertOk()
        ->assertSee('Detailed Monthly Attendance Record')
        ->assertViewHas('selectedUpload', fn ($upload) => $upload->original_filename === 'June-source.pdf')
        ->assertDontSee('July-source.pdf')
        ->assertDontSee('Uploaded attendance records')
        ->assertDontSee('Saved monthly counts')
        ->assertViewHas('rows', fn ($rows) => $rows->first()['records']->get(6)->days_absent === 0);

    $this->get(route('teacher.advisory.attendance', [
        'section' => $section, 'view' => 'detailed', 'month' => 99,
    ]))->assertOk()->assertViewHas('selectedMonth', 6);

    $this->get(route('teacher.advisory.attendance', [
        'section' => $section, 'view' => 'detailed', 'month' => 8,
    ]))->assertOk()->assertSee('No attendance record uploaded for');
});

test('uploaded sf2 opens in monthly detail and its pdf is restricted to the adviser and section', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();
    $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 7,
        'sf2_file' => UploadedFile::fake()->create('July.pdf', 10, 'application/pdf'),
    ])->assertRedirect(route('teacher.advisory.attendance', [
        'section' => $section, 'view' => 'detailed', 'month' => 7, 'upload' => SectionSf2Upload::query()->firstOrFail()->id,
    ]));

    $upload = SectionSf2Upload::query()->firstOrFail();
    $url = route('teacher.advisory.attendance.sf2.view', ['section' => $section, 'upload' => $upload]);
    $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');

    $otherSection = $section->replicate();
    $otherSection->name = 'Other section';
    $otherSection->save();
    $this->get(route('teacher.advisory.attendance.sf2.view', [
        'section' => $otherSection, 'upload' => $upload,
    ]))->assertNotFound();

    $section->update(['staff_ID' => null]);
    $this->get($url)->assertForbidden();
    $section->update(['staff_ID' => $teacher->staff_id]);
    Storage::disk('local')->delete($upload->storage_path);
    $this->get($url)->assertNotFound();
});

function sampleSf2ImportReport(): array
{
    static $report;

    return $report ??= (new Sf2AttendancePdf)->read(base_path('tests/Fixtures/sf2-september-2026-sample.pdf'));
}

function createSf2ImportFixtures(): array
{
    $fixtures = createAdvisoryAttendanceFixtures();
    $fixtures['section']->update(['name' => 'G7-A']);
    foreach (sampleSf2ImportReport()['rows'] as $index => $row) {
        [$last, $given] = explode(', ', $row['name'], 2);
        $parts = explode(' ', $given);
        $middle = array_pop($parts);
        $attributes = [
            'lrn' => (string) (900000000000 + $index),
            'first_name' => implode(' ', $parts),
            'middle_name' => $index === 0 ? 'Garcia' : $middle,
            'last_name' => $last,
            'status' => 'active',
        ];
        if ($index === 0) {
            $fixtures['enrollment']->student->update($attributes);
        } else {
            $student = Student::query()->create($attributes);
            $enrollment = $fixtures['enrollment']->replicate();
            $enrollment->student_ID = $student->id;
            $enrollment->save();
        }
    }

    return $fixtures;
}

function uploadSampleSf2($test, Staff $teacher, Section $section, array $extra = [])
{
    return $test->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), $extra + [
        'report_month' => 9,
        'sf2_file' => new UploadedFile(base_path('tests/Fixtures/sf2-september-2026-sample.pdf'), 'September-SF2.pdf', 'application/pdf', null, true),
    ]);
}

test('sf2 import populates monthly detail overview and printable sf9 with source counts', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createSf2ImportFixtures();
    AcademicYearAttendanceSetting::factory()->create(['SY_ID' => $section->SY_ID, 'month' => 9, 'school_days' => 20]);

    uploadSampleSf2($this, $teacher, $section)->assertRedirect()->assertSessionHas('status');
    $upload = SectionSf2Upload::query()->firstOrFail();
    expect($upload->status)->toBe('imported')
        ->and($upload->school_days)->toBe(22)
        ->and($upload->import_rows)->toHaveCount(15)
        ->and($upload->imported_at)->not->toBeNull()
        ->and(EnrollmentMonthlyAttendance::query()->count())->toBe(15);
    $record = EnrollmentMonthlyAttendance::query()->where('enrollment_ID', $enrollment->enrollment_ID)->firstOrFail();
    expect($record->days_present)->toBe(21)
        ->and($record->days_absent)->toBe(1)
        ->and($record->source_sf2_upload_id)->toBe($upload->id);
    expect(Sf9AttendanceSummary::schoolDaysForSection($section)[9])->toBe(22)
        ->and(Sf9AttendanceSummary::schoolDaysForAcademicYear($section->SY_ID)[9])->toBe(20);

    $this->get(route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => 9]))
        ->assertOk()->assertSee('Recreated from September-SF2.pdf')->assertSee('Santos, Adrian Mae A.')
        ->assertDontSee('Uploaded attendance records')->assertDontSee('Saved monthly counts')
        ->assertSee('Daily Attendance Report of Learners')
        ->assertSee('Combined total present per day')
        ->assertDontSee('sf2-monthly-summary-title')
        ->assertDontSee('View month</button>', false)
        ->assertSee('Monthly attendance highlights')
        ->assertSee('Average daily attendance')
        ->assertSee('data-test="attendance-upload-status"', false)
        ->assertSee('SF2 attendance uploaded successfully.')
        ->assertDontSee('learner records imported')
        ->assertSee('96.06%')->assertSee('14.41')
        ->assertSee('Late arrival');
    $this->get(route('teacher.advisory.attendance', $section))
        ->assertOk()->assertViewHas('rows', fn ($rows) => $rows->sum(fn ($row) => $row['summary']['total_present']) === 317);
    $this->get(route('teacher.advisory.sf9', $section))
        ->assertOk()->assertSee('22')->assertSee('21');
    $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, Sf9AttendanceSummary::schoolDaysForSection($section));
    expect($summary['days_present'][9])->toBe(21)->and($summary['days_absent'][9])->toBe(1)->and($summary['total_school_days'])->toBe(22);
});

test('corrected uploads replace monthly counts without duplicate records and keep old source snapshots', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createSf2ImportFixtures();
    uploadSampleSf2($this, $teacher, $section);
    $original = SectionSf2Upload::query()->firstOrFail();
    $report = sampleSf2ImportReport();
    $report['rows'][0]['days_present'] = 22;
    $report['rows'][0]['days_absent'] = 0;
    $this->mock(Sf2AttendancePdf::class)->shouldReceive('read')->once()->andReturn($report);

    uploadSampleSf2($this, $teacher, $section)->assertSessionHas('status');
    $latest = SectionSf2Upload::query()->latest('id')->firstOrFail();
    $record = EnrollmentMonthlyAttendance::query()->where('enrollment_ID', $enrollment->enrollment_ID)->firstOrFail();
    expect(EnrollmentMonthlyAttendance::query()->count())->toBe(15)
        ->and($record->days_present)->toBe(22)->and($record->days_absent)->toBe(0)
        ->and($record->source_sf2_upload_id)->toBe($latest->id)
        ->and($original->fresh()->import_rows[0]['days_absent'])->toBe(1);
    $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, Sf9AttendanceSummary::schoolDaysForSection($section));
    expect($summary['days_absent'][9])->toBe(0)->and($summary['total_absent'])->toBe(0);
});

test('report month section and school year mismatches leave attendance unchanged', function (string $mismatch) {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section] = createSf2ImportFixtures();
    $report = sampleSf2ImportReport();
    $report[$mismatch] = match ($mismatch) {
        'month' => 8,
        'section' => '7-B',
        'school_year' => '2025-2026',
        'year' => 2025,
    };
    $this->mock(Sf2AttendancePdf::class)->shouldReceive('read')->once()->andReturn($report);
    uploadSampleSf2($this, $teacher, $section)->assertSessionHas('attendance_import_error');
    expect(SectionSf2Upload::query()->firstOrFail()->status)->toBe('failed')
        ->and(EnrollmentMonthlyAttendance::query()->count())->toBe(0)
        ->and(SectionAttendanceSetting::query()->count())->toBe(0);
})->with(['month', 'section', 'school_year', 'year']);

test('the removed school year override cannot bypass matching the section school year', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section] = createSf2ImportFixtures();
    $section->academicYear->update(['school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31']);
    $this->mock(Sf2AttendancePdf::class)->shouldReceive('read')->once()->andReturn(sampleSf2ImportReport());
    $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section))
        ->assertOk()->assertDontSee('name="use_section_school_year"', false);
    uploadSampleSf2($this, $teacher, $section, ['use_section_school_year' => '1'])->assertSessionHas('attendance_import_error');
    $upload = SectionSf2Upload::query()->firstOrFail();
    expect($upload->status)->toBe('failed')
        ->and($upload->use_section_school_year)->toBeFalse()
        ->and($upload->parse_notes)->toContain('Upload an SF2 file with the matching school year')
        ->and(EnrollmentMonthlyAttendance::query()->count())->toBe(0);
});

test('unmatched and ambiguous names are visible and never assigned to another learner', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createSf2ImportFixtures();
    $duplicate = $enrollment->student->replicate();
    $duplicate->lrn = '999999999999';
    $duplicate->save();
    $duplicateEnrollment = $enrollment->replicate();
    $duplicateEnrollment->student_ID = $duplicate->id;
    $duplicateEnrollment->save();
    $report = sampleSf2ImportReport();
    $report['rows'][1]['name'] = 'Unknown, Learner Q.';
    $this->mock(Sf2AttendancePdf::class)->shouldReceive('read')->once()->andReturn($report);

    uploadSampleSf2($this, $teacher, $section)->assertSessionHas('status');
    $upload = SectionSf2Upload::query()->firstOrFail();
    expect($upload->status)->toBe('partial')
        ->and($upload->import_rows[0]['match_status'])->toContain('Ambiguous')
        ->and($upload->import_rows[1]['match_status'])->toContain('No matching')
        ->and(EnrollmentMonthlyAttendance::query()->count())->toBe(13)
        ->and(EnrollmentMonthlyAttendance::query()->where('enrollment_ID', $enrollment->enrollment_ID)->exists())->toBeFalse();
    $this->get(route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => 9]))
        ->assertOk()->assertSee('Unknown, Learner Q.')
        ->assertViewHas('selectedUpload', fn ($upload) => $upload->status === 'partial');
});

test('no matching names leaves existing records and class days unchanged', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    $section->update(['name' => 'G7-A']);
    EnrollmentMonthlyAttendance::query()->create(['enrollment_ID' => $enrollment->enrollment_ID, 'month' => 9, 'days_present' => 18, 'days_absent' => 2]);
    $this->mock(Sf2AttendancePdf::class)->shouldReceive('read')->once()->andReturn(sampleSf2ImportReport());

    uploadSampleSf2($this, $teacher, $section)->assertSessionHas('attendance_import_error');
    $upload = SectionSf2Upload::query()->firstOrFail();
    expect($upload->status)->toBe('failed')->and($upload->import_rows[0]['match_status'])->toContain('No matching')
        ->and(EnrollmentMonthlyAttendance::query()->firstOrFail()->days_present)->toBe(18)
        ->and(SectionAttendanceSetting::query()->count())->toBe(0);
});

test('teacher can upload LIS Excel attendance and use its present column in SF9', function () {
    \App\Models\Sf2Configuration::create(['format' => 'lis']);
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    $response = $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 9,
        'sf2_file' => new UploadedFile(base_path('tests/Fixtures/sf2-lis-september-2026.xls'), 'SF2.xls', 'application/vnd.ms-excel', null, true),
    ]);
    $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('status');
    $upload = SectionSf2Upload::query()->firstOrFail();
    expect($upload->status)->toBe('partial')->and($upload->storage_path)->toEndWith('.xls');
    Storage::disk('local')->assertExists($upload->storage_path);
    $record = EnrollmentMonthlyAttendance::query()->firstOrFail();
    expect($record->days_present)->toBe(3)->and($record->days_absent)->toBe(1)
        ->and($record->days_tardy)->toBe(1)->and($record->source_sf2_upload_id)->toBe($upload->id);
    $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, Sf9AttendanceSummary::schoolDaysForSection($section));
    expect($summary['days_present'][9])->toBe(3)->and($summary['days_absent'][9])->toBe(1);
    $this->get(route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => 9]))
        ->assertOk()->assertSee('Recreated from SF2.xls')->assertSee('Santos,Maria');
});

test('Excel month mismatch preserves previously saved attendance', function () {
    \App\Models\Sf2Configuration::create(['format' => 'lis']);
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    EnrollmentMonthlyAttendance::query()->create(['enrollment_ID' => $enrollment->enrollment_ID, 'month' => 8, 'days_present' => 18, 'days_absent' => 2]);
    $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 8,
        'sf2_file' => new UploadedFile(base_path('tests/Fixtures/sf2-lis-september-2026.xls'), 'SF2.xls', 'application/vnd.ms-excel', null, true),
    ])->assertSessionHas('attendance_import_error');
    expect(EnrollmentMonthlyAttendance::query()->sole()->days_present)->toBe(18)
        ->and(SectionAttendanceSetting::query()->count())->toBe(0);
});

test('SF2 selection checks form columns rather than restricting PDF or Excel extensions', function (string $format, string $rejected, string $mime) {
    Storage::fake('local');
    \App\Models\Sf2Configuration::create(['format' => $format]);
    ['teacher' => $teacher, 'section' => $section] = createAdvisoryAttendanceFixtures();
    $this->actingAs($teacher)->get(route('teacher.advisory.attendance', $section))->assertOk()
        ->assertSee('accept="application/pdf,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,.pdf,.xls,.xlsx"', false);
    $this->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 9,
        'sf2_file' => new UploadedFile(base_path('tests/Fixtures/'.$rejected), $rejected, $mime, null, true),
    ])->assertSessionHasNoErrors()->assertSessionHas('attendance_import_error');
    $upload = SectionSf2Upload::query()->sole();
    expect($upload->status)->toBe('failed')->and($upload->parse_notes)->toContain('different SF2 form');
    expect(EnrollmentMonthlyAttendance::query()->count())->toBe(0);
})->with([
    ['legacy', 'sf2-lis-september-2026.xls', 'application/vnd.ms-excel'],
    ['lis', 'sf2-september-2026-sample.pdf', 'application/pdf'],
]);

test('switching SF2 formats changes existing detail columns without modifying attendance or SF9', function () {
    Storage::fake('local');
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createSf2ImportFixtures();
    uploadSampleSf2($this, $teacher, $section)->assertSessionHas('status');
    $before = EnrollmentMonthlyAttendance::query()->get()->toArray();
    $url = route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => 9]);
    $this->get($url)->assertOk()->assertSee('data-sf2-format="legacy"', false)
        ->assertSee('data-test="sf2-second-total">Tardy</th>', false);
    $config = \App\Models\Sf2Configuration::create(['format' => 'lis']);
    $this->get($url)->assertOk()->assertSee('data-sf2-format="lis"', false)
        ->assertSee('data-test="sf2-second-total">Present</th>', false)
        ->assertDontSee('data-test="sf2-second-total">Tardy</th>', false);
    $config->update(['format' => 'legacy']);
    $this->get($url)->assertOk()->assertSee('data-test="sf2-second-total">Tardy</th>', false);
    expect(EnrollmentMonthlyAttendance::query()->get()->toArray())->toBe($before);
    $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, Sf9AttendanceSummary::schoolDaysForSection($section));
    expect($summary['days_present'][9])->toBe(21)->and($summary['days_absent'][9])->toBe(1);
});

test('new form sample imports from PDF and XLSX into the same monthly record', function () {
    Storage::fake('local');
    \App\Models\Sf2Configuration::create(['format' => 'lis']);
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    $section->update(['name' => 'G7-E']);
    $section->academicYear->update(['school_year' => '2025-2026', 'start_date' => '2025-06-01', 'end_date' => '2026-03-31']);
    $enrollment->student->update(['last_name' => 'Bautista', 'first_name' => 'Gabriel Joy', 'middle_name' => 'A.']);
    foreach (['pdf' => 'application/pdf', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'] as $extension => $mime) {
        $file = 'sf2-lis-june-2025-sample.'.$extension;
        $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
            'report_month' => 6,
            'sf2_file' => new UploadedFile(base_path('tests/Fixtures/'.$file), $file, $mime, null, true),
        ])->assertSessionHasNoErrors()->assertSessionHas('status');
        $upload = SectionSf2Upload::query()->latest('id')->firstOrFail();
        expect($upload->status)->toBe('partial')->and($upload->import_rows)->toHaveCount(15)
            ->and($upload->school_days)->toBe(21)->and($upload->storage_path)->toEndWith('.'.$extension);
        $record = EnrollmentMonthlyAttendance::query()->sole();
        expect($record->days_present)->toBe(13)->and($record->days_absent)->toBe(8)
            ->and($record->days_tardy)->toBe(0)->and($record->source_sf2_upload_id)->toBe($upload->id);
        $this->get(route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => 6]))
            ->assertOk()->assertSee('data-test="sf2-second-total">Present</th>', false)->assertSee('76.51%')->assertSee('11.48');
        $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, Sf9AttendanceSummary::schoolDaysForSection($section));
        expect($summary['days_present'][6])->toBe(13)->and($summary['days_absent'][6])->toBe(8);
    }
    expect(SectionSf2Upload::query()->count())->toBe(2);
});

test('old SF2 accepts Excel uploads and retains tardy separately from present counts', function (string $extension, string $mime) {
    Storage::fake('local');
    \App\Models\Sf2Configuration::create(['format' => 'legacy']);
    ['teacher' => $teacher, 'section' => $section, 'enrollment' => $enrollment] = createAdvisoryAttendanceFixtures();
    $file = 'sf2-legacy-september-2026.'.$extension;
    $this->actingAs($teacher)->post(route('teacher.advisory.attendance.sf2', $section), [
        'report_month' => 9,
        'sf2_file' => new UploadedFile(base_path('tests/Fixtures/'.$file), $file, $mime, null, true),
    ])->assertSessionHasNoErrors()->assertSessionHas('status');
    $upload = SectionSf2Upload::query()->sole();
    expect($upload->status)->toBe('partial')->and($upload->storage_path)->toEndWith('.'.$extension);
    $record = EnrollmentMonthlyAttendance::query()->sole();
    expect($record->days_present)->toBe(3)->and($record->days_absent)->toBe(1)->and($record->days_tardy)->toBe(1);
    $this->get(route('teacher.advisory.attendance', ['section' => $section, 'view' => 'detailed', 'month' => 9]))
        ->assertOk()->assertSee('data-sf2-format="legacy"', false)->assertSee('data-test="sf2-second-total">Tardy</th>', false);
    $summary = Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, Sf9AttendanceSummary::schoolDaysForSection($section));
    expect($summary['days_present'][9])->toBe(3)->and($summary['days_absent'][9])->toBe(1);
})->with([
    ['xls', 'application/vnd.ms-excel'],
    ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
]);
