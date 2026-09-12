<?php

use App\Models\GradingPeriodStatus;
use App\Models\GradingQuarter;
use App\Models\GradingSemester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createStatusTable();
        $this->createSemesterTable();
        $this->createQuarterTable();
        $this->convertTermStatusColumn();
        $this->convertSeniorHighSettingColumns();
    }

    public function down(): void
    {
        $this->restoreSeniorHighSettingColumns();
        $this->restoreTermStatusColumn();

        Schema::dropIfExists('grading_quarters');
        Schema::dropIfExists('grading_semesters');
        Schema::dropIfExists('grading_period_statuses');
        GradingPeriodStatus::clearOptionsCache();
    }

    private function createStatusTable(): void
    {
        if (! Schema::hasTable('grading_period_statuses')) {
            Schema::create('grading_period_statuses', function (Blueprint $table): void {
                $table->increments('grading_period_status_ID');
                $table->string('slug')->unique();
                $table->string('name')->unique();
                $table->unsignedTinyInteger('sort_order');
                $table->timestamps();
            });
        }

        $now = now();

        foreach (GradingPeriodStatus::definitions() as $status) {
            DB::table('grading_period_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                [
                    'name' => $status['name'],
                    'sort_order' => $status['sort_order'],
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        GradingPeriodStatus::clearOptionsCache();
    }

    private function createSemesterTable(): void
    {
        if (! Schema::hasTable('grading_semesters')) {
            Schema::create('grading_semesters', function (Blueprint $table): void {
                $table->increments('semester_ID');
                $table->string('key')->unique();
                $table->string('label');
                $table->unsignedTinyInteger('sort_order')->default(1);
                $table->unsignedInteger('grading_period_status_ID');
                $table->timestamps();

                $table->foreign('grading_period_status_ID', 'grading_semesters_status_foreign')
                    ->references('grading_period_status_ID')
                    ->on('grading_period_statuses')
                    ->restrictOnDelete();
            });
        }

        $now = now();
        $activeId = $this->statusId(GradingPeriodStatus::ACTIVE);

        foreach (GradingSemester::definitions() as $semester) {
            DB::table('grading_semesters')->updateOrInsert(
                ['key' => $semester['key']],
                [
                    'label' => $semester['label'],
                    'sort_order' => $semester['sort_order'],
                    'grading_period_status_ID' => $activeId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function createQuarterTable(): void
    {
        if (! Schema::hasTable('grading_quarters')) {
            Schema::create('grading_quarters', function (Blueprint $table): void {
                $table->increments('quarter_ID');
                $table->unsignedInteger('semester_ID');
                $table->string('key')->unique();
                $table->string('label');
                $table->unsignedTinyInteger('quarter_number');
                $table->unsignedTinyInteger('sort_order')->default(1);
                $table->unsignedInteger('grading_period_status_ID');
                $table->timestamps();

                $table->unique(['semester_ID', 'quarter_number'], 'grading_quarters_semester_quarter_unique');

                $table->foreign('semester_ID', 'grading_quarters_semester_foreign')
                    ->references('semester_ID')
                    ->on('grading_semesters')
                    ->restrictOnDelete();

                $table->foreign('grading_period_status_ID', 'grading_quarters_status_foreign')
                    ->references('grading_period_status_ID')
                    ->on('grading_period_statuses')
                    ->restrictOnDelete();
            });
        }

        $now = now();
        $activeId = $this->statusId(GradingPeriodStatus::ACTIVE);
        $semesterIds = DB::table('grading_semesters')->pluck('semester_ID', 'key');

        foreach (GradingQuarter::definitions() as $quarter) {
            $semesterId = $semesterIds[$quarter['semester_key']] ?? null;

            if ($semesterId === null) {
                continue;
            }

            DB::table('grading_quarters')->updateOrInsert(
                ['key' => $quarter['key']],
                [
                    'semester_ID' => $semesterId,
                    'label' => $quarter['label'],
                    'quarter_number' => $quarter['quarter_number'],
                    'sort_order' => $quarter['sort_order'],
                    'grading_period_status_ID' => $activeId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    private function convertTermStatusColumn(): void
    {
        if (! Schema::hasTable('grading_terms') || Schema::hasColumn('grading_terms', 'grading_period_status_ID')) {
            return;
        }

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->unsignedInteger('grading_period_status_ID')->nullable()->after('sort_order');
        });

        $activeId = $this->statusId(GradingPeriodStatus::ACTIVE);
        $inactiveId = $this->statusId(GradingPeriodStatus::INACTIVE);

        if (Schema::hasColumn('grading_terms', 'is_active')) {
            DB::table('grading_terms')->where('is_active', 1)->update([
                'grading_period_status_ID' => $activeId,
            ]);
            DB::table('grading_terms')->where('is_active', 0)->update([
                'grading_period_status_ID' => $inactiveId,
            ]);
        }

        DB::table('grading_terms')->whereNull('grading_period_status_ID')->update([
            'grading_period_status_ID' => $activeId,
        ]);

        if (Schema::hasColumn('grading_terms', 'is_active')) {
            Schema::table('grading_terms', function (Blueprint $table): void {
                $table->dropColumn('is_active');
            });
        }

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->foreign('grading_period_status_ID', 'grading_terms_status_foreign')
                ->references('grading_period_status_ID')
                ->on('grading_period_statuses')
                ->restrictOnDelete();
        });
    }

    private function convertSeniorHighSettingColumns(): void
    {
        if (! Schema::hasTable('grading_term_settings')) {
            return;
        }

        if (! Schema::hasColumn('grading_term_settings', 'semester_ID')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->unsignedInteger('semester_ID')->nullable()->after('open_terms_count');
            });
        }

        if (! Schema::hasColumn('grading_term_settings', 'quarter_ID')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->unsignedInteger('quarter_ID')->nullable()->after('semester_ID');
            });
        }

        $rows = DB::table('grading_term_settings')->get();

        foreach ($rows as $row) {
            $semesterKey = GradingSemester::FIRST;
            $quarterNumber = 1;

            if (Schema::hasColumn('grading_term_settings', 'shs_semester')) {
                $semesterKey = $row->shs_semester === GradingSemester::SECOND
                    ? GradingSemester::SECOND
                    : GradingSemester::FIRST;
            }

            if (Schema::hasColumn('grading_term_settings', 'shs_quarter')) {
                $quarterNumber = (int) $row->shs_quarter === 2 ? 2 : 1;
            }

            $quarter = DB::table('grading_quarters')
                ->join('grading_semesters', 'grading_quarters.semester_ID', '=', 'grading_semesters.semester_ID')
                ->where('grading_semesters.key', $semesterKey)
                ->where('grading_quarters.quarter_number', $quarterNumber)
                ->select('grading_quarters.quarter_ID', 'grading_quarters.semester_ID')
                ->first();

            if ($quarter === null) {
                continue;
            }

            DB::table('grading_term_settings')
                ->where('id', $row->id)
                ->update([
                    'semester_ID' => $quarter->semester_ID,
                    'quarter_ID' => $quarter->quarter_ID,
                ]);
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('grading_term_settings', 'shs_quarter') ? 'shs_quarter' : null,
            Schema::hasColumn('grading_term_settings', 'shs_semester') ? 'shs_semester' : null,
        ]));

        if ($columns !== []) {
            Schema::table('grading_term_settings', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }

        Schema::table('grading_term_settings', function (Blueprint $table): void {
            $table->foreign('semester_ID', 'grading_term_settings_semester_foreign')
                ->references('semester_ID')
                ->on('grading_semesters')
                ->restrictOnDelete();

            $table->foreign('quarter_ID', 'grading_term_settings_quarter_foreign')
                ->references('quarter_ID')
                ->on('grading_quarters')
                ->restrictOnDelete();
        });
    }

    private function restoreTermStatusColumn(): void
    {
        if (! Schema::hasTable('grading_terms') || ! Schema::hasColumn('grading_terms', 'grading_period_status_ID')) {
            return;
        }

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->dropForeign('grading_terms_status_foreign');
        });

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('sort_order');
        });

        $activeId = $this->statusId(GradingPeriodStatus::ACTIVE);

        DB::table('grading_terms')->update([
            'is_active' => DB::raw('CASE WHEN grading_period_status_ID = '.(int) $activeId.' THEN 1 ELSE 0 END'),
        ]);

        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->dropColumn('grading_period_status_ID');
        });
    }

    private function restoreSeniorHighSettingColumns(): void
    {
        if (! Schema::hasTable('grading_term_settings')) {
            return;
        }

        if (Schema::hasColumn('grading_term_settings', 'quarter_ID')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->dropForeign('grading_term_settings_quarter_foreign');
            });
        }

        if (Schema::hasColumn('grading_term_settings', 'semester_ID')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->dropForeign('grading_term_settings_semester_foreign');
            });
        }

        if (! Schema::hasColumn('grading_term_settings', 'shs_semester')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->string('shs_semester', 20)->default('first')->after('open_terms_count');
            });
        }

        if (! Schema::hasColumn('grading_term_settings', 'shs_quarter')) {
            Schema::table('grading_term_settings', function (Blueprint $table): void {
                $table->unsignedTinyInteger('shs_quarter')->default(1)->after('shs_semester');
            });
        }

        $rows = DB::table('grading_term_settings')->get();

        foreach ($rows as $row) {
            $semesterKey = GradingSemester::FIRST;
            $quarterNumber = 1;

            if (Schema::hasColumn('grading_term_settings', 'semester_ID') && $row->semester_ID) {
                $semesterKey = DB::table('grading_semesters')
                    ->where('semester_ID', $row->semester_ID)
                    ->value('key') ?: GradingSemester::FIRST;
            }

            if (Schema::hasColumn('grading_term_settings', 'quarter_ID') && $row->quarter_ID) {
                $quarterNumber = (int) DB::table('grading_quarters')
                    ->where('quarter_ID', $row->quarter_ID)
                    ->value('quarter_number') ?: 1;
            }

            DB::table('grading_term_settings')
                ->where('id', $row->id)
                ->update([
                    'shs_semester' => $semesterKey === GradingSemester::SECOND ? GradingSemester::SECOND : GradingSemester::FIRST,
                    'shs_quarter' => $quarterNumber === 2 ? 2 : 1,
                ]);
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('grading_term_settings', 'quarter_ID') ? 'quarter_ID' : null,
            Schema::hasColumn('grading_term_settings', 'semester_ID') ? 'semester_ID' : null,
        ]));

        if ($columns !== []) {
            Schema::table('grading_term_settings', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    private function statusId(string $slug): int
    {
        return (int) DB::table('grading_period_statuses')->where('slug', $slug)->value('grading_period_status_ID');
    }
};
