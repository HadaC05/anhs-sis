<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class PlacementStatus extends Model
{
    /** @use HasFactory<\Database\Factories\PlacementStatusFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const AGE_APPROPRIATE = 'age_appropriate';

    public const RECOMMENDED = 'recommended';

    public const PASSED = 'passed';

    public const FAILED = 'failed';

    public const RESOLVED = 'resolved';

    protected $table = 'placement_statuses';

    protected $primaryKey = 'placement_status_ID';

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
            ['slug' => self::PENDING, 'name' => 'Pending', 'sort_order' => 1],
            ['slug' => self::AGE_APPROPRIATE, 'name' => 'Age Appropriate', 'sort_order' => 2],
            ['slug' => self::RECOMMENDED, 'name' => 'Recommended', 'sort_order' => 3],
            ['slug' => self::PASSED, 'name' => 'Passed', 'sort_order' => 4],
            ['slug' => self::FAILED, 'name' => 'Failed', 'sort_order' => 5],
            ['slug' => self::RESOLVED, 'name' => 'Resolved', 'sort_order' => 6],
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
     * @return array<string, int>
     */
    public static function idsBySlug(): array
    {
        if (self::$idsBySlug !== null) {
            return self::$idsBySlug;
        }

        if (! Schema::hasTable('placement_statuses')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('placement_status_ID', 'slug')
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

    public static function recommendedId(): ?int
    {
        return self::idFor(self::RECOMMENDED);
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

        if (! Schema::hasTable('placement_statuses')) {
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

    public static function badgeClasses(?string $slug): string
    {
        return match ($slug) {
            self::AGE_APPROPRIATE => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::RECOMMENDED => 'bg-[#296374]/10 text-[#296374] ring-[#296374]/20',
            self::PASSED => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::FAILED => 'bg-red-50 text-red-700 ring-red-200',
            self::RESOLVED => 'bg-slate-100 text-slate-700 ring-slate-200',
            default => 'bg-gray-100 text-gray-700 ring-gray-200',
        };
    }

    public static function cardClasses(?string $slug): string
    {
        return match ($slug) {
            self::AGE_APPROPRIATE => 'border-sky-200 bg-sky-50/80',
            self::RECOMMENDED => 'border-[#296374]/25 bg-[#296374]/5',
            self::PASSED => 'border-emerald-200 bg-emerald-50/80',
            self::FAILED => 'border-red-200 bg-red-50/80',
            self::RESOLVED => 'border-slate-200 bg-slate-50',
            default => 'border-gray-200 bg-white',
        };
    }

    public static function clearOptionsCache(): void
    {
        self::$optionCache = null;
        self::$idsBySlug = null;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'placement_status_ID', 'placement_status_ID');
    }
}
