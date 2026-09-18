<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_statuses', function (Blueprint $table): void {
            $table->increments('data_status_ID');
            $table->string('key')->unique();
            $table->string('label')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();
        DB::table('data_statuses')->insert([
            ['key' => 'active', 'label' => 'Active', 'sort_order' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'archived', 'label' => 'Archived', 'sort_order' => 2, 'created_at' => $now, 'updated_at' => $now],
        ]);

        Schema::create('curricula', function (Blueprint $table): void {
            $table->increments('curricula_ID');
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('data_status_ID');
            $table->timestamps();

            $table->foreign('data_status_ID')
                ->references('data_status_ID')
                ->on('data_statuses')
                ->restrictOnDelete();
        });

        // The existing records identify a grade/semester offering. Renaming the
        // table preserves their IDs, so current section and subject-assignment
        // foreign keys continue to point at the same offerings.
        Schema::rename('curriculum', 'curriculum_grade_levels');

        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->unsignedInteger('curricula_ID')->nullable()->after('curriculum_ID');
            $table->unsignedInteger('grade_ID')->nullable()->after('curricula_ID');
            $table->unsignedInteger('semester_ID')->nullable()->after('grade_ID');
        });

        $activeStatusId = (int) DB::table('data_statuses')->where('key', 'active')->value('data_status_ID');
        $archivedStatusId = (int) DB::table('data_statuses')->where('key', 'archived')->value('data_status_ID');
        $gradeIds = DB::table('grade_level')->pluck('grade_ID', 'grade_label');
        $semesterIds = DB::table('grading_semesters')->pluck('semester_ID', 'key');

        foreach (DB::table('curriculum_grade_levels')->orderBy('curriculum_ID')->get() as $offering) {
            $name = (string) $offering->name;
            $curriculumName = 'Legacy Curriculum';
            $description = $offering->description;
            $gradeLabel = null;
            $semesterKey = null;

            if (preg_match('/^Grade (7|8|9|10)$/', $name, $matches)) {
                $curriculumName = 'Junior High School';
                $gradeLabel = 'Grade '.$matches[1];
                $semesterKey = 'full_year';
            } elseif (preg_match('/^Grade (11|12) (First|Second) Semester - (.+)$/', $name, $matches)) {
                $curriculumName = $matches[3];
                $gradeLabel = 'Grade '.$matches[1];
                $semesterKey = strtolower($matches[2]);
            }

            $statusId = $offering->status ? $activeStatusId : $archivedStatusId;
            $curriculaId = DB::table('curricula')->where('name', $curriculumName)->value('curricula_ID');

            if (! $curriculaId) {
                $curriculaId = DB::table('curricula')->insertGetId([
                    'name' => $curriculumName,
                    'description' => $description,
                    'data_status_ID' => $statusId,
                    'created_at' => $offering->created_at ?? $now,
                    'updated_at' => $offering->updated_at ?? $now,
                ]);
            }

            DB::table('curriculum_grade_levels')
                ->where('curriculum_ID', $offering->curriculum_ID)
                ->update([
                    'curricula_ID' => $curriculaId,
                    'grade_ID' => $gradeLabel ? ($gradeIds[$gradeLabel] ?? null) : null,
                    'semester_ID' => $semesterKey ? ($semesterIds[$semesterKey] ?? null) : null,
                ]);
        }

        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->foreign('curricula_ID')
                ->references('curricula_ID')
                ->on('curricula')
                ->restrictOnDelete();
            $table->foreign('grade_ID')
                ->references('grade_ID')
                ->on('grade_level')
                ->restrictOnDelete();
            $table->foreign('semester_ID')
                ->references('semester_ID')
                ->on('grading_semesters')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_grade_levels', function (Blueprint $table): void {
            $table->dropForeign(['curricula_ID']);
            $table->dropForeign(['grade_ID']);
            $table->dropForeign(['semester_ID']);
            $table->dropColumn(['curricula_ID', 'grade_ID', 'semester_ID']);
        });

        Schema::rename('curriculum_grade_levels', 'curriculum');
        Schema::dropIfExists('curricula');
        Schema::dropIfExists('data_statuses');
    }
};
