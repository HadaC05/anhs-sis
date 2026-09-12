<?php

namespace App\Models;

use App\Models\Concerns\HasGradingPeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class GradingSemester extends Model
{
    /** @use HasFactory<\Database\Factories\GradingSemesterFactory> */
    use HasFactory;

    use HasGradingPeriodStatus;

    public const FIRST = 'first';

    public const SECOND = 'second';

    protected $table = 'grading_semesters';

    protected $primaryKey = 'semester_ID';

    public $incrementing = true;

    protected $keyType = 'int';

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

    /**
     * @return list<array{key: string, label: string, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            ['key' => self::FIRST, 'label' => 'First Semester', 'sort_order' => 1],
            ['key' => self::SECOND, 'label' => 'Second Semester', 'sort_order' => 2],
        ];
    }

    public function subjectGrades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'semester_ID', 'semester_ID');
    }

    public static function idFor(?string $key): ?int
    {
        if (! $key || ! Schema::hasTable('grading_semesters')) {
            return null;
        }

        $id = self::query()->where('key', $key)->value('semester_ID');

        return $id !== null ? (int) $id : null;
    }
}
