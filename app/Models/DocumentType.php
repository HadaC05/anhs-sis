<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class DocumentType extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTypeFactory> */
    use HasFactory;

    public const BIRTH_CERTIFICATE = 'birth_certificate';

    public const FORM_137 = 'form_137';

    public const GOOD_MORAL = 'good_moral';

    public const ID_PHOTO = 'id_photo';

    protected $table = 'document_types';

    protected $primaryKey = 'document_type_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_required',
        'sort_order',
    ];

    /**
     * @var array<string, int>|null
     */
    private static ?array $idsBySlug = null;

    /**
     * @var array<string, array{title: string, desc: string, required: bool}>|null
     */
    private static ?array $definitionCache = null;

    /**
     * @return list<array{slug: string, name: string, description: string, is_required: bool, sort_order: int}>
     */
    public static function definitions(): array
    {
        return [
            [
                'slug' => self::BIRTH_CERTIFICATE,
                'name' => 'Birth Certificate',
                'description' => 'PSA or local civil registrar copy',
                'is_required' => true,
                'sort_order' => 1,
            ],
            [
                'slug' => self::FORM_137,
                'name' => 'Form 137 / SF9',
                'description' => 'Latest report card or permanent record',
                'is_required' => true,
                'sort_order' => 2,
            ],
            [
                'slug' => self::GOOD_MORAL,
                'name' => 'Good Moral Certificate',
                'description' => 'Issued by previous school',
                'is_required' => false,
                'sort_order' => 3,
            ],
            [
                'slug' => self::ID_PHOTO,
                'name' => '2x2 Photo',
                'description' => 'Recent photo with white background',
                'is_required' => false,
                'sort_order' => 4,
            ],
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
     * @return list<string>
     */
    public static function enrollmentSlugs(): array
    {
        return array_values(array_filter(
            self::slugs(),
            fn (string $slug): bool => $slug !== self::ID_PHOTO,
        ));
    }

    /**
     * @return list<string>
     */
    public static function requiredSlugs(): array
    {
        return array_values(array_map(
            fn (array $type): string => $type['slug'],
            array_filter(self::definitions(), fn (array $type): bool => $type['is_required']),
        ));
    }

    /**
     * @return array<string, int>
     */
    public static function idsBySlug(): array
    {
        if (self::$idsBySlug !== null) {
            return self::$idsBySlug;
        }

        if (! Schema::hasTable('document_types')) {
            return [];
        }

        return self::$idsBySlug = self::query()
            ->pluck('document_type_ID', 'slug')
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
     * @return array<string, array{title: string, desc: string, required: bool}>
     */
    public static function typeDefinitions(bool $enrollmentOnly = false): array
    {
        if (self::$definitionCache === null) {
            self::$definitionCache = collect(self::definitions())
                ->sortBy('sort_order')
                ->mapWithKeys(fn (array $type): array => [$type['slug'] => [
                    'title' => $type['name'],
                    'desc' => $type['description'],
                    'required' => $type['is_required'],
                ]])
                ->all();

            if (Schema::hasTable('document_types')) {
                self::$definitionCache = self::query()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn (self $type): array => [$type->slug => [
                        'title' => $type->name,
                        'desc' => $type->description ?? '',
                        'required' => (bool) $type->is_required,
                    ]])
                    ->all();
            }
        }

        if ($enrollmentOnly) {
            return array_intersect_key(self::$definitionCache, array_flip(self::enrollmentSlugs()));
        }

        return self::$definitionCache;
    }

    public static function nameFor(?string $slug): string
    {
        if (! $slug) {
            return '';
        }

        return self::typeDefinitions()[$slug]['title'] ?? ucwords(str_replace('_', ' ', $slug));
    }

    public static function clearOptionsCache(): void
    {
        self::$idsBySlug = null;
        self::$definitionCache = null;
    }

    /**
     * @return HasMany<StudentDocument, $this>
     */
    public function studentDocuments(): HasMany
    {
        return $this->hasMany(StudentDocument::class, 'document_type_ID', 'document_type_ID');
    }

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }
}
