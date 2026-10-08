<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemediationSubject extends Model
{
    protected $primaryKey = 'remediation_subject_ID';

    protected $fillable = [
        'remediation_case_ID', 'subject_ID', 'original_final_grade',
        'remedial_class_mark', 'recomputed_final_grade', 'remarks',
    ];

    protected function casts(): array
    {
        return [
            'original_final_grade' => 'decimal:2',
            'remedial_class_mark' => 'decimal:2',
            'recomputed_final_grade' => 'decimal:2',
        ];
    }

    public function remediationCase(): BelongsTo
    {
        return $this->belongsTo(RemediationCase::class, 'remediation_case_ID', 'remediation_case_ID');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_ID', 'subject_ID');
    }
}
