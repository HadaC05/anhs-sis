<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
        'student_subject_ID',
        'assignment_ID',
        'term_ID',
        'numeric_grade',
        'remarks',
        'status',
        'grade_status_ID',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'grade_return_reason_ID',
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
        });
    }

    public function studentSubject(): BelongsTo
    {
        return $this->belongsTo(StudentSubject::class, 'student_subject_ID', 'student_subject_ID');
    }

    /** Compatibility relation; enrollment is derived through student_subjects. */
    public function enrollment(): HasOneThrough
    {
        return $this->hasOneThrough(Enrollment::class, StudentSubject::class, 'student_subject_ID', 'enrollment_ID', 'student_subject_ID', 'enrollment_ID');
    }

    public function getEnrollmentIdAttribute(): ?int
    {
        $enrollment = $this->relationLoaded('enrollment') ? $this->getRelation('enrollment') : $this->enrollment()->first();

        return $enrollment?->enrollment_ID;
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'assignment_ID', 'assignment_ID');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(GradingTerm::class, 'term_ID', 'term_ID');
    }

    public function gradeStatus(): BelongsTo
    {
        return $this->belongsTo(GradeStatus::class, 'grade_status_ID', 'grade_status_ID');
    }

    public function gradeReturnReason(): BelongsTo
    {
        return $this->belongsTo(GradeReturnReason::class, 'grade_return_reason_ID', 'reason_ID');
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

    public function isSeniorHigh(): bool
    {
        return $this->enrollment?->semester !== null;
    }

    public function isJuniorHigh(): bool
    {
        return ! $this->isSeniorHigh();
    }

    /** Derived from the enrollment's curriculum-grade-level offering. */
    public function getSemesterIdAttribute(): ?int
    {
        return $this->enrollment?->curriculumGradeLevel?->semester_ID;
    }

    /** Resolve the legacy/UI period key through the canonical grading term. */
    public function getGradingPeriodAttribute(): string
    {
        $termKey = $this->term?->key ?? '';
        $semester = $this->studentSubject?->enrollment?->semester;

        return $semester ? GradingTerm::seniorHighPeriodKey($semester, $termKey) : $termKey;
    }

    public function setGradingPeriodAttribute(?string $periodKey): void
    {
        $termId = self::termIdForPeriodKey($periodKey);

        if (! $termId) {
            throw new InvalidArgumentException("Unknown grading period [{$periodKey}].");
        }

        $this->attributes['term_ID'] = $termId;
    }

    /** @param  Builder<self>  $query */
    public function scopeForPeriodKey(Builder $query, string $periodKey): Builder
    {
        $termId = self::termIdForPeriodKey($periodKey);

        return $query->where($this->qualifyColumn('term_ID'), $termId ?: 0);
    }

    public static function termIdForPeriodKey(?string $periodKey): ?int
    {
        $period = $periodKey && str_starts_with($periodKey, 'shs_')
            ? GradingTerm::findSeniorHighPeriodByKey($periodKey)
            : null;
        $termId = $period['term_ID'] ?? ($periodKey ? GradingTerm::query()->where('key', $periodKey)->value('term_ID') : null);

        return $termId ? (int) $termId : null;
    }
}
