<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherSubjectAssignment extends Model
{
    use HasFactory;

    protected $table = 'teacher_subject_assignments';
    protected $primaryKey = 'assignment_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'section_ID',
        'curr_subj_ID',
        'staff_ID',
        'SY_ID',
    ];

    public function getRouteKeyName(): string
    {
        return 'assignment_ID';
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_ID', 'section_ID');
    }

    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class, 'curr_subj_ID', 'curr_subj_ID');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_ID', 'staff_id');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'SY_ID', 'SY_ID');
    }

    public function grades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\StudentSubjectGrade::class, 'assignment_ID', 'assignment_ID');
    }

}
