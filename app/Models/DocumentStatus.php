<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class DocumentStatus extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentStatusFactory> */
    use HasFactory;

    public const PENDING = 'pending';

    public const VERIFIED = 'verified';

    public const RETURNED = 'returned';

    protected $table = 'document_statuses';

    protected $primaryKey = 'document_status_ID';

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
            ['slug' => self::VERIFIED, 'name' => 'Verified', 'sort_order' => 2],
            ['slug' => self::RETURNED, 'name' => 'Returned', 'sort_order' => 3],
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

        if (! Schema::hasTable('document_statuses')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('document_status_ID', 'slug')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    public static function idFor(?string $slug): ?int
    {
        if (! $slug) {
            return null;
        }

        if ($slug === 'rejected') {
            $slug = self::RETURNED;
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

        if (! Schema::hasTable('document_statuses')) {
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

        if ($slug === 'rejected') {
            $slug = self::RETURNED;
        }

        return self::options()[$slug] ?? ucwords(str_replace('_', ' ', $slug));
    }

    public static function clearOptionsCache(): void
    {
        self::$optionCache = null;
        self::$idsBySlug = null;
    }

    /**
     * @return HasMany<StudentDocument, $this>
     */
    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'document_status_ID', 'document_status_ID');
    }
}
