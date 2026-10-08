<?php

namespace App\Support;

use App\Models\Curriculum;
use App\Models\CurriculumSubject;
use App\Models\Enrollment;
use App\Models\MapehConfiguration;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MapehSetup
{
    public static function isParentSubject(?Subject $subject): bool
    {
        return self::isMapehParent($subject) || self::isCommunicationParent($subject);
    }

    public static function isMapehParent(?Subject $subject): bool
    {
        return $subject && (preg_match('/^MAPEH(?:[\s-]*(?:GRADE\s*)?\d+)?$/i', trim($subject->title))
            || preg_match('/^MAPEH(?:[\s-]*\d+)?$/i', trim($subject->code)));
    }

    public static function isCommunicationParent(?Subject $subject): bool
    {
        if (! $subject) {
            return false;
        }

        return strcasecmp(trim($subject->code), 'EFFCOM') === 0
            || (stripos($subject->title, 'Effective Communication') !== false
                && stripos($subject->title, 'Mabisang') !== false);
    }

    public static function save(Curriculum $curriculum, array $data): MapehConfiguration
    {
        return DB::transaction(function () use ($curriculum, $data): MapehConfiguration {
            // Serialize configuration changes, including the first configuration for a year.
            $curriculum = Curriculum::query()->lockForUpdate()->findOrFail($curriculum->curriculum_ID);
            $parent = CurriculumSubject::query()->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)
                ->findOrFail($data['parent_curr_subj_ID']);
            if (! self::isParentSubject($parent->subject)) {
                throw ValidationException::withMessages(['parent_curr_subj_ID' => 'Select a supported combined parent subject from this curriculum.']);
            }
            $communication = self::isCommunicationParent($parent->subject);
            $expectedLevel = $communication ? 'Senior High School' : 'Junior High School';
            if ($curriculum->gradeLevel?->category !== $expectedLevel) {
                throw ValidationException::withMessages(['mode' => "This combined subject can only be configured for {$expectedLevel} curricula."]);
            }
            if ($communication && $data['mode'] !== 'paired') {
                throw ValidationException::withMessages(['mode' => 'Effective Communication & Mabisang Communication uses two components.']);
            }
            $configuration = MapehConfiguration::query()->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)
                ->where('SY_ID', $data['SY_ID'])->with('components.curriculumSubject')->first();
            $labels = MapehConfiguration::labelsForSubject($parent->subject, $data['mode']);
            $selected = collect($data['components'] ?? [])->only(array_keys($labels))->filter();
            if ($selected->unique()->count() !== $selected->count() || $selected->contains($parent->subject_ID)) {
                throw ValidationException::withMessages(['components' => 'Each component must use a different subject, separate from the combined parent.']);
            }
            if ($configuration) {
                $existing = $configuration->components->mapWithKeys(fn ($component) => [$component->key => $component->curriculumSubject->subject_ID]);
                foreach ($labels as $key => $label) {
                    if (! $selected->has($key)) {
                        $selected->put($key, Subject::query()->where('code', self::componentCode($key, $communication))->value('subject_ID'));
                    }
                }
                $unchanged = $configuration->mode === $data['mode'] && (int) $configuration->parent_curr_subj_ID === (int) $parent->curr_subj_ID
                    && $existing->all() == $selected->all();
                if (! $unchanged) {
                    throw ValidationException::withMessages(['mode' => 'This school year already has a combined-subject configuration. Keep its component mapping to preserve assignments and grades; use a new school year for a different setup.']);
                }

                return $configuration;
            }
            $configuration = MapehConfiguration::create([
                'curriculum_grade_level_ID' => $curriculum->curriculum_ID,
                'SY_ID' => $data['SY_ID'], 'parent_curr_subj_ID' => $parent->curr_subj_ID, 'mode' => $data['mode'],
            ]);
            foreach ($labels as $key => $label) {
                $subjectId = $selected->get($key);
                if ($subjectId) {
                    $subject = Subject::query()->where('status', 'active')->where('school_level', $expectedLevel)->find($subjectId);
                    if (! $subject) {
                        throw ValidationException::withMessages(['components' => "Choose active {$expectedLevel} subjects for the components."]);
                    }
                } else {
                    $code = self::componentCode($key, $communication);
                    $subject = Subject::query()->firstOrCreate(['code' => $code], [
                        'title' => $communication ? $label : 'MAPEH - '.$label, 'school_level' => $expectedLevel,
                        'subject_type_ID' => $parent->subject->subject_type_ID, 'status' => 'active',
                    ]);
                    if ($subject->status !== 'active' || $subject->school_level !== $expectedLevel) {
                        throw ValidationException::withMessages(['components' => "The subject code {$code} already exists but is not an active {$expectedLevel} subject. Select a suitable subject explicitly."]);
                    }
                }
                $offering = CurriculumSubject::firstOrCreate([
                    'curriculum_grade_level_ID' => $curriculum->curriculum_ID, 'subject_ID' => $subject->subject_ID,
                ]);
                if ($offering->curr_subj_ID === $parent->curr_subj_ID || $configuration->components()->where('curr_subj_ID', $offering->curr_subj_ID)->exists()) {
                    throw ValidationException::withMessages(['components' => 'Each component must use a different subject, separate from the parent.']);
                }
                $configuration->components()->create(['key' => $key, 'curr_subj_ID' => $offering->curr_subj_ID]);
            }
            // Only add roster entries for the selected year. Existing records are retained.
            Enrollment::query()->where('SY_ID', $data['SY_ID'])
                ->where('curriculum_grade_level_ID', $curriculum->curriculum_ID)
                ->chunkById(100, function ($enrollments): void {
                    foreach ($enrollments as $enrollment) {
                        StudentSubjectRoster::sync($enrollment);
                    }
                }, 'enrollment_ID');

            return $configuration->load('components.curriculumSubject');
        });
    }

    private static function componentCode(string $key, bool $communication): string
    {
        if ($communication) {
            return $key === 'effective_communication' ? 'EFFCOM-ENG' : 'EFFCOM-FIL';
        }

        return 'MAPEH-'.strtoupper(str_replace('_', '-', $key));
    }
}
