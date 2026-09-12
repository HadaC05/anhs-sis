<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class LearnerType extends Model
{
    /** @use HasFactory<\Database\Factories\LearnerTypeFactory> */
    use HasFactory;

    public const REGULAR = 'regular';

    public const TRANSFEREE = 'transferee';

    public const BALIK_ARAL = 'balik_aral';

    public const RETURNEE_ALIAS = 'returnee';

    protected $table = 'learner_types';

    protected $primaryKey = 'learner_type_ID';

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
            ['slug' => self::REGULAR, 'name' => 'Regular', 'sort_order' => 1],
            ['slug' => self::TRANSFEREE, 'name' => 'Transferee', 'sort_order' => 2],
            ['slug' => self::BALIK_ARAL, 'name' => 'Balik Aral', 'sort_order' => 3],
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
     * Legacy form and filter values that map to a canonical slug.
     *
     * @return array<string, string>
     */
    public static function aliases(): array
    {
        return [
            self::RETURNEE_ALIAS => self::BALIK_ARAL,
        ];
    }

    public static function normalizeSlug(?string $slug): ?string
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return self::aliases()[$slug] ?? $slug;
    }

    /**
     * @return list<string>
     */
    public static function previousSchoolSlugs(): array
    {
        return [
            self::TRANSFEREE,
            self::BALIK_ARAL,
        ];
    }

    public static function requiresPreviousSchool(?string $slug): bool
    {
        return in_array(self::normalizeSlug($slug), self::previousSchoolSlugs(), true);
    }

    /**
     * @return array<string, int>
     */
    public static function idsBySlug(): array
    {
        if (self::$idsBySlug !== null) {
            return self::$idsBySlug;
        }

        if (! Schema::hasTable('learner_types')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('learner_type_ID', 'slug')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public static function idFor(?string $slug): ?int
    {
        $slug = self::normalizeSlug($slug);

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

        if (! Schema::hasTable('learner_types')) {
            return self::$optionCache = collect(self::definitions())
                ->sortBy('sort_order')
                ->mapWithKeys(fn (array $type): array => [$type['slug'] => $type['name']])
                ->all();
        }

        return self::$optionCache = self::query()
            ->orderBy('sort_order')
            ->pluck('name', 'slug')
            ->all();
    }

    public static function nameFor(?string $slug): string
    {
        $slug = self::normalizeSlug($slug);

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

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'learner_type_ID', 'learner_type_ID');
    }
}
