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
        'school_level',
        'subject_type_ID',
        'code',
        'title',
        'type',
        'status',
    ];

    public function subjectType(): BelongsTo
    {
        return $this->belongsTo(SubjectType::class, 'subject_type_ID', 'subject_type_ID');
    }

    /** Compatibility accessor for callers using the previous enum value. */
    public function getTypeAttribute(): ?string
    {
        $type = $this->relationLoaded('subjectType')
            ? $this->getRelation('subjectType')
            : $this->subjectType()->first();

        return $type?->key;
    }

    public function setTypeAttribute(?string $value): void
    {
        $this->attributes['subject_type_ID'] = SubjectType::idForKey($value);
    }

    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(CurriculumSubject::class, 'subject_ID', 'subject_ID');
    }
}
