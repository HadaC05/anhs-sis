<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Role;
use App\Models\SchoolInformation;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Support\ClassListSpreadsheet;
use App\Support\Sf1Workbook;

function sf1Fixtures(): array
{
    $role = Role::query()->create(['role_name' => 'teacher']);
    $teacher = Staff::query()->create(['role_id' => $role->id, 'username' => 'sf1.teacher', 'password' => 'password', 'first_name' => 'Ada', 'last_name' => 'Teacher', 'status' => 'active']);
    $year = AcademicYear::query()->create(['school_year' => '2026-2027', 'start_date' => '2026-06-01', 'end_date' => '2027-03-31', 'status' => true]);
    $curriculum = Curriculum::query()->create(['name' => 'STEM', 'description' => 'Test curriculum', 'status' => true]);
    $grade = GradeLevel::query()->where('grade_label', 'Grade 11')->firstOrFail();
    $section = Section::query()->create(['name' => '11-A', 'grade_ID' => $grade->grade_ID, 'SY_ID' => $year->SY_ID, 'curriculum_ID' => $curriculum->curriculum_ID, 'staff_ID' => $teacher->staff_id, 'capacity' => 100]);
    $students = collect();
    foreach (['male', 'female', null] as $i => $sex) {
        $student = Student::query()->create(['lrn' => '01234567890'.$i, 'first_name' => $i === 0 ? '=Excel & Text' : 'Learner '.$i, 'last_name' => 'Register', 'middle_name' => 'Middle', 'sex' => $sex, 'birthdate' => '2009-07-04', 'religion' => 'Catholic', 'status' => 'active']);
        Enrollment::query()->create(['student_ID' => $student->id, 'section_ID' => $section->section_ID, 'SY_ID' => $year->SY_ID, 'enrollment_status' => 'enrolled', 'learner_type' => 'regular']);
        $students->push($student);
    }
    $students[0]->addresses()->create(['address_type' => 'current', 'house_no' => '12', 'street_name' => 'First & Main', 'barangay' => 'Agusan', 'municipality' => 'Cagayan de Oro', 'province' => 'Misamis Oriental']);
    $students[0]->guardians()->create(['first_name' => 'Jose', 'last_name' => 'Parent', 'relationship' => 'father', 'contact_no' => '09123456789']);
    SchoolInformation::query()->updateOrCreate(['id' => 1], ['name' => 'Actual School', 'school_id' => '001234', 'region' => 'XIII', 'division' => 'City', 'district' => 'North']);

    return compact('teacher', 'section', 'students');
}

