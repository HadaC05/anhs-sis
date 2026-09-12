<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Database\Seeder;
use RuntimeException;

class SectionSeeder extends Seeder
{
    /**
     * Seed the application's sections table.
     */
    public function run(): void
    {
        $academicYearId = AcademicYear::query()
            ->where('school_year', '2026-2027')
            ->value('SY_ID');

        if (! $academicYearId) {
            throw new RuntimeException('Missing academic year: 2026-2027. Run AcademicYearSeeder first.');
        }

        $juniorHighCurriculumIds = Curriculum::query()
            ->whereIn('name', array_values(CurriculumSeeder::JUNIOR_HIGH_NAMES))
            ->pluck('curriculum_ID', 'name');

        foreach (CurriculumSeeder::JUNIOR_HIGH_NAMES as $gradeLevel => $curriculumName) {
            if (! isset($juniorHighCurriculumIds[$curriculumName])) {
                throw new RuntimeException("Missing curriculum: {$curriculumName}. Run CurriculumSeeder first.");
            }
        }

        $seniorHighCurriculumId = Curriculum::query()
            ->whereIn('name', Cluster::query()->pluck('name'))
            ->orderBy('name')
            ->value('curriculum_ID');

        if (! $seniorHighCurriculumId) {
            throw new RuntimeException('Missing senior high cluster curriculum. Run ClusterSeeder and CurriculumSeeder first.');
        }

        $gradeMap = GradeLevel::query()
            ->get()
            ->mapWithKeys(fn (GradeLevel $grade): array => [$grade->value => $grade->grade_ID]);

        $gradeLevels = DefaultNonStudentUsersSeeder::GRADE_LEVELS;

        foreach ($gradeLevels as $gradeLevel) {
            if (! isset($gradeMap[$gradeLevel])) {
                throw new RuntimeException("Missing grade level: {$gradeLevel}. Run GradeLevelSeeder first.");
            }
        }

        $teacherUsernames = DefaultNonStudentUsersSeeder::sectionTeacherUsernames();
        $teacherIdsByUsername = Staff::query()
            ->whereIn('username', array_values($teacherUsernames))
            ->pluck('staff_id', 'username');

        $sections = [];

        foreach ($gradeLevels as $gradeLevel) {
            foreach (DefaultNonStudentUsersSeeder::SECTION_LETTERS as $letter) {
                $sections[] = [
                    'name' => DefaultNonStudentUsersSeeder::sectionNameFor($gradeLevel, $letter),
                    'grade_level' => $gradeLevel,
                ];
            }
        }

        foreach ($sections as $section) {
            $teacherUsername = $teacherUsernames[$section['name']];
            $isJuniorHigh = isset(CurriculumSeeder::JUNIOR_HIGH_NAMES[$section['grade_level']]);
            $curriculumId = $isJuniorHigh
                ? $juniorHighCurriculumIds[CurriculumSeeder::JUNIOR_HIGH_NAMES[$section['grade_level']]]
                : $seniorHighCurriculumId;

            if (! $curriculumId) {
                throw new RuntimeException("Unable to resolve curriculum for {$section['grade_level']}.");
            }

            Section::query()->updateOrCreate(
                [
                    'name' => $section['name'],
                    'SY_ID' => $academicYearId,
                ],
                [
                    'cluster_ID' => null,
                    'grade_ID' => $gradeMap[$section['grade_level']],
                    'staff_ID' => $teacherIdsByUsername[$teacherUsername] ?? null,
                    'curriculum_ID' => $curriculumId,
                    'room' => null,
                    'capacity' => 45,
                ],
            );
        }

        $desiredNames = collect($sections)->pluck('name')->all();
        Section::query()
            ->where('SY_ID', $academicYearId)
            ->whereDoesntHave('enrollments')
            ->where(function ($query): void {
                $query->where('name', 'like', 'G7-%')
                    ->orWhere('name', 'like', 'G8-%')
                    ->orWhere('name', 'like', 'G9-%')
                    ->orWhere('name', 'like', 'G10-%')
                    ->orWhere('name', 'like', 'G11-%')
                    ->orWhere('name', 'like', 'G12-%');
            })
            ->whereNotIn('name', $desiredNames)
            ->delete();
    }
}
