<?php

use App\Models\GradingPeriodStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('grading_terms')) {
            return;
        }

        Schema::table('grading_terms', function (Blueprint $table): void {
            if (! Schema::hasColumn('grading_terms', 'junior_high_grading_period_status_ID')) {
                $table->unsignedInteger('junior_high_grading_period_status_ID')->nullable()->after('sort_order');
            }

            if (! Schema::hasColumn('grading_terms', 'senior_high_grading_period_status_ID')) {
                $table->unsignedInteger('senior_high_grading_period_status_ID')->nullable()->after('junior_high_grading_period_status_ID');
            }
        });

        $activeId = GradingPeriodStatus::activeId();
        $legacyColumn = Schema::hasColumn('grading_terms', 'grading_period_status_ID');

        DB::table('grading_terms')->orderBy('term_ID')->get()->each(function (object $term) use ($activeId, $legacyColumn): void {
            $statusId = $legacyColumn && $term->grading_period_status_ID
                ? (int) $term->grading_period_status_ID
                : $activeId;

            DB::table('grading_terms')->where('term_ID', $term->term_ID)->update([
                'junior_high_grading_period_status_ID' => $statusId,
                'senior_high_grading_period_status_ID' => $statusId,
            ]);
        });

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->foreign('junior_high_grading_period_status_ID', 'grading_terms_jhs_status_foreign')
                ->references('grading_period_status_ID')->on('grading_period_statuses')->restrictOnDelete();
            $table->foreign('senior_high_grading_period_status_ID', 'grading_terms_shs_status_foreign')
                ->references('grading_period_status_ID')->on('grading_period_statuses')->restrictOnDelete();
        });

        if ($legacyColumn) {
            $this->dropForeignKey('grading_terms', 'grading_terms_status_foreign', 'grading_period_status_ID');
            Schema::table('grading_terms', function (Blueprint $table): void {
                $table->dropColumn('grading_period_status_ID');
            });
        }

        $this->renameInactiveStatus();
    }

    public function down(): void
    {
        if (! Schema::hasTable('grading_terms')) {
            return;
        }

        Schema::table('grading_terms', function (Blueprint $table): void {
            if (! Schema::hasColumn('grading_terms', 'grading_period_status_ID')) {
                $table->unsignedInteger('grading_period_status_ID')->nullable()->after('sort_order');
            }
        });

        DB::table('grading_terms')->update([
            'grading_period_status_ID' => DB::raw('"junior_high_grading_period_status_ID"'),
        ]);

        $this->dropForeignKey('grading_terms', 'grading_terms_jhs_status_foreign', 'junior_high_grading_period_status_ID');
        $this->dropForeignKey('grading_terms', 'grading_terms_shs_status_foreign', 'senior_high_grading_period_status_ID');

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->foreign('grading_period_status_ID', 'grading_terms_status_foreign')
                ->references('grading_period_status_ID')->on('grading_period_statuses')->restrictOnDelete();
            $table->dropColumn([
                'junior_high_grading_period_status_ID',
                'senior_high_grading_period_status_ID',
            ]);
        });

        DB::table('grading_period_statuses')->where('slug', GradingPeriodStatus::CLOSED)->update([
            'slug' => 'inactive',
            'name' => 'Inactive',
        ]);
        GradingPeriodStatus::clearOptionsCache();
    }

    private function renameInactiveStatus(): void
    {
        DB::table('grading_period_statuses')->where('slug', 'inactive')->update([
            'slug' => GradingPeriodStatus::CLOSED,
            'name' => 'Closed',
        ]);

        DB::table('grading_period_statuses')->updateOrInsert(
            ['slug' => GradingPeriodStatus::CLOSED],
            ['name' => 'Closed', 'sort_order' => 2, 'updated_at' => now(), 'created_at' => now()],
        );

        GradingPeriodStatus::clearOptionsCache();
    }

    private function dropForeignKey(string $tableName, string $constraintName, string $column): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($constraintName, $column): void {
            if (DB::getDriverName() === 'sqlite') {
                $table->dropForeign([$column]);

                return;
            }

            $table->dropForeign($constraintName);
        });
    }
};
