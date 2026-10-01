<?php

namespace App\Support;

use App\Models\GradeStatus;
use App\Models\GradingTerm;
use App\Models\MapehConfiguration;
use App\Models\Section;
use App\Models\StudentSubjectGrade;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Collection;

class MapehGrades
{
    /** Reporting objects only: the combined grade is never stored as a second editable grade. */
    public static function assignments(Section $section, Collection $assignments): Collection
    {
        $config = MapehConfiguration::forSection($section);
        if (! $config) {
            return $assignments;
        }
        $components = $config->components->keyBy('curr_subj_ID');
        $result = $assignments->reject(fn ($row) => (int) $row->curr_subj_ID === (int) $config->parent_curr_subj_ID || in_array($row->curr_subj_ID, $config->inactiveComponentIds()))
            ->map(function ($row) use ($components) {
                $copy = clone $row;
                if ($component = $components->get($row->curr_subj_ID)) {
                    $copy->mapeh_component = true;
                    $copy->mapeh_slot = $component->key;
                }

                return $copy;
            })->values();
        foreach ($components as $component) {
            if (! $result->contains('curr_subj_ID', $component->curr_subj_ID)) {
                $placeholder = new TeacherSubjectAssignment;
                $placeholder->forceFill(['assignment_ID' => -$component->curr_subj_ID, 'curr_subj_ID' => $component->curr_subj_ID, 'mapeh_component' => true, 'mapeh_slot' => $component->key]);
                $placeholder->setRelation('curriculumSubject', $component->curriculumSubject)->setRelation('staff', null);
                $result->push($placeholder);
            }
        }
        $parent = new TeacherSubjectAssignment;
        $parent->forceFill([
            'assignment_ID' => -$config->parent_curr_subj_ID, 'curr_subj_ID' => $config->parent_curr_subj_ID,
            'computed_mapeh' => true, 'mapeh_slot' => 'mapeh',
            'legacy_assignment_id' => $assignments->firstWhere('curr_subj_ID', $config->parent_curr_subj_ID)?->assignment_ID,
        ]);
        $parent->setRelation('curriculumSubject', $config->parentSubject)->setRelation('staff', null);
        $result->push($parent);

        return $result;
    }

    /** $grades is keyed by assignment ID, then grading-period key. */
    public static function grades(Collection $assignments, Collection $grades, array $periodKeys, bool $releasedOnly = false): Collection
    {
        $parent = $assignments->firstWhere('computed_mapeh', true);
        if (! $parent) {
            return $grades;
        }
        $grades = clone $grades;
        $children = $assignments->where('mapeh_component', true);
        $combined = collect();
        foreach ($periodKeys as $key) {
            $records = $children->map(fn ($child) => $grades->get($child->assignment_ID, collect())->get($key));
            $present = $records->filter();
            $accepted = $releasedOnly ? [GradeStatus::RELEASED] : [GradeStatus::APPROVED, GradeStatus::RELEASED];
            $complete = $records->isNotEmpty() && $records->every(fn ($grade) => $grade && $grade->numeric_grade !== null && in_array($grade->status, $accepted, true));
            $value = $complete ? (int) round($records->avg(fn ($grade) => round((float) $grade->numeric_grade))) : null;
            $status = $complete && $records->every(fn ($grade) => $grade->status === GradeStatus::RELEASED) ? GradeStatus::RELEASED : GradeStatus::APPROVED;
            // Keep historical standalone grades only while the term has no component entries.
            if ($present->isEmpty() && $parent->legacy_assignment_id) {
                $legacy = $grades->get($parent->legacy_assignment_id, collect())->get($key);
                if ($legacy && in_array($legacy->status, $accepted, true)) {
                    $value = $legacy->numeric_grade;
                    $status = $legacy->status;
                }
            }
            $grade = new StudentSubjectGrade(['numeric_grade' => $value, 'status' => $value === null ? GradeStatus::DRAFT : $status]);
            $grade->setRelation('term', new GradingTerm(['key' => $key]))->setRelation('studentSubject', null);
            $combined->put($key, $grade);
        }
        $grades->put($parent->assignment_ID, $combined);

        return $grades;
    }

    public static function isComputed(TeacherSubjectAssignment $assignment): bool
    {
        $config = MapehConfiguration::forSection($assignment->section);

        return $config && (int) $config->parent_curr_subj_ID === (int) $assignment->curr_subj_ID;
    }

    public static function inputBlocked(TeacherSubjectAssignment $assignment): bool
    {
        $config = MapehConfiguration::forSection($assignment->section);

        return $config && ((int) $config->parent_curr_subj_ID === (int) $assignment->curr_subj_ID || in_array($assignment->curr_subj_ID, $config->inactiveComponentIds()));
    }
}
