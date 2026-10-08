<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradingTerm;
use App\Models\RemediationCase;
use App\Models\Section;
use App\Models\Student;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LearnerPermanentRecordBuilder
{
    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  array<string, Collection<int, TeacherSubjectAssignment>>  $assignmentsBySectionYear
     * @param  array<int, Collection<int, \App\Models\StudentSubjectGrade>>  $gradesByEnrollment
     * @param  array<string, mixed>  $schoolMeta
     * @return array<string, mixed>
     */
    public static function buildStudentCard(
        Student $student,
        Collection $enrollments,
        array $assignmentsBySectionYear,
        array $gradesByEnrollment,
        array $schoolMeta = [],
    ): array {
        $periods = GradingTerm::activePeriods();

        $scholasticRecords = $enrollments
            ->sortBy([
                fn (Enrollment $enrollment): int => self::gradeSortKey($enrollment->grade_level),
                fn (Enrollment $enrollment): string => $enrollment->academicYear?->school_year ?? '',
            ])
            ->values()
            ->map(function (Enrollment $enrollment) use ($assignmentsBySectionYear, $gradesByEnrollment, $schoolMeta): array {
                $section = $enrollment->section;
                $assignmentKey = $section ? "{$section->section_ID}:{$enrollment->SY_ID}" : null;
                $assignments = $assignmentKey ? ($assignmentsBySectionYear[$assignmentKey] ?? collect()) : collect();
                $grades = $gradesByEnrollment[$enrollment->enrollment_ID] ?? collect();

                return self::buildScholasticRecord(
                    $enrollment,
                    $section,
                    $assignments,
                    $grades,
                    GradingTerm::periodsForSection($section),
                    $schoolMeta,
                );
            })
            ->all();

        while (count($scholasticRecords) < 5) {
            $scholasticRecords[] = self::emptyScholasticRecord($periods);
        }

        $firstEnrollment = $enrollments->first();
        $fullName = trim(implode(' ', array_filter([
            $student->first_name,
            $student->middle_name,
            $student->last_name,
            $student->suffix,
        ])));

        return [
            'student' => $student,
            'last_name' => $student->last_name ?? '',
            'first_name' => $student->first_name ?? '',
            'suffix' => $student->suffix ?? '',
            'middle_name' => $student->middle_name ?? '',
            'full_name' => $fullName,
            'lrn' => $student->lrn ?? '',
            'birthdate' => $student->birthdate?->format('m/d/Y') ?? '',
            'sex' => $student->sex ? Str::title($student->sex) : '',
            'eligibility' => self::buildEligibility($firstEnrollment),
            'scholastic_records' => $scholasticRecords,
            'school_meta' => array_merge(self::defaultSchoolMeta(), $schoolMeta),
            'last_school_year' => $enrollments->sortByDesc(fn (Enrollment $enrollment): string => $enrollment->academicYear?->school_year ?? '')
                ->first()
                ?->academicYear
                ?->school_year ?? '',
        ];
    }

    /**
     * @param  Collection<int, TeacherSubjectAssignment>  $assignments
     * @param  Collection<int, \App\Models\StudentSubjectGrade>  $grades
     * @param  array<int, array{key: string, label: string}>  $periods
     * @param  array<string, mixed>  $schoolMeta
     * @return array<string, mixed>
     */
    public static function buildScholasticRecord(
        Enrollment $enrollment,
        ?Section $section,
        Collection $assignments,
        Collection $grades,
        array $periods,
        array $schoolMeta = [],
    ): array {
        $periods = self::periodsForRecord($enrollment, $grades, $periods);
        $periodKeys = array_column($periods, 'key');
        $assignments = self::assignmentsForEnrollment($enrollment, $assignments);
        $gradesByAssignment = $grades->groupBy('assignment_ID')
            ->map(fn (Collection $assignmentGrades) => $assignmentGrades->keyBy('grading_period'));

        if ($enrollment->isSeniorHigh()) {
            return self::buildSeniorHighScholasticRecord(
                $enrollment,
                $section,
                $assignments,
                $gradesByAssignment,
                $periods,
                $schoolMeta,
            );
        }

        if ($section) {
            $assignments = MapehGrades::assignments($section, $assignments);
            $gradesByAssignment = MapehGrades::grades($assignments, $gradesByAssignment, $periodKeys);
        }

        $subjectGrades = [];

        foreach ($assignments as $assignment) {
            $subject = $assignment->subject;
            $slot = $assignment->mapeh_slot ?: self::subjectSlot((string) ($subject?->title ?? $subject?->code ?? ''));
            $assignmentGrades = $gradesByAssignment->get($assignment->assignment_ID, collect());
            $quarterGrades = [];
            $values = [];

            foreach ($periodKeys as $periodKey) {
                $value = $assignmentGrades->get($periodKey)?->numeric_grade;
                $quarterGrades[$periodKey] = $value === null || $value === '' ? null : round((float) $value);
                if ($quarterGrades[$periodKey] !== null) {
                    $values[] = $quarterGrades[$periodKey];
                }
            }

            if (! $slot) {
                $slot = 'other_'.$assignment->assignment_ID;
            }

            if (in_array($slot, ['music', 'arts'], true)) {
                $slot = 'music_arts';
            }

            if (in_array($slot, ['pe', 'health'], true)) {
                $slot = 'pe_health';
            }

            if (isset($subjectGrades[$slot])) {
                $subjectGrades[$slot] = self::mergeSubjectGrades($subjectGrades[$slot], $quarterGrades, $values);
            } else {
                $subjectGrades[$slot] = [
                    'quarters' => $quarterGrades,
                    'final' => count($values) ? round(array_sum($values) / count($values)) : null,
                ];
            }
        }

        if (! isset($subjectGrades['mapeh'])) {
            $mapehChildren = collect(['music_arts', 'pe_health'])
                ->map(fn (string $slot) => $subjectGrades[$slot]['final'] ?? null)
                ->filter(fn ($value) => $value !== null);

            if ($mapehChildren->isNotEmpty()) {
                $subjectGrades['mapeh'] = [
                    'quarters' => [],
                    'final' => round($mapehChildren->avg()),
                ];
            }
        }

        $rows = collect(self::officialSubjectRows())->map(function (array $row) use ($subjectGrades, $periodKeys): array {
            $grade = $subjectGrades[$row['slot']] ?? null;
            $final = $grade['final'] ?? null;

            return [
                'label' => $row['label'],
                'child' => $row['child'] ?? false,
                'quarters' => $grade['quarters'] ?? array_fill_keys($periodKeys, null),
                'final' => $final,
                'remarks' => $final === null ? '' : ($final >= 75 ? 'Passed' : 'Failed'),
            ];
        })->all();

        $generalAverageValues = collect($rows)
            ->reject(fn (array $row): bool => $row['child'])
            ->pluck('final')
            ->filter(fn ($value) => $value !== null);
        $generalAverage = $generalAverageValues->isNotEmpty() ? round($generalAverageValues->avg()) : null;
        if ($assignments->contains('computed_mapeh', true) && ($subjectGrades['mapeh']['final'] ?? null) === null) {
            $generalAverage = null;
        }

        $meta = array_merge(self::defaultSchoolMeta(), $schoolMeta);
        $adviser = $section?->adviser;

        return [
            'school' => $meta['name'],
            'school_id' => $meta['id'],
            'district' => $meta['district'],
            'division' => $meta['division'],
            'region' => $meta['region'],
            'grade' => $section ? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) : '',
            'section' => $section?->name ?? '',
            'school_year' => $section?->academicYear?->school_year ?? $enrollment->academicYear?->school_year ?? '',
            'adviser' => $adviser ? trim(($adviser->first_name ?? '').' '.($adviser->last_name ?? '')) : '',
            'subjects' => $rows,
            'general_average' => $generalAverage,
            'general_remarks' => $generalAverage === null ? '' : ($generalAverage >= 75 ? 'Passed' : 'Failed'),
            'is_empty' => false,
            'is_senior_high' => false,
            'periods' => $periods,
            'remediation' => self::remediationFor($enrollment),
        ];
    }

    /** The archived fourth term identifies records from the four-quarter curriculum. */
    private static function periodsForRecord(Enrollment $enrollment, Collection $grades, array $configuredPeriods): array
    {
        if ($enrollment->isSeniorHigh() || ! $grades->contains(fn ($grade): bool => $grade->grading_period === 'term_4')) {
            return $configuredPeriods;
        }

        return array_map(fn (int $number): array => [
            'key' => 'term_'.$number,
            'label' => 'Quarter '.$number,
        ], range(1, 4));
    }

    /**
     * SHS permanent records use the learner's actual enlisted subjects instead
     * of attempting to squeeze them into the fixed Junior High learning areas.
     *
     * @param  Collection<int, TeacherSubjectAssignment>  $assignments
     * @param  Collection<int, Collection<string, mixed>>  $gradesByAssignment
     * @param  array<int, array{key: string, label: string}>  $periods
     * @param  array<string, mixed>  $schoolMeta
     * @return array<string, mixed>
     */
    private static function buildSeniorHighScholasticRecord(
        Enrollment $enrollment,
        ?Section $section,
        Collection $assignments,
        Collection $gradesByAssignment,
        array $periods,
        array $schoolMeta,
    ): array {
        $periodKeys = array_column($periods, 'key');
        $rows = $assignments->map(function (TeacherSubjectAssignment $assignment) use ($gradesByAssignment, $periodKeys): array {
            $grades = $gradesByAssignment->get($assignment->assignment_ID, collect());
            $termGrades = collect($periodKeys)->mapWithKeys(function (string $periodKey) use ($grades): array {
                $value = $grades->get($periodKey)?->numeric_grade;

                return [$periodKey => $value === null || $value === '' ? null : (int) round((float) $value)];
            })->all();
            $recorded = array_values(array_filter($termGrades, fn ($value): bool => $value !== null));
            $final = $recorded === [] ? null : (int) round(array_sum($recorded) / count($recorded));
            $subject = $assignment->subject;

            return [
                'label' => $subject?->title ?? $subject?->code ?? 'Subject',
                'child' => false,
                'quarters' => $termGrades,
                'final' => $final,
                'remarks' => $final === null ? '' : ($final >= 75 ? 'Passed' : 'Failed'),
            ];
        })->values()->all();

        $generalAverageValues = collect($rows)->pluck('final')->filter(fn ($value) => $value !== null);
        $generalAverage = $generalAverageValues->isEmpty() ? null : (int) round($generalAverageValues->avg());
        $meta = array_merge(self::defaultSchoolMeta(), $schoolMeta);
        $adviser = $section?->adviser;

        return [
            'school' => $meta['name'],
            'school_id' => $meta['id'],
            'district' => $meta['district'],
            'division' => $meta['division'],
            'region' => $meta['region'],
            'grade' => $section ? strtoupper(str_replace('grade_', 'Grade ', $section->grade_level)) : '',
            'section' => $section?->name ?? '',
            'school_year' => $section?->academicYear?->school_year ?? $enrollment->academicYear?->school_year ?? '',
            'adviser' => $adviser ? trim(($adviser->first_name ?? '').' '.($adviser->last_name ?? '')) : '',
            'subjects' => $rows,
            'general_average' => $generalAverage,
            'general_remarks' => $generalAverage === null ? '' : ($generalAverage >= 75 ? 'Passed' : 'Failed'),
            'is_empty' => false,
            'is_senior_high' => true,
            'periods' => $periods,
            'remediation' => self::remediationFor($enrollment),
        ];
    }

    /**
     * @param  array<int, array{key: string, label: string}>  $periods
     * @return array<string, mixed>
     */
    public static function emptyScholasticRecord(array $periods): array
    {
        $periodKeys = array_column($periods, 'key');
        $rows = collect(self::officialSubjectRows())->map(fn (array $row): array => [
            'label' => $row['label'],
            'child' => $row['child'] ?? false,
            'quarters' => array_fill_keys($periodKeys, null),
            'final' => null,
            'remarks' => '',
        ])->all();

        return [
            'school' => '',
            'school_id' => '',
            'district' => '',
            'division' => '',
            'region' => '',
            'grade' => '',
            'section' => '',
            'school_year' => '',
            'adviser' => '',
            'subjects' => $rows,
            'general_average' => null,
            'general_remarks' => '',
            'is_empty' => true,
            'is_senior_high' => false,
            'periods' => $periods,
            'remediation' => null,
        ];
    }

    public static function subjectSlot(string $subject): ?string
    {
        $normalized = Str::of($subject)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();

        return match (true) {
            str_contains($normalized, 'filipino') => 'filipino',
            str_contains($normalized, 'english') => 'english',
            str_contains($normalized, 'mathematics'), str_contains($normalized, 'math') => 'mathematics',
            str_contains($normalized, 'science') => 'science',
            str_contains($normalized, 'araling panlipunan'), $normalized === 'ap' => 'ap',
            str_contains($normalized, 'values education'),
            str_contains($normalized, 'edukasyon sa pagpapakatao'),
            $normalized === 'esp' => 'values_education',
            str_contains($normalized, 'technology and livelihood'),
            str_contains($normalized, 'edukasyong pantahanan'),
            preg_match('/^(?:tle|epp)(?:\s+\d+)?$/', $normalized) === 1 => 'tle',
            $normalized === 'mapeh' => 'mapeh',
            str_contains($normalized, 'music') => 'music',
            str_contains($normalized, 'arts') => 'arts',
            str_contains($normalized, 'physical education'), $normalized === 'pe', $normalized === 'p e' => 'pe',
            str_contains($normalized, 'health') => 'health',
            default => null,
        };
    }

    /**
     * @return array<int, array{slot: string, label: string, child?: bool}>
     */
    public static function officialSubjectRows(): array
    {
        return [
            ['slot' => 'filipino', 'label' => 'Filipino'],
            ['slot' => 'english', 'label' => 'English'],
            ['slot' => 'mathematics', 'label' => 'Mathematics'],
            ['slot' => 'science', 'label' => 'Science'],
            ['slot' => 'ap', 'label' => 'Araling Panlipunan (AP)'],
            ['slot' => 'values_education', 'label' => 'Values Education'],
            ['slot' => 'tle', 'label' => 'Technology and Livelihood Education (TLE)'],
            ['slot' => 'mapeh', 'label' => 'MAPEH'],
            ['slot' => 'music_arts', 'label' => 'Music & Arts', 'child' => true],
            ['slot' => 'pe_health', 'label' => 'Physical Education & Health', 'child' => true],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function defaultSchoolMeta(): array
    {
        $school = \App\Models\SchoolInformation::current();

        return [
            'name' => $school->name,
            'id' => $school->school_id ?? '',
            'district' => $school->district ?? '',
            'division' => $school->division ?? '',
            'region' => $school->region ?? '',
        ];
    }

    /**
     * @param  array<string, int|null>  $quarterGrades
     * @param  array<int, int|float>  $values
     * @param  array{quarters: array<string, int|null>, final: int|null}  $existing
     * @return array{quarters: array<string, int|null>, final: int|null}
     */
    private static function mergeSubjectGrades(array $existing, array $quarterGrades, array $values): array
    {
        $mergedQuarters = $existing['quarters'];

        foreach ($quarterGrades as $periodKey => $value) {
            if ($value === null) {
                continue;
            }

            if (! isset($mergedQuarters[$periodKey]) || $mergedQuarters[$periodKey] === null) {
                $mergedQuarters[$periodKey] = $value;
            } else {
                $mergedQuarters[$periodKey] = round(($mergedQuarters[$periodKey] + $value) / 2);
            }
        }

        $mergedValues = array_filter($mergedQuarters, fn ($value) => $value !== null);

        return [
            'quarters' => $mergedQuarters,
            'final' => $mergedValues !== [] ? round(array_sum($mergedValues) / count($mergedValues)) : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function buildEligibility(?Enrollment $enrollment): array
    {
        if (! $enrollment) {
            return [
                'elementary_average' => '',
                'citation' => '',
                'elementary_school' => '',
                'elementary_school_id' => '',
                'elementary_school_address' => '',
            ];
        }

        $isElementaryCompleter = str_contains(
            strtolower((string) ($enrollment->last_grade_level_completed ?? '')),
            'grade 6',
        );

        return [
            'elementary_average' => $isElementaryCompleter ? '' : '',
            'citation' => '',
            'elementary_school' => $enrollment->last_school_attended ?? '',
            'elementary_school_id' => $enrollment->school_id_from_previous_school ?? '',
            'elementary_school_address' => '',
        ];
    }

    private static function gradeSortKey(string $gradeLevel): int
    {
        return (int) preg_replace('/\D+/', '', $gradeLevel);
    }

    /**
     * @param  Collection<int, Enrollment>  $selectedEnrollments
     * @return Collection<int, array<string, mixed>>
     */
    public static function buildCardsForStudents(Collection $selectedEnrollments): Collection
    {
        $studentIds = $selectedEnrollments->pluck('student_ID')->unique()->values();

        $allEnrollments = Enrollment::query()
            ->with(['student', 'section.academicYear', 'section.adviser', 'section.gradeLevel', 'academicYear', 'gradeLevel', 'studentSubjects', 'remediationCase.subjects.subject'])
            ->whereIn('student_ID', $studentIds)
            ->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds())
            ->get();

        if ($allEnrollments->isEmpty()) {
            return collect();
        }

        $assignments = TeacherSubjectAssignment::query()
            ->with(['subject.subjectType'])
            ->where(function ($query) use ($allEnrollments): void {
                foreach ($allEnrollments->unique(fn (Enrollment $enrollment): string => "{$enrollment->section_ID}:{$enrollment->SY_ID}") as $enrollment) {
                    $query->orWhere(function ($inner) use ($enrollment): void {
                        $inner->where('section_ID', $enrollment->section_ID)
                            ->where('SY_ID', $enrollment->SY_ID);
                    });
                }
            })
            ->get();

        $assignmentsBySectionYear = $assignments
            ->groupBy(fn (TeacherSubjectAssignment $assignment): string => "{$assignment->section_ID}:{$assignment->SY_ID}")
            ->all();

        $gradesByEnrollment = \App\Models\StudentSubjectGrade::query()
            ->with(['studentSubject.enrollment', 'term'])
            ->whereHas('studentSubject', fn ($query) => $query->whereIn('enrollment_ID', $allEnrollments->pluck('enrollment_ID')))
            ->whereIn('assignment_ID', $assignments->pluck('assignment_ID'))
            ->get()
            ->groupBy(fn (\App\Models\StudentSubjectGrade $grade) => $grade->studentSubject?->enrollment_ID)
            ->all();

        return $selectedEnrollments->map(function (Enrollment $enrollment) use ($allEnrollments, $assignmentsBySectionYear, $gradesByEnrollment) {
            $student = $enrollment->student;
            abort_if(! $student, 404);

            $studentEnrollments = $allEnrollments
                ->where('student_ID', $student->id)
                ->filter(fn (Enrollment $history): bool => $history->isSeniorHigh() === $enrollment->isSeniorHigh())
                ->values();

            $card = self::buildStudentCard(
                $student,
                $studentEnrollments,
                $assignmentsBySectionYear,
                $gradesByEnrollment,
            );

            $card['is_senior_high'] = $enrollment->isSeniorHigh();

            return $card;
        });
    }

    /** Keep a scholastic record scoped to the learner's enlisted subjects. */
    private static function assignmentsForEnrollment(Enrollment $enrollment, Collection $assignments): Collection
    {
        $subjectIds = $enrollment->relationLoaded('studentSubjects')
            ? $enrollment->studentSubjects->pluck('subject_ID')
            : $enrollment->studentSubjects()->pluck('subject_ID');

        return $assignments
            ->whereIn('subject_ID', $subjectIds->map(fn (mixed $id): int => (int) $id)->all())
            ->values();
    }

    /** @return array<string, mixed>|null */
    private static function remediationFor(Enrollment $enrollment): ?array
    {
        $case = $enrollment->relationLoaded('remediationCase')
            ? $enrollment->getRelation('remediationCase')
            : ($enrollment->exists ? $enrollment->remediationCase()->with('subjects.subject')->first() : null);

        if (! $case || ! in_array($case->status, [RemediationCase::APPROVED_PASSED, RemediationCase::NEEDS_INTERVENTION], true)) {
            return null;
        }

        $case->loadMissing('subjects.subject');

        return [
            'start_date' => $case->start_date?->format('m/d/Y') ?? '',
            'end_date' => $case->end_date?->format('m/d/Y') ?? '',
            'subjects' => $case->subjects->map(fn ($subject): array => [
                'label' => $subject->subject?->title ?? $subject->subject?->code ?? 'Learning Area',
                'final_rating' => self::displayGrade($subject->original_final_grade),
                'remedial_class_mark' => self::displayGrade($subject->remedial_class_mark),
                'recomputed_final_grade' => self::displayGrade($subject->recomputed_final_grade),
                'remarks' => (float) $subject->recomputed_final_grade >= 75 ? 'Passed' : 'Needs Intervention',
            ])->all(),
        ];
    }

    private static function displayGrade(mixed $grade): int|float|string
    {
        if ($grade === null || $grade === '') {
            return '';
        }

        $value = round((float) $grade, 2);

        return (int) $value == $value ? (int) $value : $value;
    }
}
