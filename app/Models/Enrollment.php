<?php

namespace App\Models;

use App\Support\PlacementAssessmentAdvisor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'enrollments';

    protected $primaryKey = 'enrollment_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'student_ID',
        'section_ID',
        'SY_ID',
        'cluster_ID',
        'course_ID',
        'grade_ID',
        'grade_level',
        'semester',
        'learner_type',
        'learner_type_ID',
        'last_grade_level_completed',
        'last_school_year_completed',
        'last_school_attended',
        'school_id_from_previous_school',
        'enrollment_status',
        'enrollment_status_ID',
        'placement_status',
        'placement_status_ID',
        'promotion_status',
        'promotion_status_ID',
    ];

    protected $appends = [
        'grade_level',
        'enrollment_status',
        'learner_type',
        'placement_status',
        'promotion_status',
    ];

    public function getRouteKeyName(): string
    {
        return 'enrollment_ID';
    }

    protected static function booted(): void
    {
        static::creating(function (Enrollment $enrollment): void {
            if (empty($enrollment->attributes['learner_type_ID'])) {
                $enrollment->attributes['learner_type_ID'] = LearnerType::idFor(LearnerType::REGULAR);
            }

            if (empty($enrollment->attributes['placement_status_ID'])) {
                $enrollment->attributes['placement_status_ID'] = PlacementStatus::idFor(PlacementStatus::PENDING);
            }

            if (empty($enrollment->attributes['promotion_status_ID'])) {
                $enrollment->attributes['promotion_status_ID'] = PromotionStatus::idFor(PromotionStatus::PENDING);
            }
        });
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_ID');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_ID', 'section_ID');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'SY_ID', 'SY_ID');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_ID', 'cluster_ID');
    }

    public function preferredCourse(): BelongsTo
    {
        return $this->belongsTo(PreferredCourse::class, 'course_ID', 'course_ID');
    }

    public function enrollmentStatus(): BelongsTo
    {
        return $this->belongsTo(EnrollmentStatus::class, 'enrollment_status_ID', 'enrollment_status_ID');
    }

    public function learnerType(): BelongsTo
    {
        return $this->belongsTo(LearnerType::class, 'learner_type_ID', 'learner_type_ID');
    }

    public function placementStatus(): BelongsTo
    {
        return $this->belongsTo(PlacementStatus::class, 'placement_status_ID', 'placement_status_ID');
    }

    public function promotionStatus(): BelongsTo
    {
        return $this->belongsTo(PromotionStatus::class, 'promotion_status_ID', 'promotion_status_ID');
    }

    public function getPromotionStatusAttribute(): string
    {
        if ($this->relationLoaded('promotionStatus')) {
            return (string) ($this->getRelation('promotionStatus')?->slug ?? '');
        }

        return PromotionStatus::slugFor($this->attributes['promotion_status_ID'] ?? null) ?? '';
    }

    public function setPromotionStatusAttribute(?string $value): void
    {
        $statusId = PromotionStatus::idFor($value ?: PromotionStatus::PENDING);
        if ($statusId === null) {
            throw new InvalidArgumentException("Unknown promotion status [{$value}].");
        }

        $this->attributes['promotion_status_ID'] = $statusId;
    }

    public function getPromotionStatusLabelAttribute(): string
    {
        if ($this->relationLoaded('promotionStatus')) {
            return $this->getRelation('promotionStatus')?->name ?: PromotionStatus::nameFor($this->promotion_status);
        }

        return PromotionStatus::nameFor($this->promotion_status);
    }

    public function getLearnerTypeAttribute(): string
    {
        if (array_key_exists('learner_type', $this->attributes) && ! array_key_exists('learner_type_ID', $this->attributes)) {
            return (string) $this->attributes['learner_type'];
        }

        if ($this->relationLoaded('learnerType')) {
            return (string) ($this->getRelation('learnerType')?->slug ?? '');
        }

        $typeId = $this->attributes['learner_type_ID'] ?? null;

        return $typeId ? (string) (LearnerType::slugFor((int) $typeId) ?? '') : '';
    }

    public function setLearnerTypeAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['learner_type_ID'] = LearnerType::idFor(LearnerType::REGULAR);

            return;
        }

        if (! Schema::hasTable('learner_types')) {
            $this->attributes['learner_type'] = LearnerType::normalizeSlug($value) ?? $value;

            return;
        }

        $typeId = LearnerType::idFor($value);

        if ($typeId === null) {
            throw new InvalidArgumentException("Unknown learner type [{$value}].");
        }

        $this->attributes['learner_type_ID'] = $typeId;
    }

    public function getLearnerTypeLabelAttribute(): string
    {
        if ($this->relationLoaded('learnerType')) {
            return $this->getRelation('learnerType')?->name ?: LearnerType::nameFor($this->learner_type);
        }

        return LearnerType::nameFor($this->learner_type);
    }

    public function getEnrollmentStatusAttribute(): string
    {
        if (array_key_exists('enrollment_status', $this->attributes) && ! array_key_exists('enrollment_status_ID', $this->attributes)) {
            return (string) $this->attributes['enrollment_status'];
        }

        if ($this->relationLoaded('enrollmentStatus')) {
            return (string) ($this->getRelation('enrollmentStatus')?->slug ?? '');
        }

        $statusId = $this->attributes['enrollment_status_ID'] ?? null;

        return $statusId ? (string) (EnrollmentStatus::slugFor((int) $statusId) ?? '') : '';
    }

    public function setEnrollmentStatusAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['enrollment_status_ID'] = null;

            return;
        }

        if (! Schema::hasTable('enrollment_statuses')) {
            $this->attributes['enrollment_status'] = $value;

            return;
        }

        $statusId = EnrollmentStatus::idFor($value);

        if ($statusId === null) {
            throw new InvalidArgumentException("Unknown enrollment status [{$value}].");
        }

        $this->attributes['enrollment_status_ID'] = $statusId;
    }

    public function getEnrollmentStatusLabelAttribute(): string
    {
        if ($this->relationLoaded('enrollmentStatus')) {
            return $this->getRelation('enrollmentStatus')?->name ?: EnrollmentStatus::nameFor($this->enrollment_status);
        }

        return EnrollmentStatus::nameFor($this->enrollment_status);
    }

    public function getPlacementStatusAttribute(): string
    {
        if (array_key_exists('placement_status', $this->attributes) && ! array_key_exists('placement_status_ID', $this->attributes)) {
            return (string) $this->attributes['placement_status'];
        }

        if ($this->relationLoaded('placementStatus')) {
            return (string) ($this->getRelation('placementStatus')?->slug ?? '');
        }

        $statusId = $this->attributes['placement_status_ID'] ?? null;

        return $statusId ? (string) (PlacementStatus::slugFor((int) $statusId) ?? '') : '';
    }

    public function setPlacementStatusAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['placement_status_ID'] = PlacementStatus::idFor(PlacementStatus::PENDING);

            return;
        }

        if (! Schema::hasTable('placement_statuses')) {
            $this->attributes['placement_status'] = $value;

            return;
        }

        $statusId = PlacementStatus::idFor($value);

        if ($statusId === null) {
            throw new InvalidArgumentException("Unknown placement status [{$value}].");
        }

        $this->attributes['placement_status_ID'] = $statusId;
    }

    public function getPlacementStatusLabelAttribute(): string
    {
        if ($this->relationLoaded('placementStatus')) {
            return $this->getRelation('placementStatus')?->name ?: PlacementStatus::nameFor($this->placement_status);
        }

        return PlacementStatus::nameFor($this->placement_status);
    }

    public function isPlacementRecommended(): bool
    {
        return $this->placement_status === PlacementStatus::RECOMMENDED;
    }

    public function hasPlacementStatusMark(): bool
    {
        return ! in_array($this->placement_status, [
            '',
            PlacementStatus::PENDING,
            PlacementStatus::AGE_APPROPRIATE,
        ], true);
    }

    public function markRecommendedIfOverage(): bool
    {
        if ($this->placement_status !== PlacementStatus::PENDING && $this->placement_status !== '') {
            return false;
        }

        $placementStatus = $this->requiresPlacementAssessment()
            ? PlacementStatus::RECOMMENDED
            : PlacementStatus::AGE_APPROPRIATE;

        $this->update(['placement_status' => $placementStatus]);

        return true;
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_ID', 'grade_ID');
    }

    public function getGradeLevelAttribute(): string
    {
        $gradeLevel = $this->relationLoaded('gradeLevel')
            ? $this->getRelation('gradeLevel')
            : $this->gradeLevel()->first();

        return GradeLevel::labelToValue($gradeLevel?->grade_label);
    }

    public function isSeniorHigh(): bool
    {
        return in_array($this->grade_level, ['grade_11', 'grade_12'], true);
    }

    public function setGradeLevelAttribute(string $value): void
    {
        $this->attributes['grade_ID'] = GradeLevel::idForValue($value);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function placementAssessmentRecommendation(): ?array
    {
        return PlacementAssessmentAdvisor::forEnrollment($this);
    }

    public function requiresPlacementAssessment(): bool
    {
        return $this->placementAssessmentRecommendation() !== null;
    }

    public function requiresPreviousSchoolDetails(): bool
    {
        return LearnerType::requiresPreviousSchool($this->learner_type);
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'section_ID', 'section_ID')
            ->where('SY_ID', $this->SY_ID);
    }
}
