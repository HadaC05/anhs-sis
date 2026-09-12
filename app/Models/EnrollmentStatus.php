<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class EnrollmentStatus extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentStatusFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const ENROLLED = 'enrolled';

    public const TEMPORARILY_ENROLLED = 'temporarily_enrolled';

    public const TRANSFERRED_OUT = 'transferred_out';

    public const DROPPED_OUT = 'dropped_out';

    public const WITHDRAWN = 'withdrawn';

    public const CANCELLED = 'cancelled';

    public const NO_SHOW = 'no_show';

    protected $table = 'enrollment_statuses';

    protected $primaryKey = 'enrollment_status_ID';

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
            ['slug' => self::ENROLLED, 'name' => 'Enrolled', 'sort_order' => 2],
            ['slug' => self::TEMPORARILY_ENROLLED, 'name' => 'Temporarily Enrolled', 'sort_order' => 3],
            ['slug' => self::TRANSFERRED_OUT, 'name' => 'Transferred Out', 'sort_order' => 4],
            ['slug' => self::DROPPED_OUT, 'name' => 'Dropped Out', 'sort_order' => 5],
            ['slug' => self::WITHDRAWN, 'name' => 'Withdrawn', 'sort_order' => 6],
            ['slug' => self::CANCELLED, 'name' => 'Cancelled', 'sort_order' => 7],
            ['slug' => self::NO_SHOW, 'name' => 'No Show', 'sort_order' => 8],
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
     * Statuses that count as currently in school.
     *
     * @return list<string>
     */
    public static function activeSlugs(): array
    {
        return [
            self::ENROLLED,
            self::TEMPORARILY_ENROLLED,
        ];
    }

    /**
     * Statuses in the current enrollment workflow.
     *
     * @return list<string>
     */
    public static function inProgressSlugs(): array
    {
        return [
            self::PENDING,
            self::ENROLLED,
            self::TEMPORARILY_ENROLLED,
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

        if (! Schema::hasTable('enrollment_statuses')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('enrollment_status_ID', 'slug')
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

    /**
     * @return list<int>
     */
    public static function activeIds(): array
    {
        return self::idsFor(self::activeSlugs());
    }

    /**
     * @return list<int>
     */
    public static function inProgressIds(): array
    {
        return self::idsFor(self::inProgressSlugs());
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

        if (! Schema::hasTable('enrollment_statuses')) {
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

    /**
     * @param  list<string>  $slugs
     * @return array<string, string>
     */
    public static function optionsFor(array $slugs): array
    {
        return collect(self::options())
            ->only($slugs)
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

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'enrollment_status_ID', 'enrollment_status_ID');
    }
}
