<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class GradeStatus extends Model
{
    /** @use HasFactory<\Database\Factories\GradeStatusFactory> */
    use HasFactory;

    public const DRAFT = 'draft';

    public const SUBMITTED = 'submitted';

    public const APPROVED = 'approved';

    public const RELEASED = 'released';

    public const REJECTED = 'rejected';

    protected $table = 'grade_statuses';

    protected $primaryKey = 'grade_status_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'slug',
        'name',
        'sort_order',
    ];

    /**
     * @var array<string, string>|null
     */
    private static ?array $optionCache = null;

    /**
     * @var array<string, int>|null
     */
    private static ?array $idsBySlug = null;

    /**
     * @return list<array{slug: string, name: string, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            ['slug' => self::DRAFT, 'name' => 'Draft', 'sort_order' => 1],
            ['slug' => self::SUBMITTED, 'name' => 'Submitted', 'sort_order' => 2],
            ['slug' => self::APPROVED, 'name' => 'Approved', 'sort_order' => 3],
            ['slug' => self::RELEASED, 'name' => 'Released', 'sort_order' => 4],
            ['slug' => self::REJECTED, 'name' => 'Rejected', 'sort_order' => 5],
        ];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_column(self::definitions(), 'slug');
    }

    /**
     * Statuses that lock the grade from teacher edits until unlocked.
     *
     * @return list<string>
     */
    public static function teacherLockedSlugs(): array
    {
        return [
            self::SUBMITTED,
            self::APPROVED,
            self::RELEASED,
        ];
    }

    /**
     * Statuses teachers can still edit.
     *
     * @return list<string>
     */
    public static function teacherEditableSlugs(): array
    {
        return [
            self::DRAFT,
            self::REJECTED,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function idsBySlug(): array
    {
        if (self::$idsBySlug !== null) {
            return self::$idsBySlug;
        }

        if (! Schema::hasTable('grade_statuses')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('grade_status_ID', 'slug')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public static function idFor(?string $slug): ?int
    {
        if (! $slug) {
            return null;
        }

        return self::idsBySlug()[$slug] ?? null;
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    public static function idsFor(array $slugs): array
    {
        return array_values(array_filter(
            array_map(fn (string $slug): ?int => self::idFor($slug), $slugs),
            fn (?int $id): bool => $id !== null,
        ));
    }

    public static function slugFor(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        return array_flip(self::idsBySlug())[$id] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        if (self::$optionCache !== null) {
            return self::$optionCache;
        }

        if (! Schema::hasTable('grade_statuses')) {
            return self::$optionCache = collect(self::definitions())
                ->sortBy('sort_order')
                ->mapWithKeys(fn (array $status): array => [$status['slug'] => $status['name']])
                ->all();
        }

        return self::$optionCache = self::query()
            ->orderBy('sort_order')
            ->pluck('name', 'slug')
            ->all();
    }

    public static function nameFor(?string $slug): string
    {
        if (! $slug) {
            return '';
        }

        return self::options()[$slug] ?? ucwords(str_replace('_', ' ', $slug));
    }

    public static function clearOptionsCache(): void
    {
        self::$optionCache = null;
        self::$idsBySlug = null;
    }

    public function grades(): HasMany
    {
        return $this->hasMany(StudentSubjectGrade::class, 'grade_status_ID', 'grade_status_ID');
    }
}
