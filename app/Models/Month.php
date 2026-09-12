<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Month extends Model
{
    /** @use HasFactory<\Database\Factories\MonthFactory> */
    use HasFactory;

    protected $primaryKey = 'month_ID';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'month_ID',
        'name',
    ];

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function labels(): array
    {
        if (! Schema::hasTable('months')) {
            return self::names();
        }

        return self::query()
            ->orderBy('month_ID')
            ->get()
            ->mapWithKeys(fn (self $month): array => [(int) $month->month_ID => $month->name])
            ->all();
    }

    /**
     * @return list<int>
     */
    public static function ids(): array
    {
        return array_keys(self::labels());
    }

    public function academicYearAttendanceSettings(): HasMany
    {
        return $this->hasMany(AcademicYearAttendanceSetting::class, 'month', 'month_ID');
    }
}
