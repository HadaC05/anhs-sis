<?php

namespace Database\Seeders;

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
     * Senior high tracks offered by the school.
     *
     * @var list<string>
     */
    public const SENIOR_HIGH_TRACKS = [
        'Arts, Social Sciences & Humanities',
        'Business and Entrepreneurship',
        'Science, Technology, Engineering and Mathematics',
    ];

    /**
     * Return the display name for a grade- and semester-specific SHS curriculum.
     */
    public static function seniorHighCurriculumName(int $grade, string $semester, string $track): string
    {
        return "Grade {$grade} {$semester} Semester - {$track}";
    }

    /**
     * Seed the application's curriculum table.
     */
    public function run(): void
    {
        $activeStatusId = DataStatus::query()->where('key', 'active')->value('data_status_ID');
        $gradeIds = GradeLevel::query()->pluck('grade_ID', 'grade_label');
        $semesterIds = GradingSemester::query()->pluck('semester_ID', 'key');
        $juniorHigh = Curricula::query()->updateOrCreate(
            ['name' => 'Junior High School'],
            [
                'description' => 'Junior High School subject offerings.',
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
                    'curricula_ID' => $juniorHigh->curricula_ID,
                    'grade_ID' => $gradeIds['Grade '.$gradeNumber] ?? null,
                    'semester_ID' => $semesterIds[GradingSemester::FULL_YEAR] ?? null,
                ],
            );
        }

        foreach ([11, 12] as $grade) {
            foreach (['First', 'Second'] as $semester) {
                foreach (self::SENIOR_HIGH_TRACKS as $track) {
                    $curricula = Curricula::query()->updateOrCreate(
                        ['name' => $track],
                        [
                            'description' => "Senior High School curriculum for {$track}.",
                            'data_status_ID' => $activeStatusId,
                        ],
                    );

                    Curriculum::query()->updateOrCreate(
                        ['name' => self::seniorHighCurriculumName($grade, $semester, $track)],
                        [
                            'description' => "Senior High School Grade {$grade} {$semester} Semester curriculum for {$track}.",
                            'data_status_ID' => $activeStatusId,
                            'curricula_ID' => $curricula->curricula_ID,
                            'grade_ID' => $gradeIds['Grade '.$grade] ?? null,
                            'semester_ID' => $semesterIds[strtolower($semester)] ?? null,
                        ],
                    );
                }
            }
        }
    }
}
