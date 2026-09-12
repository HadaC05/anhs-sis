<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\GradeLevel;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PlacementAssessmentAdvisor
{
    private const AVERAGE_AGES = [
        'grade_7' => 12,
        'grade_8' => 13,
        'grade_9' => 14,
        'grade_10' => 15,
        'grade_11' => 16,
        'grade_12' => 17,
    ];

    public static function forEnrollment(Enrollment $enrollment): ?array
    {
        $assessment = self::assessmentForEnrollment($enrollment);

        if (! $assessment || $assessment['status'] !== 'overage') {
            return null;
        }

        $gradeLabel = GradeLevel::valueToLabel($assessment['grade_level']);
        $summary = "Above expected age range for {$gradeLabel}";
        $detail = "Expected {$assessment['minimum_age']}–{$assessment['maximum_age']} years at school year start.";

        return [
            'age' => $assessment['age'],
            'average_age' => $assessment['average_age'],
            'minimum_age' => $assessment['minimum_age'],
            'maximum_age' => $assessment['maximum_age'],
            'grade_level' => $assessment['grade_level'],
            'status' => $assessment['status'],
            'direction' => $assessment['direction'],
            'summary' => $summary,
            'detail' => $detail,
            'message' => trim("{$summary}. {$detail} Recommend placement assessment."),
        ];
    }

    public static function assessmentForEnrollment(Enrollment $enrollment): ?array
    {
        $student = $enrollment->relationLoaded('student')
            ? $enrollment->getRelation('student')
            : $enrollment->student()->first();

        $birthdate = $student?->birthdate;
        $gradeLevel = $enrollment->grade_level;
        $range = self::expectedRangeForGradeLevel($gradeLevel);

        if (! $birthdate || ! $range) {
            return null;
        }

        $academicYear = $enrollment->relationLoaded('academicYear')
            ? $enrollment->getRelation('academicYear')
            : $enrollment->academicYear()->first();

        $referenceDate = $academicYear?->start_date
            ?? $enrollment->created_at
            ?? now();

        $age = self::ageAt($birthdate, $referenceDate);
        $minimumAge = $range['minimum_age'];
        $maximumAge = $range['maximum_age'];

        if ($age >= $minimumAge && $age <= $maximumAge) {
            $status = 'appropriate';
            $direction = 'within';
        } elseif ($age < $minimumAge) {
            $status = 'underage';
            $direction = 'below';
        } else {
            $status = 'overage';
            $direction = 'above';
        }

        return [
            'age' => $age,
            'average_age' => $range['average_age'],
            'minimum_age' => $minimumAge,
            'maximum_age' => $maximumAge,
            'grade_level' => $gradeLevel,
            'status' => $status,
            'direction' => $direction,
        ];
    }

    public static function expectedRangeForGradeLevel(string $gradeLevel): ?array
    {
        $averageAge = self::AVERAGE_AGES[$gradeLevel] ?? null;

        if ($averageAge === null) {
            return null;
        }

        return [
            'average_age' => $averageAge,
            'minimum_age' => $averageAge,
            'maximum_age' => $averageAge + 1,
        ];
    }

    /**
     * @return list<array{
     *     grade_level: string,
     *     label: string,
     *     average_age: int,
     *     minimum_age: int,
     *     maximum_age: int
     * }>
     */
    public static function expectedRanges(): array
    {
        $ranges = [];

        foreach (array_keys(self::AVERAGE_AGES) as $gradeLevel) {
            $range = self::expectedRangeForGradeLevel($gradeLevel);

            if (! $range) {
                continue;
            }

            $ranges[] = [
                'grade_level' => $gradeLevel,
                'label' => GradeLevel::valueToLabel($gradeLevel),
                ...$range,
            ];
        }

        return $ranges;
    }

    private static function ageAt(CarbonInterface $birthdate, CarbonInterface $referenceDate): int
    {
        return $birthdate->diff($referenceDate)->y;
    }

    /**
     * @param  iterable<int, Enrollment>  $enrollments
     * @return array{
     *     reviewed: int,
     *     appropriate: int,
     *     overage: int,
     *     underage: int,
     *     average_age: float|null,
     *     by_grade: list<array{
     *         grade_level: string,
     *         label: string,
     *         reviewed: int,
     *         average_age: float|null,
     *         expected_average_age: int,
     *         appropriate: int,
     *         overage: int,
     *         underage: int
     *     }>
     * }
     */
    public static function summarizeAgeAlignment(iterable $enrollments): array
    {
        $appropriate = 0;
        $overage = 0;
        $underage = 0;
        $ages = [];
        $byGrade = [];

        foreach ($enrollments as $enrollment) {
            if (! $enrollment instanceof Enrollment) {
                continue;
            }

            $assessment = self::assessmentForEnrollment($enrollment);

            if (! $assessment) {
                continue;
            }

            $ages[] = $assessment['age'];
            $appropriate += $assessment['status'] === 'appropriate' ? 1 : 0;
            $overage += $assessment['status'] === 'overage' ? 1 : 0;
            $underage += $assessment['status'] === 'underage' ? 1 : 0;

            $gradeLevel = $assessment['grade_level'];

            if (! isset($byGrade[$gradeLevel])) {
                $byGrade[$gradeLevel] = [
                    'grade_level' => $gradeLevel,
                    'label' => GradeLevel::valueToLabel($gradeLevel),
                    'reviewed' => 0,
                    'ages' => [],
                    'expected_average_age' => $assessment['average_age'],
                    'appropriate' => 0,
                    'overage' => 0,
                    'underage' => 0,
                ];
            }

            $byGrade[$gradeLevel]['reviewed']++;
            $byGrade[$gradeLevel]['ages'][] = $assessment['age'];
            $byGrade[$gradeLevel][$assessment['status']]++;
        }

        $byGradeRows = Collection::make($byGrade)
            ->sortBy(fn (array $row): int => (int) preg_replace('/\D+/', '', $row['grade_level']))
            ->map(function (array $row): array {
                $agesForGrade = $row['ages'];
                unset($row['ages']);

                $row['average_age'] = count($agesForGrade) > 0
                    ? round(array_sum($agesForGrade) / count($agesForGrade), 1)
                    : null;

                return $row;
            })
            ->values()
            ->all();

        $reviewed = count($ages);

        return [
            'reviewed' => $reviewed,
            'appropriate' => $appropriate,
            'overage' => $overage,
            'underage' => $underage,
            'average_age' => $reviewed > 0 ? round(array_sum($ages) / $reviewed, 1) : null,
            'by_grade' => $byGradeRows,
        ];
    }
}
