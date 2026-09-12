<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class StudentSubjectGrade extends Model
{
    use HasFactory;

    protected $table = 'student_subject_grades';

    protected $primaryKey = 'grade_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'enrollment_ID',
        'assignment_ID',
        'term_ID',
        'semester_ID',
        'grading_period',
        'numeric_grade',
        'remarks',
        'status',
        'grade_status_ID',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'posted_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $grade): void {
            if (empty($grade->grade_status_ID) && $grade->status === '') {
                $grade->status = GradeStatus::DRAFT;
            }

            $grade->syncPeriodReferences();
        });
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'assignment_ID', 'assignment_ID');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(GradingTerm::class, 'term_ID', 'term_ID');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(GradingSemester::class, 'semester_ID', 'semester_ID');
    }

    public function gradeStatus(): BelongsTo
    {
        return $this->belongsTo(GradeStatus::class, 'grade_status_ID', 'grade_status_ID');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'posted_by', 'staff_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by', 'staff_id');
    }

    public function getStatusAttribute(): string
    {
        if (array_key_exists('status', $this->attributes) && ! array_key_exists('grade_status_ID', $this->attributes)) {
            return (string) $this->attributes['status'];
        }

        if ($this->relationLoaded('gradeStatus')) {
            return (string) ($this->getRelation('gradeStatus')?->slug ?? '');
        }

        $statusId = $this->attributes['grade_status_ID'] ?? null;

        return $statusId ? (string) (GradeStatus::slugFor((int) $statusId) ?? '') : '';
    }

    public function setStatusAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['grade_status_ID'] = GradeStatus::idFor(GradeStatus::DRAFT);

            return;
        }

        if (! Schema::hasTable('grade_statuses')) {
            $this->attributes['status'] = $value;

            return;
        }

        $statusId = GradeStatus::idFor($value);

        if ($statusId === null) {
            throw new InvalidArgumentException("Unknown grade status [{$value}].");
        }

        $this->attributes['grade_status_ID'] = $statusId;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->relationLoaded('gradeStatus')) {
            return $this->getRelation('gradeStatus')?->name ?: GradeStatus::nameFor($this->status);
        }

        return GradeStatus::nameFor($this->status);
    }

    /**
     * @param  Builder<self>  $query
     * @param  string|list<string>  $statuses
     * @return Builder<self>
     */
    public function scopeWhereStatus(Builder $query, string|array $statuses): Builder
    {
        $slugs = is_array($statuses) ? $statuses : [$statuses];

        return $query->whereIn($this->qualifyColumn('grade_status_ID'), GradeStatus::idsFor($slugs));
    }

    /**
     * @return list<string>
     */
    public static function teacherLockedStatuses(): array
    {
        return GradeStatus::teacherLockedSlugs();
    }

    public function isTeacherLocked(): bool
    {
        return in_array($this->status, GradeStatus::teacherLockedSlugs(), true);
    }

    public function isJuniorHigh(): bool
    {
        return $this->term_ID !== null && $this->semester_ID === null;
    }

    public function isSeniorHigh(): bool
    {
        return $this->semester_ID !== null;
    }

    /**
     * @return array{term_ID: int|null, semester_ID: int|null}
     */
    public static function referencesForPeriodKey(?string $periodKey): array
    {
        $empty = [
            'term_ID' => null,
            'semester_ID' => null,
        ];

        if (! $periodKey) {
            return $empty;
        }

        if (str_starts_with($periodKey, 'shs_')) {
            $seniorHighPeriod = GradingTerm::findSeniorHighPeriodByKey($periodKey);

            if ($seniorHighPeriod !== null) {
                return [
                    'term_ID' => isset($seniorHighPeriod['term_ID']) ? (int) $seniorHighPeriod['term_ID'] : null,
                    'semester_ID' => isset($seniorHighPeriod['semester_ID'])
                        ? (int) $seniorHighPeriod['semester_ID']
                        : GradingSemester::idFor($seniorHighPeriod['semester'] ?? null),
                ];
            }
        }

        if (Schema::hasTable('grading_terms')) {
            $termId = GradingTerm::query()->where('key', $periodKey)->value('term_ID');

            if ($termId) {
                return [
                    'term_ID' => (int) $termId,
                    'semester_ID' => null,
                ];
            }
        }

        return $empty;
    }

    public function syncPeriodReferences(): void
    {
        $periodKey = $this->attributes['grading_period'] ?? $this->grading_period;

        if (! is_string($periodKey) || $periodKey === '') {
            return;
        }

        $seniorHighPeriod = str_starts_with($periodKey, 'shs_')
            ? GradingTerm::findSeniorHighPeriodByKey($periodKey)
            : null;

        if ($seniorHighPeriod !== null && $seniorHighPeriod['key'] !== $periodKey) {
            $this->attributes['grading_period'] = $seniorHighPeriod['key'];
        }

        foreach (self::referencesForPeriodKey($this->attributes['grading_period'] ?? $periodKey) as $column => $value) {
            $this->attributes[$column] = $value;
        }
    }
}
