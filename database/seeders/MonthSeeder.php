<?php

namespace Database\Seeders;

use App\Models\Month;
use Illuminate\Database\Seeder;

class MonthSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Month::names() as $monthId => $name) {
            Month::query()->updateOrCreate(
                ['month_ID' => $monthId],
                ['name' => $name],
            );
        }
    }
}
