<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Seed the application's academic_years table.
     */
    public function run(): void
    {
        foreach ([
            [
                'school_year' => '2025-2026',
                'start_date' => '2025-06-03',
                'end_date' => '2026-03-31',
                'status' => false,
            ],
            [
                'school_year' => '2026-2027',
                'start_date' => '2026-06-01',
                'end_date' => '2027-03-31',
                'status' => true,
            ],
            [
                'school_year' => '2027-2028',
                'start_date' => '2027-06-01',
                'end_date' => '2028-03-31',
                'status' => false,
            ],
        ] as $year) {
            AcademicYear::query()->updateOrCreate(
                ['school_year' => $year['school_year']],
                [
                    'start_date' => $year['start_date'],
                    'end_date' => $year['end_date'],
                    'status' => $year['status'],
                ],
            );
        }
    }
}
