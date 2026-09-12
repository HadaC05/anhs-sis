<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentGradeTermUnlock extends Model
{
    protected $fillable = [
        'assignment_ID',
        'grading_period',
        'unlocked_by',
        'notes',
        'unlocked_at',
    ];

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherSubjectAssignment::class, 'assignment_ID', 'assignment_ID');
    }

    public function unlockedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'unlocked_by', 'staff_id');
    }
}
