<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\CurriculumSubject;
use App\Models\Section;
use App\Models\TeacherSubjectAssignment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSubjectAssignmentSeeder extends Seeder
{
    /**
     * Seed teacher subject assignments for testing.
     */
    public function run(): void
    {
        $teacherStaffId = DB::table('staffs')
            ->join('roles', 'staffs.role_id', '=', 'roles.id')
            ->where('roles.role_name', 'teacher')
            ->value('staffs.staff_id');

        if (! $teacherStaffId) {
            return;
        }

        $activeYearId = AcademicYear::query()
            ->where('status', true)
            ->value('SY_ID');

        $sections = Section::query()
            ->when($activeYearId, fn ($q) => $q->where('SY_ID', $activeYearId))
            ->get();

        foreach ($sections as $section) {
            $subjects = CurriculumSubject::query()
                ->where('curriculum_ID', $section->curriculum_ID)
                ->where('grade_level', $section->grade_level)
                ->get();

            foreach ($subjects as $subject) {
                TeacherSubjectAssignment::query()->updateOrCreate(
                    [
                        'section_ID' => $section->section_ID,
                        'curr_subj_ID' => $subject->curr_subj_ID,
                    ],
                    [
                        'staff_ID' => $teacherStaffId,
                        'SY_ID' => $section->SY_ID,
                    ]
                );
            }
        }
    }
}
