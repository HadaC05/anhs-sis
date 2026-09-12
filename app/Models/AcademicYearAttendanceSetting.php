<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicYearAttendanceSetting extends Model
{
    /** @use HasFactory<\Database\Factories\AcademicYearAttendanceSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'SY_ID',
        'month',
        'school_days',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'school_days' => 'integer',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'SY_ID', 'SY_ID');
    }

    public function calendarMonth(): BelongsTo
    {
        return $this->belongsTo(Month::class, 'month', 'month_ID');
    }
}
