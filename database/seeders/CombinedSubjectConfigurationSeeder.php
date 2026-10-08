<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Curriculum;
use App\Models\MapehConfiguration;
use App\Models\Subject;
use App\Support\MapehSetup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class CombinedSubjectConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $yearIds = AcademicYear::query()->pluck('SY_ID');

        $this->seedMapehConfigurations($yearIds);
        $this->seedEffectiveCommunicationConfigurations($yearIds);
    }

    private function seedMapehConfigurations(Collection $yearIds): void
    {
        Curriculum::query()
            ->with(['gradeLevel', 'curriculumSubjects.subject'])
            ->whereHas('gradeLevel', fn ($query) => $query->whereIn('grade_label', [
                'Grade 7',
                'Grade 8',
                'Grade 9',
                'Grade 10',
            ]))
            ->each(function (Curriculum $curriculum) use ($yearIds): void {
                $parent = $curriculum->curriculumSubjects->first(
                    fn ($offering) => MapehSetup::isMapehParent($offering->subject)
                );

                if (! $parent) {
                    return;
                }

                foreach ($yearIds as $yearId) {
                    if ($this->configurationExists($curriculum, $yearId)) {
                        continue;
                    }

                    MapehSetup::save($curriculum, [
                        'SY_ID' => $yearId,
                        'parent_curr_subj_ID' => $parent->curr_subj_ID,
                        'mode' => 'paired',
                    ]);
                }
            });
    }

    private function seedEffectiveCommunicationConfigurations(Collection $yearIds): void
    {
        $componentIds = Subject::query()
            ->whereIn('code', array_keys(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS))
            ->pluck('subject_ID', 'code');

        if ($componentIds->count() !== count(SubjectSeeder::EFFECTIVE_COMMUNICATION_COMPONENTS)) {
            return;
        }

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
                    if ($this->configurationExists($curriculum, $yearId)) {
                        continue;
                    }

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

    private function configurationExists(Curriculum $curriculum, int $yearId): bool
    {
        return MapehConfiguration::query()
            ->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)
            ->where('SY_ID', $yearId)
            ->exists();
    }
}
