<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class GradingTermSetting extends Model
{
    use HasFactory;

    protected $table = 'grading_term_settings';

    protected $fillable = [
        'max_terms',
        'open_terms_count',
        'semester_ID',
        'term_ID',
    ];

    protected function casts(): array
    {
        return [
            'max_terms' => 'integer',
            'open_terms_count' => 'integer',
        ];
    }

    public static function current(): self
    {
        $hasSemesterColumn = Schema::hasColumn('grading_term_settings', 'semester_ID');
        $hasTermColumn = Schema::hasColumn('grading_term_settings', 'term_ID');

        $defaults = array_filter([
            'max_terms' => 4,
            'open_terms_count' => 1,
            'semester_ID' => $hasSemesterColumn && Schema::hasTable('grading_semesters')
                ? GradingSemester::idFor(GradingSemester::FIRST)
                : null,
            'term_ID' => $hasTermColumn && Schema::hasTable('grading_terms')
                ? GradingTerm::query()->orderBy('sort_order')->orderBy('term_ID')->value('term_ID')
                : null,
        ], fn (mixed $value, string $key): bool => $value !== null && Schema::hasColumn('grading_term_settings', $key), ARRAY_FILTER_USE_BOTH);

        if (! Schema::hasTable('grading_term_settings')) {
            return new self($defaults);
        }

        $settings = self::query()->firstOrCreate(['id' => 1], $defaults);

        $needsSemester = $hasSemesterColumn && $settings->semester_ID === null && isset($defaults['semester_ID']);
        $needsTerm = $hasTermColumn && $settings->term_ID === null && isset($defaults['term_ID']);

        if ($needsSemester || $needsTerm) {
            $settings->fill(array_filter([
                'semester_ID' => $needsSemester ? $defaults['semester_ID'] : null,
                'term_ID' => $needsTerm ? $defaults['term_ID'] : null,
            ], fn (mixed $value): bool => $value !== null));
            $settings->save();
        }

        $relations = array_values(array_filter([
            $hasSemesterColumn ? 'semester' : null,
            $hasTermColumn ? 'term' : null,
        ]));

        return $relations === [] ? $settings : $settings->loadMissing($relations);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(GradingSemester::class, 'semester_ID', 'semester_ID');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(GradingTerm::class, 'term_ID', 'term_ID');
    }

    public function seniorHighSemester(): string
    {
        $key = $this->semester?->key;

        return $key === GradingSemester::SECOND ? GradingSemester::SECOND : GradingSemester::FIRST;
    }

    public function seniorHighTerm(): int
    {
        $termId = $this->term_ID !== null ? (int) $this->term_ID : null;

        foreach (GradingTerm::seniorHighTerms() as $index => $term) {
            if ($termId !== null && isset($term['term_ID']) && (int) $term['term_ID'] === $termId) {
                return $index + 1;
            }
        }

        return 1;
    }

    public function setSeniorHighPeriod(string $semester, int $term): void
    {
        $period = GradingTerm::seniorHighPeriod($semester, $term);

        $this->update([
            'semester_ID' => $period['semester_ID'] ?? GradingSemester::idFor($semester),
            'term_ID' => $period['term_ID'] ?? null,
        ]);
    }
}
