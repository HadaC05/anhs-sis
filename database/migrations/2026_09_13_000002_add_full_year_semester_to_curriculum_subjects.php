<?php

use App\Models\GradingPeriodStatus;
use App\Models\GradingSemester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $fullYearId = GradingSemester::query()->updateOrCreate(
            ['key' => GradingSemester::FULL_YEAR],
            [
                'label' => 'Full Year',
                'sort_order' => 0,
                'grading_period_status_ID' => GradingPeriodStatus::activeId(),
            ],
        )->semester_ID;

        if (! Schema::hasColumn('curriculum_subjects', 'semester_ID')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->unsignedInteger('semester_ID')->nullable()->after('grade_ID');
            });
        }

        $semesterIds = GradingSemester::query()->pluck('semester_ID', 'key');

        DB::table('curriculum_subjects')->orderBy('curr_subj_ID')->each(function (object $row) use ($semesterIds, $fullYearId): void {
            $semesterId = $row->semester === null
                ? $fullYearId
                : ($semesterIds[$row->semester] ?? $fullYearId);

            DB::table('curriculum_subjects')->where('curr_subj_ID', $row->curr_subj_ID)->update(['semester_ID' => $semesterId]);
        });

        if (Schema::hasIndex('curriculum_subjects', 'curriculum_subjects_unique_assignment')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->dropUnique('curriculum_subjects_unique_assignment');
            });
        }

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->unsignedInteger('semester_ID')->nullable(false)->change();
        });

        if (! Schema::hasIndex('curriculum_subjects', 'curriculum_subjects_semester_id_foreign')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->foreign('semester_ID', 'curriculum_subjects_semester_id_foreign')
                    ->references('semester_ID')->on('grading_semesters')->restrictOnDelete();
            });
        }

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->unique(['curriculum_ID', 'subject_ID', 'grade_ID', 'semester_ID'], 'curriculum_subjects_unique_assignment');
            $table->dropColumn('semester');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->dropForeign('curriculum_subjects_semester_id_foreign');
            $table->dropUnique('curriculum_subjects_unique_assignment');
            $table->enum('semester', ['first', 'second'])->nullable()->after('grade_ID');
        });

        DB::table('curriculum_subjects')->orderBy('curr_subj_ID')->each(function (object $row): void {
            $key = DB::table('grading_semesters')->where('semester_ID', $row->semester_ID)->value('key');
            DB::table('curriculum_subjects')->where('curr_subj_ID', $row->curr_subj_ID)->update([
                'semester' => $key === GradingSemester::FULL_YEAR ? null : $key,
            ]);
        });

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->unique(['curriculum_ID', 'subject_ID', 'grade_ID', 'semester'], 'curriculum_subjects_unique_assignment');
            $table->dropColumn('semester_ID');
        });
    }
};