test('adviser downloads a complete sf1 workbook matching the sample register columns', function () {
    ['teacher' => $teacher, 'section' => $section] = sf1Fixtures();
    $this->actingAs($teacher)->get(route('teacher.advisory.class-list.index', $section))->assertOk()->assertSee('Download SF1 Excel');
    $response = $this->get(route('teacher.advisory.class-list.sf1', ['section' => $section, 'search' => 'does not match', 'sex' => 'female']));
    $response->assertOk()->assertDownload('SF1-11-a-2026-2027.xlsx')->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $path = $response->baseResponse->getFile()->getPathname();
    try {
        $rows = ClassListSpreadsheet::rowsFromPath($path, 'xlsx');
        $importRecords = app(\App\Http\Controllers\Teacher\TeacherSectionController::class)->advisoryClassListRecords($rows);
        expect($importRecords)->toHaveCount(3)
            ->and($importRecords[0]['lrn'])->toBe('012345678900')
            ->and($importRecords[0]['birthdate'])->toBe('2009-07-04');
        $learners = collect($rows)->filter(fn ($row) => preg_match('/^\d{12}$/', (string) ($row[0] ?? '')))->values();
        expect($learners)->toHaveCount(3)
            ->and($learners[0][0])->toBe('012345678900')
            ->and($learners[0][2])->toBe('Register, =Excel & Text Middle')
            ->and($learners[0][6])->toBe('M')
            ->and($learners[0][7])->toBe('07/04/2009')
            ->and($learners[0][9])->toBe('16')
            ->and($learners[0][12])->toBe('12 First & Main')
            ->and($learners[0][22])->toBe('Parent, Jose')
            ->and($learners[0][29])->toBe('09123456789');
        $zip = new ZipArchive;
        $zip->open($path);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($sheet)->toContain('Actual School', '2026-2027', '001234', 'SEX UNSPECIFIED')
            ->not->toContain('Sample SHS', 'Aguilar, Adrian', '<f>');
        expect($zip->getFromName('xl/worksheets/sheet2.xml'))->toBeFalse()
            ->and($zip->getFromName('xl/sharedStrings.xml'))->toBeFalse();
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('management can export the section sf1 from section details', function (string $role) {
    ['section' => $section] = sf1Fixtures();
    $manager = Staff::query()->create([
        'role_id' => Role::query()->firstOrCreate(['role_name' => $role])->id,
        'username' => 'sf1.'.$role,
        'password' => 'password',
        'first_name' => 'School',
        'last_name' => 'Manager',
        'status' => 'active',
    ]);
    $url = route($role.'.section-config.sf1', $section);
    $this->actingAs($manager)->get(route($role.'.section-config.index', ['tab' => 'details', 'section' => $section->section_ID]))
        ->assertOk()->assertSee('Export SF1 Excel')->assertSee($url);
    $response = $this->get($url);
    $response->assertOk()->assertDownload('SF1-11-a-2026-2027.xlsx')
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $path = $response->baseResponse->getFile()->getPathname();
    try {
        $rows = ClassListSpreadsheet::rowsFromPath($path, 'xlsx');
        expect(collect($rows)->filter(fn ($row) => preg_match('/^\d{12}$/', (string) ($row[0] ?? ''))))->toHaveCount(3);
    } finally {
        unlink($path);
    }
})->with(['admin', 'principal']);

test('teachers cannot use management sf1 export routes', function (string $role) {
    ['teacher' => $teacher, 'section' => $section] = sf1Fixtures();
    $this->actingAs($teacher)->get(route($role.'.section-config.sf1', $section))->assertForbidden();
})->with(['admin', 'principal']);

test('sf1 export remains restricted to the assigned adviser', function () {
    ['teacher' => $teacher, 'section' => $section] = sf1Fixtures();
    $other = $teacher->replicate();
    $other->username = 'other.sf1';
    $other->save();
    $this->actingAs($other)->get(route('teacher.advisory.class-list.sf1', $section))->assertForbidden();
});

test('sf1 expands beyond the sample forty learner slots without truncating the roster', function () {
    $rows = collect(range(1, 44))->map(fn ($i) => ['sex' => $i <= 41 ? 'male' : ($i <= 43 ? 'female' : 'unspecified'), 'name' => 'Learner '.$i, 'cells' => ['A' => (string) (900000000000 + $i), 'C' => 'Learner '.$i, 'G' => $i <= 41 ? 'M' : ($i <= 43 ? 'F' : '')]]);
    $path = Sf1Workbook::create($rows, ['A100' => 'Test roster']);
    try {
        $read = ClassListSpreadsheet::rowsFromPath($path, 'xlsx');
        expect(collect($read)->filter(fn ($row) => preg_match('/^\d{12}$/', (string) ($row[0] ?? ''))))->toHaveCount(44);
        $zip = new ZipArchive;
        $zip->open($path);
        $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $xml->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        expect((string) $xml->xpath('//s:c[@r="A52"]/s:v')[0])->toBe('41')
            ->and((string) $xml->xpath('//s:c[@r="A93"]/s:v')[0])->toBe('2')
            ->and((string) $xml->xpath('//s:c[@r="A97"]/s:v')[0])->toBe('44');
        $refs = array_map(fn ($cell) => (string) $cell['r'], $xml->xpath('//s:c'));
        expect(count($refs))->toBe(count(array_unique($refs)));
        $zip->close();
    } finally {
        unlink($path);
    }
});

test('empty sf1 workbook contains only blank learner slots and zero totals', function () {
    $path = Sf1Workbook::create(collect(), ['A100' => 'Empty class']);
    try {
        $rows = ClassListSpreadsheet::rowsFromPath($path, 'xlsx');
        expect(collect($rows)->filter(fn ($row) => preg_match('/^\d{12}$/', (string) ($row[0] ?? ''))))->toHaveCount(0);
    } finally {
        unlink($path);
    }
});
