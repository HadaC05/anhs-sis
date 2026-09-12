<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentMonthlyAttendance extends Model
{
    protected $table = 'enrollment_monthly_attendance';

    protected $fillable = [
        'enrollment_ID',
        'month',
        'days_present',
        'days_absent',
        'days_tardy',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'days_present' => 'integer',
            'days_absent' => 'integer',
            'days_tardy' => 'integer',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_ID', 'enrollment_ID');
    }
}
