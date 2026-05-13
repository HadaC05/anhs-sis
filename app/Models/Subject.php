<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $table = 'subjects';
    protected $primaryKey = 'subject_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cluster_ID',
        'code',
        'title',
        'type',
        'status',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_ID', 'cluster_ID');
    }

    public function books(): HasMany
    {
        return $this->hasMany(RefBook::class, 'subject_ID', 'subject_ID');
    }

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class, 'subject_ID', 'subject_ID');
    }
}
