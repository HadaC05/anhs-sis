<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class CurriculumSubject extends Model
{
    use HasFactory;

    protected $table = 'curriculum_subjects';

    protected $primaryKey = 'curr_subj_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'curriculum_grade_level_ID',
        // Legacy input aliases resolve to the curriculum-grade-level identifier.
        'curriculum_ID',
        'subject_ID',
        'cluster_ID',
        'grade_ID',
        'grade_level',
        'semester_ID',
        'semester',
    ];

    protected $appends = [
        'grade_level',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->curriculumGradeLevel();
    }

    public function curriculumGradeLevel(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_grade_level_ID', 'curriculum_ID');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_ID', 'subject_ID');
    }

    public function cluster(): HasOneThrough
    {
        return $this->hasOneThrough(Cluster::class, Curriculum::class, 'curriculum_ID', 'cluster_ID', 'curriculum_grade_level_ID', 'cluster_ID');
    }

    public function gradeLevel(): HasOneThrough
    {
        return $this->hasOneThrough(GradeLevel::class, Curriculum::class, 'curriculum_ID', 'grade_ID', 'curriculum_grade_level_ID', 'grade_ID');
    }

    public function gradingSemester(): HasOneThrough
    {
        return $this->hasOneThrough(GradingSemester::class, Curriculum::class, 'curriculum_ID', 'semester_ID', 'curriculum_grade_level_ID', 'semester_ID');
    }

    /** Legacy semester key; Full Year remains null for JHS callers. */
    public function getSemesterAttribute(): ?string
    {
        $semester = $this->relationLoaded('gradingSemester')
            ? $this->getRelation('gradingSemester')
            : $this->gradingSemester()->first();

        return $semester?->key === GradingSemester::FULL_YEAR ? null : $semester?->key;
    }

    public function setSemesterAttribute(?string $value): void
    {
        // Semester belongs to the selected curriculum-grade-level offering.
    }

    /** Compatibility value for existing presentation and assignment code. */
    public function getGradeLevelAttribute(): string
    {
        $gradeLevel = $this->relationLoaded('gradeLevel')
            ? $this->getRelation('gradeLevel')
            : $this->gradeLevel()->first();

        return GradeLevel::labelToValue($gradeLevel?->grade_label);
    }

    public function setGradeLevelAttribute(string $value): void
    {
        // Grade level belongs to the selected curriculum-grade-level offering.
    }

    public function setGradeIdAttribute(?int $value): void
    {
        // Grade level belongs to the selected curriculum-grade-level offering.
    }

    public function setClusterIdAttribute(?int $value): void
    {
        // Cluster belongs to the selected curriculum-grade-level offering.
    }

    public function setCurriculumIdAttribute(?int $value): void
    {
        $this->attributes['curriculum_grade_level_ID'] = $value;
    }

    public function getCurriculumIdAttribute(): ?int
    {
        return $this->attributes['curriculum_grade_level_ID'] ?? null;
    }

    /**
     * Compatibility value for code that reads the subject's grade directly.
     * Grade context is stored by the curriculum-grade-level offering.
     */
    public function getGradeIdAttribute(): ?int
    {
        $offering = $this->relationLoaded('curriculumGradeLevel')
            ? $this->getRelation('curriculumGradeLevel')
            : $this->curriculumGradeLevel()->first();

        return $offering?->grade_ID;
    }

    /**
     * Compatibility value for code that reads the subject's cluster directly.
     * Cluster context is stored by the curriculum-grade-level offering.
     */
    public function getClusterIdAttribute(): ?int
    {
        $offering = $this->relationLoaded('curriculumGradeLevel')
            ? $this->getRelation('curriculumGradeLevel')
            : $this->curriculumGradeLevel()->first();

        return $offering?->cluster_ID;
    }
}
