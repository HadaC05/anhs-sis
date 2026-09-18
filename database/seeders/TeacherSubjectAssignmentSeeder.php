<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CurriculumSubject;
use App\Models\Section;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TeacherSubjectAssignmentSeeder extends Seeder
{
    /**
     * Seed teacher subject assignments so each adviser keeps one section
     * and subject teachers handle the same learning area across that grade.
     */
    public function run(): void
    {
        $activeYearId = AcademicYear::query()
            ->where('status', true)
            ->value('SY_ID');

        $sections = Section::query()
            ->with('gradeLevel')
            ->when($activeYearId, fn ($query) => $query->where('SY_ID', $activeYearId))
            ->whereNotNull('staff_ID')
            ->orderBy('name')
            ->get();

        $groups = $sections->groupBy(fn (Section $section): string => implode(':', [
            (string) $section->SY_ID,
            $section->grade_level,
            (string) $section->curriculum_ID,
        ]));

        foreach ($groups as $gradeSections) {
            $this->assignSubjectsForGradeSections($gradeSections);
        }
    }

    /**
     * @param  Collection<int, Section>  $gradeSections
     */
    private function assignSubjectsForGradeSections(Collection $gradeSections): void
    {
        $teachersByLetter = $gradeSections
            ->mapWithKeys(fn (Section $section): array => [
                Str::afterLast($section->name, '-') => $section->staff_ID,
            ])
            ->filter();

        if ($teachersByLetter->isEmpty()) {
            return;
        }

        $sampleSection = $gradeSections->first();
        $teacherIds = $teachersByLetter
            ->sortKeys()
            ->values();

        $subjects = $this->curriculumSubjectsForSection($sampleSection);

        foreach ($subjects->values() as $index => $subject) {
            $teacherId = $teacherIds[$index % $teacherIds->count()];

            foreach ($gradeSections as $section) {
                TeacherSubjectAssignment::query()->updateOrCreate(
                    [
                        'section_ID' => $section->section_ID,
                        'curr_subj_ID' => $subject->curr_subj_ID,
                    ],
                    [
                        'staff_ID' => $teacherId,
                        'SY_ID' => $section->SY_ID,
                    ]
                );
            }
        }
    }

    /**
     * @return Collection<int, CurriculumSubject>
     */
    private function curriculumSubjectsForSection(Section $section): Collection
    {
        return CurriculumSubject::query()
            ->with(['subject', 'gradingSemester'])
            ->where('curriculum_ID', $section->curriculum_ID)
            ->where('grade_ID', $section->grade_ID)
            ->when($section->cluster_ID, function ($query) use ($section): void {
                $query->where(function ($inner) use ($section): void {
                    $inner->whereNull('cluster_ID')
                        ->orWhere('cluster_ID', $section->cluster_ID);
                });
            })
            ->get()
            ->sortBy(fn (CurriculumSubject $subject): string => $this->subjectSortKey($subject))
            ->values();
    }

    private function subjectSortKey(CurriculumSubject $subject): string
    {
        $code = $subject->subject?->code ?? '';
        $prefix = preg_replace('/\d+$/', '', $code) ?: $code;
        $areaOrder = array_search($prefix, array_keys(SubjectSeeder::JUNIOR_HIGH_AREAS), true);

        if ($areaOrder !== false) {
            return sprintf('%02d:%s:%s', $areaOrder, $subject->semester ?? '', $subject->curr_subj_ID);
        }

        return implode(':', [$code, $subject->semester ?? '', (string) $subject->curr_subj_ID]);
    }
}
