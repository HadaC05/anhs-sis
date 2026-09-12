<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Religion extends Model
{
    /** @use HasFactory<\Database\Factories\ReligionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            'Baptist',
            'Buddhism',
            'Catholic',
            'Christian',
            'Hindu',
            'Iglesia ni Cristo',
            'Islam',
            "Jehovah's Witness",
            'Judaism',
            'Seventh-day Adventist',
            'Not Applicable',
        ];
    }

    /**
     * @return Collection<int, string>
     */
    public static function options(): Collection
    {
        return self::query()
            ->orderBy('id')
            ->pluck('name');
    }
}
