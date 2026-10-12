<?php

use App\Models\PromotionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('promotion_statuses')->updateOrInsert(
            ['slug' => PromotionStatus::COMPLETED_SENIOR_HIGH],
            ['name' => 'Completed Senior High School', 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
        );

        PromotionStatus::clearCache();
    }

    public function down(): void
    {
        $completedId = DB::table('promotion_statuses')
            ->where('slug', PromotionStatus::COMPLETED_SENIOR_HIGH)
            ->value('promotion_status_ID');

        if ($completedId) {
            DB::table('enrollments')->where('promotion_status_ID', $completedId)->update([
                'promotion_status_ID' => DB::table('promotion_statuses')
                    ->where('slug', PromotionStatus::PENDING)
                    ->value('promotion_status_ID'),
            ]);
            DB::table('promotion_statuses')->where('promotion_status_ID', $completedId)->delete();
        }

        PromotionStatus::clearCache();
    }
};
