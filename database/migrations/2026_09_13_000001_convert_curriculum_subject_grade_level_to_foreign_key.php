<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('curriculum_subjects', 'grade_ID')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->unsignedInteger('grade_ID')->nullable()->after('cluster_ID');
            });
        }

        // This guard also makes the migration safe to re-run after a MySQL DDL
        // failure, since ALTER TABLE statements are not transactional.
        if (Schema::hasColumn('curriculum_subjects', 'grade_level')) {
            DB::table('curriculum_subjects')->whereNull('grade_ID')->orderBy('curr_subj_ID')->each(function (object $row): void {
                $gradeId = DB::table('grade_level')
                    ->where('grade_label', 'Grade '.preg_replace('/\D+/', '', (string) $row->grade_level))
                    ->value('grade_ID');

                DB::table('curriculum_subjects')->where('curr_subj_ID', $row->curr_subj_ID)->update([
                    'grade_ID' => $gradeId,
                ]);
            });
        }

        // The old composite unique index is also MySQL's supporting index for
        // curriculum_ID's foreign key. Add a dedicated index before dropping it.
        if (! Schema::hasIndex('curriculum_subjects', 'curriculum_subjects_curriculum_id_index')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->index('curriculum_ID', 'curriculum_subjects_curriculum_id_index');
            });
        }

        if (Schema::hasIndex('curriculum_subjects', 'curriculum_subjects_unique_assignment')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->dropUnique('curriculum_subjects_unique_assignment');
            });
        }

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            if (DB::getDriverName() === 'pgsql') {
                $table->string('semester')->nullable()->change();
            } else {
                $table->enum('semester', ['first', 'second'])->nullable()->change();
            }
            $table->unsignedInteger('grade_ID')->nullable(false)->change();
        });

        // Junior High subjects run for the full school year.
        DB::table('curriculum_subjects')
            ->whereIn('grade_ID', DB::table('grade_level')->where('category', 'Junior High School')->pluck('grade_ID'))
            ->update(['semester' => null]);

        if (! Schema::hasIndex('curriculum_subjects', 'curriculum_subjects_grade_id_foreign')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->foreign('grade_ID', 'curriculum_subjects_grade_id_foreign')
                    ->references('grade_ID')->on('grade_level')->restrictOnDelete();
            });
        }

        if (! Schema::hasIndex('curriculum_subjects', 'curriculum_subjects_unique_assignment')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->unique(['curriculum_ID', 'subject_ID', 'grade_ID', 'semester'], 'curriculum_subjects_unique_assignment');
            });
        }

        if (Schema::hasColumn('curriculum_subjects', 'grade_level')) {
            Schema::table('curriculum_subjects', function (Blueprint $table): void {
                $table->dropColumn('grade_level');
            });
        }
    }

    public function down(): void
    {
        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            $table->dropForeign('curriculum_subjects_grade_id_foreign');
            $table->dropUnique('curriculum_subjects_unique_assignment');
            $table->enum('grade_level', ['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])->nullable()->after('cluster_ID');
        });

        DB::table('curriculum_subjects')->orderBy('curr_subj_ID')->each(function (object $row): void {
            $label = DB::table('grade_level')->where('grade_ID', $row->grade_ID)->value('grade_label');
            DB::table('curriculum_subjects')->where('curr_subj_ID', $row->curr_subj_ID)->update([
                'grade_level' => 'grade_'.preg_replace('/\D+/', '', (string) $label),
                'semester' => $row->semester ?? 'first',
            ]);
        });

        Schema::table('curriculum_subjects', function (Blueprint $table): void {
            if (DB::getDriverName() === 'pgsql') {
                $table->string('grade_level')->nullable(false)->change();
                $table->string('semester')->nullable(false)->change();
            } else {
                $table->enum('grade_level', ['grade_7', 'grade_8', 'grade_9', 'grade_10', 'grade_11', 'grade_12'])->nullable(false)->change();
                $table->enum('semester', ['first', 'second'])->nullable(false)->change();
            }
            $table->unique(['curriculum_ID', 'subject_ID', 'grade_level', 'semester'], 'curriculum_subjects_unique_assignment');
            $table->dropColumn('grade_ID');
        });
    }
};
