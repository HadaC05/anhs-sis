<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvisoryClassListImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_ID',
        'requested_by',
        'audit_actor',
        'original_filename',
        'file_contents',
        'status',
        'total_students',
        'processed_students',
        'result',
        'failure_message',
        'started_at',
        'completed_at',
    ];

    protected $hidden = [
        'file_contents',
    ];

    protected function casts(): array
    {
        return [
            'audit_actor' => 'array',
            'total_students' => 'integer',
            'processed_students' => 'integer',
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
