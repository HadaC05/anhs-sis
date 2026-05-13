<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use RuntimeException;

class CurriculumSubjectSeeder extends Seeder
{
    /**
     * Seed the application's curriculum_subjects table.
     */
    public function run(): void
    {
        $curriculumMap = Curriculum::query()
            ->whereIn('name', ['DepEd SHS - ABM', 'DepEd SHS - STEM', 'DepEd SHS - HUMSS', 'DepEd SHS - GAS'])
            ->pluck('curriculum_ID', 'name');

        foreach (['DepEd SHS - ABM', 'DepEd SHS - STEM', 'DepEd SHS - HUMSS', 'DepEd SHS - GAS'] as $curriculumName) {
            if (! isset($curriculumMap[$curriculumName])) {
                throw new RuntimeException("Missing curriculum: {$curriculumName}. Run CurriculumSeeder first.");
            }
        }

        $subjectMap = Subject::query()
            ->where('status', 'active')
            ->get(['subject_ID', 'code', 'cluster_ID'])
            ->keyBy('code');

        $commonAssignments = [
            ['code' => 'ORALCOM', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'KOMFIL', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'GENMAT', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'STATPROB', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'PERDEV', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'UCSP', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'MIL', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'IMMTECH', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'HOPE', 'grade_level' => 'grade_11', 'semester' => 'first'],
            ['code' => 'EAPP', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'PAGSULAT', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'RPH', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'ENTREP', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'PRACTRESEARCH1', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'INQUIRY', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'HOPE', 'grade_level' => 'grade_11', 'semester' => 'second'],
            ['code' => 'PRACTRESEARCH2', 'grade_level' => 'grade_12', 'semester' => 'first'],
            ['code' => 'FILIPINO', 'grade_level' => 'grade_12', 'semester' => 'first'],
            ['code' => 'CULMINATING', 'grade_level' => 'grade_12', 'semester' => 'first'],
            ['code' => 'HOPE', 'grade_level' => 'grade_12', 'semester' => 'first'],
            ['code' => 'HOPE', 'grade_level' => 'grade_12', 'semester' => 'second'],
        ];

        $juniorHighAssignments = [];
        foreach (['grade_7', 'grade_8', 'grade_9', 'grade_10'] as $gradeLevel) {
            foreach (['JH-MATH', 'JH-SCI', 'JH-ENG', 'JH-FIL', 'JH-AP', 'JH-MAPEH', 'JH-TLE', 'JH-ESP'] as $code) {
                $juniorHighAssignments[] = ['code' => $code, 'grade_level' => $gradeLevel, 'semester' => 'first'];
            }
        }

        $specializedAssignments = [
            'DepEd SHS - ABM' => [
                ['code' => 'BUSMATH', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'ACCOUNTING', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'ORGMGMT', 'grade_level' => 'grade_12', 'semester' => 'first'],
                ['code' => 'APPECON', 'grade_level' => 'grade_12', 'semester' => 'second'],
            ],
            'DepEd SHS - STEM' => [
                ['code' => 'PRECALC', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'GENBIO1', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'GENCHEM1', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'BASICCALC', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'GENBIO2', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'GENCHEM2', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'GENPHYS1', 'grade_level' => 'grade_12', 'semester' => 'first'],
                ['code' => 'GENPHYS2', 'grade_level' => 'grade_12', 'semester' => 'second'],
            ],
            'DepEd SHS - HUMSS' => [],
            'DepEd SHS - GAS' => [],
        ];

        foreach ($curriculumMap as $curriculumName => $curriculumId) {
            $assignments = array_merge($commonAssignments, $specializedAssignments[$curriculumName] ?? []);

            if ($curriculumName === 'DepEd SHS - GAS') {
                $assignments = array_merge($assignments, $juniorHighAssignments);
            }

            foreach ($assignments as $assignment) {
                $subject = $subjectMap[$assignment['code']] ?? null;
                if (! $subject) {
                    throw new RuntimeException("Missing subject code: {$assignment['code']}. Run SubjectSeeder first.");
                }

                CurriculumSubject::query()->updateOrCreate(
                    [
                        'curriculum_ID' => $curriculumId,
                        'subject_ID' => $subject->subject_ID,
                        'grade_level' => $assignment['grade_level'],
                        'semester' => $assignment['semester'],
                    ],
                    [
                        'cluster_ID' => $subject->cluster_ID,
                    ],
                );
            }
        }
    }
}
