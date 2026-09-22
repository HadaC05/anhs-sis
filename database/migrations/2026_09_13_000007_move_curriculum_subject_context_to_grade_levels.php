<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $activeStatusId = (int) DB::table('data_statuses')->where('key', 'active')->value('data_status_ID');
        $archivedStatusId = (int) DB::table('data_statuses')->where('key', 'archived')->value('data_status_ID');
        $now = now();

        // Curricula is the master curriculum catalog. Grade, semester, cluster,
        // and status belong to its curriculum-grade-level offerings instead.
        $matatagId = DB::table('curricula')->orderBy('curricula_ID')->value('curricula_ID');

        if (! $matatagId) {
            $matatagId = DB::table('curricula')->insertGetId([
                'name' => 'MATATAG',
                'description' => 'MATATAG K to 10 and Senior High School curriculum.',
                'data_status_ID' => $activeStatusId,
                'created_at' => $now,
                'updated_at' => $now,
            ], 'curricula_ID');
        } else {
            DB::table('curricula')->where('curricula_ID', $matatagId)->update([
                'name' => 'MATATAG',
                'description' => 'MATATAG K to 10 and Senior High School curriculum.',
                'data_status_ID' => $activeStatusId,
                'updated_at' => $now,
            ]);
        }

        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->unsignedInteger('data_status_ID')->nullable()->after('semester_ID');
            $table->unsignedInteger('cluster_ID')->nullable()->after('data_status_ID');
        });

        foreach (DB::table('curriculum_grade_levels')->orderBy('curriculum_ID')->get() as $offering) {
            $clusterId = DB::table('curriculum_subjects')
                ->where('curriculum_ID', $offering->curriculum_ID)
                ->whereNotNull('cluster_ID')
                ->value('cluster_ID');

            DB::table('curriculum_grade_levels')
                ->where('curriculum_ID', $offering->curriculum_ID)
                ->update([
                    'curricula_ID' => $matatagId,
                    'data_status_ID' => $offering->status ? $activeStatusId : $archivedStatusId,
                    'cluster_ID' => $clusterId,
                ]);
        }

        DB::table('curricula')->where('curricula_ID', '!=', $matatagId)->delete();

        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->foreign('data_status_ID')
                ->references('data_status_ID')
                ->on('data_statuses')
                ->restrictOnDelete();
            $table->foreign('cluster_ID')
                ->references('cluster_ID')
                ->on('clusters')
                ->restrictOnDelete();
        });

        // curriculum_subjects now inherits grade, semester, and cluster through
        // the curriculum-grade-level offering it belongs to.
        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->unsignedInteger('curriculum_grade_level_ID')->nullable()->after('curr_subj_ID');
        });

        DB::table('curriculum_subjects')->orderBy('curr_subj_ID')->each(function (object $assignment): void {
            DB::table('curriculum_subjects')
                ->where('curr_subj_ID', $assignment->curr_subj_ID)
                ->update(['curriculum_grade_level_ID' => $assignment->curriculum_ID]);
        });

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->foreign('curriculum_grade_level_ID')
                ->references('curriculum_ID')
                ->on('curriculum_grade_levels')
                ->cascadeOnDelete();
            $table->dropUnique('curriculum_subjects_unique_assignment');
            $table->unique(['curriculum_grade_level_ID', 'subject_ID'], 'curriculum_subjects_grade_level_subject_unique');
            $table->dropForeign(['curriculum_ID']);
            $table->dropIndex('curriculum_subjects_curriculum_id_index');
            $table->dropForeign(['cluster_ID']);
            $table->dropForeign(['grade_ID']);
            $table->dropForeign(['semester_ID']);
            $table->dropColumn(['curriculum_ID', 'cluster_ID', 'grade_ID', 'semester_ID']);
        });

        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->boolean('status')->default(true);
        });

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->unsignedInteger('curriculum_ID')->nullable();
            $table->unsignedInteger('cluster_ID')->nullable();
            $table->unsignedInteger('grade_ID')->nullable();
            $table->unsignedInteger('semester_ID')->nullable();
        });

        DB::table('curriculum_subjects')->orderBy('curr_subj_ID')->each(function (object $assignment): void {
            $offering = DB::table('curriculum_grade_levels')
                ->where('curriculum_ID', $assignment->curriculum_grade_level_ID)
                ->first(['curriculum_ID', 'cluster_ID', 'grade_ID', 'semester_ID']);

            DB::table('curriculum_subjects')->where('curr_subj_ID', $assignment->curr_subj_ID)->update([
                'curriculum_ID' => $offering?->curriculum_ID,
                'cluster_ID' => $offering?->cluster_ID,
                'grade_ID' => $offering?->grade_ID,
                'semester_ID' => $offering?->semester_ID,
            ]);
        });

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->dropForeign(['curriculum_grade_level_ID']);
            $table->dropUnique('curriculum_subjects_grade_level_subject_unique');
            $table->dropColumn('curriculum_grade_level_ID');
        });

        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->dropForeign(['data_status_ID']);
            $table->dropForeign(['cluster_ID']);
            $table->dropColumn(['data_status_ID', 'cluster_ID']);
        });
    }
};
