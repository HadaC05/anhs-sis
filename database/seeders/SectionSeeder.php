<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
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

        $gradeMap = GradeLevel::query()
            ->get()
            ->mapWithKeys(fn (GradeLevel $grade): array => [$grade->value => $grade->grade_ID]);

        $gradeLevels = DefaultNonStudentUsersSeeder::GRADE_LEVELS;

        foreach ($gradeLevels as $gradeLevel) {
            if (! isset($gradeMap[$gradeLevel])) {
                throw new RuntimeException("Missing grade level: {$gradeLevel}. Run GradeLevelSeeder first.");
            }
        }

        $fullYearSemesterId = GradingSemester::idFor(GradingSemester::FULL_YEAR);
        $firstSemesterId = GradingSemester::idFor(GradingSemester::FIRST);
        $secondSemesterId = GradingSemester::idFor(GradingSemester::SECOND);

        if (! $fullYearSemesterId || ! $firstSemesterId || ! $secondSemesterId) {
            throw new RuntimeException('Missing required cluster or grading semester. Run the reference seeders first.');
        }

        $clusterIds = Cluster::query()->pluck('cluster_ID', 'name');

        foreach (CurriculumSeeder::SENIOR_HIGH_TRACKS as $track) {
            if (! isset($clusterIds[$track])) {
                throw new RuntimeException("Missing senior high cluster: {$track}. Run ClusterSeeder first.");
            }
        }

        $teacherUsernames = DefaultNonStudentUsersSeeder::sectionTeacherUsernames();
        $teacherIdsByUsername = Staff::query()
            ->whereIn('username', array_values($teacherUsernames))
            ->pluck('staff_id', 'username');

        $sections = [];

        foreach (array_keys(CurriculumSeeder::JUNIOR_HIGH_NAMES) as $gradeLevel) {
            foreach (DefaultNonStudentUsersSeeder::SECTION_LETTERS as $letter) {
                $sections[] = [
                    'name' => DefaultNonStudentUsersSeeder::sectionNameFor($gradeLevel, $letter),
                    'grade_level' => $gradeLevel,
                    'semester_ID' => $fullYearSemesterId,
                    'cluster_ID' => null,
                ];
            }
        }

        foreach (DefaultNonStudentUsersSeeder::seniorHighSectionDefinitions() as $section) {
            $sections[] = [
                'name' => $section['name'],
                'grade_level' => $section['grade_level'],
                'semester_ID' => $section['semester'] === 'first' ? $firstSemesterId : $secondSemesterId,
                'cluster_ID' => $clusterIds[$section['track']],
            ];
        }

        foreach ($sections as $section) {
            $offering = Curriculum::query()
                ->where('grade_ID', $gradeMap[$section['grade_level']])
                ->where('semester_ID', $section['semester_ID'])
                ->when(
                    $section['cluster_ID'],
                    fn ($query) => $query->where('cluster_ID', $section['cluster_ID']),
                    fn ($query) => $query->whereNull('cluster_ID'),
                )
                ->first();

            if (! $offering) {
                throw new RuntimeException("Unable to resolve a curriculum grade level for {$section['grade_level']}.");
            }

            Section::query()->updateOrCreate(
                [
                    'name' => $section['name'],
                    'SY_ID' => $academicYearId,
                ],
                [
                    'cluster_ID' => $offering->cluster_ID,
                    'grade_ID' => $offering->grade_ID,
                    'staff_ID' => isset($teacherUsernames[$section['name']])
                        ? ($teacherIdsByUsername[$teacherUsernames[$section['name']]] ?? null)
                        : null,
                    'curriculum_grade_level_ID' => $offering->curriculum_ID,
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
