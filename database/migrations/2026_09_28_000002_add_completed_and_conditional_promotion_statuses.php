<?php

use App\Models\PromotionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (PromotionStatus::definitions() as $status) {
            DB::table('promotion_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                [...$status, 'created_at' => now(), 'updated_at' => now()],
            );
        }
        PromotionStatus::clearCache();
    }

    public function down(): void
    {
        $ids = DB::table('promotion_statuses')
            ->whereIn('slug', [PromotionStatus::PROMOTED, PromotionStatus::CONDITIONALLY_PROMOTED])
            ->pluck('promotion_status_ID');
        DB::table('enrollments')->whereIn('promotion_status_ID', $ids)->update([
            'promotion_status_ID' => DB::table('promotion_statuses')->where('slug', PromotionStatus::PENDING)->value('promotion_status_ID'),
        ]);
        DB::table('promotion_statuses')->whereIn('promotion_status_ID', $ids)->delete();
        PromotionStatus::clearCache();
    }
};
