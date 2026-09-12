<?php

namespace App\Models;

use App\Models\Concerns\HasGradingPeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class GradingTerm extends Model
{
    use HasFactory;
    use HasGradingPeriodStatus;

    public const SENIOR_HIGH_TERMS_PER_SEMESTER = 3;

    protected $table = 'grading_terms';

    protected $primaryKey = 'term_ID';

    protected $fillable = [
        'key',
        'label',
        'sort_order',
        'grading_period_status_ID',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function subjectGrades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'term_ID', 'term_ID');
    }

    public static function activePeriods(): array
    {
        return self::configuredPeriods();
    }

    public static function configuredPeriods(): array
    {
        if (! Schema::hasTable('grading_terms')) {
            return array_slice(self::fallbackPeriods(), 0, GradingTermSetting::current()->max_terms);
        }

        $terms = self::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('term_ID')
            ->limit(GradingTermSetting::current()->max_terms)
            ->get(['key', 'label']);

        if ($terms->isEmpty()) {
            return array_slice(self::fallbackPeriods(), 0, GradingTermSetting::current()->max_terms);
        }

        return $terms
            ->map(fn (self $term): array => [
                'key' => $term->key,
                'label' => $term->label,
            ])
            ->all();
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function gradingOpenPeriods(): array
    {
        $configured = self::configuredPeriods();
        $openCount = max(1, (int) GradingTermSetting::current()->open_terms_count);

        return array_slice($configured, 0, min($openCount, count($configured)));
    }

    /**
     * @return list<string>
     */
    public static function lockedGradingPeriodKeys(): array
    {
        $openPeriods = self::gradingOpenPeriods();

        if (count($openPeriods) <= 1) {
            return [];
        }

        return array_column(array_slice($openPeriods, 0, -1), 'key');
    }

    public static function currentEditablePeriodKey(): ?string
    {
        $openPeriods = self::gradingOpenPeriods();

        return $openPeriods[array_key_last($openPeriods)]['key'] ?? null;
    }

    public static function currentEditablePeriodLabel(): ?string
    {
        $openPeriods = self::gradingOpenPeriods();

        return $openPeriods[array_key_last($openPeriods)]['label'] ?? null;
    }

    public static function fallbackPeriods(): array
    {
        return [
            ['key' => 'term_1', 'label' => 'Term 1'],
            ['key' => 'term_2', 'label' => 'Term 2'],
            ['key' => 'term_3', 'label' => 'Term 3'],
            ['key' => 'term_4', 'label' => 'Term 4'],
        ];
    }

    public static function isSeniorHighSection(?Section $section): bool
    {
        return $section !== null && in_array($section->grade_level, ['grade_11', 'grade_12'], true);
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function periodsForSection(?Section $section, ?string $semester = null): array
    {
        if (self::isSeniorHighSection($section)) {
            return self::seniorHighPeriods($semester, true);
        }

        return self::activePeriods();
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function openPeriodsForSection(?Section $section, ?string $semester = null): array
    {
        if (self::isSeniorHighSection($section)) {
            return self::seniorHighOpenPeriods($semester);
        }

        return self::gradingOpenPeriods();
    }

    /**
     * @return list<string>
     */
    public static function lockedPeriodKeysForSection(?Section $section, ?string $semester = null): array
    {
        $openPeriods = self::openPeriodsForSection($section, $semester);
        $editableKey = self::currentEditablePeriodKeyForSection($section, $semester);

        return array_values(array_filter(
            array_column($openPeriods, 'key'),
            fn (string $key): bool => $key !== $editableKey,
        ));
    }

    public static function currentEditablePeriodKeyForSection(?Section $section, ?string $semester = null): ?string
    {
        if (! self::isSeniorHighSection($section)) {
            return self::currentEditablePeriodKey();
        }

        $current = self::currentSeniorHighPeriod();

        if ($semester && $current['semester'] !== $semester) {
            return null;
        }

        return $current['key'];
    }

    public static function currentEditablePeriodLabelForSection(?Section $section, ?string $semester = null): ?string
    {
        if (! self::isSeniorHighSection($section)) {
            return self::currentEditablePeriodLabel();
        }

        $current = self::currentSeniorHighPeriod();

        if ($semester && $current['semester'] !== $semester) {
            return null;
        }

        return $current['label'];
    }

    /**
     * @return array<int, array{key: string, label: string, term_ID?: int}>
     */
    public static function seniorHighTerms(): array
    {
        $limit = self::SENIOR_HIGH_TERMS_PER_SEMESTER;

        if (Schema::hasTable('grading_terms')) {
            $terms = self::query()
                ->orderBy('sort_order')
                ->orderBy('term_ID')
                ->limit($limit)
                ->get(['term_ID', 'key', 'label']);

            if ($terms->isNotEmpty()) {
                return $terms
                    ->map(fn (self $term): array => [
                        'key' => $term->key,
                        'label' => $term->label,
                        'term_ID' => (int) $term->term_ID,
                    ])
                    ->all();
            }
        }

        return array_slice(self::fallbackPeriods(), 0, $limit);
    }

    /**
     * @return array<int, array{key: string, semester: string, term: int, semester_label: string, term_label: string, label: string, semester_ID?: int|null, term_ID?: int, is_active?: bool}>
     */
    public static function seniorHighPeriods(?string $semester = null, bool $activeOnly = false): array
    {
        $normalizedSemester = in_array($semester, [GradingSemester::FIRST, GradingSemester::SECOND], true)
            ? $semester
            : null;
        $terms = self::seniorHighTerms();
        $semesters = [];

        if (Schema::hasTable('grading_semesters')) {
            $semesters = GradingSemester::query()
                ->with('status')
                ->when($normalizedSemester, fn ($query) => $query->where('key', $normalizedSemester))
                ->when($activeOnly, fn ($query) => $query->active())
                ->orderBy('sort_order')
                ->orderBy('semester_ID')
                ->get()
                ->all();
        }

        if ($semesters === []) {
            $fallbackSemesters = $normalizedSemester
                ? [$normalizedSemester]
                : [GradingSemester::FIRST, GradingSemester::SECOND];

            $periods = [];

            foreach ($fallbackSemesters as $semesterKey) {
                foreach (array_keys($terms) as $index) {
                    $periods[] = self::seniorHighPeriod($semesterKey, $index + 1);
                }
            }

            return $periods;
        }

        $periods = [];

        foreach ($semesters as $gradingSemester) {
            foreach ($terms as $index => $term) {
                $periods[] = self::seniorHighPeriodFromParts($gradingSemester, $term, $index + 1);
            }
        }

        return $periods;
    }

    /**
     * @return array{key: string, semester: string, term: int, semester_label: string, term_label: string, label: string, semester_ID?: int|null, term_ID?: int, is_active?: bool}
     */
    public static function seniorHighPeriod(string $semester, int $termNumber): array
    {
        $normalizedSemester = $semester === GradingSemester::SECOND ? GradingSemester::SECOND : GradingSemester::FIRST;
        $terms = self::seniorHighTerms();
        $normalizedTerm = max(1, min($termNumber, max(1, count($terms))));
        $term = $terms[$normalizedTerm - 1] ?? ['key' => 'term_'.$normalizedTerm, 'label' => 'Term '.$normalizedTerm];
        $gradingSemester = Schema::hasTable('grading_semesters')
            ? GradingSemester::query()->with('status')->where('key', $normalizedSemester)->first()
            : null;

        if ($gradingSemester) {
            return self::seniorHighPeriodFromParts($gradingSemester, $term, $normalizedTerm);
        }

        $semesterLabel = $normalizedSemester === GradingSemester::SECOND ? 'Second Semester' : 'First Semester';

        return [
            'key' => self::seniorHighPeriodKey($normalizedSemester, $term['key']),
            'semester' => $normalizedSemester,
            'term' => $normalizedTerm,
            'semester_label' => $semesterLabel,
            'term_label' => $term['label'],
            'label' => $semesterLabel.' · '.$term['label'],
            'term_ID' => isset($term['term_ID']) ? (int) $term['term_ID'] : null,
        ];
    }

    /**
     * @param  array{key: string, label: string, term_ID?: int}  $term
     * @return array{key: string, semester: string, term: int, semester_label: string, term_label: string, label: string, semester_ID: int|null, term_ID?: int, is_active: bool}
     */
    public static function seniorHighPeriodFromParts(GradingSemester $semester, array $term, int $termNumber): array
    {
        $semesterKey = $semester->key === GradingSemester::SECOND ? GradingSemester::SECOND : GradingSemester::FIRST;

        return [
            'key' => self::seniorHighPeriodKey($semesterKey, $term['key']),
            'semester' => $semesterKey,
            'term' => $termNumber,
            'semester_label' => $semester->label,
            'term_label' => $term['label'],
            'label' => $semester->label.' · '.$term['label'],
            'semester_ID' => (int) $semester->semester_ID,
            'term_ID' => isset($term['term_ID']) ? (int) $term['term_ID'] : null,
            'is_active' => $semester->isActive(),
        ];
    }

    public static function seniorHighPeriodKey(string $semester, string $termKey): string
    {
        $semesterNumber = $semester === GradingSemester::SECOND ? 2 : 1;

        return 'shs_sem'.$semesterNumber.'_'.$termKey;
    }

    /**
     * @return array{key: string, semester: string, term: int, semester_label: string, term_label: string, label: string, semester_ID?: int|null, term_ID?: int, is_active?: bool}|null
     */
    public static function findSeniorHighPeriodByKey(string $periodKey): ?array
    {
        $legacy = [
            'shs_sem1_q1' => [GradingSemester::FIRST, 1],
            'shs_sem1_q2' => [GradingSemester::FIRST, 2],
            'shs_sem2_q1' => [GradingSemester::SECOND, 1],
            'shs_sem2_q2' => [GradingSemester::SECOND, 2],
        ];

        if (isset($legacy[$periodKey])) {
            return self::seniorHighPeriod($legacy[$periodKey][0], $legacy[$periodKey][1]);
        }

        foreach (self::seniorHighPeriods() as $period) {
            if ($period['key'] === $periodKey) {
                return $period;
            }
        }

        return null;
    }

    /**
     * @return array{key: string, semester: string, term: int, semester_label: string, term_label: string, label: string, semester_ID?: int|null, term_ID?: int, is_active?: bool}
     */
    public static function currentSeniorHighPeriod(): array
    {
        $settings = GradingTermSetting::current();

        return self::seniorHighPeriod($settings->seniorHighSemester(), $settings->seniorHighTerm());
    }

    /**
     * @return array<int, array{key: string, semester: string, term: int, semester_label: string, term_label: string, label: string, semester_ID?: int|null, term_ID?: int, is_active?: bool}>
     */
    public static function seniorHighOpenPeriods(?string $semester = null): array
    {
        $current = self::currentSeniorHighPeriod();
        $currentPosition = self::seniorHighPeriodPosition($current['semester'], $current['term']);

        return array_values(array_filter(
            self::seniorHighPeriods($semester, true),
            fn (array $period): bool => self::seniorHighPeriodPosition($period['semester'], $period['term']) <= $currentPosition,
        ));
    }

    /**
     * @return list<string>
     */
    public static function lockedSeniorHighPeriodKeys(?string $semester = null): array
    {
        $openPeriods = self::seniorHighOpenPeriods($semester);
        $current = self::currentSeniorHighPeriod();
        $currentIsInSemester = ! $semester || $current['semester'] === $semester;

        if (! $currentIsInSemester) {
            return array_column($openPeriods, 'key');
        }

        if (count($openPeriods) <= 1) {
            return [];
        }

        return array_values(array_filter(
            array_column($openPeriods, 'key'),
            fn (string $key): bool => $key !== $current['key'],
        ));
    }

    public static function currentSeniorHighPeriodKey(): string
    {
        return self::currentSeniorHighPeriod()['key'];
    }

    public static function currentSeniorHighPeriodLabel(): string
    {
        return self::currentSeniorHighPeriod()['label'];
    }

    public static function seniorHighPeriodPosition(string $semester, int $term): int
    {
        $offset = $semester === GradingSemester::SECOND ? self::SENIOR_HIGH_TERMS_PER_SEMESTER : 0;

        return $offset + max(1, min($term, self::SENIOR_HIGH_TERMS_PER_SEMESTER));
    }

    public static function syncActiveStatus(?int $maxTerms = null): void
    {
        $limit = max(0, $maxTerms ?? (int) GradingTermSetting::current()->max_terms);
        $activeId = GradingPeriodStatus::activeId();
        $inactiveId = GradingPeriodStatus::inactiveId();

        if ($activeId === null || $inactiveId === null) {
            return;
        }

        $terms = self::query()
            ->orderBy('sort_order')
            ->orderBy('term_ID')
            ->get();

        foreach ($terms as $index => $term) {
            $statusId = $index < $limit ? $activeId : $inactiveId;

            if ((int) $term->grading_period_status_ID !== $statusId) {
                $term->update(['grading_period_status_ID' => $statusId]);
            }
        }
    }

    /**
     * @param  array<int, array{key: string, label: string}>  $periods
     */
    public static function periodGroupLabel(array $periods): string
    {
        if ($periods === []) {
            return 'Term';
        }

        $firstLabel = $periods[0]['label'] ?? '';

        if (preg_match('/\b(quarter|term|semester|period)\b/i', $firstLabel, $matches)) {
            return ucfirst(strtolower($matches[1]));
        }

        return 'Period';
    }

    public static function periodColumnLabel(string $label): string
    {
        if (preg_match('/(\d+)\s*$/', $label, $matches)) {
            return $matches[1];
        }

        return $label;
    }

    public static function periodSignatureLabel(string $label, int $index): string
    {
        $ordinals = ['1st', '2nd', '3rd', '4th', '5th', '6th'];
        $ordinal = $ordinals[$index] ?? ($index + 1).'th';

        if (preg_match('/(\d+)\s*$/', $label)) {
            return $ordinal.' '.self::periodGroupLabel([['key' => '', 'label' => $label]]);
        }

        return $label;
    }

    /**
     * @param  array<int, array{key: string, label: string}>  $periods
     */
    public static function periodRatingLabel(array $periods): string
    {
        $group = self::periodGroupLabel($periods);

        return $group === 'Quarter' ? 'Quarterly Rating' : $group.' Rating';
    }
}
