<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'enrollment_status',
    ];

    protected $appends = [
        'grade_level',
    ];

    public function getRouteKeyName(): string
    {
        return 'enrollment_ID';
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

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function subjectAssignments(): HasMany
    {
        return $this->hasMany(TeacherSubjectAssignment::class, 'section_ID', 'section_ID')
            ->where('SY_ID', $this->SY_ID);
    }

}
