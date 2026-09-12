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
        $juniorHighNames = array_values(CurriculumSeeder::JUNIOR_HIGH_NAMES);
        $clusterNames = [
            'Arts, Social Sciences & Humanities',
            'Business and Entrepreneurship',
            'Science, Technology, Engineering and Mathematics',
        ];

        $curriculumMap = Curriculum::query()
            ->whereIn('name', array_merge($juniorHighNames, $clusterNames))
            ->pluck('curriculum_ID', 'name');

        foreach (array_merge($juniorHighNames, $clusterNames) as $curriculumName) {
            if (! isset($curriculumMap[$curriculumName])) {
                throw new RuntimeException("Missing curriculum: {$curriculumName}. Run CurriculumSeeder first.");
            }
        }

        $subjectMap = Subject::query()
            ->where('status', 'active')
            ->get(['subject_ID', 'code', 'cluster_ID'])
            ->keyBy('code');

        foreach (CurriculumSeeder::JUNIOR_HIGH_NAMES as $gradeLevel => $curriculumName) {
            $gradeNumber = str_replace('grade_', '', $gradeLevel);

            foreach (array_keys(SubjectSeeder::JUNIOR_HIGH_AREAS) as $prefix) {
                $this->assignSubject(
                    $subjectMap,
                    $curriculumMap[$curriculumName],
                    $prefix.$gradeNumber,
                    $gradeLevel,
                    'first',
                );
            }
        }

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

        $specializedAssignments = [
            'Arts, Social Sciences & Humanities' => [
                ['code' => 'DISS', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'CREATIVEWRITING', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'DIASS', 'grade_level' => 'grade_12', 'semester' => 'first'],
                ['code' => 'PPG', 'grade_level' => 'grade_12', 'semester' => 'second'],
            ],
            'Business and Entrepreneurship' => [
                ['code' => 'BUSMATH', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'ACCOUNTING', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'ORGMGMT', 'grade_level' => 'grade_12', 'semester' => 'first'],
                ['code' => 'APPECON', 'grade_level' => 'grade_12', 'semester' => 'second'],
            ],
            'Science, Technology, Engineering and Mathematics' => [
                ['code' => 'PRECALC', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'GENBIO1', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'GENCHEM1', 'grade_level' => 'grade_11', 'semester' => 'first'],
                ['code' => 'BASICCALC', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'GENBIO2', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'GENCHEM2', 'grade_level' => 'grade_11', 'semester' => 'second'],
                ['code' => 'GENPHYS1', 'grade_level' => 'grade_12', 'semester' => 'first'],
                ['code' => 'GENPHYS2', 'grade_level' => 'grade_12', 'semester' => 'second'],
            ],
        ];

        foreach ($clusterNames as $curriculumName) {
            $assignments = array_merge($commonAssignments, $specializedAssignments[$curriculumName] ?? []);

            foreach ($assignments as $assignment) {
                $this->assignSubject(
                    $subjectMap,
                    $curriculumMap[$curriculumName],
                    $assignment['code'],
                    $assignment['grade_level'],
                    $assignment['semester'],
                );
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Subject>  $subjectMap
     */
    private function assignSubject($subjectMap, int $curriculumId, string $code, string $gradeLevel, string $semester): void
    {
        $subject = $subjectMap[$code] ?? null;

        if (! $subject) {
            throw new RuntimeException("Missing subject code: {$code}. Run SubjectSeeder first.");
        }

        CurriculumSubject::query()->updateOrCreate(
            [
                'curriculum_ID' => $curriculumId,
                'subject_ID' => $subject->subject_ID,
                'grade_level' => $gradeLevel,
                'semester' => $semester,
            ],
            [
                'cluster_ID' => $subject->cluster_ID,
            ],
        );
    }
}
