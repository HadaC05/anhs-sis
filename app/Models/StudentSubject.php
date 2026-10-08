<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentSubject extends Model
{
    use HasFactory;

    protected $table = 'student_subjects';

    protected $primaryKey = 'student_subject_ID';

    protected $keyType = 'int';

    protected $fillable = ['enrollment_ID', 'subject_ID'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_ID', 'enrollment_ID');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_ID', 'subject_ID');
    }

    /** Compatibility relation for curriculum metadata; electives may not have one. */
    public function curriculumSubject(): HasOne
    {
        return $this->hasOne(CurriculumSubject::class, 'subject_ID', 'subject_ID');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'student_subject_ID', 'student_subject_ID');
    }
}
