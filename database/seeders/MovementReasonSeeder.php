<?php

namespace Database\Seeders;

use App\Models\MovementReason;
use Illuminate\Database\Seeder;

class MovementReasonSeeder extends Seeder
{
    /**
     * Seed the application's movement_reasons table.
     */
    public function run(): void
    {
        $reasons = [
            ['name' => 'Had to take care of siblings', 'description' => 'Domestic-Related Factors (a.1)'],
            ['name' => 'Early marriage/pregnancy', 'description' => 'Domestic-Related Factors (a.2)'],
            ['name' => "Parents' attitude toward schooling", 'description' => 'Domestic-Related Factors (a.3)'],
            ['name' => 'Family problems', 'description' => 'Domestic-Related Factors (a.4)'],

            ['name' => 'Illness', 'description' => 'Individual-Related Factors (b.1)'],
            ['name' => 'Overage', 'description' => 'Individual-Related Factors (b.2)'],
            ['name' => 'Death', 'description' => 'Individual-Related Factors (b.3)'],
            ['name' => 'Drug Abuse', 'description' => 'Individual-Related Factors (b.4)'],
            ['name' => 'Poor academic performance', 'description' => 'Individual-Related Factors (b.5)'],
            ['name' => 'Lack of interest/Distractions', 'description' => 'Individual-Related Factors (b.6)'],
            ['name' => 'Hunger/Malnutrition', 'description' => 'Individual-Related Factors (b.7)'],

            ['name' => 'Teacher Factor', 'description' => 'School-Related Factors (c.1)'],
            ['name' => 'Physical condition of classroom', 'description' => 'School-Related Factors (c.2)'],
            ['name' => 'Peer influence', 'description' => 'School-Related Factors (c.3)'],

            ['name' => 'Distance between home and school', 'description' => 'Geographic/Environmental (d.1)'],
            ['name' => 'Armed conflict (incl. Tribal wars & clanfeuds)', 'description' => 'Geographic/Environmental (d.2)'],
            ['name' => 'Calamities/Disasters', 'description' => 'Geographic/Environmental (d.3)'],

            ['name' => 'Child labor, work', 'description' => 'Financial-Related (e.1)'],
            ['name' => 'Others (Specify)', 'description' => 'Others (f)'],
        ];

        foreach ($reasons as $reason) {
            MovementReason::query()->updateOrCreate(
                ['name' => $reason['name']],
                ['description' => $reason['description']],
            );
        }
    }
}
