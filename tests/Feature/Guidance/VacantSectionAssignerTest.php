<?php

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Support\VacantSectionAssigner;

/**
 * @return array{academicYear: AcademicYear, curriculum: Curriculum, gradeLevel: GradeLevel, enrollment: Enrollment, section: Section}
 */
function createVacantSectionFixtures(array $overrides = []): array
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

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 7'],
        ['category' => 'Junior High School']
    );

    $section = Section::query()->create([
        'name' => $overrides['section_name'] ?? 'Newton',
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'room' => 'Room 101',
        'capacity' => $overrides['capacity'] ?? 1,
        'status' => $overrides['status'] ?? true,
    ]);

    $student = Student::query()->create([
        'lrn' => $overrides['lrn'] ?? '611111111111',
        'first_name' => 'Pending',
        'last_name' => 'Learner',
        'status' => 'pending',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => null,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => null,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    return compact('academicYear', 'curriculum', 'gradeLevel', 'enrollment', 'section');
}

function fillSectionToCapacity(Section $section, AcademicYear $academicYear, GradeLevel $gradeLevel, string $lrnPrefix = '622'): void
{
    $needed = max(1, (int) $section->capacity);

    for ($i = 0; $i < $needed; $i++) {
        $student = Student::query()->create([
            'lrn' => $lrnPrefix.str_pad((string) $i, 9, '0', STR_PAD_LEFT),
            'first_name' => 'Enrolled',
            'last_name' => 'Student'.$i,
            'status' => 'approved',
        ]);

        Enrollment::query()->create([
            'student_ID' => $student->id,
            'section_ID' => $section->section_ID,
            'SY_ID' => $academicYear->SY_ID,
            'cluster_ID' => $section->cluster_ID,
            'grade_ID' => $gradeLevel->grade_ID,
            'semester' => null,
            'learner_type' => 'regular',
            'enrollment_status' => 'enrolled',
        ]);
    }
}

test('vacant section assigner keeps using a section that still has room', function () {
    ['enrollment' => $enrollment, 'section' => $section] = createVacantSectionFixtures([
        'capacity' => 2,
    ]);

    $assigned = VacantSectionAssigner::resolve($enrollment);

    expect($assigned)->not->toBeNull()
        ->and($assigned->section_ID)->toBe($section->section_ID)
        ->and($assigned->wasRecentlyCreated)->toBeFalse();
});

test('vacant section assigner creates the next junior high section when matching sections are full', function () {
    ['academicYear' => $academicYear, 'gradeLevel' => $gradeLevel, 'enrollment' => $enrollment, 'section' => $section] = createVacantSectionFixtures([
        'section_name' => 'G7-A',
        'capacity' => 1,
    ]);

    fillSectionToCapacity($section, $academicYear, $gradeLevel);

    $assigned = VacantSectionAssigner::resolve($enrollment);

    expect($assigned)->not->toBeNull()
        ->and($assigned->wasRecentlyCreated)->toBeTrue()
        ->and($assigned->name)->toBe('G7-B')
        ->and($assigned->grade_ID)->toBe($gradeLevel->grade_ID)
        ->and($assigned->SY_ID)->toBe($academicYear->SY_ID)
        ->and($assigned->cluster_ID)->toBeNull()
        ->and($assigned->capacity)->toBe(VacantSectionAssigner::DEFAULT_CAPACITY)
        ->and($assigned->status)->toBeTrue()
        ->and($assigned->section_ID)->not->toBe($section->section_ID);
});

test('vacant section assigner names overflow sections from the grade when existing names are custom', function () {
    ['academicYear' => $academicYear, 'gradeLevel' => $gradeLevel, 'enrollment' => $enrollment, 'section' => $section] = createVacantSectionFixtures([
        'section_name' => 'Newton',
        'capacity' => 1,
    ]);

    fillSectionToCapacity($section, $academicYear, $gradeLevel);

    $assigned = VacantSectionAssigner::resolve($enrollment);

    expect($assigned)->not->toBeNull()
        ->and($assigned->name)->toBe('G7-A')
        ->and($assigned->capacity)->toBe(VacantSectionAssigner::DEFAULT_CAPACITY);
});

test('vacant section assigner creates a senior high overflow section for the same cluster', function () {
    $academicYear = AcademicYear::query()->create([
        'school_year' => '2026-2027',
        'start_date' => '2026-06-01',
        'end_date' => '2027-03-31',
        'status' => true,
    ]);

    $curriculum = Curriculum::query()->create([
        'name' => 'Arts, Social Sciences & Humanities',
        'description' => 'Senior High School cluster curriculum',
        'status' => true,
    ]);

    $cluster = Cluster::query()->create([
        'name' => 'Arts, Social Sciences & Humanities',
    ]);

    $gradeLevel = GradeLevel::query()->firstOrCreate(
        ['grade_label' => 'Grade 11'],
        ['category' => 'Senior High School']
    );

    $section = Section::query()->create([
        'name' => 'G11-ASSH-A',
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'SY_ID' => $academicYear->SY_ID,
        'curriculum_ID' => $curriculum->curriculum_ID,
        'capacity' => 1,
        'status' => true,
    ]);

    fillSectionToCapacity($section, $academicYear, $gradeLevel, '633');

    $student = Student::query()->create([
        'lrn' => '644444444444',
        'first_name' => 'Senior',
        'last_name' => 'Pending',
        'status' => 'pending',
    ]);

    $enrollment = Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => null,
        'SY_ID' => $academicYear->SY_ID,
        'cluster_ID' => $cluster->cluster_ID,
        'grade_ID' => $gradeLevel->grade_ID,
        'semester' => 1,
        'learner_type' => 'regular',
        'enrollment_status' => 'pending',
    ]);

    $assigned = VacantSectionAssigner::resolve($enrollment);

    expect($assigned)->not->toBeNull()
        ->and($assigned->name)->toBe('G11-ASSH-B')
        ->and($assigned->cluster_ID)->toBe($cluster->cluster_ID)
        ->and($assigned->curriculum_ID)->toBe($curriculum->curriculum_ID)
        ->and($assigned->capacity)->toBe(VacantSectionAssigner::DEFAULT_CAPACITY);
});

test('vacant section assigner creates a new active section when the only matching section is archived', function () {
    ['academicYear' => $academicYear, 'gradeLevel' => $gradeLevel, 'enrollment' => $enrollment, 'section' => $section] = createVacantSectionFixtures([
        'section_name' => 'G7-A',
        'capacity' => 40,
        'status' => false,
    ]);

    $assigned = VacantSectionAssigner::resolve($enrollment);

    expect($assigned)->not->toBeNull()
        ->and($assigned->name)->toBe('G7-B')
        ->and($assigned->status)->toBeTrue()
        ->and($assigned->section_ID)->not->toBe($section->section_ID)
        ->and($section->fresh()->enrollments()->count())->toBe(0);
});
