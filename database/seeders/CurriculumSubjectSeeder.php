<?php

namespace Database\Seeders;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class CurriculumSubjectSeeder extends Seeder
{
    /**
     * Seed the subjects offered by every curriculum-grade-level offering.
     *
     * The offering already owns the grade, semester, and track context, so a
     * curriculum subject only needs its offering ID and subject ID.
     */
    public function run(): void
    {
        Curriculum::query()
            ->with(['gradeLevel', 'gradingSemester', 'cluster'])
            ->orderBy('curriculum_ID')
            ->each(function (Curriculum $curriculum): void {
                $subjectCodes = $this->subjectCodesFor($curriculum);

                if ($subjectCodes === []) {
                    return;
                }

                $subjectIds = Subject::query()
                    ->whereIn('code', $subjectCodes)
                    ->pluck('subject_ID');

                foreach ($subjectIds as $subjectId) {
                    CurriculumSubject::query()->firstOrCreate([
                        'curriculum_grade_level_ID' => $curriculum->curriculum_ID,
                        'subject_ID' => $subjectId,
                    ]);
                }
            });
    }

    /** @return list<string> */
    private function subjectCodesFor(Curriculum $curriculum): array
    {
        $grade = (int) preg_replace('/\D+/', '', (string) $curriculum->gradeLevel?->grade_label);

        if ($grade >= 7 && $grade <= 10) {
            return array_map(
                fn (string $prefix): string => $prefix.$grade,
                array_keys(SubjectSeeder::JUNIOR_HIGH_AREAS),
            );
        }

        if ($grade < 11 || $grade > 12) {
            return [];
        }

        $semester = $curriculum->gradingSemester?->key
            ?? (str_contains($curriculum->name, 'Second Semester') ? 'second' : 'first');

        $commonCodes = $semester === 'second'
            ? ['STATPROB', 'RPH', 'EAPP', 'PAGSULAT', 'HOPE', 'ENTREP', 'PRACTRESEARCH2', 'INQUIRY', 'FILIPINO', 'CULMINATING']
            : ['ORALCOM', 'KOMFIL', 'GENMAT', 'MIL', 'UCSP', 'HOPE', 'PERDEV', 'IMMTECH', 'PRACTRESEARCH1'];

        $trackCodes = match ($curriculum->cluster?->name) {
            'Arts, Social Sciences & Humanities' => ['DISS', 'DIASS', 'CREATIVEWRITING', 'PPG'],
            'Business and Entrepreneurship' => ['ACCOUNTING', 'BUSMATH', 'ORGMGMT', 'APPECON'],
            'Science, Technology, Engineering and Mathematics' => [
                'PRECALC', 'BASICCALC', 'GENBIO1', 'GENBIO2',
                'GENCHEM1', 'GENCHEM2', 'GENPHYS1', 'GENPHYS2',
            ],
            default => [],
        };

        return [...$commonCodes, ...$trackCodes];
    }
}
