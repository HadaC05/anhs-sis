<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curricula extends Model
{
    use HasFactory;

    protected $table = 'curricula';

    protected $primaryKey = 'curricula_ID';

    protected $fillable = ['name', 'description', 'data_status_ID'];

    public function dataStatus(): BelongsTo
    {
        return $this->belongsTo(DataStatus::class, 'data_status_ID', 'data_status_ID');
    }

    public function curriculumGradeLevels(): HasMany
    {
        return $this->hasMany(Curriculum::class, 'curricula_ID', 'curricula_ID');
    }
}
