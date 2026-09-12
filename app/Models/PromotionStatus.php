<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class PromotionStatus extends Model
{
    public const PENDING = 'pending';

    public const ELIGIBLE = 'eligible';

    public const RETAINED = 'retained';

    protected $table = 'promotion_statuses';

    protected $primaryKey = 'promotion_status_ID';

    protected $fillable = ['slug', 'name', 'sort_order'];

    private static ?array $idsBySlug = null;

    public static function definitions(): array
    {
        return [
            ['slug' => self::PENDING, 'name' => 'Pending Evaluation', 'sort_order' => 1],
            ['slug' => self::ELIGIBLE, 'name' => 'Eligible for Promotion', 'sort_order' => 2],
            ['slug' => self::RETAINED, 'name' => 'Retained', 'sort_order' => 3],
        ];
    }

    public static function idFor(?string $slug): ?int
    {
        if (! $slug || ! Schema::hasTable('promotion_statuses')) {
            return null;
        }

        if (self::$idsBySlug === null) {
            self::$idsBySlug = self::query()->pluck('promotion_status_ID', 'slug')
                ->map(fn (mixed $id): int => (int) $id)->all();
        }

        return self::$idsBySlug[$slug] ?? null;
    }

    public static function slugFor(?int $id): ?string
    {
        return $id ? array_flip(self::$idsBySlug ?? self::loadIds())[$id] ?? null : null;
    }

    private static function loadIds(): array
    {
        return self::$idsBySlug ??= self::query()->pluck('promotion_status_ID', 'slug')
            ->map(fn (mixed $id): int => (int) $id)->all();
    }

    public static function nameFor(?string $slug): string
    {
        return collect(self::definitions())->firstWhere('slug', $slug)['name'] ?? '';
    }

    public static function clearCache(): void
    {
        self::$idsBySlug = null;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'promotion_status_ID', 'promotion_status_ID');
    }
}
