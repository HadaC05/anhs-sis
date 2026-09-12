<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    use HasFactory;

    public const EARLIEST_DATE = '2000-01-01';

    public const LATEST_DATE = '2100-12-31';

    protected $table = 'academic_years';

    protected $primaryKey = 'SY_ID';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'school_year',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'SY_ID', 'SY_ID');
    }

    public function attendanceSettings(): HasMany
    {
        return $this->hasMany(AcademicYearAttendanceSetting::class, 'SY_ID', 'SY_ID');
    }

    public static function labelFromDates(\DateTimeInterface $startDate, \DateTimeInterface $endDate): string
    {
        return $startDate->format('Y').'-'.$endDate->format('Y');
    }

    public function makeActive(): void
    {
        static::query()
            ->where('SY_ID', '!=', $this->SY_ID)
            ->where('status', true)
            ->update(['status' => false]);

        $this->update(['status' => true]);
    }
}
