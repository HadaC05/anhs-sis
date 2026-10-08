<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Track extends Model
{
    use HasFactory;

    protected $table = 'tracks';

    protected $primaryKey = 'track_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
    ];

    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class, 'track_ID', 'track_ID');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'track_ID', 'track_ID');
    }

    public function isAcademic(): bool
    {
        return $this->name === 'Academic Track';
    }
}
