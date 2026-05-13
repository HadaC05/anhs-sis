<?php

namespace App\Models;

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

    protected $fillable = [
        'name',
        'cluster_ID',
        'grade_ID',
        'grade_level',
        'staff_ID',
        'SY_ID',
        'curriculum_ID',
        'room',
        'capacity',
    ];

    protected $appends = [
        'grade_level',
    ];

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

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_ID', 'curriculum_ID');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'section_ID', 'section_ID');
    }
}
