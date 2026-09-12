<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentObservedValue extends Model
{
    use HasFactory;

    protected $table = 'student_observed_values';

    protected $primaryKey = 'observed_value_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'enrollment_ID',
        'statement_key',
        'grading_period',
        'marking',
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

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'posted_by', 'staff_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'reviewed_by', 'staff_id');
    }
}
