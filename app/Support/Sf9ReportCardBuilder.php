<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\GradingTerm;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Sf9ReportCardBuilder
{
    /**
     * @return array<string, string>
     */
    public static function observedValueMarkings(): array
    {
        return [
            'AO' => 'Always Observed',
            'SO' => 'Sometimes Observed',
            'RO' => 'Rarely Observed',
            'NO' => 'Not Observed',
        ];
    }

    /**
     * @return array<int, array{key: string, core_value: string, statement: string}>
     */
    public static function observedValueStatements(): array
    {
        return [
            [
                'key' => 'maka_diyos_spiritual_respect',
                'core_value' => 'Maka-Diyos',
                'statement' => "Expresses one's spiritual beliefs while respecting the spiritual beliefs of others.",
            ],
            [
                'key' => 'maka_diyos_ethical_truth',
                'core_value' => 'Maka-Diyos',
                'statement' => 'Shows adherence to ethical principles by upholding truth in all undertakings.',
            ],
            [
                'key' => 'makatao_sensitive_differences',
                'core_value' => 'Makatao',
                'statement' => 'Is sensitive to individual, social, and cultural differences.',
            ],
            [
                'key' => 'makatao_solidarity',
                'core_value' => 'Makatao',
                'statement' => 'Demonstrates contributions towards solidarity.',
            ],
            [
                'key' => 'makakalikasan_resources',
                'core_value' => 'Makakalikasan',
                'statement' => 'Cares for environment and utilizes resources wisely, judiciously and economically.',
            ],
            [
                'key' => 'makabansa_filipino_rights',
                'core_value' => 'Makabansa',
                'statement' => 'Demonstrates pride in being a Filipino; exercises the rights and responsibilities of a Filipino citizen.',
            ],
            [
                'key' => 'makabansa_community_country',
                'core_value' => 'Makabansa',
                'statement' => 'Demonstrates appropriate behavior in carrying out activities in school, community and country.',
            ],
        ];
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function seniorHighObservedPeriods(): array
    {
        return [
            ['key' => 't1', 'label' => '1'],
            ['key' => 't2', 'label' => '2'],
            ['key' => 't3', 'label' => '3'],
            ['key' => 't4', 'label' => '4'],
            ['key' => 't5', 'label' => '5'],
            ['key' => 't6', 'label' => '6'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function seniorHighSignatureLabels(): array
    {
        return ['Term 1', 'Term 2', 'Term 3'];
    }

    /**
     * @return list<array{scale: string, description: string, remarks: string}>
     */
    public static function seniorHighPerformanceDescriptors(): array
    {
        return [
            ['scale' => '90-100', 'description' => 'Advancing', 'remarks' => 'Passed'],
            ['scale' => '80-89', 'description' => 'Benchmarking', 'remarks' => 'Passed'],
            ['scale' => '75-79', 'description' => 'Connecting', 'remarks' => 'Passed'],
            ['scale' => '65-74', 'description' => 'Developing', 'remarks' => 'Failed'],
            ['scale' => '0-64', 'description' => 'Emerging', 'remarks' => 'Failed'],
        ];
    }

    /**
     * @return list<int>
     */
    public static function seniorHighAttendanceMonthKeys(): array
    {
        return [6, 7, 8, 9, 10, 11, 12, 1, 2, 3, 4];
    }

    public static function attendanceMonthAbbreviation(int $month): string
    {
        return match ($month) {
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'July',
            8 => 'Aug',
            9 => 'Sept',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
            default => '',
        };
    }

    public static function principalName(): string
    {
        $principal = Staff::query()
            ->where('status', 'active')
            ->whereHas('role', fn ($query) => $query->where('role_name', 'principal'))
            ->first();

        return $principal ? trim($principal->first_name.' '.$principal->last_name) : '';
    }

    /**
     * @param  Collection<int, mixed>  $assignments
     * @param  Collection<int, Collection<string, mixed>>  $gradesByAssignment
     * @param  Collection<string, Collection<string, mixed>>  $observedValues
     * @param  array<int, array{key: string, label: string}>  $periods
     * @param  array<int, int>  $schoolDaysByMonth
     * @return array<string, mixed>
     */
    public static function buildCard(
        Enrollment $enrollment,
        Section $section,
        Collection $assignments,
        Collection $gradesByAssignment,
        Collection $observedValues,
        array $periods,
        array $schoolDaysByMonth = [],
        ?string $principalName = null,
    ): array {
        $isSeniorHigh = self::isSeniorHigh($section, $enrollment);
        $student = $enrollment->student;
        $adviser = $section->adviser;
        $periodKeys = array_column($periods, 'key');
        $observedPeriods = $isSeniorHigh ? self::seniorHighObservedPeriods() : $periods;

        $base = [
            'student' => $student,
            'is_senior_high' => $isSeniorHigh,
            'name' => trim(($student?->last_name ?? '').', '.($student?->first_name ?? '').' '.($student?->middle_name ?? '')),
            'last_name' => $student?->last_name ?? '',
            'first_name' => $student?->first_name ?? '',
            'middle_name' => $student?->middle_name ?? '',
            'lrn' => $student?->lrn,
            'age' => $student?->birthdate ? $student->birthdate->age : null,
            'sex' => $student?->sex ? Str::title($student->sex) : '',
            'grade' => $section->gradeLevel?->grade_label ?? strtoupper(str_replace('grade_', 'Grade ', (string) $section->grade_level)),
            'section_name' => $section->name,
            'school_year' => $section->academicYear?->school_year ?? $enrollment->academicYear?->school_year,
            'curriculum' => $section->curriculum?->name ?: 'K to 12 Basic Education Curriculum',
            'track_strand' => self::trackStrand($enrollment, $section),
            'shs_track' => $isSeniorHigh ? self::seniorHighTrackGroup($enrollment, $section) : '',
            'adviser' => $adviser ? trim($adviser->first_name.' '.$adviser->last_name) : '',
            'principal' => $principalName ?? self::principalName(),
            'school_name' => (string) config('app.name'),
            'school_region' => 'Region XIII',
            'performance_descriptors' => $isSeniorHigh ? self::seniorHighPerformanceDescriptors() : [],
            'attendance_months' => $isSeniorHigh
                ? self::seniorHighAttendanceMonthKeys()
                : Sf9AttendanceSummary::monthKeys(),
            'observed_periods' => $observedPeriods,
            'signature_labels' => $isSeniorHigh
                ? self::seniorHighSignatureLabels()
                : array_map(
                    fn (array $period, int $index): string => GradingTerm::periodSignatureLabel($period['label'], $index),
                    $periods,
                    array_keys($periods),
                ),
            'observed_values' => self::observedRows($observedValues, $isSeniorHigh, $periodKeys),
            'attendance' => $schoolDaysByMonth !== []
                ? Sf9AttendanceSummary::forEnrollment($enrollment->enrollment_ID, $schoolDaysByMonth)
                : Sf9AttendanceSummary::empty(),
        ];

        if ($isSeniorHigh) {
            return array_merge($base, self::seniorHighSubjects(
                $assignments,
                $gradesByAssignment,
                ($base['shs_track'] ?? '') === 'TECHPRO',
            ));
        }

        return array_merge($base, self::juniorHighSubjects($assignments, $gradesByAssignment, $periodKeys));
    }

    public static function isSeniorHigh(Section $section, ?Enrollment $enrollment = null): bool
    {
        if ($enrollment?->isSeniorHigh()) {
            return true;
        }

        return in_array($section->grade_level, ['grade_11', 'grade_12'], true);
    }

    public static function trackStrand(Enrollment $enrollment, Section $section): string
    {
        $cluster = $enrollment->cluster?->name ?? $section->cluster?->name;
        $course = $enrollment->preferredCourse?->name;

        return collect([$cluster, $course])->filter()->implode(' / ');
    }

    public static function seniorHighTrackGroup(Enrollment $enrollment, Section $section): string
    {
        $haystack = Str::of(collect([
            $enrollment->cluster?->name,
            $section->cluster?->name,
            $enrollment->preferredCourse?->name,
            $section->curriculum?->name,
        ])->filter()->implode(' '))->lower()->value();

        if (Str::contains($haystack, [
            'techpro',
            'tech-pro',
            'tech pro',
            'tvl',
            'tech-voc',
            'tech voc',
            'technical-vocational',
            'technical vocational',
            'technical professional',
        ])) {
            return 'TECHPRO';
        }

        return 'ACADEMIC';
    }

    public static function remarksForGrade(?int $grade): string
    {
        if ($grade === null) {
            return '';
        }

        return $grade >= 75 ? 'Passed' : 'Failed';
    }

    /**
     * @param  Collection<int, mixed>  $assignments
     * @param  Collection<int, Collection<string, mixed>>  $gradesByAssignment
     * @param  list<string>  $periodKeys
     * @return array<string, mixed>
     */
    private static function juniorHighSubjects(Collection $assignments, Collection $gradesByAssignment, array $periodKeys): array
    {
        $subjectGrades = [];

        foreach ($assignments as $assignment) {
            $subject = $assignment->curriculumSubject?->subject;
            $slot = self::juniorHighSubjectSlot((string) ($subject?->title ?? $subject?->code ?? ''));
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

            $subjectGrades[$slot] = [
                'title' => $subject?->title ?? 'Subject',
                'quarters' => $quarterGrades,
                'final' => count($values) ? round(array_sum($values) / count($values)) : null,
            ];
        }

        if (! isset($subjectGrades['mapeh'])) {
            $mapehChildren = collect(['music', 'arts', 'pe', 'health'])
                ->map(fn ($slot) => $subjectGrades[$slot]['final'] ?? null)
                ->filter(fn ($value) => $value !== null);

            if ($mapehChildren->isNotEmpty()) {
                $subjectGrades['mapeh'] = [
                    'title' => 'MAPEH',
                    'quarters' => [],
                    'final' => round($mapehChildren->avg()),
                ];
            }
        }

        $rows = collect(self::juniorHighOfficialRows())->map(function (array $row) use ($subjectGrades, $periodKeys): array {
            $grade = $subjectGrades[$row['slot']] ?? null;

            return [
                'label' => $row['label'],
                'child' => $row['child'] ?? false,
                'quarters' => $grade['quarters'] ?? array_fill_keys($periodKeys, null),
                'final' => $grade['final'] ?? null,
                'remarks' => self::remarksForGrade($grade['final'] ?? null),
            ];
        })->all();

        $generalAverageValues = collect($rows)
            ->reject(fn ($row) => $row['child'])
            ->pluck('final')
            ->filter(fn ($value) => $value !== null);
        $generalAverage = $generalAverageValues->isNotEmpty() ? (int) round($generalAverageValues->avg()) : null;

        return [
            'subjects' => $rows,
            'general_average' => $generalAverage,
            'general_remarks' => self::remarksForGrade($generalAverage),
            'first_semester' => self::emptySemester(),
            'second_semester' => self::emptySemester(),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $assignments
     * @param  Collection<int, Collection<string, mixed>>  $gradesByAssignment
     * @return array<string, mixed>
     */
    private static function seniorHighSubjects(Collection $assignments, Collection $gradesByAssignment, bool $isTechPro): array
    {
        $emptyTerms = ['term_1' => null, 'term_2' => null, 'term_3' => null];
        $slotted = [];
        $electives = [];

        foreach ($assignments as $assignment) {
            $curriculumSubject = $assignment->curriculumSubject;
            $subject = $curriculumSubject?->subject;
            $semester = $curriculumSubject?->semester === 'second' ? 'second' : 'first';
            $assignmentGrades = $gradesByAssignment->get($assignment->assignment_ID, collect());
            $terms = [];
            $values = [];

            foreach (array_keys($emptyTerms) as $termKey) {
                $grade = self::firstNumericGrade($assignmentGrades, self::seniorHighGradeSourceKeys($termKey, $semester));
                $terms[$termKey] = $grade;
                if ($grade !== null) {
                    $values[] = $grade;
                }
            }

            $final = count($values) ? (int) round(array_sum($values) / count($values)) : null;
            $label = $subject?->title ?? $subject?->code ?? 'Subject';
            $row = [
                'label' => $label,
                'terms' => $terms,
                'final' => $final,
                'remarks' => self::remarksForGrade($final),
            ];
            $slot = self::seniorHighSubjectSlot($label);

            if ($slot === null) {
                $electives[] = $row;

                continue;
            }

            $slotted[$slot] = isset($slotted[$slot])
                ? self::mergeSeniorHighGradeRows($slotted[$slot], $row)
                : $row;
        }

        $rows = collect(self::seniorHighOfficialRows($isTechPro))
            ->map(function (array $definition) use ($slotted, $emptyTerms, &$electives): array {
                if ($definition['category'] ?? false) {
                    return [
                        'slot' => $definition['slot'],
                        'label' => $definition['label'],
                        'category' => true,
                        'child' => false,
                        'terms' => $emptyTerms,
                        'final' => null,
                        'remarks' => '',
                    ];
                }

                $slot = $definition['slot'];
                $child = (bool) ($definition['child'] ?? false);

                if (str_starts_with($slot, 'elective_')) {
                    $grade = array_shift($electives) ?? ($slotted[$slot] ?? null);

                    return [
                        'slot' => $slot,
                        'label' => $grade['label'] ?? $definition['label'],
                        'category' => false,
                        'child' => false,
                        'terms' => $grade['terms'] ?? $emptyTerms,
                        'final' => $grade['final'] ?? null,
                        'remarks' => $grade['remarks'] ?? '',
                    ];
                }

                $grade = $slotted[$slot] ?? null;

                if ($slot === 'effective_communication_group' && $grade === null) {
                    $grade = self::averagedSeniorHighRow(
                        $definition['label'],
                        array_filter([
                            $slotted['effective_communication'] ?? null,
                            $slotted['mabisang_komunikasyon'] ?? null,
                        ]),
                    );
                }

                return [
                    'slot' => $slot,
                    'label' => $definition['label'],
                    'category' => false,
                    'child' => $child,
                    'terms' => $grade['terms'] ?? $emptyTerms,
                    'final' => $grade['final'] ?? null,
                    'remarks' => $grade['remarks'] ?? '',
                ];
            })
            ->values();

        foreach ($electives as $index => $elective) {
            $rows->push([
                'slot' => 'elective_extra_'.$index,
                'label' => $elective['label'],
                'category' => false,
                'child' => false,
                'terms' => $elective['terms'],
                'final' => $elective['final'],
                'remarks' => $elective['remarks'],
            ]);
        }

        $subjectRows = $rows->all();
        $generalAverageValues = collect($subjectRows)
            ->reject(fn (array $row): bool => ($row['category'] ?? false) || ($row['child'] ?? false))
            ->pluck('final')
            ->filter(fn ($value) => $value !== null);
        $generalAverage = $generalAverageValues->isNotEmpty() ? (int) round($generalAverageValues->avg()) : null;

        return [
            'subjects' => $subjectRows,
            'general_average' => $generalAverage,
            'general_remarks' => self::remarksForGrade($generalAverage),
            'first_semester' => self::emptySemester(),
            'second_semester' => self::emptySemester(),
        ];
    }

    /**
     * @param  Collection<string, mixed>  $assignmentGrades
     * @param  list<string>  $keys
     */
    private static function firstNumericGrade(Collection $assignmentGrades, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $assignmentGrades->get($key)?->numeric_grade;
            if ($value !== null && $value !== '') {
                return (int) round((float) $value);
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public static function seniorHighGradeSourceKeys(string $termKey, string $semester): array
    {
        $semesterNumber = $semester === 'second' ? 2 : 1;

        return match ($termKey) {
            'term_1' => ['shs_sem'.$semesterNumber.'_term_1', 'shs_sem'.$semesterNumber.'_q1', 'term_1'],
            'term_2' => ['shs_sem'.$semesterNumber.'_term_2', 'shs_sem'.$semesterNumber.'_q2', 'term_2'],
            'term_3' => ['shs_sem'.$semesterNumber.'_term_3', 'term_3'],
            default => [],
        };
    }

    /**
     * @return array{core: array<int, array<string, mixed>>, applied: array<int, array<string, mixed>>, average: int|null, remarks: string}
     */
    private static function emptySemester(): array
    {
        return [
            'core' => [],
            'applied' => [],
            'average' => null,
            'remarks' => '',
        ];
    }

    /**
     * @param  array{label: string, terms: array<string, int|null>, final: int|null, remarks: string}  $existing
     * @param  array{label: string, terms: array<string, int|null>, final: int|null, remarks: string}  $incoming
     * @return array{label: string, terms: array<string, int|null>, final: int|null, remarks: string}
     */
    private static function mergeSeniorHighGradeRows(array $existing, array $incoming): array
    {
        $terms = $existing['terms'];

        foreach ($incoming['terms'] as $termKey => $value) {
            if ($value === null) {
                continue;
            }

            $terms[$termKey] = $terms[$termKey] === null
                ? $value
                : (int) round(($terms[$termKey] + $value) / 2);
        }

        $values = array_values(array_filter($terms, fn ($value) => $value !== null));
        $final = $values !== [] ? (int) round(array_sum($values) / count($values)) : null;

        return [
            'label' => $existing['label'],
            'terms' => $terms,
            'final' => $final,
            'remarks' => self::remarksForGrade($final),
        ];
    }

    /**
     * @param  array<int, array{label?: string, terms: array<string, int|null>, final?: int|null, remarks?: string}>  $children
     * @return array{label: string, terms: array<string, int|null>, final: int|null, remarks: string}|null
     */
    private static function averagedSeniorHighRow(string $label, array $children): ?array
    {
        if ($children === []) {
            return null;
        }

        $terms = ['term_1' => null, 'term_2' => null, 'term_3' => null];

        foreach (array_keys($terms) as $termKey) {
            $values = collect($children)
                ->pluck('terms.'.$termKey)
                ->filter(fn ($value) => $value !== null);

            $terms[$termKey] = $values->isNotEmpty() ? (int) round($values->avg()) : null;
        }

        $values = array_values(array_filter($terms, fn ($value) => $value !== null));
        $final = $values !== [] ? (int) round(array_sum($values) / count($values)) : null;

        return [
            'label' => $label,
            'terms' => $terms,
            'final' => $final,
            'remarks' => self::remarksForGrade($final),
        ];
    }

    /**
     * @param  Collection<string, Collection<string, mixed>>  $observedValues
     * @param  list<string>  $periodKeys
     * @return array<int, array<string, mixed>>
     */
    private static function observedRows(Collection $observedValues, bool $isSeniorHigh, array $periodKeys): array
    {
        return collect(self::observedValueStatements())->map(function (array $statement) use ($observedValues, $isSeniorHigh, $periodKeys): array {
            $statementValues = $observedValues->get($statement['key'], collect());

            return [
                'core_value' => $statement['core_value'],
                'statement' => $statement['statement'],
                'quarters' => $isSeniorHigh
                    ? self::seniorHighObservedMarkings($statementValues)
                    : collect($periodKeys)
                        ->mapWithKeys(fn ($periodKey) => [$periodKey => $statementValues->get($periodKey)?->marking])
                        ->all(),
            ];
        })->all();
    }

    /**
     * @param  Collection<string, mixed>  $statementValues
     * @return array<string, string|null>
     */
    private static function seniorHighObservedMarkings(Collection $statementValues): array
    {
        $sources = [
            't1' => ['shs_sem1_term_1', 'shs_sem1_q1', 'term_1'],
            't2' => ['shs_sem1_term_2', 'shs_sem1_q2', 'term_2'],
            't3' => ['shs_sem1_term_3', 'term_3'],
            't4' => ['shs_sem2_term_1', 'shs_sem2_q1'],
            't5' => ['shs_sem2_term_2', 'shs_sem2_q2'],
            't6' => ['shs_sem2_term_3'],
        ];

        $periods = [];

        foreach ($sources as $periodKey => $keys) {
            $marking = null;

            foreach ($keys as $key) {
                $found = $statementValues->get($key)?->marking;
                if (is_string($found) && $found !== '') {
                    $marking = $found;
                    break;
                }
            }

            $periods[$periodKey] = $marking;
        }

        return $periods;
    }

    /**
     * @return array<int, array{slot: string, label: string, category?: bool, child?: bool}>
     */
    public static function seniorHighOfficialRows(bool $isTechPro = false): array
    {
        $rows = [
            ['slot' => 'core_header', 'label' => 'Core Subjects', 'category' => true],
            ['slot' => 'effective_communication_group', 'label' => 'Effective Communication/Mabisang Komunikasyon'],
            ['slot' => 'effective_communication', 'label' => 'Effective Communication', 'child' => true],
            ['slot' => 'mabisang_komunikasyon', 'label' => 'Mabisang Komunikasyon', 'child' => true],
            ['slot' => 'general_mathematics', 'label' => 'General Mathematics'],
            ['slot' => 'general_science', 'label' => 'General Science'],
            ['slot' => 'life_and_career', 'label' => 'Life and Career Skills'],
            ['slot' => 'kasaysayan', 'label' => 'Pag-aaral ng Kasaysayan at Lipunang Pilipino'],
            ['slot' => 'elective_header', 'label' => 'Elective Subjects', 'category' => true],
        ];

        if ($isTechPro) {
            $rows[] = ['slot' => 'elective_1', 'label' => 'Academic Elective 1'];
            $rows[] = ['slot' => 'elective_2', 'label' => ''];

            return $rows;
        }

        $rows[] = ['slot' => 'elective_1', 'label' => 'Academic Elective 1'];
        $rows[] = ['slot' => 'elective_2', 'label' => 'Academic Elective 2'];
        $rows[] = ['slot' => 'elective_3', 'label' => 'Academic Elective 3'];

        return $rows;
    }

    public static function seniorHighSubjectSlot(string $subject): ?string
    {
        $normalized = Str::of($subject)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();

        return match (true) {
            str_contains($normalized, 'effective communication') && str_contains($normalized, 'mabisang') => 'effective_communication_group',
            str_contains($normalized, 'effective communication'),
            str_contains($normalized, 'oral communication'),
            str_contains($normalized, 'english for academic') => 'effective_communication',
            str_contains($normalized, 'mabisang komunikasyon'),
            str_contains($normalized, 'komunikasyon at pananaliksik'),
            str_contains($normalized, 'pagsulat sa filipino') => 'mabisang_komunikasyon',
            str_contains($normalized, 'general mathematics'),
            str_contains($normalized, 'statistics and probability') => 'general_mathematics',
            str_contains($normalized, 'general science'),
            str_contains($normalized, 'earth science') => 'general_science',
            str_contains($normalized, 'life and career'),
            str_contains($normalized, 'personal development') => 'life_and_career',
            str_contains($normalized, 'pag aaral ng kasaysayan'),
            str_contains($normalized, 'readings in philippine history') => 'kasaysayan',
            default => null,
        };
    }

    /**
     * @return array<int, array{slot: string, label: string, child?: bool}>
     */
    private static function juniorHighOfficialRows(): array
    {
        return [
            ['slot' => 'filipino', 'label' => 'Filipino'],
            ['slot' => 'english', 'label' => 'English'],
            ['slot' => 'mathematics', 'label' => 'Mathematics'],
            ['slot' => 'science', 'label' => 'Science'],
            ['slot' => 'ap', 'label' => 'Araling Panlipunan (AP)'],
            ['slot' => 'esp', 'label' => 'Edukasyon sa Pagpapakatao (EsP)'],
            ['slot' => 'epp_tle', 'label' => 'Edukasyong Pantahanan at Pangkabuhayan'],
            ['slot' => 'mapeh', 'label' => 'MAPEH'],
            ['slot' => 'music', 'label' => 'Music', 'child' => true],
            ['slot' => 'arts', 'label' => 'Arts', 'child' => true],
            ['slot' => 'pe', 'label' => 'Physical Education', 'child' => true],
            ['slot' => 'health', 'label' => 'Health', 'child' => true],
        ];
    }

    public static function juniorHighSubjectSlot(string $subject): ?string
    {
        $normalized = Str::of($subject)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish()->value();

        return match (true) {
            str_contains($normalized, 'filipino') => 'filipino',
            str_contains($normalized, 'english') => 'english',
            str_contains($normalized, 'mathematics'), str_contains($normalized, 'math') => 'mathematics',
            str_contains($normalized, 'science') => 'science',
            str_contains($normalized, 'araling panlipunan'), $normalized === 'ap' => 'ap',
            str_contains($normalized, 'edukasyon sa pagpapakatao'), $normalized === 'esp' => 'esp',
            str_contains($normalized, 'edukasyong pantahanan'), str_contains($normalized, 'technology and livelihood'), $normalized === 'tle', $normalized === 'epp' => 'epp_tle',
            $normalized === 'mapeh' => 'mapeh',
            str_contains($normalized, 'music') => 'music',
            str_contains($normalized, 'arts') => 'arts',
            str_contains($normalized, 'physical education'), $normalized === 'pe', $normalized === 'p e' => 'pe',
            str_contains($normalized, 'health') => 'health',
            default => null,
        };
    }
}
