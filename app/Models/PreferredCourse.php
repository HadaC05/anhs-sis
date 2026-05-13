<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreferredCourse extends Model
{
    use HasFactory;

    protected $table = 'preferred_courses';
    protected $primaryKey = 'course_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cluster_ID',
        'name',
        'description',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class, 'cluster_ID', 'cluster_ID');
    }
}
