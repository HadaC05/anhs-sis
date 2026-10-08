<?php

namespace Database\Seeders;

use App\Models\Cluster;
use App\Models\Curricula;
use App\Models\Curriculum;
use App\Models\DataStatus;
use App\Models\GradeLevel;
use App\Models\GradingSemester;
use Illuminate\Database\Seeder;

class CurriculumSeeder extends Seeder
{
    /**
     * Junior high curricula keyed by grade level.
     *
     * @var array<string, string>
     */
    public const JUNIOR_HIGH_NAMES = [
        'grade_7' => 'Grade 7',
        'grade_8' => 'Grade 8',
        'grade_9' => 'Grade 9',
        'grade_10' => 'Grade 10',
    ];

    /**
     * Senior high clusters offered by the school.
     *
     * @var list<string>
     */
    public const SENIOR_HIGH_CLUSTERS = [
        'Arts, Social Sciences & Humanities',
        'Business and Entrepreneurship',
        'Science, Technology, Engineering and Mathematics',
        'Sports, Health, and Wellness',
        'ICT Support and Computer Programming Technologies',
        'Aesthetic, Wellness, and Human Care',
        'Agri-Fishery Business and Food Innovation',
        'Artisanal and Creative Enterprise',
        'Automotive and Small Engine Technologies',
        'Construction and Building Technologies',
        'Creative Arts and Design Technologies',
        'Hospitality and Tourism',
        'Industrial Technologies',
    ];

    /** @deprecated Use SENIOR_HIGH_CLUSTERS. */
    public const SENIOR_HIGH_TRACKS = self::SENIOR_HIGH_CLUSTERS;

    /**
     * Return the display name for a grade- and semester-specific SHS curriculum.
     */
    public static function seniorHighCurriculumName(int $grade, string $semester, string $cluster): string
    {
        return "Grade {$grade} {$semester} Semester - {$cluster}";
    }

    /**
     * Seed the application's curriculum table.
     */
    public function run(): void
    {
        $activeStatusId = DataStatus::query()->where('key', 'active')->value('data_status_ID');
        $gradeIds = GradeLevel::query()->pluck('grade_ID', 'grade_label');
        $semesterIds = GradingSemester::query()->pluck('semester_ID', 'key');
        $clusterIds = Cluster::query()->pluck('cluster_ID', 'name');
        $matatag = Curricula::query()->updateOrCreate(
            ['name' => 'MATATAG'],
            [
                'description' => 'MATATAG curriculum for Grades 7 to 10.',
                'data_status_ID' => $activeStatusId,
            ],
        );
        $strengthenedSeniorHigh = Curricula::query()->updateOrCreate(
            ['name' => 'Strengthened Senior High School'],
            [
                'description' => 'Strengthened Senior High School curriculum for Grades 11 and 12.',
                'data_status_ID' => $activeStatusId,
            ],
        );

        foreach (self::JUNIOR_HIGH_NAMES as $gradeLevel => $name) {
            $gradeNumber = str_replace('grade_', '', $gradeLevel);

            Curriculum::query()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "Junior High School Grade {$gradeNumber} subject offerings.",
                    'data_status_ID' => $activeStatusId,
                    'curricula_ID' => $matatag->curricula_ID,
                    'grade_ID' => $gradeIds['Grade '.$gradeNumber] ?? null,
                    'semester_ID' => $semesterIds[GradingSemester::FULL_YEAR] ?? null,
                ],
            );
        }

        foreach ([11, 12] as $grade) {
            foreach (['First', 'Second'] as $semester) {
                foreach (self::SENIOR_HIGH_CLUSTERS as $cluster) {
                    Curriculum::query()->updateOrCreate(
                        ['name' => self::seniorHighCurriculumName($grade, $semester, $cluster)],
                        [
                            'description' => "Senior High School Grade {$grade} {$semester} Semester curriculum for {$cluster}.",
                            'data_status_ID' => $activeStatusId,
                            'curricula_ID' => $strengthenedSeniorHigh->curricula_ID,
                            'grade_ID' => $gradeIds['Grade '.$grade] ?? null,
                            'semester_ID' => $semesterIds[strtolower($semester)] ?? null,
                            'cluster_ID' => $clusterIds[$cluster] ?? null,
                        ],
                    );
                }
            }
        }

        Curricula::query()
            ->whereNotIn('curricula_ID', [$matatag->curricula_ID, $strengthenedSeniorHigh->curricula_ID])
            ->whereDoesntHave('curriculumGradeLevels')
            ->delete();
    }
}
