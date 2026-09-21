<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    use HasFactory;

    protected $table = 'curriculum_grade_levels';

    protected $primaryKey = 'curriculum_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'description',
        'curricula_ID',
        'grade_ID',
        'semester_ID',
        'cluster_ID',
        'data_status_ID',
    ];

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class, 'curriculum_grade_level_ID', 'curriculum_ID');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'curriculum_grade_level_ID', 'curriculum_ID');
    }

    public function curricula(): BelongsTo
    {
        return $this->belongsTo(Curricula::class, 'curricula_ID', 'curricula_ID');
    }

    public function gradeLevel(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_ID', 'grade_ID');
    }

    public function gradingSemester(): BelongsTo
    {
        return $this->belongsTo(GradingSemester::class, 'semester_ID', 'semester_ID');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_ID', 'cluster_ID');
    }

    public function dataStatus(): BelongsTo
    {
        return $this->belongsTo(DataStatus::class, 'data_status_ID', 'data_status_ID');
    }

    public function getStatusAttribute(): bool
    {
        $status = $this->relationLoaded('dataStatus') ? $this->dataStatus : $this->dataStatus()->first();

        return $status?->key === 'active';
    }
}
