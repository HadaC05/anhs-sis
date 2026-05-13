<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cluster extends Model
{
    use HasFactory;

    protected $table = 'clusters';
    protected $primaryKey = 'cluster_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'name',
    ];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'cluster_ID', 'cluster_ID');
    }

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class, 'cluster_ID', 'cluster_ID');
    }

    public function preferredCourses(): HasMany
    {
        return $this->hasMany(PreferredCourse::class, 'cluster_ID', 'cluster_ID');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'cluster_ID', 'cluster_ID');
    }
}
