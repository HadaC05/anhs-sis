<?php

namespace App\Support;

use App\Models\Cluster;
use App\Models\Curriculum;
use App\Models\Enrollment;
use App\Models\EnrollmentStatus;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VacantSectionAssigner
{
    public const DEFAULT_CAPACITY = 45;

    /**
     * @var array<string, string>
     */
    private const CLUSTER_SUFFIXES = [
        'Arts, Social Sciences & Humanities' => 'ASSH',
        'Business and Entrepreneurship' => 'BUS',
        'Science, Technology, Engineering and Mathematics' => 'STEM',
    ];

    public static function resolve(Enrollment $enrollment): ?Section
    {
        return DB::transaction(function () use ($enrollment): ?Section {
            $section = self::firstVacant($enrollment);

            if ($section) {
                return $section;
            }

            return self::createForEnrollment($enrollment);
        });
    }

    public static function firstVacant(Enrollment $enrollment): ?Section
    {
        $sections = self::matchingSectionsQuery($enrollment)
            ->withCount(['enrollments as active_enrollments_count' => function ($query): void {
                $query->whereIn('enrollment_status_ID', EnrollmentStatus::activeIds());
            }])
            ->orderBy('section_ID')
            ->lockForUpdate()
            ->get();

        foreach ($sections as $section) {
            $capacity = (int) $section->capacity;
            $current = (int) $section->active_enrollments_count;

            if ($capacity === 0 || $current < $capacity) {
                return $section;
            }
        }

        return null;
    }

    public static function createForEnrollment(Enrollment $enrollment): ?Section
    {
        $gradeLevel = $enrollment->grade_level;
        $isSeniorHigh = in_array($gradeLevel, ['grade_11', 'grade_12'], true);
        $clusterId = $enrollment->cluster_ID ? (int) $enrollment->cluster_ID : null;

        if ($isSeniorHigh && $clusterId === null) {
            return null;
        }

        $curriculumId = self::curriculumIdFor($gradeLevel, $clusterId);

        if ($curriculumId === null) {
            return null;
        }

        for ($attempt = 0; $attempt < 26; $attempt++) {
            try {
                return Section::query()->create([
                    'name' => self::nextSectionName($enrollment),
                    'cluster_ID' => $clusterId,
                    'grade_ID' => $enrollment->grade_ID,
                    'staff_ID' => null,
                    'SY_ID' => $enrollment->SY_ID,
                    'curriculum_ID' => $curriculumId,
                    'room' => null,
                    'capacity' => self::DEFAULT_CAPACITY,
                    'status' => true,
                ]);
            } catch (QueryException $exception) {
                if (! self::isUniqueNameConflict($exception) || $attempt === 25) {
                    throw $exception;
                }
            }
        }

        return null;
    }

    public static function curriculumIdFor(string $gradeLevel, ?int $clusterId): ?int
    {
        if (in_array($gradeLevel, ['grade_11', 'grade_12'], true)) {
            $cluster = Cluster::query()->find($clusterId);

            if (! $cluster) {
                return null;
            }

            $curriculumName = $cluster->name;
        } else {
            $curriculumName = GradeLevel::valueToLabel($gradeLevel);
        }

        return Curriculum::query()
            ->where('name', $curriculumName)
            ->whereHas('dataStatus', fn ($status) => $status->where('key', 'active'))
            ->value('curriculum_ID');
    }

    /**
     * @return Builder<Section>
     */
    private static function matchingSectionsQuery(Enrollment $enrollment): Builder
    {
        return Section::query()
            ->active()
            ->where('SY_ID', $enrollment->SY_ID)
            ->where('grade_ID', $enrollment->grade_ID)
            ->when($enrollment->cluster_ID, function ($query) use ($enrollment): void {
                $query->where('cluster_ID', $enrollment->cluster_ID);
            }, function ($query): void {
                $query->whereNull('cluster_ID');
            });
    }

    private static function nextSectionName(Enrollment $enrollment): string
    {
        $prefix = self::namePrefix($enrollment);
        $taken = Section::query()
            ->where('SY_ID', $enrollment->SY_ID)
            ->pluck('name')
            ->all();

        $sequence = 'A';

        while (in_array($prefix.$sequence, $taken, true)) {
            $sequence = self::incrementLetter($sequence);
        }

        return $prefix.$sequence;
    }

    private static function namePrefix(Enrollment $enrollment): string
    {
        $gradeNumber = preg_replace('/\D+/', '', $enrollment->grade_level) ?: '0';
        $prefix = 'G'.$gradeNumber.'-';

        if (! in_array($enrollment->grade_level, ['grade_11', 'grade_12'], true)) {
            return $prefix;
        }

        $enrollment->loadMissing('cluster');
        $clusterName = $enrollment->cluster?->name;
        $suffix = self::CLUSTER_SUFFIXES[$clusterName] ?? Str::upper(Str::substr(preg_replace('/[^A-Za-z]/', '', (string) $clusterName) ?: 'SEC', 0, 4));

        return $prefix.$suffix.'-';
    }

    private static function incrementLetter(string $letter): string
    {
        $letter = strtoupper($letter);
        $length = strlen($letter);

        for ($i = $length - 1; $i >= 0; $i--) {
            if ($letter[$i] !== 'Z') {
                $letter[$i] = chr(ord($letter[$i]) + 1);

                return $letter;
            }

            $letter[$i] = 'A';
        }

        return 'A'.$letter;
    }

    private static function isUniqueNameConflict(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = Str::lower($exception->getMessage());

        return $sqlState === '23000'
            || in_array($driverCode, [19, 1062], true)
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }
}
