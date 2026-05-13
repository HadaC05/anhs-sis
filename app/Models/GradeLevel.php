<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    use HasFactory;

    protected $table = 'grade_level';
    protected $primaryKey = 'grade_ID';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'grade_label',
        'category',
    ];

    public function getValueAttribute(): string
    {
        return self::labelToValue($this->grade_label);
    }

    public static function labelToValue(?string $label): string
    {
        if (! $label) {
            return '';
        }

        return 'grade_'.preg_replace('/\D+/', '', $label);
    }

    public static function valueToLabel(string $value): string
    {
        return 'Grade '.preg_replace('/\D+/', '', $value);
    }

    public static function idForValue(string $value): ?int
    {
        $label = self::valueToLabel($value);

        return self::query()
            ->where('grade_label', $label)
            ->value('grade_ID');
    }

    public static function options(): array
    {
        return self::query()
            ->orderBy('grade_ID')
            ->get()
            ->map(fn (self $grade): array => [
                'value' => $grade->value,
                'label' => $grade->grade_label,
            ])
            ->all();
    }
}
