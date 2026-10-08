<?php

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use Database\Seeders\AcademicYearSeeder;
use Database\Seeders\ClusterSeeder;
use Database\Seeders\CurriculumSeeder;
use Database\Seeders\DefaultNonStudentUsersSeeder;
use Database\Seeders\GradeLevelSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SectionSeeder;

test('section seeder creates junior high sections and one section per senior high offering', function () {
    $this->seed([
        ClusterSeeder::class,
        AcademicYearSeeder::class,
        CurriculumSeeder::class,
        GradeLevelSeeder::class,
        SectionSeeder::class,
    ]);

    $academicYearId = AcademicYear::query()
        ->where('school_year', '2026-2027')
        ->value('SY_ID');

    $sections = Section::query()
        ->where('SY_ID', $academicYearId)
        ->with('gradeLevel', 'curriculum', 'cluster')
        ->orderBy('name')
        ->get();

    $seniorHighDefinitions = DefaultNonStudentUsersSeeder::seniorHighSectionDefinitions();
    $juniorHighSections = $sections->filter(fn (Section $section): bool => in_array($section->grade_level, ['grade_7', 'grade_8', 'grade_9', 'grade_10'], true));
    $seniorHighSections = $sections->filter(fn (Section $section): bool => in_array($section->grade_level, ['grade_11', 'grade_12'], true));

    expect($sections)->toHaveCount(20 + count($seniorHighDefinitions))
        ->and($juniorHighSections)->toHaveCount(20)
        ->and($juniorHighSections->every(fn (Section $section): bool => $section->cluster_ID === null))->toBeTrue()
        ->and($seniorHighSections)->toHaveCount(count($seniorHighDefinitions))
        ->and($seniorHighSections->pluck('name')->unique())->toHaveCount(count($seniorHighDefinitions));

    foreach ([7, 8, 9, 10] as $grade) {
        $names = $sections
            ->filter(fn (Section $section): bool => $section->grade_level === 'grade_'.$grade)
            ->pluck('name')
            ->values()
            ->all();

        expect($names)->toBe([
            "G{$grade}-A",
            "G{$grade}-B",
            "G{$grade}-C",
            "G{$grade}-D",
            "G{$grade}-E",
        ])
            ->and($sections->firstWhere('name', "G{$grade}-A")?->curriculum?->name)->toBe("Grade {$grade}");
    }

    foreach ($seniorHighDefinitions as $definition) {
        $section = $sections->firstWhere('name', $definition['name']);
        $grade = (int) str_replace('grade_', '', $definition['grade_level']);

        expect($section)->not->toBeNull()
            ->and($section->cluster?->name)->toBe($definition['cluster'])
            ->and($section->curriculum?->name)->toBe(CurriculumSeeder::seniorHighCurriculumName(
                $grade,
                ucfirst($definition['semester']),
                $definition['cluster'],
            ));
    }
});

test('section seeder removes leftover lettered sections that have no enrollments', function () {
    $this->seed([
        ClusterSeeder::class,
        AcademicYearSeeder::class,
        CurriculumSeeder::class,
        GradeLevelSeeder::class,
    ]);

    $academicYearId = AcademicYear::query()
        ->where('school_year', '2026-2027')
        ->value('SY_ID');
    $curriculumId = Curriculum::query()
        ->where('name', 'Grade 7')
        ->value('curriculum_ID');
    $grade7Id = GradeLevel::query()->where('grade_label', 'Grade 7')->value('grade_ID');
    $grade11Id = GradeLevel::query()->where('grade_label', 'Grade 11')->value('grade_ID');

    Section::query()->create([
        'name' => 'G7-F',
        'grade_ID' => $grade7Id,
        'SY_ID' => $academicYearId,
        'curriculum_ID' => $curriculumId,
        'capacity' => 45,
        'status' => true,
    ]);

    Section::query()->create([
        'name' => 'G11-ASSH-A',
        'grade_ID' => $grade11Id,
        'SY_ID' => $academicYearId,
        'curriculum_ID' => $curriculumId,
        'capacity' => 45,
        'status' => true,
    ]);

    $kept = Section::query()->create([
        'name' => 'G11-ASSH-B',
        'grade_ID' => $grade11Id,
        'SY_ID' => $academicYearId,
        'curriculum_ID' => $curriculumId,
        'capacity' => 45,
        'status' => true,
    ]);

    $student = Student::query()->create([
        'lrn' => '611111111111',
        'first_name' => 'Kept',
        'last_name' => 'Learner',
        'status' => 'approved',
    ]);

    Enrollment::query()->create([
        'student_ID' => $student->id,
        'section_ID' => $kept->section_ID,
        'SY_ID' => $academicYearId,
        'grade_ID' => $grade11Id,
        'learner_type' => 'regular',
        'enrollment_status' => 'enrolled',
    ]);

    $this->seed(SectionSeeder::class);

    expect(Section::query()->where('name', 'G7-F')->exists())->toBeFalse()
        ->and(Section::query()->where('name', 'G11-ASSH-A')->exists())->toBeFalse()
        ->and(Section::query()->where('name', 'G11-ASSH-B')->exists())->toBeTrue()
        ->and(Section::query()->where('name', 'G11-A')->exists())->toBeFalse()
        ->and(Section::query()->where('name', 'G11-ASSH-1ST')->exists())->toBeTrue();
});

test('section seeder assigns a unique adviser to each section', function () {
    $this->seed([
        RoleSeeder::class,
        DefaultNonStudentUsersSeeder::class,
        ClusterSeeder::class,
        AcademicYearSeeder::class,
        CurriculumSeeder::class,
        GradeLevelSeeder::class,
        SectionSeeder::class,
    ]);

    $academicYearId = AcademicYear::query()
        ->where('school_year', '2026-2027')
        ->value('SY_ID');

    $sections = Section::query()
        ->where('SY_ID', $academicYearId)
        ->get();

    $adviserIds = $sections->pluck('staff_ID');

    $expectedSectionCount = 20 + count(DefaultNonStudentUsersSeeder::seniorHighSectionDefinitions());

    expect($sections)->toHaveCount($expectedSectionCount)
        ->and($adviserIds->filter())->toHaveCount($expectedSectionCount)
        ->and($adviserIds->filter()->unique())->toHaveCount($expectedSectionCount);

    foreach (DefaultNonStudentUsersSeeder::sectionTeacherUsernames() as $sectionName => $username) {
        $teacherId = Staff::query()->where('username', $username)->value('staff_id');
        $section = $sections->firstWhere('name', $sectionName);

        expect($section)->not->toBeNull()
            ->and($section->staff_ID)->toBe($teacherId);
    }
});
