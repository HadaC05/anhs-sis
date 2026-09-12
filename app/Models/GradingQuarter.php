<?php

namespace App\Models;

use App\Models\Concerns\HasGradingPeriodStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class GradingQuarter extends Model
{
    /** @use HasFactory<\Database\Factories\GradingQuarterFactory> */
    use HasFactory;

    use HasGradingPeriodStatus;

    protected $table = 'grading_quarters';

    protected $primaryKey = 'quarter_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'semester_ID',
        'key',
        'label',
        'quarter_number',
        'sort_order',
        'grading_period_status_ID',
    ];

    protected function casts(): array
    {
        return [
            'quarter_number' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return list<array{semester_key: string, key: string, label: string, quarter_number: int, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            ['semester_key' => GradingSemester::FIRST, 'key' => 'shs_sem1_q1', 'label' => 'Quarter 1', 'quarter_number' => 1, 'sort_order' => 1],
            ['semester_key' => GradingSemester::FIRST, 'key' => 'shs_sem1_q2', 'label' => 'Quarter 2', 'quarter_number' => 2, 'sort_order' => 2],
            ['semester_key' => GradingSemester::SECOND, 'key' => 'shs_sem2_q1', 'label' => 'Quarter 1', 'quarter_number' => 1, 'sort_order' => 1],
            ['semester_key' => GradingSemester::SECOND, 'key' => 'shs_sem2_q2', 'label' => 'Quarter 2', 'quarter_number' => 2, 'sort_order' => 2],
        ];
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(GradingSemester::class, 'semester_ID', 'semester_ID');
    }

    public function subjectGrades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'quarter_ID', 'quarter_ID');
    }

    /**
     * @return array{key: string, semester: string, quarter: int, semester_label: string, quarter_label: string, label: string, semester_ID: int|null, quarter_ID: int, is_active: bool}
     */
    public function toPeriodArray(): array
    {
        $semester = $this->semester;
        $semesterKey = $semester?->key === GradingSemester::SECOND ? GradingSemester::SECOND : GradingSemester::FIRST;
        $quarterNumber = (int) $this->quarter_number === 2 ? 2 : 1;
        $semesterLabel = $semester?->label ?? ($semesterKey === GradingSemester::SECOND ? 'Second Semester' : 'First Semester');

        return [
            'key' => $this->key,
            'semester' => $semesterKey,
            'quarter' => $quarterNumber,
            'semester_label' => $semesterLabel,
            'quarter_label' => $this->label,
            'label' => $semesterLabel.' · '.$this->label,
            'semester_ID' => $this->semester_ID !== null ? (int) $this->semester_ID : null,
            'quarter_ID' => (int) $this->quarter_ID,
            'is_active' => $this->isActive() && ($semester?->isActive() ?? false),
        ];
    }

    public static function findBySemesterAndQuarter(string $semester, int $quarter): ?self
    {
        if (! Schema::hasTable('grading_quarters')) {
            return null;
        }

        $normalizedSemester = $semester === GradingSemester::SECOND ? GradingSemester::SECOND : GradingSemester::FIRST;
        $normalizedQuarter = $quarter === 2 ? 2 : 1;

        return self::query()
            ->with(['semester.status', 'status'])
            ->where('quarter_number', $normalizedQuarter)
            ->whereHas('semester', fn ($query) => $query->where('key', $normalizedSemester))
            ->first();
    }
}
