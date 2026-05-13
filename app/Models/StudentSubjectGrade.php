<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'grading_period',
        'numeric_grade',
        'remarks',
        'status',
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

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'assignment_ID', 'assignment_ID');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'posted_by', 'staff_id');
    }
}
