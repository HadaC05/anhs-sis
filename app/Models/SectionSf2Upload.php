<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SectionSf2Upload extends Model
{
    protected $fillable = [
        'section_ID',
        'SY_ID',
        'report_month',
        'original_filename',
        'storage_path',
        'status',
        'parse_notes',
        'uploaded_by',
        'report_year',
        'source_school_year',
        'school_days',
        'import_rows',
        'sf2_layout',
        'class_dates',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'report_month' => 'integer',
            'report_year' => 'integer',
            'use_section_school_year' => 'boolean',
            'school_days' => 'integer',
            'import_rows' => 'array',
            'sf2_layout' => 'array',
            'class_dates' => 'array',
            'imported_at' => 'datetime',
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

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'uploaded_by', 'staff_id');
    }
}
