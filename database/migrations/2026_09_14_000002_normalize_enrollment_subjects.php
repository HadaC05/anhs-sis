<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->unsignedInteger('curriculum_grade_level_ID')->nullable()->after('SY_ID');
        });

        // The offering is the authoritative combination of grade, cluster and semester.
        DB::table('enrollments')->orderBy('enrollment_ID')->each(function (object $enrollment): void {
            $offering = DB::table('curriculum_grade_levels')
                ->where('grade_ID', $enrollment->grade_ID)
                ->when($enrollment->cluster_ID, fn ($query) => $query->where('cluster_ID', $enrollment->cluster_ID), fn ($query) => $query->whereNull('cluster_ID'))
                ->when(
                    $enrollment->semester,
                    fn ($query) => $query->whereIn('semester_ID', DB::table('grading_semesters')->whereIn('key', ['full_year', $enrollment->semester])->pluck('semester_ID')),
                    fn ($query) => $query->whereIn('semester_ID', DB::table('grading_semesters')->where('key', 'full_year')->pluck('semester_ID')),
                )
                ->orderBy('curriculum_ID')
                ->value('curriculum_ID');

            if ($offering) {
                DB::table('enrollments')->where('enrollment_ID', $enrollment->enrollment_ID)->update([
                    'curriculum_grade_level_ID' => $offering,
                ]);
            }
        });

        if (DB::table('enrollments')->whereNull('curriculum_grade_level_ID')->exists()) {
            throw new RuntimeException('Every enrollment must match a curriculum grade level before this migration can run.');
        }

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->foreign('curriculum_grade_level_ID')
                ->references('curriculum_ID')->on('curriculum_grade_levels')->restrictOnDelete();
            $table->unsignedInteger('curriculum_grade_level_ID')->nullable(false)->change();

            $table->dropForeign(['cluster_ID']);
            $table->dropForeign(['grade_ID']);
            $table->dropUnique('enrollments_unique_student_section_sy_semester');
            $table->dropColumn(['cluster_ID', 'grade_ID', 'semester']);
        });

        Schema::create('student_subjects', function (Blueprint $table): void {
            $table->increments('student_subject_ID');
            $table->unsignedInteger('enrollment_ID');
            $table->unsignedInteger('curr_subj_ID');
            $table->timestamps();

            $table->foreign('enrollment_ID')->references('enrollment_ID')->on('enrollments')->cascadeOnDelete();
            $table->foreign('curr_subj_ID')->references('curr_subj_ID')->on('curriculum_subjects')->restrictOnDelete();
            $table->unique(['enrollment_ID', 'curr_subj_ID'], 'student_subjects_enrollment_curriculum_subject_unique');
        });

        DB::table('enrollments')->orderBy('enrollment_ID')->each(function (object $enrollment): void {
            DB::table('curriculum_subjects')
                ->where('curriculum_grade_level_ID', $enrollment->curriculum_grade_level_ID)
                ->orderBy('curr_subj_ID')
                ->each(function (object $subject) use ($enrollment): void {
                    DB::table('student_subjects')->updateOrInsert(
                        ['enrollment_ID' => $enrollment->enrollment_ID, 'curr_subj_ID' => $subject->curr_subj_ID],
                        ['created_at' => now(), 'updated_at' => now()],
                    );
                });
        });

        Schema::table('student_subject_grades', function (Blueprint $table): void {
            $table->unsignedInteger('student_subject_ID')->nullable()->after('grade_ID');
        });

        DB::table('student_subject_grades')->orderBy('grade_ID')->each(function (object $grade): void {
            $curriculumSubjectId = DB::table('teacher_subject_assignments')
                ->where('assignment_ID', $grade->assignment_ID)
                ->value('curr_subj_ID');

            $studentSubjectId = DB::table('student_subjects')
                ->where('enrollment_ID', $grade->enrollment_ID)
                ->where('curr_subj_ID', $curriculumSubjectId)
                ->value('student_subject_ID');

            if (! $studentSubjectId) {
                throw new RuntimeException("Grade {$grade->grade_ID} has no matching student subject.");
            }

            DB::table('student_subject_grades')->where('grade_ID', $grade->grade_ID)->update([
                'student_subject_ID' => $studentSubjectId,
            ]);
        });

        Schema::table('student_subject_grades', function (Blueprint $table): void {
            $table->foreign('student_subject_ID')->references('student_subject_ID')->on('student_subjects')->cascadeOnDelete();
            $table->unsignedInteger('student_subject_ID')->nullable(false)->change();
            // enrollment_ID remains as a compatibility/query column. Its value is
            // constrained by the student_subject record in application writes.
            $table->index('student_subject_ID', 'student_subject_grades_student_subject_index');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('This data-normalizing migration cannot be safely reversed.');
    }
};
