<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumSubject extends Model
{
    use HasFactory;

    protected $table = 'curriculum_subjects';
    protected $primaryKey = 'curr_subj_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'curriculum_ID',
        'subject_ID',
        'cluster_ID',
        'grade_level',
        'semester',
    ];

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_ID', 'curriculum_ID');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_ID', 'subject_ID');
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_ID', 'cluster_ID');
    }
}
