<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\Subject;
use App\Support\MapehSetup;
use Illuminate\Database\Seeder;

class CombinedSubjectConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $componentIds = Subject::query()
            ->whereIn('code', array_keys(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS))
            ->pluck('subject_ID', 'code');

        if ($componentIds->count() !== count(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS)) {
            return;
        }

        $yearIds = AcademicYear::query()->pluck('SY_ID');

        Curriculum::query()
            ->with(['gradeLevel', 'gradingSemester', 'curriculumSubjects.subject'])
            ->whereHas('gradeLevel', fn ($query) => $query->where('grade_label', 'Grade 11'))
            ->each(function (Curriculum $curriculum) use ($componentIds, $yearIds): void {
                $parent = $curriculum->curriculumSubjects->first(
                    fn ($offering) => $offering->subject?->code === 'EFFCOM'
                );

                if (! $parent) {
                    return;
                }

                foreach ($yearIds as $yearId) {
                    MapehSetup::save($curriculum, [
                        'SY_ID' => $yearId,
                        'parent_curr_subj_ID' => $parent->curr_subj_ID,
                        'mode' => 'paired',
                        'components' => [
                            'effective_communication' => $componentIds['EFFCOM-ENG'],
                            'mabisang_communication' => $componentIds['EFFCOM-FIL'],
                        ],
                    ]);
                }
            });
    }
}
