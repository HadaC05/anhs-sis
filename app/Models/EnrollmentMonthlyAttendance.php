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
        'source_sf2_upload_id',
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

    public function sourceUpload(): BelongsTo
    {
        return $this->belongsTo(SectionSf2Upload::class, 'source_sf2_upload_id');
    }
}
