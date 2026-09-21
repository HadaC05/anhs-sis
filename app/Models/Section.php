<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $table = 'sections';

    protected $primaryKey = 'section_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $attributes = [
        'status' => true,
    ];

    protected $fillable = [
        'name',
        'cluster_ID',
        'grade_ID',
        'grade_level',
        'staff_ID',
        'SY_ID',
        'curriculum_grade_level_ID',
        // Compatibility for legacy importers; persisted as curriculum_grade_level_ID.
        'curriculum_ID',
        'room',
        'capacity',
        'status',
    ];

    protected $appends = [
        'grade_level',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'section_ID';
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_ID', 'cluster_ID');
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

        if (! $gradeLevel) {
            $offering = $this->relationLoaded('curriculumGradeLevel')
                ? $this->getRelation('curriculumGradeLevel')
                : $this->curriculumGradeLevel()->with('gradeLevel')->first();

            $gradeLevel = $offering?->relationLoaded('gradeLevel')
                ? $offering->getRelation('gradeLevel')
                : $offering?->gradeLevel;
        }

        return GradeLevel::labelToValue($gradeLevel?->grade_label);
    }

    public function setGradeLevelAttribute(string $value): void
    {
        $this->attributes['grade_ID'] = GradeLevel::idForValue($value);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_ID', 'staff_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'SY_ID', 'SY_ID');
    }

    public function curriculumGradeLevel(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_grade_level_ID', 'curriculum_ID');
    }

    /** @deprecated Use curriculum_grade_level_ID. */
    public function getCurriculumIdAttribute(): ?int
    {
        return isset($this->attributes['curriculum_grade_level_ID'])
            ? (int) $this->attributes['curriculum_grade_level_ID']
            : null;
    }

    /** @deprecated Use curriculum_grade_level_ID. */
    public function setCurriculumIdAttribute(?int $value): void
    {
        $this->attributes['curriculum_grade_level_ID'] = $value;
    }

    /** @deprecated Use curriculumGradeLevel(). */
    public function curriculum(): BelongsTo
    {
        return $this->curriculumGradeLevel();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'section_ID', 'section_ID');
    }

    public function teacherSubjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'section_ID', 'section_ID');
    }

    /**
     * @param  Builder<Section>  $query
     * @return Builder<Section>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    public function isActive(): bool
    {
        return (bool) $this->status;
    }
}
