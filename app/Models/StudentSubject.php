<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentSubject extends Model
{
    use HasFactory;

    protected $table = 'student_subjects';
    protected $primaryKey = 'student_subject_ID';
    protected $keyType = 'int';

    protected $fillable = ['enrollment_ID', 'curr_subj_ID'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function curriculumSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class, 'curr_subj_ID', 'curr_subj_ID');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'student_subject_ID', 'student_subject_ID');
    }
}
