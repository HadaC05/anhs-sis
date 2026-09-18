<?php

use App\Models\GradingPeriodStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (GradingPeriodStatus::definitions() as $status) {
            DB::table('grading_period_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        GradingPeriodStatus::clearOptionsCache();
        $activeId = GradingPeriodStatus::activeId();
        $openId = GradingPeriodStatus::openId();
        $archivedId = GradingPeriodStatus::archivedId();

        $terms = DB::table('grading_terms')->orderBy('sort_order')->orderBy('term_ID')->get();
        $maximumTerms = (int) (DB::table('grading_term_settings')->where('id', 1)->value('max_terms') ?: 4);

        foreach ($terms as $index => $term) {
            DB::table('grading_terms')->where('term_ID', $term->term_ID)->update([
                'junior_high_grading_period_status_ID' => $index === 0
                    ? $openId
                    : ($index < $maximumTerms ? $activeId : $archivedId),
                'senior_high_grading_period_status_ID' => $index === 0
                    ? $openId
                    : ($index < 3 ? $activeId : $archivedId),
            ]);
        }

        GradingPeriodStatus::clearOptionsCache();
    }

    public function down(): void
    {
        $activeId = GradingPeriodStatus::activeId();

        DB::table('grading_terms')
            ->whereIn('junior_high_grading_period_status_ID', [GradingPeriodStatus::openId(), GradingPeriodStatus::archivedId()])
            ->update(['junior_high_grading_period_status_ID' => $activeId]);
        DB::table('grading_terms')
            ->whereIn('senior_high_grading_period_status_ID', [GradingPeriodStatus::openId(), GradingPeriodStatus::archivedId()])
            ->update(['senior_high_grading_period_status_ID' => $activeId]);

        DB::table('grading_period_statuses')->whereIn('slug', [GradingPeriodStatus::OPEN, GradingPeriodStatus::ARCHIVED])->delete();
        GradingPeriodStatus::clearOptionsCache();
    }
};
