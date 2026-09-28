<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grading_terms', function (Blueprint $table): void {
            $table->string('school_level', 20)->default('junior_high');
            $table->dropUnique('grading_terms_key_unique');
            $table->unique(['school_level', 'key']);
        });

        DB::transaction(function (): void {
            $seniorGradeIds = DB::table('grade_level')->whereIn('grade_label', ['Grade 11', 'Grade 12'])->pluck('grade_ID');
            $seniorAssignments = DB::table('teacher_subject_assignments')
                ->join('sections', 'sections.section_ID', '=', 'teacher_subject_assignments.section_ID')
                ->whereIn('sections.grade_ID', $seniorGradeIds)->pluck('assignment_ID');
            foreach (DB::table('grading_terms')->where('school_level', 'junior_high')->orderBy('term_ID')->get() as $term) {
                $seniorId = DB::table('grading_terms')->insertGetId([
                    'school_level' => 'senior_high', 'key' => $term->key, 'label' => $term->label,
                    'sort_order' => $term->sort_order,
                    'junior_high_grading_period_status_ID' => null,
                    'senior_high_grading_period_status_ID' => $term->senior_high_grading_period_status_ID,
                    'created_at' => $term->created_at, 'updated_at' => $term->updated_at,
                ], 'term_ID');
                DB::table('student_subject_grades')->where('term_ID', $term->term_ID)
                    ->whereIn('assignment_ID', $seniorAssignments)->update(['term_ID' => $seniorId]);
                DB::table('grading_term_settings')->where('term_ID', $term->term_ID)->update(['term_ID' => $seniorId]);
                DB::table('grading_terms')->where('term_ID', $term->term_ID)->update(['senior_high_grading_period_status_ID' => null]);
            }
        });
    }

    public function down(): void
    {
        // An automatic merge would discard independently edited names and statuses.
        throw new RuntimeException('Independent grading terms cannot be merged without losing configuration. Restore a database backup to revert this migration.');
    }
};
