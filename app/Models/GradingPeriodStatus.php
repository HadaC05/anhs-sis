<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class GradingPeriodStatus extends Model
{
    /** @use HasFactory<\Database\Factories\GradingPeriodStatusFactory> */
    use HasFactory;

    public const ACTIVE = 'active';

    public const INACTIVE = 'inactive';

    protected $table = 'grading_period_statuses';

    protected $primaryKey = 'grading_period_status_ID';

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
            ['slug' => self::ACTIVE, 'name' => 'Active', 'sort_order' => 1],
            ['slug' => self::INACTIVE, 'name' => 'Inactive', 'sort_order' => 2],
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

        if (! Schema::hasTable('grading_period_statuses')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('grading_period_status_ID', 'slug')
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

    public static function activeId(): ?int
    {
        return self::idFor(self::ACTIVE);
    }

    public static function inactiveId(): ?int
    {
        return self::idFor(self::INACTIVE);
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

        if (! Schema::hasTable('grading_period_statuses')) {
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

    public function terms(): HasMany
    {
        return $this->hasMany(GradingTerm::class, 'grading_period_status_ID', 'grading_period_status_ID');
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(GradingSemester::class, 'grading_period_status_ID', 'grading_period_status_ID');
    }
}
