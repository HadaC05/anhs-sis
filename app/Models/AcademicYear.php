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
        'attendance_start_month',
        'attendance_end_month',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => 'boolean',
            'attendance_start_month' => 'integer',
            'attendance_end_month' => 'integer',
        ];
    }

    public function attendanceStartMonth(): int
    {
        return $this->attendance_start_month ?? $this->start_date?->month ?? 1;
    }

    public function attendanceEndMonth(): int
    {
        return $this->attendance_end_month ?? $this->end_date?->month ?? 12;
    }

    /** @return array<int, string> */
    public function attendanceMonths(): array
    {
        $labels = Month::labels();
        $months = [];
        $start = $this->attendanceStartMonth();
        $end = $this->attendanceEndMonth();

        for ($offset = 0; $offset < 12; $offset++) {
            $month = (($start - 1 + $offset) % 12) + 1;
            $months[$month] = $labels[$month];
            if ($month === $end) {
                break;
            }
        }

        return $months;
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
