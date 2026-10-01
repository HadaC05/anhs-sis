<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapehConfiguration extends Model
{
    private ?array $inactiveIds = null;

    protected $fillable = ['curriculum_grade_level_ID', 'SY_ID', 'parent_curr_subj_ID', 'mode'];

    public function components(): HasMany
    {
        return $this->hasMany(MapehComponent::class);
    }

    public function parentSubject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class, 'parent_curr_subj_ID', 'curr_subj_ID');
    }

    public static function labels(string $mode): array
    {
        return $mode === 'paired'
            ? ['music_arts' => 'Music & Arts', 'pe_health' => 'Physical Education & Health']
            : ['music' => 'Music', 'arts' => 'Arts', 'pe' => 'Physical Education', 'health' => 'Health'];
    }

    public function inactiveComponentIds(): array
    {
        return $this->inactiveIds ??= MapehComponent::query()
            ->whereIn('mapeh_configuration_id', self::query()->where('curriculum_grade_level_ID', $this->curriculum_grade_level_ID)->select('id'))
            ->whereNotIn('curr_subj_ID', $this->components->pluck('curr_subj_ID'))->distinct()->pluck('curr_subj_ID')->all();
    }

    public static function forSection(?Section $section): ?self
    {
        if (! $section?->exists) {
            return null;
        }
        if (! $section->relationLoaded('mapehConfiguration')) {
            $section->setRelation('mapehConfiguration', self::query()
                ->with(['components.curriculumSubject.subject', 'parentSubject.subject'])
                ->where('curriculum_grade_level_ID', $section->curriculum_grade_level_ID)
                ->where('SY_ID', $section->SY_ID)->first());
        }

        return $section->getRelation('mapehConfiguration');
    }
}
