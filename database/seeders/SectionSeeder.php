<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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

        $curriculumMap = Curriculum::query()
            ->whereIn('name', ['DepEd SHS - ABM', 'DepEd SHS - STEM', 'DepEd SHS - HUMSS', 'DepEd SHS - GAS'])
            ->pluck('curriculum_ID', 'name');

        $clusterMap = Cluster::query()
            ->whereIn('name', [
                'Arts, Social Sciences & Humanities',
                'Business and Entrepreneurship',
                'Science, Technology, Engineering and Mathematics',
            ])
            ->pluck('cluster_ID', 'name');

        $gradeMap = GradeLevel::query()
            ->get()
            ->mapWithKeys(fn (GradeLevel $grade): array => [$grade->value => $grade->grade_ID]);

        foreach (['DepEd SHS - ABM', 'DepEd SHS - STEM', 'DepEd SHS - HUMSS', 'DepEd SHS - GAS'] as $curriculumName) {
            if (! isset($curriculumMap[$curriculumName])) {
                throw new RuntimeException("Missing curriculum: {$curriculumName}. Run CurriculumSeeder first.");
            }
        }

        foreach ([
            'Arts, Social Sciences & Humanities',
            'Business and Entrepreneurship',
            'Science, Technology, Engineering and Mathematics',
        ] as $clusterName) {
            if (! isset($clusterMap[$clusterName])) {
                throw new RuntimeException("Missing cluster: {$clusterName}. Run ClusterSeeder first.");
            }
        }

        $alphabet = range('A', 'Z');

        $sections = [];

        foreach (['grade_7', 'grade_8', 'grade_9', 'grade_10'] as $gradeLevel) {
            for ($i = 0; $i < 10; $i++) {
                $sections[] = [
                    'name' => strtoupper(str_replace('grade_', 'G', $gradeLevel)).'-'.$alphabet[$i],
                    'cluster' => null,
                    'grade_level' => $gradeLevel,
                    'curriculum' => 'DepEd SHS - GAS',
                    'room' => null,
                    'capacity' => 45,
                ];
            }
        }

        $clusterToCurriculum = [
            'Arts, Social Sciences & Humanities' => 'DepEd SHS - HUMSS',
            'Business and Entrepreneurship' => 'DepEd SHS - ABM',
            'Science, Technology, Engineering and Mathematics' => 'DepEd SHS - STEM',
        ];

        $clusterSuffix = [
            'Arts, Social Sciences & Humanities' => 'ASSH',
            'Business and Entrepreneurship' => 'BUS',
            'Science, Technology, Engineering and Mathematics' => 'STEM',
        ];

        foreach (['grade_11', 'grade_12'] as $gradeLevel) {
            $sectionOffset = 0;
            foreach ($clusterToCurriculum as $clusterName => $curriculumName) {
                $sectionsPerCluster = $clusterName === 'Arts, Social Sciences & Humanities' ? 4 : 3;
                for ($i = 0; $i < $sectionsPerCluster; $i++) {
                    $sections[] = [
                        'name' => strtoupper(str_replace('grade_', 'G', $gradeLevel)).'-'.$clusterSuffix[$clusterName].'-'.$alphabet[$sectionOffset],
                        'cluster' => $clusterName,
                        'grade_level' => $gradeLevel,
                        'curriculum' => $curriculumName,
                        'room' => null,
                        'capacity' => 45,
                    ];
                    $sectionOffset++;
                }
            }
        }

        foreach ($sections as $section) {
            Section::query()->updateOrCreate(
                [
                    'name' => $section['name'],
                    'SY_ID' => $academicYearId,
                ],
                [
                    'cluster_ID' => $section['cluster'] ? $clusterMap[$section['cluster']] : null,
                    'grade_ID' => $gradeMap[$section['grade_level']],
                    'staff_ID' => null,
                    'curriculum_ID' => $curriculumMap[$section['curriculum']],
                    'room' => $section['room'],
                    'capacity' => $section['capacity'],
                ],
            );
        }

        $desiredNames = collect($sections)->pluck('name')->all();
        Section::query()
            ->where('SY_ID', $academicYearId)
            ->whereDoesntHave('enrollments')
            ->where(function ($query): void {
                $query->where('name', 'like', 'G11-%')
                    ->orWhere('name', 'like', 'G12-%');
            })
            ->whereNotIn('name', $desiredNames)
            ->delete();

        $teacherStaffId = DB::table('staffs')
            ->join('roles', 'staffs.role_id', '=', 'roles.id')
            ->where('roles.role_name', 'teacher')
            ->value('staffs.staff_id');

        if ($teacherStaffId) {
            Section::query()
                ->where('SY_ID', $academicYearId)
                ->update(['staff_ID' => $teacherStaffId]);
        }
    }
}
