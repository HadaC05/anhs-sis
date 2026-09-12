<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class NotificationType extends Model
{
    /** @use HasFactory<\Database\Factories\NotificationTypeFactory> */
    use HasFactory;

    public const ENROLLMENT_STATUS = 'enrollment_status';

    public const PLACEMENT_STATUS = 'placement_status';

    public const PLACEMENT_TEST_RECOMMENDED = 'placement_test_recommended';

    public const GRADES_APPROVED = 'grades_approved';

    public const GRADES_RELEASED = 'grades_released';

    public const GRADING_TERM_OPENED = 'grading_term_opened';

    public const GRADES_UNLOCKED = 'grades_unlocked';

    public const DOCUMENT_STATUS_UPDATED = 'document_status_updated';

    protected $table = 'notification_types';

    protected $primaryKey = 'notification_type_ID';

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
            ['slug' => self::ENROLLMENT_STATUS, 'name' => 'Enrollment status updated', 'sort_order' => 1],
            ['slug' => self::PLACEMENT_STATUS, 'name' => 'Placement status updated', 'sort_order' => 2],
            ['slug' => self::PLACEMENT_TEST_RECOMMENDED, 'name' => 'Placement test recommended', 'sort_order' => 3],
            ['slug' => self::GRADES_APPROVED, 'name' => 'Grades approved', 'sort_order' => 4],
            ['slug' => self::GRADES_RELEASED, 'name' => 'Grades released', 'sort_order' => 5],
            ['slug' => self::GRADING_TERM_OPENED, 'name' => 'Grading term opened', 'sort_order' => 6],
            ['slug' => self::GRADES_UNLOCKED, 'name' => 'Grades unlocked', 'sort_order' => 7],
            ['slug' => self::DOCUMENT_STATUS_UPDATED, 'name' => 'Document status updated', 'sort_order' => 8],
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

        if (! Schema::hasTable('notification_types')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('notification_type_ID', 'slug')
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

    public static function requireIdFor(string $slug): int
    {
        $id = self::idFor($slug);

        if ($id === null) {
            throw new InvalidArgumentException("Unknown notification type [{$slug}].");
        }

        return $id;
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

        if (! Schema::hasTable('notification_types')) {
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
        if (! $slug) {
            return '';
        }

        return self::options()[$slug] ?? ucwords(str_replace('_', ' ', $slug));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function payload(string $slug, array $data = []): array
    {
        return array_merge([
            'notification_type_ID' => self::requireIdFor($slug),
            'title' => self::nameFor($slug),
        ], $data);
    }

    public static function clearOptionsCache(): void
    {
        self::$optionCache = null;
        self::$idsBySlug = null;
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(DatabaseNotification::class, 'notification_type_ID', 'notification_type_ID');
    }
}
