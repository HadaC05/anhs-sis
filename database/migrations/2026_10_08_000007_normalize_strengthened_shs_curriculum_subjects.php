<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $seniorGradeIds = DB::table('grade_level')
            ->whereIn('grade_label', ['Grade 11', 'Grade 12'])
            ->pluck('grade_ID');
        $gradeElevenId = DB::table('grade_level')->where('grade_label', 'Grade 11')->value('grade_ID');
        $coreTypeId = DB::table('subject_types')->where('key', 'core')->value('subject_type_ID');

        if (! $gradeElevenId || ! $coreTypeId) {
            return;
        }

        $offerings = DB::table('curriculum_grade_levels')
            ->whereIn('grade_ID', $seniorGradeIds)
            ->get(['curriculum_ID', 'grade_ID', 'semester_ID']);

        foreach ($offerings as $offering) {
            $carriesCore = (int) $offering->grade_ID === (int) $gradeElevenId;

            $assignments = DB::table('curriculum_subjects as cs')
                ->join('subjects as s', 's.subject_ID', '=', 'cs.subject_ID')
                ->where('cs.curriculum_grade_level_ID', $offering->curriculum_ID)
                ->when($carriesCore, fn ($query) => $query->where('s.subject_type_ID', '!=', $coreTypeId))
                ->when(! $carriesCore, fn ($query) => $query)
                ->get(['cs.curr_subj_ID', 'cs.subject_ID']);

            foreach ($assignments as $assignment) {
                if (DB::table('student_subjects')->where('subject_ID', $assignment->subject_ID)->exists()) {
                    continue;
                }

                $teacherAssignmentIds = DB::table('teacher_subject_assignments')
                    ->where('subject_ID', $assignment->subject_ID)
                    ->pluck('assignment_ID');

                if ($teacherAssignmentIds->isNotEmpty()
                    && DB::table('student_subject_grades')->whereIn('assignment_ID', $teacherAssignmentIds)->exists()) {
                    continue;
                }

                DB::table('teacher_subject_assignments')->whereIn('assignment_ID', $teacherAssignmentIds)->delete();
                DB::table('curriculum_subjects')->where('curr_subj_ID', $assignment->curr_subj_ID)->delete();
            }

            if ($carriesCore) {
                foreach (DB::table('subjects')->where('subject_type_ID', $coreTypeId)->where('status', 'active')->pluck('subject_ID') as $subjectId) {
                    DB::table('curriculum_subjects')->updateOrInsert(
                        ['curriculum_grade_level_ID' => $offering->curriculum_ID, 'subject_ID' => $subjectId],
                        ['created_at' => now(), 'updated_at' => now()],
                    );
                }
            }
        }
    }

    public function down(): void
    {
        // Obsolete generated assignments cannot be reconstructed safely.
    }
};
