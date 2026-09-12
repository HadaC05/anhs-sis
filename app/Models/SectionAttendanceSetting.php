<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionAttendanceSetting extends Model
{
    protected $fillable = [
        'section_ID',
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_ID', 'section_ID');
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class, 'SY_ID', 'SY_ID');
    }
}
