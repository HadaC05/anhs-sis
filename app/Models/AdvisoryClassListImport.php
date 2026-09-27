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
        'original_filename',
        'file_contents',
        'status',
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
            'result' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
