<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertStudentSubjects();
        $this->convertTeacherAssignments();
    }

    private function convertStudentSubjects(): void
    {
        if (! Schema::hasColumn('student_subjects', 'curr_subj_ID')) {
            return;
        }

        Schema::table('student_subjects', function (Blueprint $table): void {
            $table->unsignedInteger('subject_ID')->nullable();
        });

        DB::table('student_subjects')->orderBy('student_subject_ID')->each(function (object $row): void {
            $subjectId = DB::table('curriculum_subjects')
                ->where('curr_subj_ID', $row->curr_subj_ID)
                ->value('subject_ID');

            if (! $subjectId) {
                throw new RuntimeException("Student subject {$row->student_subject_ID} has no matching subject.");
            }

            DB::table('student_subjects')->where('student_subject_ID', $row->student_subject_ID)
                ->update(['subject_ID' => $subjectId]);
        });

        if (DB::table('student_subjects')->select('enrollment_ID', 'subject_ID')
            ->groupBy('enrollment_ID', 'subject_ID')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate student subjects must be resolved before converting their references.');
        }

        Schema::table('student_subjects', function (Blueprint $table): void {
            $table->dropForeign(['curr_subj_ID']);
            $table->dropUnique('student_subjects_enrollment_curriculum_subject_unique');
            $table->dropColumn('curr_subj_ID');
            $table->unsignedInteger('subject_ID')->nullable(false)->change();
            $table->foreign('subject_ID')->references('subject_ID')->on('subjects')->restrictOnDelete();
            $table->unique(['enrollment_ID', 'subject_ID'], 'student_subjects_enrollment_subject_unique');
        });
    }

    private function convertTeacherAssignments(): void
    {
        if (! Schema::hasColumn('teacher_subject_assignments', 'curr_subj_ID')) {
            return;
        }

        Schema::table('teacher_subject_assignments', function (Blueprint $table): void {
            $table->unsignedInteger('subject_ID')->nullable();
        });

        DB::table('teacher_subject_assignments')->orderBy('assignment_ID')->each(function (object $row): void {
            $subjectId = DB::table('curriculum_subjects')
                ->where('curr_subj_ID', $row->curr_subj_ID)
                ->value('subject_ID');

            if (! $subjectId) {
                throw new RuntimeException("Teacher assignment {$row->assignment_ID} has no matching subject.");
            }

            DB::table('teacher_subject_assignments')->where('assignment_ID', $row->assignment_ID)
                ->update(['subject_ID' => $subjectId]);
        });

        if (DB::table('teacher_subject_assignments')->select('section_ID', 'subject_ID')
            ->groupBy('section_ID', 'subject_ID')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException('Duplicate teacher subject assignments must be resolved before converting their references.');
        }

        Schema::table('teacher_subject_assignments', function (Blueprint $table): void {
            $table->dropForeign(['curr_subj_ID']);
            $table->dropUnique('teacher_subject_assignments_unique');
            $table->dropColumn('curr_subj_ID');
            $table->unsignedInteger('subject_ID')->nullable(false)->change();
            $table->foreign('subject_ID')->references('subject_ID')->on('subjects')->restrictOnDelete();
            $table->unique(['section_ID', 'subject_ID'], 'teacher_subject_assignments_unique');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Converting legacy subject references cannot be safely reversed.');
    }
};
