<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubjectType extends Model
{
    use HasFactory;

    protected $table = 'subject_types';

    protected $primaryKey = 'subject_type_ID';

    protected $fillable = ['key', 'label', 'sort_order'];

    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class, 'subject_type_ID', 'subject_type_ID');
    }

    public static function idForKey(?string $key): ?int
    {
        if (! $key) {
            return null;
        }

        return static::query()->where('key', $key)->value('subject_type_ID');
    }
}
